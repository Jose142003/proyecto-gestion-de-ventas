<?php
// config_email.php - Configuración mejorada para Gmail
require_once 'PHPMailer.php';
require_once 'SMTP.php';
require_once 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;
    
    // Tus credenciales - ACTUALIZA LA CONTRASEÑA SI ES NECESARIO
    private $email = 'jose14chacon2003@gmail.com';
    private $password = 'bzwusevvegbuqozg';  // ← VERIFICA ESTA CONTRASEÑA
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Configuración SMTP para Gmail
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->Port = 587;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $this->email;
        $this->mail->Password = $this->password;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->CharSet = 'UTF-8';
        
        // IMPORTANTE: Deshabilitar verificación SSL (para evitar problemas)
        $this->mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        // Deshabilitar depuración en producción
        $this->mail->SMTPDebug = 0; // Cambia a 2 para depuración
        
        // Timeout más largo
        $this->mail->Timeout = 30;
    }
    
    /**
     * Envía un correo
     */
    public function send($to, $subject, $body, $fromName = '') {
        try {
            // Limpiar destinatarios anteriores
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            // Remitente
            $this->mail->setFrom($this->email, $fromName ?: 'PIC Sistema de Facturación');
            
            // Destinatario
            $this->mail->addAddress($to);
            
            // Reply-To
            $this->mail->addReplyTo('picca.ventas@gmail.com', 'Soporte PIC');
            
            // Contenido
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Correo enviado correctamente'];
            
        } catch (Exception $e) {
            $errorMsg = $this->mail->ErrorInfo;
            error_log("Error PHPMailer: " . $errorMsg);
            return ['success' => false, 'message' => $errorMsg];
        }
    }
    
    /**
     * Prueba la conexión SMTP
     */
    public function testConnection() {
        try {
            $this->mail->smtpConnect();
            $this->mail->smtpClose();
            return ['success' => true, 'message' => 'Conexión SMTP exitosa'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
}

/**
 * Función helper para enviar correos
 */
function enviarCorreo($to, $subject, $body, $fromName = '') {
    $sender = new EmailSender();
    return $sender->send($to, $subject, $body, $fromName);
}
?>