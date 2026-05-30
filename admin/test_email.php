<?php
require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();
header('Content-Type: text/html; charset=utf-8');

echo "<h2>Prueba de Envío de Correo</h2>";

require_once __DIR__ . '/../usuarios/PHPMailer.php';
require_once __DIR__ . '/../usuarios/SMTP.php';
require_once __DIR__ . '/../usuarios/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    echo "<p>Configurando SMTP...</p>";
    echo "<p>Host: " . SMTP_HOST . "</p>";
    echo "<p>Port: " . SMTP_PORT . "</p>";
    echo "<p>User: " . SMTP_USER . "</p>";
    echo "<p>Pass: " . substr(SMTP_PASS, 0, 4) . "****</p>";
    
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) { echo htmlspecialchars($str) . "<br>"; };
    
    $mail->setFrom(SMTP_FROM_EMAIL, 'PIC Test');
    $mail->addAddress(SMTP_USER);
    $mail->Subject = 'Prueba SMTP - PIC';
    $mail->Body = 'Si recibes esto, el SMTP funciona correctamente.';
    
    $mail->send();
    echo "<p style='color:green;font-weight:bold;'>✅ CORREO ENVIADO EXITOSAMENTE</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red;font-weight:bold;'>❌ ERROR: " . htmlspecialchars($mail->ErrorInfo) . "</p>";
}
