<?php
// proceso_compra/procesar-pago.php

header('Content-Type: application/json');

// Detectar sesión del cliente primero (CLIENTSESSID), sino usar PHPSESSID (admin)
if (isset($_COOKIE['CLIENTSESSID'])) {
    session_name('CLIENTSESSID');
}
session_start();

// ========== VERIFICAR QUE EL USUARIO ESTÉ LOGUEADO ==========
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión para continuar',
        'redirect' => '/proyecto/interfaz_usuario/login.html'
    ]);
    exit;
}

// Obtener datos de sesión
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

require_once __DIR__ . '/../conexion/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// USAR EL user_id DE LA SESIÓN, NO DEL INPUT
$payment_method = $input['payment_method'] ?? '';
$referencia = $input['referencia'] ?? '';
$client_type = $input['client_type'] ?? 'regular';
$items = $input['items'] ?? [];

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

// Calcular total
$total = 0;
foreach ($items as $item) {
    $total += floatval($item['price']) * intval($item['quantity']);
}

// Generar número de pedido
$numero_pedido = 'PED-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

try {
    $pdo = conectarDB();
    $pdo->beginTransaction();

    // Verificar si existe columna referencia_pago
    $check = "SHOW COLUMNS FROM pedidos LIKE 'referencia_pago'";
    $result = $pdo->query($check);
    $has_referencia = $result->rowCount() > 0;
    
    if (!$has_referencia) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN referencia_pago VARCHAR(100) DEFAULT NULL");
    }
    
    $ivaPorcentaje = $pdo->query("SELECT valor FROM configuracion_sistema WHERE clave = 'iva_porcentaje'")->fetchColumn();
    $ivaPorcentaje = $ivaPorcentaje ?: 16;
    $factor = 1 + ($ivaPorcentaje / 100);
    $subtotal = $total / $factor;
    $iva = $total - $subtotal;
    $observaciones = "Pedido por {$payment_method} - Referencia: {$referencia}";
    
    if ($has_referencia) {
        $query = "INSERT INTO pedidos (usuario_id, numero_pedido, total, subtotal, iva, metodo_pago, estado, observaciones, referencia_pago, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $numero_pedido, $total, $subtotal, $iva, $payment_method, $observaciones, $referencia]);
    } else {
        $query = "INSERT INTO pedidos (usuario_id, numero_pedido, total, subtotal, iva, metodo_pago, estado, observaciones, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, NOW())";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id, $numero_pedido, $total, $subtotal, $iva, $payment_method, $observaciones]);
    }
    
    $pedido_id = $pdo->lastInsertId();
    
    // Insertar detalles
    $detalle_query = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, precio_original, subtotal, producto_nombre) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
    $detalle_stmt = $pdo->prepare($detalle_query);
    
    foreach ($items as $item) {
        $subtotal_item = floatval($item['price']) * intval($item['quantity']);
        $detalle_stmt->execute([
            $pedido_id, 
            $item['id'], 
            $item['quantity'], 
            $item['price'], 
            $item['price'], 
            $subtotal_item, 
            $item['name']
        ]);
    }
    
    // ========================================================================
    // CREAR FACTURA AUTOMÁTICAMENTE
    // ========================================================================
    $user_stmt = $pdo->prepare("SELECT nombre, correo, cedula, telefono, direccion FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
    
    $client_stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = ?");
    $client_stmt->execute([$user_data['correo']]);
    $cliente = $client_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cliente) {
        $insert_cliente = $pdo->prepare("INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion, estado) VALUES ('cedula', ?, ?, ?, ?, ?, 'activo')");
        $insert_cliente->execute([
            $user_data['cedula'] ?? '99999999',
            $user_data['nombre'],
            $user_data['correo'],
            $user_data['telefono'] ?? '',
            $user_data['direccion'] ?? ''
        ]);
        $cliente_id = $pdo->lastInsertId();
    } else {
        $cliente_id = $cliente['id'];
    }
    
    $anio = date('Y');
    $fact_num_stmt = $pdo->prepare("SELECT numero_factura FROM facturas WHERE numero_factura LIKE ? ORDER BY id DESC LIMIT 1");
    $fact_num_stmt->execute(["FAC-$anio-%"]);
    $last_fact = $fact_num_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($last_fact) {
        preg_match('/FAC-' . $anio . '-(\d+)/', $last_fact['numero_factura'], $matches);
        $siguiente = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
    } else {
        $siguiente = 1;
    }
    $numero_factura = "FAC-$anio-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
    
    $check_fact = $pdo->prepare("SELECT id FROM facturas WHERE numero_factura = ?");
    $check_fact->execute([$numero_factura]);
    if ($check_fact->fetch()) {
        do {
            $siguiente++;
            $numero_factura = "FAC-$anio-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
            $check_fact->execute([$numero_factura]);
        } while ($check_fact->fetch());
    }
    
    $estado_factura = 'pendiente';
    
    $fact_insert = $pdo->prepare("INSERT INTO facturas (numero_factura, cliente_id, pedido_id, fecha_emision, fecha_vencimiento, subtotal, iva, total, metodo_pago, estado, usuario_id, observaciones) VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), ?, ?, ?, ?, ?, ?, ?)");
    $fact_insert->execute([$numero_factura, $cliente_id, $pedido_id, $subtotal, $iva, $total, $payment_method, $estado_factura, $user_id, $observaciones]);
    $factura_id = $pdo->lastInsertId();
    
    $detalles_fact = $pdo->prepare("INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal) SELECT ?, producto_id, cantidad, precio_unitario, subtotal FROM pedido_detalles WHERE pedido_id = ?");
    $detalles_fact->execute([$factura_id, $pedido_id]);
    
    // Marcar pedido como facturado
    $update_ped = $pdo->prepare("UPDATE pedidos SET estado = 'facturado', fecha_facturacion = NOW() WHERE id = ?");
    $update_ped->execute([$pedido_id]);
    
    // Limpiar carrito
    $clean = "DELETE FROM cart_items WHERE user_id = ?";
    $clean_stmt = $pdo->prepare($clean);
    $clean_stmt->execute([$user_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'pedido_id' => $pedido_id,
        'numero_pedido' => $numero_pedido,
        'total' => $total,
        'subtotal' => $subtotal,
        'iva' => $iva,
        'metodo_pago' => $payment_method,
        'referencia_pago' => $referencia,
        'message' => 'Pedido procesado correctamente'
    ]);
    
    // Enviar factura por correo (después de responder para no ralentizar)
    try {
        if (ob_get_level()) ob_flush();
        flush();
        
        require_once __DIR__ . '/../usuarios/enviar_factura_email.php';
        
        $stmt_f = $pdo->prepare("SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email, c.documento as cliente_documento, c.telefono as cliente_telefono, c.direccion as cliente_direccion, p.metodo_pago as pedido_metodo_pago, p.referencia_pago as pedido_referencia_pago, p.observaciones as pedido_observaciones, a.nombre as vendedor_nombre, a.correo as vendedor_email FROM facturas f LEFT JOIN clientes c ON f.cliente_id = c.id LEFT JOIN admin_users a ON f.usuario_id = a.id LEFT JOIN pedidos p ON f.pedido_id = p.id WHERE f.id = ?");
        $stmt_f->execute([$factura_id]);
        $factura_data = $stmt_f->fetch(PDO::FETCH_ASSOC);
        
        if ($factura_data && !empty($factura_data['cliente_email'])) {
            $stmt_d = $pdo->prepare("SELECT fd.*, p.name as producto_nombre, p.sku FROM factura_detalles fd LEFT JOIN products p ON fd.producto_id = p.id WHERE fd.factura_id = ?");
            $stmt_d->execute([$factura_id]);
            $detalles_data = $stmt_d->fetchAll(PDO::FETCH_ASSOC);
            
            $html = generarHTMLFacturaEmail($factura_data, $detalles_data);
            enviarCorreo($factura_data['cliente_email'], 'Factura Electrónica #' . $factura_data['numero_factura'] . ' - PIC Sistema', $html, 'PIC Sistema de Facturación');
        }
    } catch (Exception $e) {
        error_log("Error enviando factura email: " . $e->getMessage());
    }

    // Notificar nuevo pedido por Telegram
    try {
        require_once __DIR__ . '/../telegram/notificar_pedido.php';
        telegramNotificarPedido($pdo, $pedido_id);
    } catch (Throwable $e) {
        error_log("Error notificando pedido por Telegram: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en procesar-pago.php: " . $e->getMessage() . " | Line: " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>