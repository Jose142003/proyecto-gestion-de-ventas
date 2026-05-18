<?php
// proceso_compra/procesar-pago.php

header('Content-Type: application/json');
session_start();

// ========== VERIFICACIÓN CRÍTICA: SOLO CLIENTES PUEDEN COMPRAR ==========
$tabla_origen = $_SESSION['tabla_origen'] ?? null;
$es_admin = $_SESSION['es_admin'] ?? false;

// Verificar si es administrador (NO puede comprar)
if ($tabla_origen === 'admin_users' || $es_admin === true) {
    echo json_encode([
        'success' => false, 
        'message' => 'Los administradores no pueden realizar compras. Inicia sesión como cliente.',
        'redirect' => '/proyecto/panel_admin/panel_admin.php'
    ]);
    exit;
}

// Verificar que sea un cliente válido (tabla users)
if ($tabla_origen !== 'users') {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión como cliente para comprar',
        'redirect' => '/proyecto/interfaz_usuario/login.html'
    ]);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión',
        'redirect' => '/proyecto/interfaz_usuario/login.html'
    ]);
    exit;
}

// Obtener el user_id de la sesión (cliente)
$user_id = $_SESSION['user_id'] ?? 1;

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

$pdo = conectarDB();

try {
    $pdo->beginTransaction();

    // Verificar si existe columna referencia_pago
    $check = "SHOW COLUMNS FROM pedidos LIKE 'referencia_pago'";
    $result = $pdo->query($check);
    $has_referencia = $result->rowCount() > 0;
    
    if (!$has_referencia) {
        $pdo->exec("ALTER TABLE pedidos ADD COLUMN referencia_pago VARCHAR(100) DEFAULT NULL");
    }
    
    $subtotal = $total / 1.16;
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>