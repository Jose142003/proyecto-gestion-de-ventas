<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de registro no proporcionado']);
    exit;
}

$id = intval($_GET['id']);
require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    $sql = "SELECT 
                id,
                fecha_creacion,
                COALESCE(usuario_nombre, 'Sistema') as usuario_nombre,
                COALESCE(usuario_rol, 'sistema') as usuario_rol,
                COALESCE(modulo, 'sistema') as modulo,
                COALESCE(accion, 'N/A') as accion,
                COALESCE(descripcion, 'Sin descripción') as descripcion,
                COALESCE(ip_address, '0.0.0.0') as ip_address,
                tabla_afectada,
                registro_id,
                datos_anteriores,
                datos_nuevos,
                edit_count,
                last_edit_at,
                last_edit_by
            FROM auditoria_logs 
            WHERE id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        echo json_encode(['success' => false, 'message' => 'Registro no encontrado']);
        exit;
    }
    
    echo json_encode(['success' => true, 'registro' => $registro]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>