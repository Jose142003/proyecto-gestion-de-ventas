<?php
// acciones.php - Manejar acciones de facturación
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();
verificarCSRF();

try {
    $pdo = conectarDB();
    
    // Obtener acción
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $facturaId = $_GET['id'] ?? $_POST['id'] ?? 0;
    
    if (empty($action) || $facturaId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
        exit;
    }
    
    switch ($action) {
        case 'marcar_pagada':
            marcarComoPagada($pdo, $facturaId, $_SESSION['user_id']);
            break;
            
        case 'anular':
            anularFactura($pdo, $facturaId, $_SESSION['user_id']);
            break;
            
        case 'generar_factura':
            generarFactura($pdo, $facturaId, $_SESSION['user_id']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}

// Función para marcar factura como pagada
function marcarComoPagada($pdo, $facturaId, $usuarioId) {
    // Verificar que la factura existe y está pendiente
    $stmt = $pdo->prepare("SELECT estado FROM facturas WHERE id = ?");
    $stmt->execute([$facturaId]);
    $factura = $stmt->fetch();
    
    if (!$factura) {
        throw new Exception("Factura no encontrada");
    }
    
    if ($factura['estado'] !== 'pendiente') {
        throw new Exception("La factura ya está " . $factura['estado']);
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Actualizar estado de la factura
        $stmt = $pdo->prepare("UPDATE facturas SET estado = 'pagada' WHERE id = ?");
        $stmt->execute([$facturaId]);
        
        // Registrar movimiento en inventario (si aplica)
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion, referencia, usuario_id)
            SELECT fd.producto_id, 'salida', fd.cantidad, 
                   CONCAT('Confirmación pago factura ', f.numero_factura), 
                   f.numero_factura, ?
            FROM factura_detalles fd
            JOIN facturas f ON fd.factura_id = f.id
            WHERE fd.factura_id = ?
        ");
        $stmt->execute([$usuarioId, $facturaId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Factura marcada como pagada exitosamente',
            'factura_id' => $facturaId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Función para anular factura
function anularFactura($pdo, $facturaId, $usuarioId) {
    // Verificar que la factura existe
    $stmt = $pdo->prepare("SELECT estado, numero_factura FROM facturas WHERE id = ?");
    $stmt->execute([$facturaId]);
    $factura = $stmt->fetch();
    
    if (!$factura) {
        throw new Exception("Factura no encontrada");
    }
    
    if ($factura['estado'] === 'anulada') {
        throw new Exception("La factura ya está anulada");
    }
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    try {
        // Actualizar estado de la factura
        $stmt = $pdo->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ?");
        $stmt->execute([$facturaId]);
        
        // Devolver stock al inventario
        $stmt = $pdo->prepare("
            UPDATE products p
            JOIN factura_detalles fd ON p.id = fd.producto_id
            SET p.stock = p.stock + fd.cantidad
            WHERE fd.factura_id = ?
        ");
        $stmt->execute([$facturaId]);
        
        // Registrar movimiento de devolución en inventario
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion, referencia, usuario_id)
            SELECT fd.producto_id, 'devolucion', fd.cantidad, 
                   CONCAT('Anulación factura ', ?), 
                   ?, ?
            FROM factura_detalles fd
            WHERE fd.factura_id = ?
        ");
        $stmt->execute([$factura['numero_factura'], $factura['numero_factura'], $usuarioId, $facturaId]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Factura anulada exitosamente',
            'factura_id' => $facturaId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Función para generar factura desde pedido
function generarFactura($pdo, $pedidoId, $usuarioId) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->execute([$pedidoId]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        throw new Exception("Pedido no encontrado");
    }

    $stmtCheck = $pdo->prepare("SELECT id FROM facturas WHERE pedido_id = ?");
    $stmtCheck->execute([$pedidoId]);
    if ($stmtCheck->fetch()) {
        throw new Exception("El pedido ya tiene una factura asociada");
    }

    $pdo->beginTransaction();
    try {
        $anio = date('Y');
        try {
            $stmtSeq = $pdo->prepare("SELECT siguiente_valor FROM secuencias_facturacion WHERE tipo = 'factura' AND anio = ? FOR UPDATE");
            $stmtSeq->execute([$anio]);
            $seq = $stmtSeq->fetch();
        } catch (PDOException $e) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS secuencias_facturacion (id INT AUTO_INCREMENT PRIMARY KEY, tipo VARCHAR(50), prefijo VARCHAR(10), siguiente_valor INT, anio INT, UNIQUE KEY unique_tipo_anio (tipo, anio))");
            $stmtSeq = $pdo->prepare("SELECT siguiente_valor FROM secuencias_facturacion WHERE tipo = 'factura' AND anio = ? FOR UPDATE");
            $stmtSeq->execute([$anio]);
            $seq = $stmtSeq->fetch();
        }

        if ($seq) {
            $numValor = $seq['siguiente_valor'];
            $pdo->prepare("UPDATE secuencias_facturacion SET siguiente_valor = ? WHERE tipo = 'factura' AND anio = ?")->execute([$numValor + 1, $anio]);
        } else {
            $numValor = 1;
            $pdo->prepare("INSERT INTO secuencias_facturacion (tipo, prefijo, siguiente_valor, anio) VALUES ('factura', 'FAC', 2, ?)")->execute([$anio]);
        }

        $numeroFactura = 'FAC-' . $anio . '-' . str_pad($numValor, 6, '0', STR_PAD_LEFT);

        $stmtIns = $pdo->prepare("INSERT INTO facturas (pedido_id, usuario_id, numero_factura, subtotal, iva, total, metodo_pago, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW())");
        $stmtIns->execute([$pedidoId, $pedido['usuario_id'], $numeroFactura, $pedido['subtotal'], $pedido['iva'], $pedido['total'], $pedido['metodo_pago']]);
        $facturaId = $pdo->lastInsertId();

        $stmtDet = $pdo->prepare("SELECT * FROM pedido_detalles WHERE pedido_id = ?");
        $stmtDet->execute([$pedidoId]);
        $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        $stmtInsDet = $pdo->prepare("INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal, producto_nombre) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($detalles as $d) {
            $stmtInsDet->execute([$facturaId, $d['producto_id'], $d['cantidad'], $d['precio_unitario'], $d['subtotal'], $d['producto_nombre']]);
        }

        $pdo->prepare("UPDATE pedidos SET estado = 'facturado' WHERE id = ?")->execute([$pedidoId]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "Factura $numeroFactura generada exitosamente",
            'factura_id' => $facturaId,
            'numero_factura' => $numeroFactura
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
?>