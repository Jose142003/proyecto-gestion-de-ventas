<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../conexion/conexion.php';
verificarCSRF();

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}

// Verificar sesión
$usuario_id = null;
if (isset($_SESSION['user_id'])) {
    $usuario_id = $_SESSION['user_id'];
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Generar número de pedido único
    $numero_pedido = $input['numero_pedido'] ?? 'PED-' . date('Ymd') . '-' . random_int(1000, 9999);
    
    // Determinar el estado según el método de pago
    $estado = 'pendiente';
    if ($input['metodo_pago'] === 'efectivo') {
        $estado = 'Esperando pago en efectivo';
    } else if ($input['metodo_pago'] === 'mixto') {
        $estado = 'Pendiente de verificación';
    }
    
    // Para pago mixto, guardar información en observaciones
    $observaciones = $input['observaciones'] ?? '';
    if ($input['metodo_pago'] === 'mixto' && isset($input['monto_transferencia']) && isset($input['monto_efectivo'])) {
        $observaciones = "PAGO MIXTO - Transferencia: Bs. " . number_format($input['monto_transferencia'], 2) . 
                         " | Efectivo: Bs. " . number_format($input['monto_efectivo'], 2) . 
                         " | " . $observaciones;
    }
    
    // Insertar pedido
    $sql = "INSERT INTO pedidos (
        numero_pedido, 
        usuario_id, 
        subtotal,
        iva,
        total,
        metodo_pago,
        estado,
        observaciones,
        created_at
    ) VALUES (
        :numero_pedido,
        :usuario_id,
        :subtotal,
        :iva,
        :total,
        :metodo_pago,
        :estado,
        :observaciones,
        NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->execute([
        ':numero_pedido' => $numero_pedido,
        ':usuario_id' => $usuario_id,
        ':subtotal' => $input['subtotal'] ?? 0,
        ':iva' => $input['iva_total'] ?? 0,
        ':total' => $input['total'] ?? 0,
        ':metodo_pago' => $input['metodo_pago'] ?? 'no_especificado',
        ':estado' => $estado,
        ':observaciones' => $observaciones
    ]);
    
    $pedido_id = $pdo->lastInsertId();
    
    // CORREGIDO: Guardar productos en pedido_detalles (no pedido_productos)
    if (isset($input['productos']) && is_array($input['productos'])) {
        $sql_producto = "INSERT INTO pedido_detalles (
            pedido_id, 
            producto_id, 
            cantidad, 
            precio_unitario,
            precio_original,
            subtotal, 
            producto_nombre,
            producto_sku,
            producto_categoria
        ) VALUES (
            :pedido_id, 
            :producto_id, 
            :cantidad, 
            :precio_unitario,
            :precio_original,
            :subtotal, 
            :producto_nombre,
            :producto_sku,
            :producto_categoria
        )";
        
        $stmt_producto = $pdo->prepare($sql_producto);
        
        foreach ($input['productos'] as $producto) {
            // Obtener SKU y categoría del producto si existe
            $sku = null;
            $categoria = 'General';
            if (!empty($producto['producto_id'])) {
                $stmt_prod_info = $pdo->prepare("SELECT sku, category FROM products WHERE id = ?");
                $stmt_prod_info->execute([$producto['producto_id']]);
                $prod_info = $stmt_prod_info->fetch(PDO::FETCH_ASSOC);
                if ($prod_info) {
                    $sku = $prod_info['sku'];
                    $categoria = $prod_info['category'] ?? 'General';
                }
            }
            
            $stmt_producto->execute([
                ':pedido_id' => $pedido_id,
                ':producto_id' => $producto['producto_id'] ?? null,
                ':cantidad' => $producto['cantidad'] ?? 1,
                ':precio_unitario' => $producto['precio_unitario'] ?? 0,
                ':precio_original' => $producto['precio_unitario'] ?? 0,
                ':subtotal' => ($producto['cantidad'] ?? 1) * ($producto['precio_unitario'] ?? 0),
                ':producto_nombre' => $producto['nombre'] ?? 'Producto',
                ':producto_sku' => $sku,
                ':producto_categoria' => $categoria
            ]);
        }
    }
    
    $pdo->commit();
    
    // Obtener datos del usuario
    $nombre_cliente = 'Cliente';
    $email_cliente = '';
    $telefono_cliente = '';
    if ($usuario_id) {
        $stmt_user = $pdo->prepare("SELECT nombre, correo, telefono FROM users WHERE id = ?");
        $stmt_user->execute([$usuario_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $nombre_cliente = $user['nombre'];
            $email_cliente = $user['correo'] ?? '';
            $telefono_cliente = $user['telefono'] ?? '';
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Pedido registrado correctamente',
        'pedido_id' => $pedido_id,
        'numero_pedido' => $numero_pedido,
        'cliente_nombre' => $nombre_cliente,
        'cliente_email' => $email_cliente,
        'cliente_telefono' => $telefono_cliente
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>