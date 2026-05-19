<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Verificar que sea método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Incluir configuración de base de datos
require_once '../conexion/conexion.php';

session_start();

verificarCSRF();

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado. Por favor inicie sesión nuevamente.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$es_admin = $_SESSION['es_admin'] ?? false;
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

// Validar campos (aceptar ambos nombres de campo)
$nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
$correo = isset($input['correo']) ? trim($input['correo']) : (isset($input['email']) ? trim($input['email']) : '');
$telefono = isset($input['telefono']) ? trim($input['telefono']) : '';
$direccion = isset($input['direccion']) ? trim($input['direccion']) : '';

// Validaciones
if (empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
    exit();
}

if (empty($correo)) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico es requerido']);
    exit();
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico no es válido']);
    exit();
}

// Conectar a la base de datos
$database = new Database();
$db = $database->getConnection();
    
try {
    $db->beginTransaction();
    
    // Determinar qué tabla actualizar
    $tabla = ($es_admin || $tabla_origen === 'admin_users') ? 'admin_users' : 'users';
    
    // Verificar si el correo ya está en uso por otro usuario
    if ($tabla === 'admin_users') {
        $checkQuery = "SELECT id FROM admin_users WHERE correo = :correo AND id != :user_id";
    } else {
        $checkQuery = "SELECT id FROM users WHERE correo = :correo AND id != :user_id";
    }
    
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(':correo', $correo);
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado por otro usuario']);
        exit();
    }
    
    // Actualizar perfil según la tabla (INCLUYENDO TELÉFONO PARA ADMIN)
    if ($tabla === 'admin_users') {
        $query = "UPDATE admin_users SET 
                    nombre = :nombre, 
                    correo = :correo,
                    telefono = :telefono
                  WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        $query = "UPDATE users SET 
                    nombre = :nombre, 
                    correo = :correo, 
                    telefono = :telefono,
                    direccion = :direccion
                  WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':correo', $correo);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':user_id', $user_id);
    }
    
    if ($stmt->execute()) {
        // Actualizar datos en sesión
        $_SESSION['user_nombre'] = $nombre;
        $_SESSION['user_email'] = $correo;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['correo'] = $correo;
        $_SESSION['telefono'] = $telefono;  // Agregar teléfono a sesión
        
        if (!$es_admin) {
            $_SESSION['user_telefono'] = $telefono;
        }
        
        // Registrar en auditoría
        $auditQuery = "INSERT INTO auditoria_logs (usuario_id, usuario_nombre, usuario_rol, accion, modulo, descripcion, ip_address, fecha_creacion) 
                       VALUES (:usuario_id, :usuario_nombre, :usuario_rol, 'actualizar_perfil', 'perfil', 'Usuario actualizó su perfil', :ip, NOW())";
        $auditStmt = $db->prepare($auditQuery);
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rol = $_SESSION['user_rol'] ?? $_SESSION['rol'] ?? 'usuario';
        $auditStmt->bindParam(':usuario_id', $user_id);
        $auditStmt->bindParam(':usuario_nombre', $nombre);
        $auditStmt->bindParam(':usuario_rol', $rol);
        $auditStmt->bindParam(':ip', $ip);
        $auditStmt->execute();
        
        $db->commit();
        
        $responseData = [
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'data' => [
                'nombre' => $nombre,
                'email' => $correo,
                'telefono' => $telefono,
                'direccion' => $direccion
            ]
        ];
        
        echo json_encode($responseData);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar el perfil']);
    }
    
} catch (PDOException $e) {
    if (isset($db)) $db->rollBack();
    error_log("Error en actualizar_perfil.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>