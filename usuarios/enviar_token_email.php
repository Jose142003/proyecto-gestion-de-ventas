<?php
// enviar_token_email.php - VERSIÓN CORREGIDA
require_once __DIR__ . '/../config/database.php';
require_once 'PHPMailer.php';
require_once 'SMTP.php';
require_once 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function enviarTokenEmail($email, $nombre, $pin) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => true,
                'verify_peer_name' => true,
                'allow_self_signed' => false
            )
        );
        
        $mail->SMTPDebug = 0;

        // Destinatarios
        $mail->setFrom(SMTP_FROM_EMAIL, 'PIC Sistema Web');
        $mail->addAddress($email, $nombre);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Código de Verificación - Restablecer Contraseña';
        
        $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px;'>
                    <h2 style='color: #333;'>Hola, {$nombre}</h2>
                    <p>Has solicitado restablecer tu contraseña. Utiliza el siguiente código para continuar:</p>
                    <div style='background: #f4f4f4; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;'>
                        {$pin}
                    </div>
                    <p>Este código expirará en 1 hora.</p>
                    <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                </div>
            </body>
            </html>
        ";

        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

function enviarNotificacionCompra($email, $nombre, $factura_id, $total) {
    $asunto = "Confirmación de compra - Proyectos Industriales";
    $mensaje = "Hola $nombre, tu compra #$factura_id por Bs. " . number_format($total, 2) . " ha sido registrada.";
    
    // Versión simplificada usando mail() nativo como respaldo
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Carrito Shop <jose14chacon2003@gmail.com>" . "\r\n";
    
    $htmlMensaje = "
    <html>
    <body>
        <h2>Confirmación de Compra</h2>
        <p>Hola <strong>$nombre</strong>,</p>
        <p>Tu compra ha sido registrada exitosamente.</p>
        <p><strong>Factura #:</strong> $factura_id</p>
        <p><strong>Total:</strong> Bs. " . number_format($total, 2) . "</p>
        <p>¡Gracias por tu compra!</p>
    </body>
    </html>";
    
    return mail($email, $asunto, $htmlMensaje, $headers);
}
?>