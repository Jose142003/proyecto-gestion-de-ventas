<?php
try {
    $pdo = Database::getConnection();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $estado = $_GET['estado'] ?? '';
    $clienteId = $_GET['cliente_id'] ?? '';
    $search = $_GET['search'] ?? '';

    $where = ["1 = 1"];
    $params = [];

    if ($estado) {
        $where[] = "p.estado = ?";
        $params[] = $estado;
    }

    if ($clienteId) {
        $where[] = "p.cliente_id = ?";
        $params[] = $clienteId;
    }

    if ($search) {
        $where[] = "(p.numero_pedido LIKE ? OR c.nombre LIKE ?)";
        $s = "%$search%";
        $params[] = $s;
        $params[] = $s;
    }

    $whereClause = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) as total FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id WHERE $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT p.id, p.numero_pedido, p.fecha_pedido, p.subtotal, p.impuesto, p.iva, p.total, p.estado, p.metodo_pago, p.referencia_pago, p.created_at, p.updated_at,
                   c.id AS cliente_id, c.nombre AS cliente_nombre, c.documento AS cliente_documento
            FROM pedidos p
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE $whereClause
            ORDER BY p.created_at DESC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll();

    apiResponse([
        'success' => true,
        'data' => $pedidos,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int)ceil($total / $perPage),
        ]
    ]);
} catch (Exception $e) {
    apiError('Error al listar pedidos: ' . $e->getMessage(), 500);
}
