<?php
// /proyecto/compras/guardar_compra.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';
verificarCSRF();

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['proveedor_id']) || !isset($input['productos']) || empty($input['productos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o sin productos']);
    exit;
}

$pdo->beginTransaction();

try {
    // Generar número de orden único
    $numero_orden = 'ORD-' . date('Ymd') . '-' . random_int(1000, 9999);
    
    // 1. Insertar la cabecera de la compra
    $sql = "INSERT INTO compras (numero_orden, proveedor_id, fecha_orden, subtotal, iva, total, observaciones, usuario_creacion_id, estado) 
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, 'aprobada')";
    
    $stmt = $pdo->prepare($sql);
    $observaciones = $input['observaciones'] ?? '';
    $stmt->execute([
        $numero_orden,
        $input['proveedor_id'], 
        $input['subtotal'], 
        $input['iva'], 
        $input['total'], 
        $observaciones,
        $_SESSION['user_id']
    ]);
    $compra_id = $pdo->lastInsertId();
    
    // 2. Insertar detalles de compra y ACTUALIZAR STOCK
    foreach ($input['productos'] as $producto) {
        // Insertar detalle
        $sql_detalle = "INSERT INTO compra_detalles (compra_id, producto_id, cantidad, precio_unitario, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        $stmt_detalle->execute([
            $compra_id, 
            $producto['producto_id'], 
            $producto['cantidad'], 
            $producto['precio_unitario'], 
            $producto['subtotal']
        ]);
        
        // Obtener stock actual antes de actualizar
        $sql_stock_actual = "SELECT stock FROM products WHERE id = ?";
        $stmt_stock = $pdo->prepare($sql_stock_actual);
        $stmt_stock->execute([$producto['producto_id']]);
        $stock_anterior = $stmt_stock->fetchColumn();
        
        // ACTUALIZAR STOCK (SUMA la cantidad comprada)
        $sql_update_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update_stock);
        $stmt_update->execute([$producto['cantidad'], $producto['producto_id']]);
        
        $stock_nuevo = $stock_anterior + $producto['cantidad'];
        
        // Registrar en historial de stock (tipo 'compra')
        $sql_historial = "INSERT INTO historial_stock (producto_id, usuario_id, cantidad, stock_anterior, stock_nuevo, tipo, referencia) 
                          VALUES (?, ?, ?, ?, ?, 'compra', ?)";
        $stmt_historial = $pdo->prepare($sql_historial);
        $stmt_historial->execute([
            $producto['producto_id'], 
            $_SESSION['user_id'], 
            $producto['cantidad'],
            $stock_anterior,
            $stock_nuevo,
            $numero_orden
        ]);
        
        // También registrar en movimientos_inventario
        $sql_movimiento = "INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion, referencia, usuario_id) 
                          VALUES (?, 'entrada', ?, ?, ?, ?)";
        $stmt_movimiento = $pdo->prepare($sql_movimiento);
        $descripcion = "Compra a proveedor - Orden: " . $numero_orden;
        $stmt_movimiento->execute([
            $producto['producto_id'], 
            $producto['cantidad'],
            $descripcion,
            $numero_orden,
            $_SESSION['user_id']
        ]);
    }
    
    $pdo->commit();
    
    auditoriaRegistrar('guardar_compra', 'compras', "Compra registrada: $numero_orden - Proveedor ID: {$input['proveedor_id']}");
    echo json_encode([
        'success' => true, 
        'message' => 'Compra registrada y stock actualizado correctamente',
        'compra_id' => $compra_id,
        'numero_orden' => $numero_orden
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>