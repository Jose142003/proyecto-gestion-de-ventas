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

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/totp.php';
// Rate limiting para 2FA
seguridadVerificarBloqueoIp();
seguridadVerificarRateLimit();

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? $_POST['code'] ?? '');
$token = trim($input['token'] ?? $_POST['token'] ?? '');

if (empty($code) || empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Código y token requeridos']);
    exit;
}

try {
    $pdo = Database::getConnection();

    $check = $pdo->query("SHOW TABLES LIKE 'sesiones_2fa'");
    if ($check->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '2FA no disponible. Ejecute la migración SQL.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT s.*, a.nombre, a.correo, a.rol, a.2fa_secret, a.2fa_backup_codes FROM sesiones_2fa s JOIN admin_users a ON s.admin_user_id = a.id WHERE s.token_verificacion = ? AND s.completado = FALSE");
    $stmt->execute([$token]);
    $sesion = $stmt->fetch();

    if (!$sesion) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sesión inválida o ya utilizada']);
        exit;
    }

    $expiracionTs = strtotime($sesion['expiracion'] . ' UTC');
    if ($expiracionTs < time()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
        exit;
    }

    if ($sesion['intentos'] >= 5) {
        $pdo->prepare("UPDATE sesiones_2fa SET completado = TRUE WHERE id = ?")->execute([$sesion['id']]);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Demasiados intentos. Inicie sesión nuevamente.']);
        exit;
    }

    $pdo->prepare("UPDATE sesiones_2fa SET intentos = intentos + 1 WHERE id = ?")->execute([$sesion['id']]);

    $isValid = false;
    $secret = $sesion['2fa_secret'];
    $timeSlice = floor(time() / 30);
    for ($i = -2; $i <= 2; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) { $isValid = true; break; }
    }

    if (!$isValid) {
        // Rate limiting específico para códigos de respaldo
        if (!isset($_SESSION['_backup_attempts'])) {
            $_SESSION['_backup_attempts'] = ['count' => 0, 'first_attempt' => time()];
        }
        $backup_attempts = &$_SESSION['_backup_attempts'];
        $backup_window = 15 * 60;
        if (time() - $backup_attempts['first_attempt'] > $backup_window) {
            $backup_attempts = ['count' => 0, 'first_attempt' => time()];
        }
        if ($backup_attempts['count'] >= 3) {
            $pdo->prepare("UPDATE sesiones_2fa SET completado = TRUE WHERE id = ?")->execute([$sesion['id']]);
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Demasiados intentos con códigos de respaldo. Inicie sesión nuevamente.']);
            exit;
        }

        $codes = json_decode($sesion['2fa_backup_codes'] ?? '[]', true);
        if (is_array($codes)) {
            $idx = array_search($code, $codes);
            if ($idx !== false) {
                unset($codes[$idx]);
                $pdo->prepare("UPDATE admin_users SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $sesion['admin_user_id']]);
                $isValid = true;
                $_SESSION['_backup_attempts'] = ['count' => 0, 'first_attempt' => time()];
            }
        }
        
        if (!$isValid) {
            $backup_attempts['count']++;
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
    $_SESSION['2fa_verified_at'] = time();

    echo json_encode(['success' => true, 'message' => 'Autenticación exitosa', 'redirect_url' => url('/panel_admin/panel_admin.php')]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar código 2FA']);
}

