<?php
// generar_factura.php
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
requerirSesion();

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$payment_method = $input['payment_method'] ?? 'transferencia';

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Usuario no especificado']);
    exit();
}

try {
    $pdo->beginTransaction();

    // 1. Obtener items del carrito
    $stmt_cart = $pdo->prepare("
        SELECT ci.*, p.name, p.price, p.stock 
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $stmt_cart->execute([$user_id]);
    $cart_items = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception('El carrito está vacío');
    }

    // 2. Calcular totales
    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $iva = $subtotal * 0.16;
    $total = $subtotal + $iva;

    // 3. Generar número de pedido
    $anio = date('Y');
    
    // Verificar si la tabla secuencias_facturacion existe
    try {
        $stmt_seq = $pdo->prepare("
            SELECT siguiente_valor FROM secuencias_facturacion 
            WHERE tipo = 'pedido' AND anio = ? FOR UPDATE
        ");
        $stmt_seq->execute([$anio]);
        $seq = $stmt_seq->fetch();
    } catch (PDOException $e) {
        // Si la tabla no existe, crearla
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS secuencias_facturacion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(50) NOT NULL,
                prefijo VARCHAR(10),
                siguiente_valor INT NOT NULL,
                anio INT NOT NULL,
                UNIQUE KEY unique_tipo_anio (tipo, anio)
            )
        ");
        
        // Reintentar la consulta
        $stmt_seq = $pdo->prepare("
            SELECT siguiente_valor FROM secuencias_facturacion 
            WHERE tipo = 'pedido' AND anio = ? FOR UPDATE
        ");
        $stmt_seq->execute([$anio]);
        $seq = $stmt_seq->fetch();
    }

    if ($seq) {
        $numero_pedido_valor = $seq['siguiente_valor'];
        $nuevo_valor = $numero_pedido_valor + 1;
        $stmt_update = $pdo->prepare("
            UPDATE secuencias_facturacion SET siguiente_valor = ? 
            WHERE tipo = 'pedido' AND anio = ?
        ");
        $stmt_update->execute([$nuevo_valor, $anio]);
    } else {
        $numero_pedido_valor = 1;
        $stmt_insert = $pdo->prepare("
            INSERT INTO secuencias_facturacion (tipo, prefijo, siguiente_valor, anio) 
            VALUES ('pedido', 'PED', 2, ?)
        ");
        $stmt_insert->execute([$anio]);
    }
    
    $numero_pedido = 'PED-' . $anio . '-' . str_pad($numero_pedido_valor, 6, '0', STR_PAD_LEFT);

    // 4. Verificar si la tabla pedidos existe
    try {
        $pdo->query("SELECT 1 FROM pedidos LIMIT 1");
    } catch (PDOException $e) {
        // Crear tabla pedidos si no existe
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pedidos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                numero_pedido VARCHAR(50) NOT NULL UNIQUE,
                subtotal DECIMAL(10,2) NOT NULL,
                iva DECIMAL(10,2) NOT NULL,
                total DECIMAL(10,2) NOT NULL,
                metodo_pago VARCHAR(50) NOT NULL,
                estado VARCHAR(50) DEFAULT 'pendiente',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_usuario (usuario_id),
                INDEX idx_numero (numero_pedido)
            )
        ");
        
        // Crear tabla pedido_detalles
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS pedido_detalles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                producto_id INT NOT NULL,
                cantidad INT NOT NULL,
                precio_unitario DECIMAL(10,2) NOT NULL,
                precio_original DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                producto_nombre VARCHAR(255) NOT NULL,
                FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
            )
        ");
    }

    // 5. Crear el pedido
    $stmt_pedido = $pdo->prepare("
        INSERT INTO pedidos (
            usuario_id, numero_pedido, subtotal, iva, total, 
            metodo_pago, estado, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())
    ");
    $stmt_pedido->execute([$user_id, $numero_pedido, $subtotal, $iva, $total, $payment_method]);
    $pedido_id = $pdo->lastInsertId();

    // 6. Insertar detalles del pedido
    $stmt_detalle = $pdo->prepare("
        INSERT INTO pedido_detalles (
            pedido_id, producto_id, cantidad, precio_unitario, 
            precio_original, subtotal, producto_nombre
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $subtotal_item = $item['price'] * $item['quantity'];
        
        // Verificar stock
        if ($item['stock'] < $item['quantity']) {
            throw new Exception('Stock insuficiente para ' . $item['name']);
        }
        
        $stmt_detalle->execute([
            $pedido_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item['price'],
            $subtotal_item,
            $item['name']
        ]);

        // Actualizar stock
        $stmt_update_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt_update_stock->execute([$item['quantity'], $item['product_id']]);
    }

    // 7. Vaciar el carrito
    $stmt_clear = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt_clear->execute([$user_id]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido creado exitosamente',
        'pedido_id' => $pedido_id,
        'numero_pedido' => $numero_pedido,
        'total' => $total,
        'subtotal' => $subtotal,
        'iva' => $iva
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error en generar_factura: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>