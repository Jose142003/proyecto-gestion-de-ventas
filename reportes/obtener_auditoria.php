<?php
session_start();
header('Content-Type: application/json');
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
                CASE 
                    WHEN ip_address = '::1' THEN '127.0.0.1'
                    WHEN ip_address IS NULL THEN '0.0.0.0'
                    ELSE ip_address 
                END as ip_address,
                edit_count,
                last_edit_at
            FROM auditoria_logs 
            ORDER BY fecha_creacion DESC 
            LIMIT 200";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($data as &$row) {
        if (!empty($row['fecha_creacion'])) {
            $row['fecha'] = date('d/m/Y H:i:s', strtotime($row['fecha_creacion']));
        } else {
            $row['fecha'] = 'Fecha no disponible';
        }
        unset($row['fecha_creacion']);
    }
    
    echo json_encode($data);
    
} catch(PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>