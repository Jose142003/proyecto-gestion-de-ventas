<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/totp.php';
seguridadVerificarBloqueoIp();
seguridadVerificarRateLimit();

define('REVERIFY_INTERVAL', 86400); // 24 hours

$action = trim($_GET['action'] ?? $_POST['action'] ?? '');
$code = trim($_POST['code'] ?? '');
$type = trim($_GET['type'] ?? $_POST['type'] ?? 'cliente');

// Start the correct session
if ($type === 'admin') {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(ini_get('session.name'));
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => $is_https, 'httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('CLIENTSESSID');
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => $is_https, 'httponly' => true, 'samesite' => 'Lax']);
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
$allowedTables = ['users', 'admin_users'];
if (!in_array($tableName, $allowedTables, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de sesión inválido']);
    exit;
}

try {
    $pdo = Database::getConnection();

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
    for ($i = -2; $i <= 2; $i++) {
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


