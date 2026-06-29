<?php
try {
    $pdo = Database::getConnection();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $category = $_GET['category'] ?? '';
    $stockFilter = $_GET['stock'] ?? '';

    $where = ["p.active = 1 AND p.deleted_at IS NULL"];
    $params = [];

    if ($category) {
        $where[] = "p.category = ?";
        $params[] = $category;
    }

    if ($stockFilter === 'bajo') {
        $where[] = "p.stock <= 5 AND p.stock > 0";
    } elseif ($stockFilter === 'sin_stock') {
        $where[] = "p.stock = 0";
    } elseif ($stockFilter === 'optimo') {
        $where[] = "p.stock > 5";
    }

    $whereClause = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) as total FROM products p WHERE $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT p.id, p.sku, p.name, p.category, p.stock, p.price, p.image_url,
                   CASE
                       WHEN p.stock = 0 THEN 'sin_stock'
                       WHEN p.stock <= 5 THEN 'stock_bajo'
                       WHEN p.stock <= 10 THEN 'stock_medio'
                       ELSE 'stock_optimo'
                   END AS estado_stock,
                   COALESCE((SELECT SUM(cantidad) FROM pedido_detalles pd JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pd.producto_id = p.id), 0) AS total_vendido
            FROM products p
            WHERE $whereClause
            ORDER BY p.stock ASC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

    foreach ($productos as $i => $prod) {
        $stmtMov = $pdo->prepare("SELECT tipo_movimiento, cantidad, descripcion, fecha_movimiento FROM movimientos_inventario WHERE producto_id = ? ORDER BY fecha_movimiento DESC LIMIT 5");
        $stmtMov->execute([$prod['id']]);
        $productos[$i]['ultimos_movimientos'] = $stmtMov->fetchAll();
    }

    apiResponse([
        'success' => true,
        'data' => $productos,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int)ceil($total / $perPage),
        ]
    ]);
} catch (Exception $e) {
    apiError('Error al listar almacenes: ' . $e->getMessage(), 500);
}
