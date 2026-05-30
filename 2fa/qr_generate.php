<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $token = bin2hex(random_bytes(32));
    $expiracion = date('Y-m-d H:i:s', time() + 120);

    $stmt = $pdo->prepare("INSERT INTO qr_login_sessions (token, estado, expires_at) VALUES (?, 'pending', ?)");
    $stmt->execute([$token, $expiracion]);

    $qrUrl = BASE_URL . '/2fa/qr_login.php?token=' . $token;

    echo json_encode([
        'success' => true,
        'token' => $token,
        'qr_url' => $qrUrl,
        'expires_at' => $expiracion
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar QR']);
}
