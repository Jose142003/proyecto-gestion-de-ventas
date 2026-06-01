<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

define('REVERIFY_INTERVAL', 86400); // 24 hours

$action = trim($_GET['action'] ?? $_POST['action'] ?? '');
$code = trim($_POST['code'] ?? '');
$type = trim($_GET['type'] ?? $_POST['type'] ?? 'cliente');

// Start the correct session
if ($type === 'admin') {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ini_get('session.name'));
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CLIENTSESSID');
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// If 2FA was never verified in this session, no need to re-verify
if (!isset($_SESSION['2fa_verified']) || !$_SESSION['2fa_verified']) {
    echo json_encode(['success' => true, 'needs_2fa' => false]);
    exit;
}

// Check DB if user actually has 2FA enabled
$has2FA = false;
$secret = null;
$tableName = ($_SESSION['tabla_origen'] === 'admin_users') ? 'admin_users' : 'users';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $checkCol = $pdo->query("SHOW COLUMNS FROM `$tableName` LIKE '2fa_enabled'");
    if ($checkCol->rowCount() > 0) {
        $stmt = $pdo->prepare("SELECT 2fa_enabled, 2fa_secret FROM $tableName WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user2fa = $stmt->fetch();
        $has2FA = ($user2fa && $user2fa['2fa_enabled'] && !empty($user2fa['2fa_secret']));
        if ($has2FA) $secret = $user2fa['2fa_secret'];
    }
} catch (Throwable $e) {
    $has2FA = false;
}

if (!$has2FA) {
    echo json_encode(['success' => true, 'needs_2fa' => false]);
    exit;
}

// --- action: check ---
if ($action === 'check') {
    $lastVerified = $_SESSION['2fa_verified_at'] ?? 0;
    $needs2FA = (time() - $lastVerified) > REVERIFY_INTERVAL;

    echo json_encode([
        'success' => true,
        'needs_2fa' => $needs2FA,
        'last_verified' => $lastVerified,
        'interval' => REVERIFY_INTERVAL
    ]);
    exit;
}

// --- action: verify ---
if ($action === 'verify') {
    $lastVerified = $_SESSION['2fa_verified_at'] ?? 0;

    if ((time() - $lastVerified) <= REVERIFY_INTERVAL) {
        echo json_encode(['success' => true, 'message' => 'Ya verificado']);
        exit;
    }

    if (empty($code) || strlen($code) !== 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código de 6 dígitos requerido']);
        exit;
    }

    $isValid = false;

    // TOTP verification
    $timeSlice = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) {
            $isValid = true;
            break;
        }
    }

    // Backup codes fallback
    if (!$isValid) {
        $stmtBackup = $pdo->prepare("SELECT 2fa_backup_codes FROM $tableName WHERE id = ?");
        $stmtBackup->execute([$_SESSION['user_id']]);
        $user = $stmtBackup->fetch();
        if ($user && !empty($user['2fa_backup_codes'])) {
            $codes = json_decode($user['2fa_backup_codes'], true);
            if (is_array($codes)) {
                $idx = array_search($code, $codes);
                if ($idx !== false) {
                    unset($codes[$idx]);
                    $pdo->prepare("UPDATE $tableName SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $_SESSION['user_id']]);
                    $isValid = true;
                }
            }
        }
    }

    if ($isValid) {
        $_SESSION['2fa_verified_at'] = time();
        session_write_close();
        echo json_encode(['success' => true, 'message' => 'Verificado correctamente']);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código inválido']);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Acción no válida']);

function generarTOTP(string $secret, int $timeSlice): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = strtoupper(str_replace('=', '', $secret));
    $bits = '';
    for ($i = 0; $i < strlen($secret); $i++) {
        $pos = strpos($chars, $secret[$i]);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $decoded = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) $decoded .= chr(bindec(substr($bits, $i, 8)));

    $timeBytes = pack('J', $timeSlice);
    $hash = hash_hmac('sha1', $timeBytes, $decoded, true);
    $offset = ord($hash[19]) & 0x0F;
    $code = (((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16) | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF)) % 1000000;
    return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
}
