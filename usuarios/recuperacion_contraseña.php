<?php
error_reporting(0);
ini_set('display_errors', 0);

ob_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';
iniciarSesion();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $email = isset($input['email']) ? trim($input['email']) : '';
    if (empty($email)) {
        $email = $_SESSION['recovery_email'] ?? '';
    }
    $newPassword = isset($input['newPassword']) ? trim($input['newPassword']) : '';
    $nuevaContrasena = isset($input['nueva_contrasena']) ? trim($input['nueva_contrasena']) : '';
    if (empty($newPassword) && !empty($nuevaContrasena)) {
        $newPassword = $nuevaContrasena;
    }

    if (empty($email) || empty($newPassword)) {
        $response['message'] = 'Email y nueva contraseña son requeridos';
    } elseif (strlen($newPassword) < 6) {
        $response['message'] = 'La contraseña debe tener al menos 6 caracteres';
    } else {
        try {
            $db = conectarDB();
            
            $stmt = $db->prepare("SELECT id, verification_token, 'users' as tipo FROM users WHERE correo = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $stmt = $db->prepare("SELECT id, verification_token, 'admin_users' as tipo FROM admin_users WHERE correo = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
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
            
            $tokenData = json_decode($user['verification_token'], true);
            
            if (!$tokenData || !isset($tokenData['pin']) || !isset($tokenData['expires'])) {
                $response['message'] = 'Token inválido. Solicita un nuevo PIN.';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            if (strtotime($tokenData['expires']) < time()) {
                $response['message'] = 'El PIN ha expirado. Solicita uno nuevo.';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $tabla = $user['tipo'];
            $tablas_permitidas = ['users', 'admin_users'];
            if (!in_array($tabla, $tablas_permitidas)) {
                $response['message'] = 'Tipo de usuario inválido';
                ob_end_clean();
                echo json_encode($response);
                exit;
            }
            $columnaPass = ($tabla === 'admin_users') ? 'contrasena' : 'password';
            $stmt = $db->prepare("UPDATE $tabla SET $columnaPass = ?, verification_token = NULL WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Contraseña actualizada correctamente';
                logSistema("Contraseña actualizada para email: $email ($tabla)", 'INFO');
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