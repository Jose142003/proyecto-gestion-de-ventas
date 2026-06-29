<?php
session_name('CLIENTSESSID');
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/enviar_token_email.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$correo = trim($_POST['correo'] ?? '');
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Correo inválido']);
    exit;
}

$db = Database::getConnection();
$stmt = $db->prepare("SELECT id, nombre, email_verified FROM users WHERE correo = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Correo no registrado']);
    exit;
}

if ($user['email_verified']) {
    echo json_encode(['success' => false, 'message' => 'La cuenta ya está verificada']);
    exit;
}

$token = bin2hex(random_bytes(32));
$tokenData = json_encode([
    'token' => $token,
    'type' => 'email_verification',
    'expires' => date('Y-m-d H:i:s', strtotime('+24 hours'))
]);
$upd = $db->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
$upd->execute([$tokenData, $user['id']]);

$enviado = enviarEmailVerificacion($correo, $user['nombre'], $token);
if ($enviado) {
    echo json_encode(['success' => true, 'message' => 'Correo de verificación enviado. Revisa tu bandeja de entrada.']);
} else {
    echo json_encode(['success' => true, 'message' => 'Registro completado. No se pudo enviar el correo (configura SMTP).']);
}
