<?php
try {
    $pdo = Database::getConnection();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $category = $_GET['category'] ?? '';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'id';
    $order = strtoupper($_GET['order'] ?? 'ASC');
    $order = in_array($order, ['ASC', 'DESC']) ? $order : 'ASC';

    $allowedSort = ['id', 'name', 'price', 'stock', 'category', 'created_at', 'rating'];
    $sort = in_array($sort, $allowedSort) ? $sort : 'id';

    $where = ["active = 1 AND deleted_at IS NULL"];
    $params = [];

    if ($category) {
        $where[] = "category = ?";
        $params[] = $category;
    }

    if ($search) {
        $where[] = "(name LIKE ? OR description LIKE ? OR sku LIKE ?)";
        $s = "%$search%";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $whereClause = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) as total FROM products WHERE $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT id, sku, name, price, image_url, description, category, rating, stock, is_featured, weight, dimensions, currency, created_at, updated_at
            FROM products
            WHERE $whereClause
            ORDER BY $sort $order
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll();

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
    apiError('Error al listar productos: ' . $e->getMessage(), 500);
}
