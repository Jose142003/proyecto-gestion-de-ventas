<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
});

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? $_POST['code'] ?? '');
$token = trim($input['token'] ?? $_POST['token'] ?? '');

if (empty($code) || empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Código y token requeridos']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $check = $pdo->query("SHOW TABLES LIKE 'sesiones_2fa'");
    if ($check->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '2FA no disponible. Ejecute la migración SQL.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT s.*, a.nombre, a.correo, a.rol, a.2fa_secret, a.2fa_backup_codes FROM sesiones_2fa s JOIN admin_users a ON s.admin_user_id = a.id WHERE s.token_verificacion = ? AND s.completado = FALSE AND s.expiracion > NOW()");
    $stmt->execute([$token]);
    $sesion = $stmt->fetch();

    if (!$sesion) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sesión expirada o inválida']);
        exit;
    }

    $pdo->prepare("UPDATE sesiones_2fa SET intentos = intentos + 1 WHERE id = ?")->execute([$sesion['id']]);

    if ($sesion['intentos'] >= 5) {
        $pdo->prepare("UPDATE sesiones_2fa SET completado = TRUE WHERE id = ?")->execute([$sesion['id']]);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Demasiados intentos. Inicie sesión nuevamente.']);
        exit;
    }

    $isValid = false;
    $secret = $sesion['2fa_secret'];
    $timeSlice = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) { $isValid = true; break; }
    }

    if (!$isValid) {
        $codes = json_decode($sesion['2fa_backup_codes'] ?? '[]', true);
        if (is_array($codes)) {
            $idx = array_search($code, $codes);
            if ($idx !== false) {
                unset($codes[$idx]);
                $pdo->prepare("UPDATE admin_users SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $sesion['admin_user_id']]);
                $isValid = true;
            }
        }
    }

    if (!$isValid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código inválido']);
        exit;
    }

    $pdo->prepare("UPDATE sesiones_2fa SET completado = TRUE WHERE id = ?")->execute([$sesion['id']]);

    session_start();
    session_regenerate_id(true);

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $sesion['admin_user_id'];
    $_SESSION['user_nombre'] = $sesion['nombre'];
    $_SESSION['user_correo'] = $sesion['correo'];
    $_SESSION['user_rol'] = $sesion['rol'];
    $_SESSION['tabla_origen'] = 'admin_users';
    $_SESSION['es_admin'] = true;
    $_SESSION['is_admin'] = true;
    $_SESSION['2fa_verified'] = true;

    echo json_encode(['success' => true, 'message' => 'Autenticación exitosa', 'redirect_url' => '/proyecto/panel_admin/panel_admin.php']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar código 2FA']);
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
