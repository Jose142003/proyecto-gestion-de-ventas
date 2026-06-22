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

    $check = $pdo->query("SHOW TABLES LIKE 'sesiones_2fa_clientes'");
    if ($check->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '2FA no disponible']);
        exit;
    }

    $check2 = $pdo->query("SHOW COLUMNS FROM users LIKE '2fa_secret'");
    if ($check2->rowCount() === 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => '2FA no configurado']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT s.*, u.nombre, u.correo, u.rol, u.password, u.2fa_secret, u.2fa_backup_codes 
                           FROM sesiones_2fa_clientes s 
                           JOIN users u ON s.user_id = u.id 
                           WHERE s.token_verificacion = ? AND s.completado = FALSE");
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

    $pdo->prepare("UPDATE sesiones_2fa_clientes SET intentos = intentos + 1 WHERE id = ?")->execute([$sesion['id']]);

    if ($sesion['intentos'] >= 5) {
        $pdo->prepare("UPDATE sesiones_2fa_clientes SET completado = TRUE WHERE id = ?")->execute([$sesion['id']]);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Demasiados intentos. Inicie sesión nuevamente.']);
        exit;
    }

    $isValid = false;
    $secret = $sesion['2fa_secret'];
    $timeSlice = floor(time() / 30);
    for ($i = -2; $i <= 2; $i++) {
        if (hash_equals(generarTOTP($secret, $timeSlice + $i), $code)) { $isValid = true; break; }
    }

    if (!$isValid) {
        $codes = json_decode($sesion['2fa_backup_codes'] ?? '[]', true);
        if (is_array($codes)) {
            $idx = array_search($code, $codes);
            if ($idx !== false) {
                unset($codes[$idx]);
                $pdo->prepare("UPDATE users SET 2fa_backup_codes = ? WHERE id = ?")->execute([json_encode(array_values($codes)), $sesion['user_id']]);
                $isValid = true;
            }
        }
    }

    if (!$isValid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Código inválido']);
        exit;
    }

    $pdo->prepare("UPDATE sesiones_2fa_clientes SET completado = TRUE WHERE id = ?")->execute([$sesion['id']]);

    if (session_status() === PHP_SESSION_NONE) {
        session_name('CLIENTSESSID');
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        session_set_cookie_params([
            'lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => $is_https,
            'httponly' => true, 'samesite' => 'Lax'
        ]);
        session_start();
    }
    session_regenerate_id(true);

    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $sesion['user_id'];
    $_SESSION['user_nombre'] = $sesion['nombre'];
    $_SESSION['user_correo'] = $sesion['correo'];
    $_SESSION['user_rol'] = $sesion['rol'];
    $_SESSION['tabla_origen'] = 'users';
    $_SESSION['es_admin'] = false;
    $_SESSION['is_admin'] = false;
    $_SESSION['is_cliente'] = true;
    $_SESSION['user_tipo'] = 'cliente';
    $_SESSION['2fa_verified'] = true;
    $_SESSION['2fa_verified_at'] = time();

    echo json_encode(['success' => true, 'message' => 'Autenticación exitosa', 'redirect_url' => '/proyecto/interfaz_usuario/pagina_modernizada.php']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar código 2FA']);
}

