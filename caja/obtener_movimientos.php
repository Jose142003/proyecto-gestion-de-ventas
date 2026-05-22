<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado', 'movimientos' => []]);
    exit;
}

require_once '../conexion/conexion.php';

try {
    $db = conectarDB();
    
    $fecha_desde = $_GET['fecha_desde'] ?? null;
    $fecha_hasta = $_GET['fecha_hasta'] ?? null;
    $tipo = $_GET['tipo'] ?? null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    
    $sql = "SELECT cm.*, u.nombre as usuario_nombre 
            FROM caja_movimientos cm
            LEFT JOIN users u ON cm.usuario_id = u.id
            WHERE 1=1";
    $params = [];
    
    if ($fecha_desde) {
        $sql .= " AND DATE(cm.fecha_movimiento) >= :fecha_desde";
        $params[':fecha_desde'] = $fecha_desde;
    }
    
    if ($fecha_hasta) {
        $sql .= " AND DATE(cm.fecha_movimiento) <= :fecha_hasta";
        $params[':fecha_hasta'] = $fecha_hasta;
    }
    
    if ($tipo && in_array($tipo, ['ingreso', 'egreso'])) {
        $sql .= " AND cm.tipo = :tipo";
        $params[':tipo'] = $tipo;
    }
    
    $sql .= " ORDER BY cm.fecha_movimiento DESC LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        if (in_array($key, [':limit', ':offset'])) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $movimientosFormateados = [];
    foreach ($movimientos as $m) {
        $movimientosFormateados[] = [
            'id' => $m['id'],
            'fecha' => $m['fecha_movimiento'],
            'tipo' => $m['tipo'],
            'categoria' => $m['categoria'],
            'monto' => floatval($m['monto']),
            'descripcion' => $m['descripcion'],
            'referencia' => $m['referencia'],
            'metodo_pago' => $m['metodo_pago'],
            'usuario_nombre' => $m['usuario_nombre'] ?? 'Sistema'
        ];
    }
    
    $countSql = "SELECT COUNT(*) FROM caja_movimientos cm WHERE 1=1";
    $countParams = [];
    if ($fecha_desde) {
        $countSql .= " AND DATE(cm.fecha_movimiento) >= ?";
        $countParams[] = $fecha_desde;
    }
    if ($fecha_hasta) {
        $countSql .= " AND DATE(cm.fecha_movimiento) <= ?";
        $countParams[] = $fecha_hasta;
    }
    if ($tipo && in_array($tipo, ['ingreso', 'egreso'])) {
        $countSql .= " AND cm.tipo = ?";
        $countParams[] = $tipo;
    }
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();

    $stmtRes = $db->query("SELECT 
                            SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
                            SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as total_egresos
                            FROM caja_movimientos");
    $resumen = $stmtRes->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'movimientos' => $movimientosFormateados,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit),
        'total_ingresos' => floatval($resumen['total_ingresos'] ?? 0),
        'total_egresos' => floatval($resumen['total_egresos'] ?? 0)
    ]);
    
} catch (Exception $e) {
    error_log("Error obtener movimientos: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al obtener movimientos', 'movimientos' => []]);
}
?>