<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');

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

    $sql .= " ORDER BY hs.fecha DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($historial as &$row) {
        $row['stock_nuevo'] = $row['stock_actual'];
    }

    echo json_encode([
        'success' => true,
        'historial' => $historial,
        'total' => count($historial)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>