<?php
// enviar_mensaje.php - VERSIÓN CORREGIDA
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $nombre = $input['name'] ?? $input['nombre'] ?? '';
    $email = $input['email'] ?? '';
    $asunto = $input['subject'] ?? $input['asunto'] ?? '';
    $mensaje = $input['message'] ?? $input['mensaje'] ?? '';
} else {
    $nombre = $_POST['name'] ?? $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $asunto = $_POST['subject'] ?? $_POST['asunto'] ?? '';
    $mensaje = $_POST['message'] ?? $_POST['mensaje'] ?? '';
}

if (empty($nombre) || empty($email) || empty($asunto) || empty($mensaje)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    
    $mail->SMTPDebug = 0;
    
    // Remitente
    $mail->setFrom(SMTP_FROM_EMAIL, 'PIC Sistema Web');
    $mail->addAddress('Picca.ventas@gmail.com', 'Soporte PIC');
    $mail->addReplyTo($email, $nombre);
    
    // Contenido
    $mail->isHTML(true);
    $mail->Subject = "Contacto Web: $asunto";
    
    $cuerpo = "
    <html>
    <body style='font-family: Arial;'>
        <h2 style='color: #294E90;'>Nuevo mensaje de contacto</h2>
        <p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Asunto:</strong> " . htmlspecialchars($asunto) . "</p>
        <p><strong>Mensaje:</strong></p>
        <p style='background: #f5f5f5; padding: 10px;'>" . nl2br(htmlspecialchars($mensaje)) . "</p>
        <hr>
        <small>Enviado el " . date('Y-m-d H:i:s') . "</small>
    </body>
    </html>";
    
    $mail->Body = $cuerpo;
    $mail->AltBody = strip_tags($cuerpo);
    
    $mail->send();
    
    // Guardar copia local
    $carpeta = 'mensajes_recibidos';
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }
    
    $archivo = $carpeta . '/contacto_' . date('Y-m-d_H-i-s') . '.txt';
    $contenido = "FECHA: " . date('Y-m-d H:i:s') . "\nNOMBRE: $nombre\nEMAIL: $email\nASUNTO: $asunto\nMENSAJE:\n$mensaje\n";
    file_put_contents($archivo, $contenido);
    
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
    
} catch (Exception $e) {
    error_log("Error PHPMailer: " . $mail->ErrorInfo);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $mail->ErrorInfo]);
}
?>