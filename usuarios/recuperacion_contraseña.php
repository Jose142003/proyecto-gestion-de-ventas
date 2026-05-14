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
    $newPassword = isset($input['newPassword']) ? trim($input['newPassword']) : '';

    if (empty($email) || empty($newPassword)) {
        $response['message'] = 'Email y nueva contraseña son requeridos';
    } elseif (strlen($newPassword) < 6) {
        $response['message'] = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        try {
            $db = conectarDB();
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // CORREGIDO: usar 'correo'
            $stmt = $db->prepare("UPDATE users SET password = ?, verification_token = NULL WHERE correo = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Contraseña actualizada correctamente';
            } else {
                $response['message'] = 'No se encontró el usuario';
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