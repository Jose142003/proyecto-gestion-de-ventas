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
    
    $pdo->beginTransaction();
    
    try {
        // Verificar stock disponible
        $sql = "SELECT stock, nombre FROM productos WHERE id = ? FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();
        
        if (!$producto) {
            throw new Exception("Producto no encontrado");
        }
        
        if ($producto['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente. Disponible: " . $producto['stock'] . " unidades");
        }
        
        // Actualizar stock
        $sql_update = "UPDATE productos SET stock = stock - ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        
        if (!$stmt_update->execute([$cantidad, $producto_id])) {
            throw new Exception("Error al actualizar stock");
        }
        
        // Registrar movimiento en historial si hay usuario
        if ($usuario_id) {
            $sql_historial = "INSERT INTO historial_stock (producto_id, usuario_id, cantidad, tipo, fecha) 
                              VALUES (?, ?, ?, 'venta', NOW())";
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([$producto_id, $usuario_id, $cantidad]);
        }
        
        // Verificar si queda stock bajo después de la venta
        $sql_check = "SELECT stock FROM productos WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$producto_id]);
        $nuevo_stock = $stmt_check->fetchColumn();
        
        $pdo->commit();
        
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
        $pdo->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>