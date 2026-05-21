<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $periodo = $_GET['periodo'] ?? 'mensual';
    $response = ['success' => true];

    $response['kpis'] = obtenerKPIs($pdo);
    $response['ventas_por_mes'] = obtenerVentasPorMes($pdo);
    $response['top_productos'] = obtenerTopProductos($pdo);
    $response['top_clientes'] = obtenerTopClientes($pdo);
    $response['ventas_por_categoria'] = obtenerVentasPorCategoria($pdo);
    $response['metodos_pago'] = obtenerMetodosPago($pdo);
    $response['crecimiento'] = obtenerCrecimiento($pdo);
    $response['distribucion_ventas'] = obtenerDistribucionVentas($pdo);
    $response['tendencia_clientes'] = obtenerTendenciaClientes($pdo);
    $response['stock_por_categoria'] = obtenerStockPorCategoria($pdo);

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos BI']);
}

function obtenerKPIs($pdo): array {
    $ventasHoy = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(fecha_emision) = CURDATE()")->fetchColumn();
    $ventasMes = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE)")->fetchColumn();
    $ventasTotales = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas")->fetchColumn();
    $pedidosPendientes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn();
    $clientesActivos = $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'activo'")->fetchColumn();
    $productosStockBajo = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND stock > 0")->fetchColumn();
    $productosAgotados = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 0 AND active = 1")->fetchColumn();
    $ticketPromedio = $pdo->query("SELECT COALESCE(ROUND(AVG(total), 2), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE)")->fetchColumn();

    $totalPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE)")->fetchColumn();
    $tasaConversion = $totalPedidos > 0 ? round(($totalPedidos / 100) * 100, 1) : 0;

    $ventasMesAnterior = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))")->fetchColumn();
    $crecimiento = $ventasMesAnterior > 0 ? round((($ventasMes - $ventasMesAnterior) / $ventasMesAnterior) * 100, 1) : 0;

    return [
        'ventas_hoy' => (float)$ventasHoy,
        'ventas_mes' => (float)$ventasMes,
        'ventas_totales' => (float)$ventasTotales,
        'pedidos_pendientes' => (int)$pedidosPendientes,
        'clientes_activos' => (int)$clientesActivos,
        'stock_bajo' => (int)$productosStockBajo,
        'agotados' => (int)$productosAgotados,
        'ticket_promedio' => (float)$ticketPromedio,
        'tasa_conversion' => (float)$tasaConversion,
        'crecimiento' => (float)$crecimiento
    ];
}

function obtenerVentasPorMes($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
            DATE_FORMAT(fecha_emision, '%M %Y') as mes_nombre,
            COUNT(*) as total_facturas,
            ROUND(SUM(total), 2) as total_ventas,
            ROUND(AVG(total), 2) as ticket_promedio
        FROM facturas 
        WHERE fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
        ORDER BY mes ASC
    ");
    return $stmt->fetchAll();
}

function obtenerTopProductos($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            p.id, p.name, p.sku, p.category, p.stock,
            SUM(pd.cantidad) as total_vendido,
            COUNT(DISTINCT pd.pedido_id) as veces_comprado,
            ROUND(SUM(pd.subtotal), 2) as ingresos_totales
        FROM pedido_detalles pd
        JOIN products p ON pd.producto_id = p.id
        JOIN pedidos pe ON pd.pedido_id = pe.id
        WHERE pe.estado NOT IN ('cancelado')
        GROUP BY pd.producto_id
        ORDER BY SUM(pd.cantidad) DESC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

function obtenerTopClientes($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            c.id, c.nombre, c.email, c.telefono,
            COUNT(DISTINCT pe.id) as total_compras,
            ROUND(COALESCE(SUM(pe.total), 0), 2) as monto_total,
            MAX(pe.fecha_pedido) as ultima_compra
        FROM clientes c
        LEFT JOIN pedidos pe ON c.id = pe.cliente_id AND pe.estado NOT IN ('cancelado')
        GROUP BY c.id
        HAVING total_compras > 0
        ORDER BY monto_total DESC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

function obtenerVentasPorCategoria($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            p.category,
            COUNT(DISTINCT pd.pedido_id) as total_pedidos,
            SUM(pd.cantidad) as total_unidades,
            ROUND(SUM(pd.subtotal), 2) as ingresos,
            ROUND(AVG(pd.precio_unitario), 2) as precio_promedio
        FROM pedido_detalles pd
        JOIN products p ON pd.producto_id = p.id
        JOIN pedidos pe ON pd.pedido_id = pe.id
        WHERE pe.estado NOT IN ('cancelado')
        GROUP BY p.category
        ORDER BY ingresos DESC
    ");
    return $stmt->fetchAll();
}

function obtenerMetodosPago($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            COALESCE(NULLIF(metodo_pago, ''), 'No especificado') as metodo_pago,
            COUNT(*) as total,
            ROUND(SUM(total), 2) as monto
        FROM facturas
        WHERE estado != 'anulada'
        GROUP BY metodo_pago
        ORDER BY monto DESC
    ");
    return $stmt->fetchAll();
}

function obtenerCrecimiento($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
            ROUND(SUM(total), 2) as ventas,
            LAG(ROUND(SUM(total), 2)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m')) as ventas_mes_anterior,
            CASE 
                WHEN LAG(ROUND(SUM(total), 2)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m')) > 0 
                THEN ROUND(((SUM(total) - LAG(SUM(total)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m'))) / LAG(SUM(total)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m'))) * 100, 1)
                ELSE 0
            END as crecimiento
        FROM facturas
        WHERE fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m')
        ORDER BY mes ASC
    ");
    return $stmt->fetchAll();
}

function obtenerDistribucionVentas($pdo): array {
    return [
        'matutino' => (int)$pdo->query("SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 6 AND 12")->fetchColumn(),
        'vespertino' => (int)$pdo->query("SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 12 AND 18")->fetchColumn(),
        'nocturno' => (int)$pdo->query("SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 18 AND 23 OR HOUR(fecha_emision) BETWEEN 0 AND 6")->fetchColumn()
    ];
}

function obtenerTendenciaClientes($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha_registro, '%Y-%m') as mes,
            COUNT(*) as nuevos_clientes
        FROM clientes
        WHERE fecha_registro >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m')
        ORDER BY mes ASC
    ");
    return $stmt->fetchAll();
}

function obtenerStockPorCategoria($pdo): array {
    $stmt = $pdo->query("
        SELECT 
            category,
            COUNT(*) as total_productos,
            SUM(stock) as stock_total,
            ROUND(AVG(stock), 1) as stock_promedio,
            SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as stock_critico,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados
        FROM products
        WHERE active = 1 AND deleted_at IS NULL
        GROUP BY category
        ORDER BY stock_total DESC
    ");
    return $stmt->fetchAll();
}
