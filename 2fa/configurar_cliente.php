<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
});

require_once __DIR__ . '/../config/database.php';

function columnaExiste($pdo, $tabla, $columna): bool {
    try { return (bool)$pdo->query("SHOW COLUMNS FROM `$tabla` LIKE " . $pdo->quote($columna))->fetch(); } catch (Throwable $e) { return false; }
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Iniciar sesión de cliente
if (session_status() === PHP_SESSION_NONE) {
    session_name('CLIENTSESSID');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$userId = $_SESSION['user_id'] ?? 0;
$userCorreo = $_SESSION['user_correo'] ?? '';

if (!$userId || $_SESSION['tabla_origen'] !== 'users') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'estado') {
    $enabled = false;
    if (columnaExiste($pdo, 'users', '2fa_enabled')) {
        $stmt = $pdo->prepare("SELECT 2fa_enabled FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $enabled = (bool)($user['2fa_enabled'] ?? false);
    }
    echo json_encode(['success' => true, 'enabled' => $enabled, 'migracion_pendiente' => !columnaExiste($pdo, 'users', '2fa_enabled')]);
    exit;
}

if (!columnaExiste($pdo, 'users', '2fa_enabled')) {
    echo json_encode(['success' => false, 'message' => 'Migración pendiente. Ejecute la migración SQL para activar 2FA.', 'migracion_pendiente' => true]);
    exit;
}

if ($action === 'generar_secreto') {
    $secret = '';
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    for ($i = 0; $i < 32; $i++) $secret .= $chars[random_int(0, strlen($chars) - 1)];

    $email = $userCorreo ?: 'cliente@pic.com.ve';
    $issuer = 'PIC - Sistema de Gestion Comercial';
    $qrContent = "otpauth://totp/$issuer:$email?secret=$secret&issuer=$issuer&algorithm=SHA1&digits=6&period=30";

    $backupCodes = [];
    for ($i = 0; $i < 8; $i++) $backupCodes[] = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2));

    $stmt = $pdo->prepare("UPDATE users SET 2fa_secret = ?, 2fa_backup_codes = ? WHERE id = ?");
    $stmt->execute([$secret, json_encode($backupCodes), $userId]);

    echo json_encode(['success' => true, 'secret' => $secret, 'qr_content' => $qrContent, 'backup_codes' => $backupCodes], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'verificar') {
    $code = $_POST['code'] ?? '';
    if (empty($code)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Código requerido']); exit; }

    $stmt = $pdo->prepare("SELECT 2fa_secret FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || empty($user['2fa_secret'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => '2FA no configurado']); exit; }

    $isValid = verificarTOTP($pdo, $userId, $user['2fa_secret'], $code);
    if ($isValid) {
        $stmt = $pdo->prepare("UPDATE users SET 2fa_enabled = TRUE, 2fa_verified_at = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true, 'message' => '2FA activado correctamente']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código inválido']);
    }
    exit;
}

if ($action === 'desactivar') {
    $password = $_POST['password'] ?? '';
    if (empty($password)) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Contraseña requerida']); exit; }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) { http_response_code(400); echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']); exit; }

    $stmt = $pdo->prepare("UPDATE users SET 2fa_enabled = FALSE, 2fa_secret = NULL, 2fa_backup_codes = NULL WHERE id = ?");
    $stmt->execute([$userId]);
    echo json_encode(['success' => true, 'message' => '2FA desactivado correctamente']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción no válida']);

function verificarTOTP($pdo, int $userId, string $secret, string $code): bool {
    $code = trim($code);
    if (strlen($code) !== 6 || !ctype_digit($code)) return false;

    $timeSlice = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) return true;
    }

    $stmtBackup = $pdo->prepare("SELECT 2fa_backup_codes FROM users WHERE id = ?");
    $stmtBackup->execute([$userId]);
    $user = $stmtBackup->fetch();
    if ($user && !empty($user['2fa_backup_codes'])) {
        $codes = json_decode($user['2fa_backup_codes'], true);
        if (is_array($codes)) {
            $idx = array_search($code, $codes);
            if ($idx !== false) {
                unset($codes[$idx]);
                $pdo->prepare("UPDATE users SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $userId]);
                return true;
            }
        }
    }
    return false;
}

function generarTOTP(string $secret, int $timeSlice): string {
    $secret = base32Decode($secret);
    $timeBytes = pack('J', $timeSlice);
    $hash = hash_hmac('sha1', $timeBytes, $secret, true);
    $offset = ord($hash[19]) & 0x0F;
    $code = (((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16) | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF)) % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}

function base32Decode(string $data): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(str_replace('=', '', $data));
    $bits = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $pos = strpos($chars, $data[$i]);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) $result .= chr(bindec(substr($bits, $i, 8)));
    return $result;
}
