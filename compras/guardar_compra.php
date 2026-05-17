<?php
// /proyecto/compras/guardar_compra.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "carrito_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conn->connect_error]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['proveedor_id']) || !isset($input['productos']) || empty($input['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos o sin productos']);
    exit;
}

$conn->begin_transaction();

try {
    // Generar número de orden único
    $numero_orden = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // 1. Insertar la cabecera de la compra (usando la estructura de tu tabla 'compras')
    $sql = "INSERT INTO compras (numero_orden, proveedor_id, fecha_orden, subtotal, iva, total, observaciones, usuario_creacion_id, estado) 
            VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, 'aprobada')";
    
    $stmt = $conn->prepare($sql);
    $observaciones = $input['observaciones'] ?? '';
    $stmt->bind_param("sidddsi", 
        $numero_orden,
        $input['proveedor_id'], 
        $input['subtotal'], 
        $input['iva'], 
        $input['total'], 
        $observaciones,
        $_SESSION['user_id']
    );
    $stmt->execute();
    $compra_id = $conn->insert_id;
    $stmt->close();
    
    // 2. Insertar detalles de compra y ACTUALIZAR STOCK (usando 'compra_detalles' - singular)
    foreach ($input['productos'] as $producto) {
        // Insertar detalle - usando la tabla correcta 'compra_detalles'
        $sql_detalle = "INSERT INTO compra_detalles (compra_id, producto_id, cantidad, precio_unitario, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmt_detalle = $conn->prepare($sql_detalle);
        $stmt_detalle->bind_param("iiidd", 
            $compra_id, 
            $producto['producto_id'], 
            $producto['cantidad'], 
            $producto['precio_unitario'], 
            $producto['subtotal']
        );
        $stmt_detalle->execute();
        $stmt_detalle->close();
        
        // Obtener stock actual antes de actualizar
        $sql_stock_actual = "SELECT stock FROM products WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_stock_actual);
        $stmt_stock->bind_param("i", $producto['producto_id']);
        $stmt_stock->execute();
        $result_stock = $stmt_stock->get_result();
        $stock_anterior = $result_stock->fetch_assoc()['stock'];
        $stmt_stock->close();
        
        // ACTUALIZAR STOCK (SUMA la cantidad comprada) - usando tabla 'products'
        $sql_update_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_stock);
        $stmt_update->bind_param("ii", $producto['cantidad'], $producto['producto_id']);
        
        if (!$stmt_update->execute()) {
            throw new Exception("Error al actualizar stock del producto ID: " . $producto['producto_id']);
        }
        $stmt_update->close();
        
        // Obtener stock nuevo
        $stock_nuevo = $stock_anterior + $producto['cantidad'];
        
        // Registrar en historial de stock (tipo 'compra')
        $sql_historial = "INSERT INTO historial_stock (producto_id, usuario_id, cantidad, stock_anterior, stock_nuevo, tipo, referencia) 
                          VALUES (?, ?, ?, ?, ?, 'compra', ?)";
        $stmt_historial = $conn->prepare($sql_historial);
        $referencia = $numero_orden;
        $stmt_historial->bind_param("iiiiis", 
            $producto['producto_id'], 
            $_SESSION['user_id'], 
            $producto['cantidad'],
            $stock_anterior,
            $stock_nuevo,
            $referencia
        );
        $stmt_historial->execute();
        $stmt_historial->close();
        
        // También registrar en movimientos_inventario
        $sql_movimiento = "INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion, referencia, usuario_id) 
                          VALUES (?, 'entrada', ?, ?, ?, ?)";
        $stmt_movimiento = $conn->prepare($sql_movimiento);
        $descripcion = "Compra a proveedor - Orden: " . $numero_orden;
        $stmt_movimiento->bind_param("iissi", 
            $producto['producto_id'], 
            $producto['cantidad'],
            $descripcion,
            $referencia,
            $_SESSION['user_id']
        );
        $stmt_movimiento->execute();
        $stmt_movimiento->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Compra registrada y stock actualizado correctamente',
        'compra_id' => $compra_id,
        'numero_orden' => $numero_orden
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => 'Error al guardar la compra: ' . $e->getMessage()
    ]);
}

$conn->close();
?>