<?php
// Desactivar visualización de errores
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';
require_once 'enviar_token_email.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Error al procesar la solicitud';
        ob_end_clean();
        echo json_encode($response);
        exit;
    }
    
    $email_raw = isset($input['email']) ? trim($input['email']) : '';
    $email = filter_var($email_raw, FILTER_SANITIZE_EMAIL);

    if (empty($email_raw)) {
        $response['message'] = 'El campo de correo está vacío.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'El formato del correo electrónico no es válido.';
    } else {
        try {
            $db = conectarDB();
            
            $stmt = $db->prepare("SELECT id, nombre, 'users' as tipo FROM users WHERE correo = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $stmt = $db->prepare("SELECT id, nombre, 'admin_users' as tipo FROM admin_users WHERE correo = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($user) {
                $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $tokenData = json_encode([
                    'pin' => $pin,
                    'expires' => $expira,
                    'type' => 'password_reset'
                ]);

                $tabla = $user['tipo'];
                $update = $db->prepare("UPDATE $tabla SET verification_token = ? WHERE id = ?");
                $update->execute([$tokenData, $user['id']]);

                if (enviarTokenEmail($email, $user['nombre'], $pin)) {
                    $response['success'] = true;
                    $response['message'] = 'Se ha enviado un código de verificación a tu correo.';
                } else {
                    $response['message'] = 'Error al enviar el correo. Verifica que el servidor SMTP esté configurado correctamente.';
                }
            } else {
                $response['message'] = 'No encontramos una cuenta asociada a este correo.';
            }
        } catch (PDOException $e) {
            error_log("Error DB: " . $e->getMessage());
            $response['message'] = 'Error de conexión con la base de datos.';
        } catch (Exception $e) {
            error_log("Error general: " . $e->getMessage());
            $response['message'] = 'Error en el servidor.';
        }
    }
} else {
    $response['message'] = 'Método no válido.';
}

ob_end_clean();
echo json_encode($response);
?>