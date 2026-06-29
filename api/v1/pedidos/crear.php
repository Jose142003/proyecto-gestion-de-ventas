<?php
$input = json_decode(file_get_contents('php://input'), true);

$clienteId = $input['cliente_id'] ?? null;
$productos = $input['productos'] ?? [];
$metodoPago = trim($input['metodo_pago'] ?? '');
$notas = trim($input['notas_cliente'] ?? '');
$direccionEnvio = trim($input['direccion_envio'] ?? '');

if (!$clienteId) {
    apiError('Cliente ID es requerido');
}
if (empty($productos) || !is_array($productos)) {
    apiError('Debe incluir al menos un producto');
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, nombre, documento FROM clientes WHERE id = ? AND estado = 'activo'");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch();
    if (!$cliente) {
        $pdo->rollBack();
        apiError('Cliente no encontrado o inactivo', 404);
    }

    $subtotal = 0;
    $detalles = [];
    foreach ($productos as $item) {
        $prodId = $item['producto_id'] ?? 0;
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));

        $stmt = $pdo->prepare("SELECT id, name, price, stock, sku, category FROM products WHERE id = ? AND active = 1 AND deleted_at IS NULL");
        $stmt->execute([$prodId]);
        $prod = $stmt->fetch();

        if (!$prod) {
            $pdo->rollBack();
            apiError("Producto ID $prodId no encontrado");
        }
        if ($prod['stock'] < $cantidad) {
            $pdo->rollBack();
            apiError("Stock insuficiente para '{$prod['name']}': disponible {$prod['stock']}, solicitado $cantidad");
        }

        $precioUnitario = (float)$prod['price'];
        $lineSubtotal = $precioUnitario * $cantidad;
        $subtotal += $lineSubtotal;

        $detalles[] = [
            'producto_id' => $prodId,
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'precio_original' => $precioUnitario,
            'subtotal' => $lineSubtotal,
            'producto_nombre' => $prod['name'],
            'producto_sku' => $prod['sku'],
            'producto_categoria' => $prod['category'],
        ];
    }

    $ivaPorcentaje = 16;
    $stmt = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = 'iva_porcentaje'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) {
        $ivaPorcentaje = (int)$row['valor'];
    }

    $iva = $subtotal * $ivaPorcentaje / 100;
    $total = $subtotal + $iva;

    $stmt = $pdo->prepare("SELECT siguiente_valor FROM secuencias_facturacion WHERE tipo = 'pedido' AND anio = YEAR(CURDATE()) FOR UPDATE");
    $stmt->execute();
    $seq = $stmt->fetch();

    if ($seq) {
        $siguiente = (int)$seq['siguiente_valor'];
        $pdo->prepare("UPDATE secuencias_facturacion SET siguiente_valor = siguiente_valor + 1 WHERE tipo = 'pedido' AND anio = YEAR(CURDATE())")->execute();
    } else {
        $siguiente = 1;
        $pdo->prepare("INSERT INTO secuencias_facturacion (tipo, prefijo, siguiente_valor, longitud, anio) VALUES ('pedido', 'PED-', 2, 6, YEAR(CURDATE()))")->execute();
    }

    $numeroPedido = 'PED-' . date('Y') . '-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, usuario_id, numero_pedido, subtotal, impuesto, iva, total, estado, metodo_pago, notas_cliente, direccion_envio)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?)");
    $stmt->execute([$clienteId, $_REQUEST['api_user_id'], $numeroPedido, $subtotal, $iva, $iva, $total, $metodoPago, $notas, $direccionEnvio]);
    $pedidoId = $pdo->lastInsertId();

    $stmtDet = $pdo->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, precio_original, subtotal, producto_nombre, producto_sku, producto_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($detalles as $det) {
        $stmtDet->execute([
            $pedidoId, $det['producto_id'], $det['cantidad'], $det['precio_unitario'],
            $det['precio_original'], $det['subtotal'], $det['producto_nombre'],
            $det['producto_sku'], $det['producto_categoria']
        ]);
        $stmtStock->execute([$det['cantidad'], $det['producto_id']]);

        $stmtHist = $pdo->prepare("INSERT INTO historial_stock (producto_id, usuario_id, cantidad, stock_anterior, stock_nuevo, tipo, referencia) VALUES (?, ?, ?, ?, ?, 'venta', ?)");
        $stmtHistOld = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmtHistOld->execute([$det['producto_id']]);
        $stockNew = (int)$stmtHistOld->fetch()['stock'];
        $stmtHist->execute([$det['producto_id'], $_REQUEST['api_user_id'], -$det['cantidad'], $stockNew + $det['cantidad'], $stockNew, $numeroPedido]);
    }

    $pdo->commit();

    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch();

    $stmtDet = $pdo->prepare("SELECT * FROM pedido_detalles WHERE pedido_id = ?");
    $stmtDet->execute([$pedidoId]);
    $pedido['detalles'] = $stmtDet->fetchAll();

    apiResponse(['success' => true, 'message' => 'Pedido creado exitosamente', 'data' => $pedido], 201);
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    apiError('Error al crear pedido: ' . $e->getMessage(), 500);
}
