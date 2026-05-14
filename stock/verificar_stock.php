<?php
// verificar_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['producto_id']) || !isset($input['cantidad'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos. Se requiere producto_id y cantidad.'
        ]);
        exit();
    }
    
    $producto_id = intval($input['producto_id']);
    $cantidad = intval($input['cantidad']);
    
    // Verificar stock disponible
    $sql = "SELECT id, nombre, precio, stock FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
    } else {
        $producto = $result->fetch_assoc();
        $stock_actual = $producto['stock'];
        
        if ($stock_actual >= $cantidad) {
            echo json_encode([
                'success' => true,
                'disponible' => true,
                'stock_actual' => $stock_actual,
                'nuevo_stock' => $stock_actual - $cantidad,
                'producto' => [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'disponible' => false,
                'stock_actual' => $stock_actual,
                'mensaje' => "Stock insuficiente. Disponible: $stock_actual unidades"
            ]);
        }
    }
    
    $stmt->close();
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Para obtener información de stock de un producto específico
    $producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($producto_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de producto inválido'
        ]);
        exit();
    }
    
    $sql = "SELECT id, nombre, stock FROM productos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Producto no encontrado'
        ]);
    } else {
        $producto = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'producto' => $producto
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}

$conn->close();
?>