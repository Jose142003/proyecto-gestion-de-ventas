<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'];
$usuario_rol = $_SESSION['rol'] ?? $_SESSION['user_rol'] ?? '';

if ($usuario_rol !== 'admin' && $usuario_rol !== 'Administrador' && $usuario_rol !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para editar auditoría']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id']) || !isset($data['nueva_descripcion']) || !isset($data['motivo'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id = intval($data['id']);
$nueva_descripcion = trim($data['nueva_descripcion']);
$motivo = trim($data['motivo']);

if (empty($nueva_descripcion)) {
    echo json_encode(['success' => false, 'message' => 'La descripción no puede estar vacía']);
    exit;
}

$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT descripcion, edit_count, edit_history FROM auditoria_logs WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }
    
    $edit_count = intval($registro['edit_count'] ?? 0);
    
    if ($edit_count >= 3) {
        echo json_encode(['success' => false, 'message' => 'Este registro ya ha sido editado 3 veces. No se permiten más ediciones.']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT nombre FROM users WHERE id = :id");
    $stmt->execute([':id' => $usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $stmt = $pdo->prepare("SELECT nombre FROM admin_users WHERE id = :id");
        $stmt->execute([':id' => $usuario_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $nombre_usuario = $usuario['nombre'] ?? 'Usuario ID: ' . $usuario_id;
    
    $edit_history = $registro['edit_history'] ?? '';
    $nueva_entrada = date('Y-m-d H:i:s') . " | Usuario: $nombre_usuario (ID: $usuario_id) | Motivo: $motivo | Descripción anterior: " . $registro['descripcion'] . "\n";
    $edit_history = $nueva_entrada . $edit_history;
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($ip == '::1') $ip = '127.0.0.1';
    
    $stmt = $pdo->prepare("UPDATE auditoria_logs 
                          SET descripcion = :descripcion, 
                              edit_count = edit_count + 1,
                              edit_history = :edit_history,
                              last_edit_by = :last_edit_by,
                              last_edit_at = NOW()
                          WHERE id = :id");
    
    $stmt->execute([
        ':descripcion' => $nueva_descripcion,
        ':edit_history' => $edit_history,
        ':last_edit_by' => $usuario_id,
        ':id' => $id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Registro actualizado correctamente']);
    
} catch (Exception $e) {
    error_log("Error editar auditoría: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>