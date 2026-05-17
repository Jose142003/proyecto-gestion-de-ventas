<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // CORREGIDO: usar 'products' en lugar de 'productos'
    $sqlUsuarios = "SELECT COUNT(*) as total_usuarios FROM users";
    $stmtUsuarios = $pdo->query($sqlUsuarios);
    $totalUsuarios = $stmtUsuarios->fetch(PDO::FETCH_ASSOC);
    
    $sqlProductos = "SELECT COUNT(*) as total_productos FROM products";
    $stmtProductos = $pdo->query($sqlProductos);
    $totalProductos = $stmtProductos->fetch(PDO::FETCH_ASSOC);
    
    $sqlStockBajo = "SELECT COUNT(*) as stock_bajo FROM products WHERE stock <= 10 AND stock > 0";
    $stmtStockBajo = $pdo->query($sqlStockBajo);
    $stockBajo = $stmtStockBajo->fetch(PDO::FETCH_ASSOC);
    
    // Ventas del día (usando facturas)
    $sqlVentasHoy = "SELECT COALESCE(SUM(total), 0) as ventas_hoy FROM facturas WHERE DATE(fecha_emision) = CURDATE()";
    $stmtVentasHoy = $pdo->query($sqlVentasHoy);
    $ventasHoy = $stmtVentasHoy->fetch(PDO::FETCH_ASSOC);
    
    // Total de ventas
    $sqlTotalVentas = "SELECT COALESCE(SUM(total), 0) as total_ventas FROM facturas";
    $stmtTotalVentas = $pdo->query($sqlTotalVentas);
    $totalVentas = $stmtTotalVentas->fetch(PDO::FETCH_ASSOC);
    
    // Total de clientes
    $sqlClientes = "SELECT COUNT(*) as total_clientes FROM clientes WHERE estado = 'activo'";
    $stmtClientes = $pdo->query($sqlClientes);
    $totalClientes = $stmtClientes->fetch(PDO::FETCH_ASSOC);
    
    // Total de pedidos y pendientes
    $sqlPedidos = "SELECT COUNT(*) as total_pedidos, SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pedidos_pendientes FROM pedidos";
    $stmtPedidos = $pdo->query($sqlPedidos);
    $pedidos = $stmtPedidos->fetch(PDO::FETCH_ASSOC);
    
    // Caja del día (si existe la tabla)
    try {
        $sqlCaja = "SELECT COALESCE(SUM(monto), 0) as caja_hoy FROM caja_movimientos WHERE DATE(fecha_movimiento) = CURDATE() AND tipo = 'ingreso'";
        $stmtCaja = $pdo->query($sqlCaja);
        $cajaHoy = $stmtCaja->fetch(PDO::FETCH_ASSOC);
        $cajaValor = $cajaHoy['caja_hoy'];
    } catch(PDOException $e) {
        $cajaValor = 0;
    }
    
    $response = [
        'success' => true,
        'total_usuarios' => (int)($totalUsuarios['total_usuarios'] ?? 0),
        'total_productos' => (int)($totalProductos['total_productos'] ?? 0),
        'total_pedidos' => (int)($pedidos['total_pedidos'] ?? 0),
        'pedidos_pendientes' => (int)($pedidos['pedidos_pendientes'] ?? 0),
        'ventas_hoy' => (float)($ventasHoy['ventas_hoy'] ?? 0),
        'total_ventas' => (float)($totalVentas['total_ventas'] ?? 0),
        'total_clientes' => (int)($totalClientes['total_clientes'] ?? 0),
        'stock_bajo' => (int)($stockBajo['stock_bajo'] ?? 0),
        'caja_hoy' => (float)$cajaValor
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => $e->getMessage(),
        'total_usuarios' => 0,
        'total_productos' => 0,
        'total_pedidos' => 0,
        'pedidos_pendientes' => 0,
        'ventas_hoy' => 0,
        'total_ventas' => 0,
        'total_clientes' => 0,
        'stock_bajo' => 0,
        'caja_hoy' => 0
    ], JSON_UNESCAPED_UNICODE);
}
?>