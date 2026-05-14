<?php
// obtener_usuario.php - Versión mejorada para PERFIL (NO para autenticación)
session_start();
require_once '../conexion/conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

try {
    $db = conectarDB();
    $usuario = null;
    
    // Buscar según la tabla de origen guardada en sesión
    if ($tabla_origen === 'admin_users') {
        // 🔧 CORREGIDO: Incluir telefono en el SELECT
        $query = "SELECT id, nombre, correo, telefono, rol, activo as is_active, 
                         fecha_registro as created_at, ultimo_login as last_login,
                         NULL as direccion
                  FROM admin_users 
                  WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            // No forzar 'No registrado' si es null, dejar que el JS lo maneje
            $usuario['direccion'] = 'Oficina Principal';
            $usuario['email'] = $usuario['correo'];
            echo json_encode([
                "success" => true,
                "usuario" => $usuario,
                "tipo" => "admin"
            ]);
            exit;
        }
    } else {
        // Buscar en users (clientes)
        $query = "SELECT id, nombre, correo as email, telefono, direccion, rol, 
                         foto_perfil, is_active, created_at, last_login
                  FROM users 
                  WHERE id = :user_id AND is_active = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($usuario) {
            $usuario['correo'] = $usuario['email'];
            echo json_encode([
                "success" => true,
                "usuario" => $usuario,
                "tipo" => "cliente"
            ]);
            exit;
        }
    }
    
    echo json_encode([
        "success" => false,
        "message" => "Usuario no encontrado"
    ]);
    
} catch (PDOException $e) {
    error_log("Error en obtener_usuario: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Error al obtener datos del usuario"
    ]);
}
?>