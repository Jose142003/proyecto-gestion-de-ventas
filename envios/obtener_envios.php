<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

try {
    $pdo = conectarDB();

    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $transportista = isset($_GET['transportista']) ? trim($_GET['transportista']) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($estado !== '' && $estado !== 'todos') {
        $where[] = "e.estado = ?";
        $params[] = $estado;
    }

    if ($transportista !== '') {
        $where[] = "e.transportista LIKE ?";
        $params[] = "%$transportista%";
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $countSql = "SELECT COUNT(*) as total FROM envios e $whereClause";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);

    $sql = "SELECT e.*, p.numero_pedido, u.nombre as cliente_nombre, u.correo as cliente_email
            FROM envios e
            LEFT JOIN pedidos p ON e.pedido_id = p.id
            LEFT JOIN users u ON p.usuario_id = u.id
            $whereClause
            ORDER BY e.created_at DESC
            LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse([
        'success' => true,
        'envios' => $envios,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit)
    ]);

} catch (PDOException $e) {
    error_log("Error en obtener_envios: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
