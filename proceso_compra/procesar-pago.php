<?php
// proceso_compra/procesar-pago.php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Detectar sesión del cliente primero (CLIENTSESSID), sino usar PHPSESSID (admin)
if (isset($_COOKIE['CLIENTSESSID'])) {
    session_name('CLIENTSESSID');
}
session_start();

// ========== VERIFICAR QUE EL USUARIO ESTÉ LOGUEADO ==========
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || !isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión para continuar',
        'redirect' => url('/interfaz_usuario/login.html')
    ]);
    exit;
}

// Obtener datos de sesión
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

require_once __DIR__ . '/../conexion/conexion.php';
verificarCSRF();

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

// USAR EL user_id DE LA SESIÓN, NO DEL INPUT
$payment_method = $input['payment_method'] ?? '';
$referencia = $input['referencia'] ?? '';
$client_type = $input['client_type'] ?? 'regular';
$items = $input['items'] ?? [];

if (empty($items)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
    exit;
}

try {
    $pdo = conectarDB();

    // Calcular total desde la BD
    $total = 0;
    foreach ($items as $item) {
        $stmtP = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmtP->execute([intval($item['id'])]);
        $prodP = $stmtP->fetch();
        $dbPrice = $prodP ? floatval($prodP['price']) : 0;
        $total += $dbPrice * intval($item['quantity']);
    }

    // Generar número de pedido único usando UUID
    $numero_pedido = 'PED-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $pdo->beginTransaction();

    // Verificar si existe columna referencia_pago (usando fetch, no rowCount que es poco confiable en PDO)
    $check = "SHOW COLUMNS FROM pedidos LIKE 'referencia_pago'";
    $result = $pdo->query($check);
    $has_referencia = $result && $result->fetch(PDO::FETCH_ASSOC) !== false;
    
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
        $stmtP2 = $pdo->prepare("SELECT price FROM products WHERE id = ?");
        $stmtP2->execute([intval($item['id'])]);
        $prodP2 = $stmtP2->fetch();
        $dbPrice2 = $prodP2 ? floatval($prodP2['price']) : 0;
        $subtotal_item = $dbPrice2 * intval($item['quantity']);
        $detalle_stmt->execute([
            $pedido_id, 
            $item['id'], 
            $item['quantity'], 
            $dbPrice2, 
            $dbPrice2, 
            $subtotal_item, 
            $item['name']
        ]);
        
        // Deduct stock
        $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_stock->execute([intval($item['quantity']), intval($item['id'])]);
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
    // Usar UUID para evitar race conditions en número de factura
    $numero_factura = 'FAC-' . $anio . '-' . strtoupper(bin2hex(random_bytes(4)));
    
    $estado_factura = (in_array($payment_method, ['transferencia', 'pago_movil', 'zelle'])) ? 'pagada' : 'pendiente';
    
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
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Error en procesar-pago.php: " . $e->getMessage() . " | Line: " . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

// Enviar notificaciones en segundo plano (asíncrono mediante cola)
// EJECUTADO FUERA DEL TRY-CATCH PRINCIPAL para que un error en notificaciones
// no sobreescriba la respuesta JSON de éxito ya enviada al cliente
if (isset($pedido_id) && isset($factura_id)) {
    try {
        ignore_user_abort(true);
        if (ob_get_level()) ob_flush();
        flush();
    } catch (Throwable $e) {
        // ignorar errores de flush
    }

    session_write_close();

    require_once __DIR__ . '/../notificaciones/cola.php';

    try {
        colaNotificacionesAgregar('email_factura', $pedido_id, $factura_id);
        colaNotificacionesAgregar('telegram_pedido', $pedido_id, $factura_id);

        if (!empty($user_data['correo'])) {
            colaNotificacionesAgregar('encuesta_satisfaccion', $pedido_id, null, [
                'email' => $user_data['correo'],
                'nombre' => $user_data['nombre'] ?? 'Cliente',
                'numero_factura' => $numero_factura ?? ''
            ]);
        }

        colaNotificacionesDispararProcesador();
    } catch (Throwable $e) {
        error_log("Error en notificaciones POST-pedido: " . $e->getMessage());
    }
}
?>