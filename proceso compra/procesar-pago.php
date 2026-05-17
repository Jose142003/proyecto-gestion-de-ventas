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
        'redirect' => '/proyecto/panel admin/panel_admin.php'
    ]);
    exit;
}

// Verificar que sea un cliente válido (tabla users)
if ($tabla_origen !== 'users') {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión como cliente para comprar',
        'redirect' => '/proyecto/interfaz usuario/login.html'
    ]);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode([
        'success' => false, 
        'message' => 'Debes iniciar sesión',
        'redirect' => '/proyecto/interfaz usuario/login.html'
    ]);
    exit;
}

// Obtener el user_id de la sesión (cliente)
$user_id = $_SESSION['user_id'] ?? 1;

// Resto del código original...
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'carrito_db';

function getDBConnection() {
    global $host, $user, $password, $database;
    $conn = mysqli_connect($host, $user, $password, $database);
    if (!$conn) die(json_encode(['success' => false, 'message' => 'Error de conexión']));
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

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

$conn = getDBConnection();

try {
    mysqli_begin_transaction($conn);

    // Verificar si existe columna referencia_pago
    $check = "SHOW COLUMNS FROM pedidos LIKE 'referencia_pago'";
    $result = mysqli_query($conn, $check);
    $has_referencia = mysqli_num_rows($result) > 0;
    
    if (!$has_referencia) {
        mysqli_query($conn, "ALTER TABLE pedidos ADD COLUMN referencia_pago VARCHAR(100) DEFAULT NULL");
    }
    
    $subtotal = $total / 1.16;
    $iva = $total - $subtotal;
    $observaciones = "Pedido por {$payment_method} - Referencia: {$referencia}";
    
    if ($has_referencia) {
        $query = "INSERT INTO pedidos (usuario_id, numero_pedido, total, subtotal, iva, metodo_pago, estado, observaciones, referencia_pago, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'isdddsss', $user_id, $numero_pedido, $total, $subtotal, $iva, $payment_method, $observaciones, $referencia);
    } else {
        $query = "INSERT INTO pedidos (usuario_id, numero_pedido, total, subtotal, iva, metodo_pago, estado, observaciones, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, NOW())";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'isdddss', $user_id, $numero_pedido, $total, $subtotal, $iva, $payment_method, $observaciones);
    }
    
    mysqli_stmt_execute($stmt);
    $pedido_id = mysqli_insert_id($conn);
    
    // Insertar detalles
    $detalle_query = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, precio_original, subtotal, producto_nombre) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
    $detalle_stmt = mysqli_prepare($conn, $detalle_query);
    
    foreach ($items as $item) {
        $subtotal_item = floatval($item['price']) * intval($item['quantity']);
        mysqli_stmt_bind_param($detalle_stmt, 'iiiddds', 
            $pedido_id, 
            $item['id'], 
            $item['quantity'], 
            $item['price'], 
            $item['price'], 
            $subtotal_item, 
            $item['name']
        );
        mysqli_stmt_execute($detalle_stmt);
    }
    
    // Limpiar carrito
    $clean = "DELETE FROM cart_items WHERE user_id = ?";
    $clean_stmt = mysqli_prepare($conn, $clean);
    mysqli_stmt_bind_param($clean_stmt, 'i', $user_id);
    mysqli_stmt_execute($clean_stmt);
    
    mysqli_commit($conn);
    
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
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    mysqli_close($conn);
}
?>