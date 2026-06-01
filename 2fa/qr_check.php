<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$token = trim($_GET['token'] ?? '');
if (empty($token)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token requerido']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT estado, user_id, user_table, user_data, expires_at FROM qr_login_sessions WHERE token = ?");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'status' => 'invalid', 'message' => 'Token inválido']);
        exit;
    }

    if (strtotime($row['expires_at']) < time()) {
        $pdo->prepare("UPDATE qr_login_sessions SET estado = 'expired' WHERE token = ?")->execute([$token]);
        echo json_encode(['success' => false, 'status' => 'expired', 'message' => 'QR expirado']);
        exit;
    }

    if ($row['estado'] === 'approved') {
        $userData = json_decode($row['user_data'], true);

        if (!$userData || empty($userData['user_table'])) {
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Datos de usuario inválidos']);
            exit;
        }

        // Eliminar el registro de QR para que no pueda reutilizarse
        $pdo->prepare("DELETE FROM qr_login_sessions WHERE token = ?")->execute([$token]);

        // Configurar la sesión en el navegador del escritorio
        if (session_status() === PHP_SESSION_NONE) {
            if ($userData['user_table'] === 'admin_users') {
                session_name(ini_get('session.name'));
            } else {
                session_name('CLIENTSESSID');
            }
            session_set_cookie_params([
                'lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false,
                'httponly' => true, 'samesite' => 'Lax'
            ]);
            session_start();
        }
        $_SESSION = array();
        session_regenerate_id(true);

        $_SESSION['_ultimo_acceso'] = time();
        $_SESSION['_regenerado_en'] = time();
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_nombre'] = $userData['nombre'];
        $_SESSION['user_correo'] = $userData['correo'];
        $_SESSION['user_rol'] = $userData['rol'];
        $_SESSION['tabla_origen'] = $userData['user_table'];
        $_SESSION['2fa_verified'] = true;
        $_SESSION['2fa_verified_at'] = time();

        if ($userData['user_table'] === 'admin_users') {
            $_SESSION['es_admin'] = true;
            $_SESSION['is_admin'] = true;
            $_SESSION['is_cliente'] = false;
            $_SESSION['user_tipo'] = 'admin';
            $redirect = BASE_URL . '/panel_admin/panel_admin.php';
        } else {
            $_SESSION['es_admin'] = false;
            $_SESSION['is_admin'] = false;
            $_SESSION['is_cliente'] = true;
            $_SESSION['user_tipo'] = 'cliente';
            $redirect = BASE_URL . '/interfaz_usuario/pagina_modernizada.html';
        }

        // Cookie persistente
        $token_data = $userData['id'] . '|' . $userData['nombre'] . '|' . $userData['user_table'];
        $token_sig = hash_hmac('sha256', $token_data, BASE_URL);
        $token_value = base64_encode($token_data . '|' . $token_sig);
        setcookie('persist_token', $token_value, time() + 86400 * 30, '/', '', false, true);

        // Actualizar último login
        $col = ($userData['user_table'] === 'admin_users') ? 'admin_users' : 'users';
        $colFecha = ($col === 'admin_users') ? 'ultimo_login' : 'last_login';
        $pdo->prepare("UPDATE $col SET $colFecha = NOW() WHERE id = ?")->execute([$userData['id']]);

        session_write_close();

        echo json_encode([
            'success' => true,
            'status' => 'approved',
            'redirect_url' => $redirect,
            'user_nombre' => $userData['nombre']
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'status' => $row['estado'],
        'message' => 'Esperando escaneo...'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar QR']);
}
