<?php
// obtener_stock_productos.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once dirname(__DIR__) . '/conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
    exit();
}

// Obtener todos los productos con stock
$sql = "SELECT id, nombre, precio, descripcion, imagen, categoria, stock FROM productos";
$result = $pdo->query($sql);

$productos = [];
while ($row = $result->fetch()) {
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
?>