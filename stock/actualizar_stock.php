<?php
// actualizar_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
    $usuario_id = isset($input['usuario_id']) ? intval($input['usuario_id']) : null;
    
    $pdo->beginTransaction();
    
    try {
        // Verificar stock disponible
        $sql = "SELECT stock, name as nombre FROM products WHERE id = ? FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            throw new PDOException("Producto no encontrado");
        }
        
        if ($producto['stock'] < $cantidad) {
            throw new PDOException("Stock insuficiente");
        }
        
        // Actualizar stock
        $sql_update = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$cantidad, $producto_id]);
        
        // Registrar movimiento en historial si hay usuario
        if ($usuario_id) {
            $stock_nuevo = $producto['stock'] - $cantidad;
            $sql_historial = "INSERT INTO historial_stock (producto_id, usuario_id, cantidad, stock_anterior, stock_nuevo, tipo, fecha) 
                              VALUES (?, ?, ?, ?, ?, 'venta', NOW())";
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([$producto_id, $usuario_id, $cantidad, $producto['stock'], $stock_nuevo]);
        }
        
        // Verificar si queda stock bajo después de la venta
        $sql_check = "SELECT stock FROM products WHERE id = ?";
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