<?php
// enviar_token_email.php - VERSIÓN CORREGIDA
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
        
        $mail->Timeout = 30;
        $mail->SMTPKeepAlive = false;
        
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
        error_log("SMTP config: host=" . SMTP_HOST . " port=" . SMTP_PORT . " user=" . SMTP_USER);
        return false;
    }
}

function enviarEmailVerificacion($email, $nombre, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->Port       = SMTP_PORT;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 30;
        $mail->SMTPOptions = ['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'allow_self_signed' => false]];
        $mail->SMTPDebug  = 0;
        $mail->setFrom(SMTP_FROM_EMAIL, 'PIC Sistema Web');
        $mail->addAddress($email, $nombre);
        $mail->isHTML(true);
        $mail->Subject = 'Verifica tu correo - PIC';
        $base = rtrim(defined('BASE_URL') ? BASE_URL : '/proyecto', '/');
        $link = $base . '/usuarios/verificar_email.php?token=' . urlencode($token);
        $mail->Body = "
            <html><body style='font-family:Arial,sans-serif;'>
            <div style='max-width:600px;margin:0 auto;border:1px solid #ddd;padding:20px;'>
                <h2 style='color:#294E90;'>Bienvenido a PIC, {$nombre}</h2>
                <p>Gracias por registrarte. Para activar tu cuenta, haz clic en el siguiente enlace:</p>
                <div style='text-align:center;margin:25px 0;'>
                    <a href='{$link}' style='background:#294E90;color:white;padding:12px 30px;text-decoration:none;border-radius:5px;font-size:16px;'>Verificar mi correo</a>
                </div>
                <p>Si el botón no funciona, copia y pega este enlace en tu navegador:</p>
                <p style='color:#666;font-size:12px;'>{$link}</p>
                <p>Este enlace expirará en 24 horas.</p>
                <p style='color:#999;font-size:11px;'>Si no creaste esta cuenta, ignora este mensaje.</p>
            </div></body></html>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Error email verification: " . $mail->ErrorInfo);
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