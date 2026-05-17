<?php
// generar_factura_desde_pedido.php
session_start();
require_once '../conexion/conexion.php';

// Verificar si es admin
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$pedido_id = $data['pedido_id'] ?? 0;

if (!$pedido_id) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit;
}

try {
    $pdo = conectarDB();
    
    $pdo->beginTransaction();
    
    // 1. Obtener información del pedido
    $query_pedido = "
        SELECT p.*, u.nombre as usuario_nombre, u.correo as usuario_email, 
               u.cedula, u.telefono
        FROM pedidos p
        JOIN users u ON p.usuario_id = u.id
        WHERE p.id = :pedido_id AND p.estado = 'pendiente'
    ";
    
    $stmt_pedido = $pdo->prepare($query_pedido);
    $stmt_pedido->execute([':pedido_id' => $pedido_id]);
    $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        throw new Exception('Pedido no encontrado o ya fue facturado');
    }
    
    // 2. Verificar/crear cliente en tabla clientes
    $query_cliente = "SELECT id FROM clientes WHERE email = :email LIMIT 1";
    $stmt_cliente = $pdo->prepare($query_cliente);
    $stmt_cliente->execute([':email' => $pedido['usuario_email']]);
    $cliente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        // Crear nuevo cliente
        $insert_cliente = "
            INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion, estado)
            VALUES ('cedula', :cedula, :nombre, :email, :telefono, 'Dirección no especificada', 'activo')
        ";
        
        $stmt_insert_cliente = $pdo->prepare($insert_cliente);
        $stmt_insert_cliente->execute([
            ':cedula' => $pedido['cedula'] ?? '00000000',
            ':nombre' => $pedido['usuario_nombre'],
            ':email' => $pedido['usuario_email'],
            ':telefono' => $pedido['telefono'] ?? ''
        ]);
        
        $cliente_id = $pdo->lastInsertId();
    } else {
        $cliente_id = $cliente['id'];
    }
    
    // 3. Generar número de factura
    require_once 'generar_numero_factura.php'; // Asumo que tienes esta función
    
    // Si no tienes generador de números, usa este:
    $numero_factura = 'FAC-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // 4. Crear factura
    $insert_factura = "
        INSERT INTO facturas (numero_factura, cliente_id, fecha_emision, fecha_vencimiento, 
                             subtotal, iva, total, metodo_pago, estado, usuario_id, observaciones)
        VALUES (:numero_factura, :cliente_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                :subtotal, :iva, :total, :metodo_pago, 'pagada', 1, 
                CONCAT('Factura generada desde pedido: ', :numero_pedido))
    ";
    
    $subtotal = $pedido['total'] / 1.16; // Asumiendo 16% de IVA
    $iva = $pedido['total'] - $subtotal;
    
    $stmt_factura = $pdo->prepare($insert_factura);
    $stmt_factura->execute([
        ':numero_factura' => $numero_factura,
        ':cliente_id' => $cliente_id,
        ':subtotal' => $subtotal,
        ':iva' => $iva,
        ':total' => $pedido['total'],
        ':metodo_pago' => $pedido['metodo_pago'],
        ':numero_pedido' => $pedido['numero_pedido']
    ]);
    
    $factura_id = $pdo->lastInsertId();
    
    // 5. Obtener detalles del pedido y agregarlos a la factura
    $query_detalles = "
        SELECT pd.*, pr.name as producto_nombre
        FROM pedido_detalles pd
        JOIN products pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = :pedido_id
    ";
    
    $stmt_detalles = $pdo->prepare($query_detalles);
    $stmt_detalles->execute([':pedido_id' => $pedido_id]);
    $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Insertar detalles de factura
    foreach ($detalles as $detalle) {
        $insert_detalle = "
            INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal)
            VALUES (:factura_id, :producto_id, :cantidad, :precio_unitario, :subtotal)
        ";
        
        $stmt_detalle = $pdo->prepare($insert_detalle);
        $stmt_detalle->execute([
            ':factura_id' => $factura_id,
            ':producto_id' => $detalle['producto_id'],
            ':cantidad' => $detalle['cantidad'],
            ':precio_unitario' => $detalle['precio_unitario'],
            ':subtotal' => $detalle['subtotal']
        ]);
        
        // 7. Actualizar stock de productos
        $update_stock = "UPDATE products SET stock = stock - :cantidad WHERE id = :producto_id";
        $stmt_stock = $pdo->prepare($update_stock);
        $stmt_stock->execute([
            ':cantidad' => $detalle['cantidad'],
            ':producto_id' => $detalle['producto_id']
        ]);
    }
    
    // 8. Actualizar estado del pedido a "facturado"
    $update_pedido = "UPDATE pedidos SET estado = 'facturado' WHERE id = :pedido_id";
    $stmt_update = $pdo->prepare($update_pedido);
    $stmt_update->execute([':pedido_id' => $pedido_id]);
    
    // 9. Registrar pago
    $insert_pago = "
        INSERT INTO pagos (factura_id, monto, metodo_pago, referencia, usuario_id, observaciones)
        VALUES (:factura_id, :monto, :metodo_pago, :referencia, 1, 'Pago registrado automáticamente desde pedido')
    ";
    
    $stmt_pago = $pdo->prepare($insert_pago);
    $stmt_pago->execute([
        ':factura_id' => $factura_id,
        ':monto' => $pedido['total'],
        ':metodo_pago' => $pedido['metodo_pago'],
        ':referencia' => $pedido['numero_pedido']
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Factura generada exitosamente',
        'numero_factura' => $numero_factura,
        'factura_id' => $factura_id,
        'redirect_url' => '/proyecto/facturacion/ver_factura.php?factura_id=' . $factura_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar factura: ' . $e->getMessage()
    ]);
}
?>