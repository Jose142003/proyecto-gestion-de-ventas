<?php
session_start();
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once dirname(__DIR__) . '/conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
    exit;
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Soporte para ambos formatos de campo
$currentPassword = $data['current_password'] ?? $data['currentPassword'] ?? '';
$newPassword = $data['new_password'] ?? $data['newPassword'] ?? '';
$confirmPassword = $data['confirm_new_password'] ?? $data['confirmNewPassword'] ?? '';

// Validaciones
if (empty($currentPassword)) {
    echo json_encode(["success" => false, "message" => "Debes ingresar tu contraseña actual"]);
    exit;
}
if (empty($newPassword)) {
    echo json_encode(["success" => false, "message" => "Debes ingresar una nueva contraseña"]);
    exit;
}
if ($newPassword !== $confirmPassword) {
    echo json_encode(["success" => false, "message" => "Las contraseñas nuevas no coinciden"]);
    exit;
}
if (strlen($newPassword) < 6) {
    echo json_encode(["success" => false, "message" => "La contraseña debe tener al menos 6 caracteres"]);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    $db = conectarDB();
    
    // Determinar qué tipo de usuario es (admin o cliente)
    $es_admin = isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
    $tabla_origen = $_SESSION['tabla_origen'] ?? null;
    
    // ============================================================
    // CASO 1: USUARIO ADMIN (tabla admin_users)
    // ============================================================
    if ($es_admin || $tabla_origen === 'admin_users') {
        
        $stmt = $db->prepare("SELECT id, nombre, contrasena, usuario FROM admin_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            echo json_encode(["success" => false, "message" => "Usuario administrador no encontrado"]);
            exit;
        }
        
        $stored_hash = $admin['contrasena'];
        $input_hash = hash('sha256', $currentPassword);
        
        // Normalizar a MAYÚSCULAS para comparación
        if (strtoupper($input_hash) !== strtoupper($stored_hash)) {
            echo json_encode(["success" => false, "message" => "Contraseña actual incorrecta"]);
            exit;
        }
        
        // Actualizar nueva contraseña (en MAYÚSCULAS)
        $new_hash = strtoupper(hash('sha256', $newPassword));
        $update = $db->prepare("UPDATE admin_users SET contrasena = ? WHERE id = ?");
        
        if ($update->execute([$new_hash, $user_id])) {
            echo json_encode(["success" => true, "message" => "Contraseña cambiada exitosamente"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al actualizar la contraseña"]);
        }
        exit;
    }
    
    // ============================================================
    // CASO 2: USUARIO CLIENTE (tabla users)
    // ============================================================
    else {
        
        $stmt = $db->prepare("SELECT id, nombre, password, correo FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
            exit;
        }
        
        // Verificar contraseña actual (bcrypt)
        if (!password_verify($currentPassword, $user['password'])) {
            // Log de intento fallido
            $log_file = dirname(__DIR__) . '/logs/password_debug.log';
            if (!is_dir(dirname($log_file))) mkdir(dirname($log_file), 0777, true);
            file_put_contents($log_file, json_encode([
                'fecha' => date('Y-m-d H:i:s'),
                'usuario_id' => $user_id,
                'usuario_email' => $user['correo'],
                'tipo' => 'cliente',
                'error' => 'Contraseña actual incorrecta'
            ]) . PHP_EOL, FILE_APPEND);
            
            echo json_encode(["success" => false, "message" => "Contraseña actual incorrecta"]);
            exit;
        }
        
        // Actualizar nueva contraseña (bcrypt)
        $new_hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($update->execute([$new_hash, $user_id])) {
            echo json_encode(["success" => true, "message" => "Contraseña cambiada exitosamente"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error al actualizar la contraseña"]);
        }
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
?>