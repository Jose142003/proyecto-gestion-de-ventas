<?php
// /proyecto/proceso_compra/obtener_todos_los_pedidos.php
session_start();
header('Content-Type: application/json');
error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Parámetros de filtro
    $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
    $desde = isset($_GET['desde']) ? $_GET['desde'] : '';
    $hasta = isset($_GET['hasta']) ? $_GET['hasta'] : '';
    $cliente = isset($_GET['cliente']) ? $_GET['cliente'] : '';
    
    $query = "SELECT 
                p.id,
                p.numero_pedido,
                p.usuario_id,
                p.cliente_id,
                p.subtotal,
                p.iva,
                p.total,
                p.metodo_pago,
                p.estado,
                p.referencia_pago,
                p.observaciones,
                DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i:%s') as created_at,
                u.nombre as cliente_nombre,
                u.correo as cliente_email,
                u.telefono as cliente_telefono,
                (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as total_productos
              FROM pedidos p
              LEFT JOIN users u ON p.usuario_id = u.id
              WHERE 1=1";
    
    $params = [];
    
    if (!empty($estado) && $estado !== 'todos') {
        $query .= " AND p.estado = ?";
        $params[] = $estado;
    }
    
    if (!empty($desde)) {
        $query .= " AND DATE(p.created_at) >= ?";
        $params[] = $desde;
    }
    
    if (!empty($hasta)) {
        $query .= " AND DATE(p.created_at) <= ?";
        $params[] = $hasta;
    }
    
    if (!empty($cliente)) {
        $query .= " AND (u.nombre LIKE ? OR u.correo LIKE ?)";
        $like = "%{$cliente}%";
        $params[] = $like;
        $params[] = $like;
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    $pedidos = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Obtener productos del pedido
        $detalle_query = "SELECT 
                            pd.producto_id,
                            pd.cantidad,
                            pd.precio_unitario,
                            pd.subtotal,
                            pd.producto_nombre,
                            p.name as nombre_producto
                          FROM pedido_detalles pd
                          LEFT JOIN products p ON pd.producto_id = p.id
                          WHERE pd.pedido_id = ?";
        $detalle_stmt = $pdo->prepare($detalle_query);
        $detalle_stmt->execute([$row['id']]);
        $detalles = $detalle_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $productos = [];
        foreach ($detalles as $detalle) {
            $productos[] = [
                'producto_id' => $detalle['producto_id'],
                'nombre' => $detalle['producto_nombre'] ?: $detalle['nombre_producto'],
                'cantidad' => $detalle['cantidad'],
                'precio_unitario' => floatval($detalle['precio_unitario']),
                'subtotal' => floatval($detalle['subtotal'])
            ];
        }
        
        // Detectar si es pago mixto
        $es_mixto = false;
        $monto_transferencia = 0;
        $monto_efectivo = 0;
        
        if (!empty($row['observaciones']) && strpos($row['observaciones'], 'Pago Mixto') !== false) {
            $es_mixto = true;
            if (preg_match('/Transferencia: Bs\. ([\d,\.]+)/', $row['observaciones'], $matches)) {
                $monto_transferencia = floatval(str_replace(',', '', $matches[1]));
            }
            if (preg_match('/Efectivo: Bs\. ([\d,\.]+)/', $row['observaciones'], $matches)) {
                $monto_efectivo = floatval(str_replace(',', '', $matches[1]));
            }
        }
        
        $pedidos[] = [
            'id' => $row['id'],
            'numero_pedido' => $row['numero_pedido'],
            'usuario_id' => $row['usuario_id'],
            'cliente_nombre' => $row['cliente_nombre'] ?? 'Cliente no registrado',
            'cliente_email' => $row['cliente_email'] ?? 'N/A',
            'cliente_telefono' => $row['cliente_telefono'] ?? 'N/A',
            'subtotal' => floatval($row['subtotal'] ?? 0),
            'iva' => floatval($row['iva'] ?? 0),
            'total' => floatval($row['total'] ?? 0),
            'metodo_pago' => $row['metodo_pago'] ?? 'pendiente',
            'estado' => $row['estado'] ?? 'pendiente',
            'referencia_pago' => $row['referencia_pago'] ?? '',
            'observaciones' => $row['observaciones'] ?? '',
            'fecha' => $row['created_at'],
            'total_productos' => intval($row['total_productos'] ?? 0),
            'productos' => $productos,
            'es_pago_mixto' => $es_mixto,
            'monto_transferencia' => $monto_transferencia,
            'monto_efectivo' => $monto_efectivo
        ];
    }
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'total' => count($pedidos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>