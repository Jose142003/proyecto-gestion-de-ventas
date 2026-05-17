<?php
// actualizar_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de la base de datos
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
    $usuario_id = isset($input['usuario_id']) ? intval($input['usuario_id']) : null;
    
    $conn->begin_transaction();
    
    try {
        // Verificar stock disponible
        $sql = "SELECT stock, nombre FROM productos WHERE id = ? FOR UPDATE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Producto no encontrado");
        }
        
        $producto = $result->fetch_assoc();
        
        if ($producto['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente. Disponible: " . $producto['stock'] . " unidades");
        }
        
        // Actualizar stock
        $sql_update = "UPDATE productos SET stock = stock - ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ii", $cantidad, $producto_id);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar stock");
        }
        
        // Registrar movimiento en historial si hay usuario
        if ($usuario_id) {
            $sql_historial = "INSERT INTO historial_stock (producto_id, usuario_id, cantidad, tipo, fecha) 
                              VALUES (?, ?, ?, 'venta', NOW())";
            $stmt_historial = $conn->prepare($sql_historial);
            $stmt_historial->bind_param("iii", $producto_id, $usuario_id, $cantidad);
            $stmt_historial->execute();
            $stmt_historial->close();
        }
        
        // Verificar si queda stock bajo después de la venta
        $sql_check = "SELECT stock FROM productos WHERE id = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("i", $producto_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $nuevo_stock = $result_check->fetch_assoc()['stock'];
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stock actualizado correctamente',
            'data' => [
                'producto_id' => $producto_id,
                'nombre' => $producto['nombre'],
                'cantidad_vendida' => $cantidad,
                'stock_anterior' => $producto['stock'],
                'stock_actual' => $nuevo_stock,
                'stock_bajo' => $nuevo_stock < 5,
                'agotado' => $nuevo_stock <= 0
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    $stmt->close();
    if (isset($stmt_update)) $stmt_update->close();
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}

$conn->close();
?>