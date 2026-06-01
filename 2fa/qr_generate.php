<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Correo requerido']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Look up user by email in both tables
    $user = null;
    $userTable = null;
    $secret = null;

    // Check admin_users
    $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, 2fa_secret, 2fa_enabled FROM admin_users WHERE correo = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $u = $stmt->fetch();
        if ($u['2fa_enabled'] && !empty($u['2fa_secret'])) {
            $user = $u;
            $userTable = 'admin_users';
            $secret = $u['2fa_secret'];
        }
    }

    // Check users
    if (!$user) {
        $stmt = $pdo->prepare("SELECT id, nombre, correo, rol, 2fa_secret, 2fa_enabled FROM users WHERE correo = ? AND is_active = 1 AND estado = 'activo' LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $u = $stmt->fetch();
            if ($u['2fa_enabled'] && !empty($u['2fa_secret'])) {
                $user = $u;
                $userTable = 'users';
                $secret = $u['2fa_secret'];
            }
        }
    }

    if (!$user) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Correo no encontrado o 2FA no configurado']);
        exit;
    }

    // Generate session token
    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', time() + 120);

    $stmt = $pdo->prepare("INSERT INTO qr_login_sessions (token, estado, expires_at) VALUES (?, 'pending', ?)");
    $stmt->execute([$token, $expiracion]);

    // Build otpauth:// URL for Google Authenticator
    $issuer = rawurlencode('PIC - Sistema de Gestion Comercial');
    $label = "$issuer:" . rawurlencode($email);
    $qrUrl = "otpauth://totp/$label?secret=$secret&issuer=$issuer&algorithm=SHA1&digits=6&period=30";

    echo json_encode([
        'success' => true,
        'token' => $token,
        'qr_url' => $qrUrl,
        'expires_at' => $expiracion,
        'user_table' => $userTable
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar QR']);
}
