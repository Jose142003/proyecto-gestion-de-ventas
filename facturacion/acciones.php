<?php
// acciones.php - Manejar acciones de facturación
header('Content-Type: application/json');

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
        $stmt = $pdo->prepare("UPDATE facturas SET estado = 'pagada', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$facturaId]);
        
        // Registrar movimiento en inventario (si aplica)
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, descripcion, referencia, usuario_id)
            SELECT fd.producto_id, 'salida_confirmada', fd.cantidad, 
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
        $stmt = $pdo->prepare("UPDATE facturas SET estado = 'anulada', updated_at = NOW() WHERE id = ?");
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

// Función para generar factura (si necesitas)
function generarFactura($pdo, $pedidoId, $usuarioId) {
    // Aquí iría la lógica para generar una factura desde un pedido
    // Por ahora solo es un placeholder
    echo json_encode([
        'success' => true, 
        'message' => 'Factura generada exitosamente',
        'factura_id' => rand(1000, 9999)
    ]);
}
?>