<?php
// /proyecto/proceso_compra/obtener_todos_los_pedidos.php
session_start();
header('Content-Type: application/json');
error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado', 'pedidos' => []]);
        exit;
    }
    
    require_once __DIR__ . '/../conexion/conexion.php';
    $pdo = conectarDB();
    
    if (!$pdo) {
        throw new Exception('Error de conexión a la base de datos');
    }
    
    $estado = $_GET['estado'] ?? '';
    $desde = $_GET['desde'] ?? '';
    $hasta = $_GET['hasta'] ?? '';
    $usuario_id = intval($_GET['usuario_id'] ?? 0);
    $metodo_pago = $_GET['metodo_pago'] ?? '';
    
    $query = "
        SELECT 
            p.id,
            p.numero_pedido,
            p.usuario_id,
            COALESCE(u.nombre, CONCAT('Cliente #', p.usuario_id), 'Cliente') as cliente_nombre,
            COALESCE(u.correo, 'No disponible') as cliente_email,
            COALESCE(u.telefono, 'No disponible') as cliente_telefono,
            p.total,
            p.subtotal,
            p.iva,
            p.metodo_pago,
            p.estado,
            p.observaciones,
            p.created_at as fecha,
            (SELECT COUNT(*) FROM facturas WHERE facturas.pedido_id = p.id) as tiene_factura
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($estado && $estado !== 'todos' && $estado !== '') {
        $query .= " AND p.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    // FILTRO POR MÉTODO DE PAGO CORREGIDO
    if ($metodo_pago && $metodo_pago !== 'todos' && $metodo_pago !== '') {
        $metodo_pago_lower = strtolower(trim($metodo_pago));
        
        switch ($metodo_pago_lower) {
            case 'efectivo':
                $query .= " AND (p.metodo_pago = 'efectivo' OR p.metodo_pago = 'Efectivo')";
                break;
            case 'transferencia':
                $query .= " AND (p.metodo_pago = 'transferencia' OR p.metodo_pago = 'Transferencia' OR p.metodo_pago = 'Transferencia Bancaria')";
                break;
            case 'pago_movil':
                $query .= " AND (p.metodo_pago = 'pago_movil' OR p.metodo_pago = 'pago movil' OR p.metodo_pago = 'Pago Móvil')";
                break;
            case 'mixto':
                $query .= " AND (p.metodo_pago = 'mixto' OR p.metodo_pago = 'Mixto' OR p.metodo_pago = 'Pago Mixto')";
                break;
            default:
                $query .= " AND p.metodo_pago = :metodo_pago";
                $params[':metodo_pago'] = $metodo_pago_lower;
                break;
        }
    }
    
    if ($desde) {
        $query .= " AND DATE(p.created_at) >= :desde";
        $params[':desde'] = $desde;
    }
    
    if ($hasta) {
        $query .= " AND DATE(p.created_at) <= :hasta";
        $params[':hasta'] = $hasta;
    }
    
    if ($usuario_id > 0) {
        $query .= " AND p.usuario_id = :usuario_id";
        $params[':usuario_id'] = $usuario_id;
    }
    
    $query .= " ORDER BY p.created_at DESC LIMIT 200";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // FUNCIÓN CORREGIDA para método de pago legible
    function getMetodoPagoLegible($metodo) {
        if (empty($metodo)) return 'No especificado';
        
        $metodoLower = strtolower(trim($metodo));
        
        // Mapeo completo
        $mapa = [
            'efectivo' => 'Efectivo',
            'cash' => 'Efectivo',
            'efectivo (bolívares)' => 'Efectivo',
            'transferencia' => 'Transferencia Bancaria',
            'transferencia bancaria' => 'Transferencia Bancaria',
            'transfer' => 'Transferencia Bancaria',
            'pago_movil' => 'Pago Móvil',
            'pago movil' => 'Pago Móvil',
            'pagomovil' => 'Pago Móvil',
            'pago móvil' => 'Pago Móvil',
            'mixto' => 'Pago Mixto',
            'mixed' => 'Pago Mixto',
            'pago mixto' => 'Pago Mixto',
            'tarjeta' => 'Tarjeta',
            'credito' => 'Tarjeta de Crédito',
            'debito' => 'Tarjeta de Débito'
        ];
        
        // Buscar coincidencia exacta
        if (isset($mapa[$metodoLower])) {
            return $mapa[$metodoLower];
        }
        
        // Buscar por coincidencia parcial
        if (strpos($metodoLower, 'efectivo') !== false) return 'Efectivo';
        if (strpos($metodoLower, 'transferencia') !== false) return 'Transferencia Bancaria';
        if (strpos($metodoLower, 'pago_movil') !== false || strpos($metodoLower, 'pago movil') !== false) return 'Pago Móvil';
        if (strpos($metodoLower, 'mixto') !== false) return 'Pago Mixto';
        if (strpos($metodoLower, 'tarjeta') !== false) return 'Tarjeta';
        
        // Si no se detecta, devolver el método original capitalizado
        return ucfirst($metodoLower);
    }
    
    foreach ($pedidos as &$pedido) {
        $pedido['total'] = floatval($pedido['total'] ?? 0);
        $pedido['subtotal'] = floatval($pedido['subtotal'] ?? 0);
        $pedido['iva'] = floatval($pedido['iva'] ?? 0);
        
        if ($pedido['subtotal'] == 0 && $pedido['total'] > 0) {
            $pedido['subtotal'] = $pedido['total'] / 1.16;
            $pedido['iva'] = $pedido['total'] - $pedido['subtotal'];
        }
        
        // Añadir método de pago legible CORREGIDO
        $pedido['metodo_pago_legible'] = getMetodoPagoLegible($pedido['metodo_pago']);
        
        // DEBUG: Log para ver qué métodos se están detectando
        error_log("Pedido ID {$pedido['id']} - Método original: {$pedido['metodo_pago']} - Método legible: {$pedido['metodo_pago_legible']}");
        
        $query_detalles = "
            SELECT 
                pd.id,
                pd.producto_id,
                pd.cantidad,
                pd.precio_unitario,
                pd.subtotal,
                COALESCE(p.name, pd.producto_nombre, 'Producto') as nombre
            FROM pedido_detalles pd
            LEFT JOIN products p ON pd.producto_id = p.id
            WHERE pd.pedido_id = :pedido_id
        ";
        
        $stmt_detalles = $pdo->prepare($query_detalles);
        $stmt_detalles->execute([':pedido_id' => $pedido['id']]);
        $pedido['productos'] = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos,
        'total' => count($pedidos)
    ]);
    
} catch (PDOException $e) {
    error_log("Error en obtener_todos_los_pedidos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'pedidos' => []
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'pedidos' => []
    ]);
}
?>