<?php
// generar_factura_desde_pedido.php
session_start();

// Verificar si es admin
if (!isset($_SESSION['user_rol']) || $_SESSION['user_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$pedido_id = $data['pedido_id'] ?? 0;

if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit;
}

  require_once __DIR__ . '/../conexion/conexion.php';
  verificarCSRF();

 try {
     $pdo = conectarDB();
     $pdo->exec("SET time_zone = '-04:00'");
     
     // Establecer zona horaria de PHP para fechas generadas con PHP
     date_default_timezone_set('America/Caracas'); // GMT-4
     
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
    $numero_factura = 'FAC-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // 4. Crear factura - usar PHP para fechas con zona horaria correcta
    $usuario_id_factura = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 1;
    
    // Generar fechas con PHP en zona horaria -04:00
    $fecha_emision = date('Y-m-d H:i:s'); // Incluye hora para mayor precisión
    $fecha_vencimiento = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    $insert_factura = "
        INSERT INTO facturas (numero_factura, cliente_id, fecha_emision, fecha_vencimiento, 
                             subtotal, iva, total, metodo_pago, estado, usuario_id, observaciones, pedido_id)
        VALUES (:numero_factura, :cliente_id, :fecha_emision, :fecha_vencimiento,
                :subtotal, :iva, :total, :metodo_pago, :estado, :usuario_id, 
                CONCAT('Factura generada desde pedido: ', :numero_pedido), :pedido_id)
    ";
    
    $subtotal_calc = $pedido['subtotal'] ?? ($pedido['total'] / 1.16);
    $iva_calc = $pedido['iva'] ?? ($pedido['total'] - $subtotal_calc);
    
    $estado_factura = 'pendiente';

    $stmt_factura = $pdo->prepare($insert_factura);
    $stmt_factura->execute([
        ':numero_factura' => $numero_factura,
        ':cliente_id' => $cliente_id,
        ':fecha_emision' => $fecha_emision,
        ':fecha_vencimiento' => $fecha_vencimiento,
        ':subtotal' => $subtotal_calc,
        ':iva' => $iva_calc,
        ':total' => $pedido['total'],
        ':metodo_pago' => $pedido['metodo_pago'],
        ':estado' => $estado_factura,
        ':usuario_id' => $usuario_id_factura,
        ':numero_pedido' => $pedido['numero_pedido'],
        ':pedido_id' => $pedido_id
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
    
    // 8. Actualizar estado del pedido a "facturado" y guardar factura_id
    try {
        $check_col = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'factura_id'");
        if ($check_col->rowCount() > 0) {
            $update_pedido = "UPDATE pedidos SET estado = 'facturado', factura_id = :factura_id WHERE id = :pedido_id";
            $stmt_update = $pdo->prepare($update_pedido);
            $stmt_update->execute([':factura_id' => $factura_id, ':pedido_id' => $pedido_id]);
        } else {
            $update_pedido = "UPDATE pedidos SET estado = 'facturado' WHERE id = :pedido_id";
            $stmt_update = $pdo->prepare($update_pedido);
            $stmt_update->execute([':pedido_id' => $pedido_id]);
        }
    } catch (Exception $e) {
        $update_pedido = "UPDATE pedidos SET estado = 'facturado' WHERE id = :pedido_id";
        $stmt_update = $pdo->prepare($update_pedido);
        $stmt_update->execute([':pedido_id' => $pedido_id]);
    }
    
    // 9. Actualizar estado del pedido
    $update_pedido_estado = "UPDATE pedidos SET fecha_facturacion = NOW() WHERE id = :pedido_id";
    $stmt_upd_ped = $pdo->prepare($update_pedido_estado);
    $stmt_upd_ped->execute([':pedido_id' => $pedido_id]);
    
    $pdo->commit();

    require_once __DIR__ . '/../notificaciones/cola.php';
    colaNotificacionesAgregar('email_factura', $pedido_id, $factura_id);
    colaNotificacionesAgregar('telegram_pedido', $pedido_id, $factura_id);
    colaNotificacionesDispararProcesador();
    
    echo json_encode([
        'success' => true,
        'message' => 'Factura generada exitosamente',
        'numero_factura' => $numero_factura,
        'factura_id' => $factura_id,
        'redirect_url' => '/proyecto/facturacion/ver_factura.php?factura_id=' . $factura_id
    ]);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>