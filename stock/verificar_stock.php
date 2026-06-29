<?php
// verificar_stock.php
header('Content-Type: application/json');
$allowedOrigin = defined('CORS_ORIGIN') ? CORS_ORIGIN : 'http://localhost';
header("Access-Control-Allow-Origin: $allowedOrigin");
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
    
    try {
        // Verificar stock disponible
        $sql = "SELECT id, name as nombre, price as precio, stock FROM products WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            echo json_encode([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
        } else {
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
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor'
        ]);
    }
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
    
    try {
        $sql = "SELECT id, name as nombre, stock FROM products WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            echo json_encode([
                'success' => false,
                'message' => 'Producto no encontrado'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'producto' => $producto
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>