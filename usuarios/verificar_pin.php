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

    if (empty($email) || empty($pin)) {
        $response['message'] = 'Email y PIN son requeridos';
    } else {
        try {
            $db = conectarDB();
            
            // CORREGIDO: usar 'correo'
            $stmt = $db->prepare("SELECT id, nombre, verification_token FROM users WHERE correo = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['verification_token']) {
                $tokenData = json_decode($user['verification_token'], true);
                
                if ($tokenData && isset($tokenData['pin']) && isset($tokenData['expires'])) {
                    $expiracion = strtotime($tokenData['expires']);
                    $ahora = time();
                    
                    if ($ahora > $expiracion) {
                        $response['message'] = 'El código ha expirado. Solicita uno nuevo.';
                    } elseif ($tokenData['pin'] == $pin) {
                        $response['success'] = true;
                        $response['message'] = 'Código verificado correctamente';
                    } else {
                        $response['message'] = 'Código incorrecto';
                    }
                } else {
                    $response['message'] = 'Token inválido';
                }
            } else {
                $response['message'] = 'No se encontró solicitud de recuperación';
            }
        } catch (Exception $e) {
            error_log("Error verificar PIN: " . $e->getMessage());
            $response['message'] = 'Error en el servidor';
        }
    }
}

ob_end_clean();
echo json_encode($response);
?>