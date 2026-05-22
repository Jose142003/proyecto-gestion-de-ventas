<?php
namespace PIC\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host = SMTP_HOST;
        $this->mail->Port = SMTP_PORT;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = SMTP_USER;
        $this->mail->Password = SMTP_PASS;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->CharSet = 'UTF-8';
        $this->mail->SMTPDebug = 0;
        $this->mail->Timeout = 30;
    }

    public function send(string $to, string $subject, string $body, ?string $fromName = null): array
    {
        try {
            $this->mail->clearAddresses();
            $this->mail->clearAttachments();
            $this->mail->setFrom(SMTP_FROM_EMAIL, $fromName ?: 'PIC Sistema');
            $this->mail->addAddress($to);
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->AltBody = strip_tags($body);
            $this->mail->send();
            return ['success' => true, 'message' => 'Correo enviado correctamente'];
        } catch (Exception $e) {
            $error = $this->mail->ErrorInfo ?: $e->getMessage();
            error_log("Error EmailService: " . $error);
            return ['success' => false, 'message' => $error];
        }
    }
}
