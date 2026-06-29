<?php
// obtener_stock_productos.php
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
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $countStmt = $pdo->query("SELECT COUNT(*) FROM products");
    $total = (int)$countStmt->fetchColumn();

    $sql = "SELECT id, name as nombre, price as precio, description as descripcion, image_url as imagen, category as categoria, stock FROM products LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$limit, $offset]);
    $productos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $productos[] = [
            'id' => $row['id'],
            'name' => $row['nombre'],
            'price' => floatval($row['precio']),
            'description' => $row['descripcion'] ?? '',
            'image' => $row['imagen'] ?? 'https://via.placeholder.com/300x300?text=Sin+imagen',
            'category' => $row['categoria'] ?? 'General',
            'stock' => intval($row['stock'])
        ];
    }

    echo json_encode([
        'success' => true,
        'products' => $productos,
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