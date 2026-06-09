<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo requerido']);
    exit;
}

try {
    $pdo = Database::getConnection();

    // Verificar si las columnas 2FA existen antes de consultarlas
    $has2faColumns = false;
    foreach (['admin_users', 'users'] as $t) {
        $ck = $pdo->query("SHOW COLUMNS FROM `$t` LIKE '2fa_enabled'");
        if ($ck->rowCount() > 0) { $has2faColumns = true; break; }
    }

    // Look up user by email in both tables
    $user = null;
    $userTable = null;
    $secret = null;

    // Check admin_users
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, 2fa_enabled, 2fa_secret FROM admin_users WHERE correo = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $u = $stmt->fetch();
        $user = $u;
        $userTable = 'admin_users';
        if ($has2faColumns && $u['2fa_enabled'] && !empty($u['2fa_secret'])) {
            $secret = $u['2fa_secret'];
        }
    }

    // Check users
    if (!$user) {
        $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, 2fa_enabled, 2fa_secret FROM users WHERE correo = ? AND is_active = 1 AND estado = 'activo' LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $u = $stmt->fetch();
            $user = $u;
            $userTable = 'users';
            if ($has2faColumns && $u['2fa_enabled'] && !empty($u['2fa_secret'])) {
                $secret = $u['2fa_secret'];
            }
        }
    }

    if (!$user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Correo no encontrado']);
        exit;
    }

    // Generate session token
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', time() + 120);

    $stmt = $pdo->prepare("INSERT INTO qr_login_sessions (token, estado, expires_at) VALUES (?, 'pending', ?)");
    $stmt->execute([$token, $expiracion]);

    // Si hay secret 2FA, construir URL TOTP; si no, usar una URL simple de sesion
    if ($secret) {
        $issuer = rawurlencode('PIC - Sistema de Gestion Comercial');
        $label = "$issuer:" . rawurlencode($email);
        $qrUrl = "otpauth://totp/$label?secret=$secret&issuer=$issuer&algorithm=SHA1&digits=6&period=30";
    } else {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'http://localhost/proyecto';
        $qrUrl = "$baseUrl/2fa/qr_login.php?token=$token";
    }

    echo json_encode([
        'success' => true,
        'token' => $token,
        'qr_url' => $qrUrl,
        'expires_at' => $expiracion,
        'user_table' => $userTable,
        'needs_2fa' => ($secret !== null)
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar QR']);
}
