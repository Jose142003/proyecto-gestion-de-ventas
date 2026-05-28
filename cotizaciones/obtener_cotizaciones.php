<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $busqueda = $_GET['busqueda'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $fecha_desde = $_GET['fecha_desde'] ?? '';
    $fecha_hasta = $_GET['fecha_hasta'] ?? '';

    $sql = "SELECT c.*, u.nombre as usuario_nombre,
            (SELECT COUNT(*) FROM cotizacion_detalles WHERE cotizacion_id = c.id) as total_productos
            FROM cotizaciones c
            LEFT JOIN admin_users u ON c.usuario_id = u.id
            WHERE 1=1";
    $params = [];

    if ($busqueda) {
        $sql .= " AND (c.numero_cotizacion LIKE ? OR c.cliente_nombre LIKE ? OR c.cliente_email LIKE ?)";
        $b = "%$busqueda%";
        $params[] = $b; $params[] = $b; $params[] = $b;
    }
    if ($estado) {
        $sql .= " AND c.estado = ?";
        $params[] = $estado;
    }
    if ($fecha_desde) {
        $sql .= " AND DATE(c.fecha_creacion) >= ?";
        $params[] = $fecha_desde;
    }
    if ($fecha_hasta) {
        $sql .= " AND DATE(c.fecha_creacion) <= ?";
        $params[] = $fecha_hasta;
    }

    $sql .= " ORDER BY c.fecha_creacion DESC LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cotizaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $cotizaciones], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al cargar cotizaciones: ' . $e->getMessage()]);
}
