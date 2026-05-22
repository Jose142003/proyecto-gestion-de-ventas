<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $countStmt = $pdo->query("SELECT COUNT(*) FROM auditoria_logs");
    $total = (int)$countStmt->fetchColumn();
    
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
            LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($data as &$row) {
        if (!empty($row['fecha_creacion'])) {
            $row['fecha'] = date('d/m/Y H:i:s', strtotime($row['fecha_creacion']));
        } else {
            $row['fecha'] = 'Fecha no disponible';
        }
        unset($row['fecha_creacion']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error interno del servidor']);
}
?>