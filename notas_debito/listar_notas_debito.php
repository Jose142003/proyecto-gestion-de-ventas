<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

requerirAdmin();

try {
    $pdo = Database::getConnection();

    $factura_id = isset($_GET['factura_id']) ? (int) $_GET['factura_id'] : null;
    $cliente_id = isset($_GET['cliente_id']) ? (int) $_GET['cliente_id'] : null;
    $fecha_desde = $_GET['fecha_desde'] ?? null;
    $fecha_hasta = $_GET['fecha_hasta'] ?? null;
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int) ($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($factura_id) {
        $where[] = 'nd.factura_id = :factura_id';
        $params[':factura_id'] = $factura_id;
    }
    if ($cliente_id) {
        $where[] = 'nd.cliente_id = :cliente_id';
        $params[':cliente_id'] = $cliente_id;
    }
    if ($fecha_desde) {
        $where[] = 'DATE(nd.created_at) >= :fecha_desde';
        $params[':fecha_desde'] = $fecha_desde;
    }
    if ($fecha_hasta) {
        $where[] = 'DATE(nd.created_at) <= :fecha_hasta';
        $params[':fecha_hasta'] = $fecha_hasta;
    }

    $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notas_debito nd $where_sql");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT nd.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento, f.numero_factura FROM notas_debito nd JOIN clientes c ON nd.cliente_id = c.id JOIN facturas f ON nd.factura_id = f.id $where_sql ORDER BY nd.id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $notas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'success' => true,
        'notas' => $notas,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);

} catch (PDOException $e) {
    error_log("Error en listar_notas_debito: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
