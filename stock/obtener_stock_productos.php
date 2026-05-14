<?php
// obtener_stock_productos.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "carrito_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $conn->connect_error
    ]);
    exit();
}

// Obtener todos los productos con stock
$sql = "SELECT id, nombre, precio, descripcion, imagen, categoria, stock FROM productos";
$result = $conn->query($sql);

$productos = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
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
}

echo json_encode([
    'success' => true,
    'products' => $productos,
    'total' => count($productos)
]);

$conn->close();
?>