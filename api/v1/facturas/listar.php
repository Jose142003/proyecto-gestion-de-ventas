<?php
try {
    $pdo = Database::getConnection();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $estado = $_GET['estado'] ?? '';
    $clienteId = $_GET['cliente_id'] ?? '';
    $desde = $_GET['desde'] ?? '';
    $hasta = $_GET['hasta'] ?? '';

    $where = ["1 = 1"];
    $params = [];

    if ($estado) {
        $where[] = "f.estado = ?";
        $params[] = $estado;
    }

    if ($clienteId) {
        $where[] = "f.cliente_id = ?";
        $params[] = $clienteId;
    }

    if ($desde) {
        $where[] = "f.fecha_emision >= ?";
        $params[] = $desde;
    }

    if ($hasta) {
        $where[] = "f.fecha_emision <= ?";
        $params[] = $hasta . ' 23:59:59';
    }

    $whereClause = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) as total FROM facturas f WHERE $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT f.id, f.numero_factura, f.fecha_emision, f.fecha_vencimiento, f.subtotal, f.iva, f.total, f.estado, f.metodo_pago, f.created_at,
                   c.id AS cliente_id, c.nombre AS cliente_nombre, c.documento AS cliente_documento
            FROM facturas f
            LEFT JOIN clientes c ON f.cliente_id = c.id
            WHERE $whereClause
            ORDER BY f.created_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $facturas = $stmt->fetchAll();

    apiResponse([
        'success' => true,
        'data' => $facturas,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int)ceil($total / $perPage),
        ]
    ]);
} catch (Exception $e) {
    apiError('Error al listar facturas: ' . $e->getMessage(), 500);
}
