<?php
header('Content-Type: application/json');
$allowedOrigin = defined('CORS_ORIGIN') ? CORS_ORIGIN : 'http://localhost';
header("Access-Control-Allow-Origin: $allowedOrigin");

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
    exit();
}

try {
    // Obtener parámetros de filtro
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : null;
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $sql = "SELECT 
                hs.*,
                p.name as producto_nombre,
                p.stock as stock_actual,
                u.nombre as usuario_nombre,
                (SELECT stock FROM products WHERE id = hs.producto_id) - hs.cantidad as stock_anterior
            FROM historial_stock hs
            LEFT JOIN products p ON hs.producto_id = p.id
            LEFT JOIN users u ON hs.usuario_id = u.id
            WHERE 1=1";

    $params = [];

    if ($fecha) {
        $sql .= " AND DATE(hs.fecha) = ?";
        $params[] = $fecha;
    }

    if ($tipo) {
        $sql .= " AND hs.tipo = ?";
        $params[] = $tipo;
    }

    $sql .= " ORDER BY hs.fecha DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($historial as &$row) {
        $row['stock_nuevo'] = $row['stock_actual'];
    }

    $countSql = "SELECT COUNT(*) FROM historial_stock hs WHERE 1=1";
    $countParams = [];
    if ($fecha) {
        $countSql .= " AND DATE(hs.fecha) = ?";
        $countParams[] = $fecha;
    }
    if ($tipo) {
        $countSql .= " AND hs.tipo = ?";
        $countParams[] = $tipo;
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $total = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'historial' => $historial,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>