<?php
try {
    $pdo = Database::getConnection();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));
    $search = $_GET['search'] ?? '';
    $estado = $_GET['estado'] ?? '';

    $where = ["1 = 1"];
    $params = [];

    if ($estado) {
        $where[] = "c.estado = ?";
        $params[] = $estado;
    }

    if ($search) {
        $where[] = "(c.nombre LIKE ? OR c.documento LIKE ? OR c.email LIKE ? OR c.telefono LIKE ?)";
        $s = "%$search%";
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
        $params[] = $s;
    }

    $whereClause = implode(' AND ', $where);

    $countSql = "SELECT COUNT(*) as total FROM clientes c WHERE $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];

    $offset = ($page - 1) * $perPage;

    $sql = "SELECT c.id, c.tipo_documento, c.documento, c.nombre, c.email, c.telefono, c.direccion, c.ciudad, c.estado, c.fecha_registro
            FROM clientes c
            WHERE $whereClause
            ORDER BY c.nombre ASC
            LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $clientes = $stmt->fetchAll();

    apiResponse([
        'success' => true,
        'data' => $clientes,
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int)ceil($total / $perPage),
        ]
    ]);
} catch (Exception $e) {
    apiError('Error al listar clientes: ' . $e->getMessage(), 500);
}
