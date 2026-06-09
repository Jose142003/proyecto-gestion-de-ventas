<?php
// config_email.php - Configuración SMTP con variables de entorno
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        $this->mail->isSMTP();
        $this->mail->Host = SMTP_HOST;
        $this->mail->Port = SMTP_PORT;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = SMTP_USER;
        $this->mail->Password = SMTP_PASS;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->CharSet = 'UTF-8';
        
        $this->mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        
        $this->mail->SMTPDebug = 0;
        $this->mail->Timeout = 30;
    }
    
    public function send($to, $subject, $body, $fromName = '') {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            
            $this->mail->setFrom(SMTP_FROM_EMAIL, $fromName ?: 'PIC Sistema de Facturación');
            $this->mail->addAddress($to);
            $this->mail->addReplyTo(SMTP_FROM_EMAIL, 'Soporte PIC');
            
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

function enviarCorreo($to, $subject, $body, $fromName = '') {
    $sender = new EmailSender();
    return $sender->send($to, $subject, $body, $fromName);
}
?>