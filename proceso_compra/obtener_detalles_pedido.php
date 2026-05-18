<?php
// /proyecto/proceso_compra/obtener_detalles_pedido.php
header('Content-Type: application/json');
session_start();
require_once '../conexion/conexion.php';

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado'
        ]);
        exit;
    }
    
    // Obtener ID del pedido (acepta tanto 'id' como 'pedido_id')
    $pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 
                 (isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0);
    
    if ($pedido_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de pedido no válido'
        ]);
        exit;
    }
    
    $pdo = conectarDB();
    
    // Obtener información del pedido (SIN la columna usuario_procesa_id que no existe)
    $query_pedido = "
        SELECT 
            p.*,
            COALESCE(u.nombre, CONCAT('Cliente #', p.usuario_id), 'Cliente') as cliente_nombre,
            u.correo as cliente_email,
            u.telefono as cliente_telefono,
            u.cedula as cliente_cedula,
            u.direccion as cliente_direccion,
            DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i:%s') as fecha_creacion_formateada,
            (SELECT numero_factura FROM facturas WHERE facturas.pedido_id = p.id LIMIT 1) as numero_factura,
            (SELECT id FROM facturas WHERE facturas.pedido_id = p.id LIMIT 1) as factura_id
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE p.id = :pedido_id
    ";
    
    $stmt = $pdo->prepare($query_pedido);
    $stmt->execute([':pedido_id' => $pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'Pedido no encontrado'
        ]);
        exit;
    }
    
    // Obtener productos del pedido
    $query_productos = "
        SELECT 
            pd.id,
            pd.producto_id,
            pd.cantidad,
            pd.precio_unitario,
            pd.subtotal,
            COALESCE(p.name, pd.producto_nombre, 'Producto') as nombre,
            p.sku,
            p.image_url,
            p.category as categoria,
            p.stock as stock_actual
        FROM pedido_detalles pd
        LEFT JOIN products p ON pd.producto_id = p.id
        WHERE pd.pedido_id = :pedido_id
        ORDER BY pd.id ASC
    ";
    
    $stmt_productos = $pdo->prepare($query_productos);
    $stmt_productos->execute([':pedido_id' => $pedido_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear valores numéricos
    $pedido['total'] = floatval($pedido['total']);
    $pedido['subtotal'] = floatval($pedido['subtotal']);
    $pedido['iva'] = floatval($pedido['iva']);
    
    foreach ($productos as &$prod) {
        $prod['precio_unitario'] = floatval($prod['precio_unitario']);
        $prod['subtotal'] = floatval($prod['subtotal']);
        $prod['stock_actual'] = intval($prod['stock_actual'] ?? 0);
    }
    
    // Detectar pago mixto en observaciones
    if (strpos($pedido['observaciones'] ?? '', 'Pago Mixto') !== false) {
        $pedido['es_pago_mixto'] = true;
        if (preg_match('/Transferencia: Bs\. ([\d,\.]+)/', $pedido['observaciones'], $matches)) {
            $pedido['monto_transferencia'] = floatval(str_replace(',', '', $matches[1]));
        }
        if (preg_match('/Efectivo: Bs\. ([\d,\.]+)/', $pedido['observaciones'], $matches)) {
            $pedido['monto_efectivo'] = floatval(str_replace(',', '', $matches[1]));
        }
    } else {
        $pedido['es_pago_mixto'] = false;
    }
    
    echo json_encode([
        'success' => true,
        'pedido' => $pedido,
        'productos' => $productos,
        'total_productos' => count($productos)
    ]);
    
} catch (PDOException $e) {
    error_log("Error al obtener detalle del pedido: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar el detalle del pedido: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>