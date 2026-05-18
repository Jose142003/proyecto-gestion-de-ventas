<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = isset($input['email']) ? trim($input['email']) : '';
    $pin = isset($input['pin']) ? trim($input['pin']) : '';
    $newPassword = isset($input['newPassword']) ? trim($input['newPassword']) : '';

    if (empty($email) || empty($pin) || empty($newPassword)) {
        $response['message'] = 'Email, PIN y nueva contraseña son requeridos';
    } elseif (strlen($newPassword) < 8) {
        $response['message'] = 'La contraseña debe tener al menos 8 caracteres';
    } else {
        try {
            $db = conectarDB();
            
            // Verificar que el usuario existe y obtener el token
            $stmt = $db->prepare("SELECT id, verification_token FROM users WHERE correo = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $response['message'] = 'No se encontró el usuario';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            if (empty($user['verification_token'])) {
                $response['message'] = 'No hay solicitud de recuperación activa. Solicita un nuevo PIN.';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            // Decodificar el token JSON
            $tokenData = json_decode($user['verification_token'], true);
            
            if (!$tokenData || !isset($tokenData['pin']) || !isset($tokenData['expires'])) {
                $response['message'] = 'Token inválido. Solicita un nuevo PIN.';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            // Verificar PIN
            if ($tokenData['pin'] !== $pin) {
                $response['message'] = 'PIN incorrecto. Verifica el código enviado a tu correo.';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            // Verificar expiración
            if (strtotime($tokenData['expires']) < time()) {
                $response['message'] = 'El PIN ha expirado. Solicita uno nuevo.';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            // Todo válido: actualizar contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = ?, verification_token = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Contraseña actualizada correctamente';
                logSistema("Contraseña actualizada para email: $email", 'INFO');
            } else {
                $response['message'] = 'Error al actualizar la contraseña';
            }
        } catch (Exception $e) {
            error_log("Error recuperación: " . $e->getMessage());
            $response['message'] = 'Error en el servidor';
        }
    }
}

ob_end_clean();
echo json_encode($response);
?>