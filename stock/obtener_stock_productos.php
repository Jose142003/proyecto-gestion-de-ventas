<?php
// obtener_stock_productos.php
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
    // Obtener todos los productos con stock
    $sql = "SELECT id, name as nombre, price as precio, description as descripcion, image_url as imagen, category as categoria, stock FROM products";
    $stmt = $pdo->query($sql);
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
        'total' => count($productos)
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>