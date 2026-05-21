<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

function q($pdo, string $sql) {
    try { return $pdo->query($sql)->fetchAll(); } catch (PDOException $e) { return []; }
}
function q1($pdo, string $sql) {
    try { $r = $pdo->query($sql); return $r ? $r->fetchColumn() : 0; } catch (PDOException $e) { return 0; }
}

try {
    $pdo = conectarDB();
    $response = ['success' => true];

    $response['kpis'] = [
        'ventas_hoy' => (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(fecha_emision) = CURDATE()"),
        'ventas_mes' => (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE)"),
        'ventas_totales' => (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas"),
        'pedidos_pendientes' => (int)q1($pdo, "SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'"),
        'clientes_activos' => (int)q1($pdo, "SELECT COUNT(*) FROM clientes WHERE estado = 'activo'"),
        'stock_bajo' => (int)q1($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 5 AND stock > 0"),
        'agotados' => (int)q1($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 0 AND active = 1"),
        'ticket_promedio' => (float)q1($pdo, "SELECT COALESCE(ROUND(AVG(total), 2), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE)"),
        'tasa_conversion' => (float)q1($pdo, "SELECT COUNT(*) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE)"),
        'crecimiento' => (float)0
    ];

    $ventasMes = $response['kpis']['ventas_mes'];
    $ventasMesAnt = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))");
    $response['kpis']['crecimiento'] = $ventasMesAnt > 0 ? round((($ventasMes - $ventasMesAnt) / $ventasMesAnt) * 100, 1) : 0;

    $response['ventas_por_mes'] = q($pdo, "
        SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, DATE_FORMAT(fecha_emision, '%M %Y') as mes_nombre,
            COUNT(*) as total_facturas, ROUND(SUM(total), 2) as total_ventas, ROUND(AVG(total), 2) as ticket_promedio
        FROM facturas WHERE fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m') ORDER BY mes ASC
    ");

    $response['top_productos'] = q($pdo, "
        SELECT p.id, p.name, p.sku, p.category, p.stock, SUM(pd.cantidad) as total_vendido,
            COUNT(DISTINCT pd.pedido_id) as veces_comprado, ROUND(SUM(pd.subtotal), 2) as ingresos_totales
        FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id JOIN pedidos pe ON pd.pedido_id = pe.id
        WHERE pe.estado NOT IN ('cancelado') GROUP BY pd.producto_id ORDER BY SUM(pd.cantidad) DESC LIMIT 10
    ");

    $response['top_clientes'] = q($pdo, "
        SELECT c.id, c.nombre, c.email, c.telefono, COUNT(DISTINCT pe.id) as total_compras,
            ROUND(COALESCE(SUM(pe.total), 0), 2) as monto_total, MAX(pe.fecha_pedido) as ultima_compra
        FROM clientes c LEFT JOIN pedidos pe ON c.id = pe.cliente_id AND pe.estado NOT IN ('cancelado')
        GROUP BY c.id HAVING total_compras > 0 ORDER BY monto_total DESC LIMIT 10
    ");

    $response['ventas_por_categoria'] = q($pdo, "
        SELECT p.category, COUNT(DISTINCT pd.pedido_id) as total_pedidos, SUM(pd.cantidad) as total_unidades,
            ROUND(SUM(pd.subtotal), 2) as ingresos, ROUND(AVG(pd.precio_unitario), 2) as precio_promedio
        FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id JOIN pedidos pe ON pd.pedido_id = pe.id
        WHERE pe.estado NOT IN ('cancelado') GROUP BY p.category ORDER BY ingresos DESC
    ");

    $response['metodos_pago'] = q($pdo, "
        SELECT COALESCE(NULLIF(metodo_pago, ''), 'No especificado') as metodo_pago,
            COUNT(*) as total, ROUND(SUM(total), 2) as monto
        FROM facturas WHERE estado != 'anulada' GROUP BY metodo_pago ORDER BY monto DESC
    ");

    $response['crecimiento'] = q($pdo, "
        SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, ROUND(SUM(total), 2) as ventas,
            LAG(ROUND(SUM(total), 2)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m')) as ventas_mes_anterior,
            CASE WHEN LAG(ROUND(SUM(total), 2)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m')) > 0 THEN ROUND(((SUM(total) - LAG(SUM(total)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m'))) / LAG(SUM(total)) OVER (ORDER BY DATE_FORMAT(fecha_emision, '%Y-%m'))) * 100, 1) ELSE 0 END as crecimiento
        FROM facturas WHERE fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m') ORDER BY mes ASC
    ");

    $response['distribucion_ventas'] = [
        'matutino' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 6 AND 12"),
        'vespertino' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 12 AND 18"),
        'nocturno' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE (HOUR(fecha_emision) BETWEEN 18 AND 23) OR (HOUR(fecha_emision) BETWEEN 0 AND 6)")
    ];

    $response['tendencia_clientes'] = q($pdo, "
        SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as nuevos_clientes
        FROM clientes WHERE fecha_registro >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m') ORDER BY mes ASC
    ");

    $response['stock_por_categoria'] = q($pdo, "
        SELECT category, COUNT(*) as total_productos, SUM(stock) as stock_total,
            ROUND(AVG(stock), 1) as stock_promedio,
            SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as stock_critico,
            SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados
        FROM products WHERE active = 1 AND deleted_at IS NULL
        GROUP BY category ORDER BY stock_total DESC
    ");

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos BI',
        'kpis' => [], 'ventas_por_mes' => [], 'top_productos' => [], 'top_clientes' => [],
        'ventas_por_categoria' => [], 'metodos_pago' => [], 'crecimiento' => [],
        'distribucion_ventas' => [], 'tendencia_clientes' => [], 'stock_por_categoria' => []
    ]);
}
