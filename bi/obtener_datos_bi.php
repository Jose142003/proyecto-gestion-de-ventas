<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno del servidor',
            'kpis' => [], 'ventas_por_mes' => [], 'top_productos' => [], 'top_clientes' => [],
            'ventas_por_categoria' => [], 'metodos_pago' => [], 'crecimiento' => [],
            'distribucion_ventas' => [], 'tendencia_clientes' => [], 'stock_por_categoria' => []
        ]);
    }
});

set_error_handler(function () { return false; });

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $response = ['success' => true];

    $response['kpis'] = kpis($pdo);
    $response['ventas_por_mes'] = ventasPorMes($pdo);
    $response['top_productos'] = topProductos($pdo);
    $response['top_clientes'] = topClientes($pdo);
    $response['ventas_por_categoria'] = ventasPorCategoria($pdo);
    $response['metodos_pago'] = metodosPago($pdo);
    $response['crecimiento'] = crecimiento($pdo);
    $response['distribucion_ventas'] = distribucionVentas($pdo);
    $response['tendencia_clientes'] = tendenciaClientes($pdo);
    $response['stock_por_categoria'] = stockPorCategoria($pdo);

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al obtener datos BI',
        'kpis' => [], 'ventas_por_mes' => [], 'top_productos' => [], 'top_clientes' => [],
        'ventas_por_categoria' => [], 'metodos_pago' => [], 'crecimiento' => [],
        'distribucion_ventas' => [], 'tendencia_clientes' => [], 'stock_por_categoria' => []
    ]);
}

function q($pdo, string $sql) {
    try { return $pdo->query($sql)->fetchAll(); } catch (Throwable $e) { return []; }
}
function q1($pdo, string $sql) {
    try { $r = $pdo->query($sql); return $r ? $r->fetchColumn() : 0; } catch (Throwable $e) { return 0; }
}
function tablaExiste($pdo, string $tabla): bool {
    try { return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla))->fetch(); } catch (Throwable $e) { return false; }
}

function kpis($pdo) {
    $r = [];
    $r['ventas_hoy'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(fecha_emision) = CURDATE()");
    $r['ventas_mes'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE)");
    $r['ventas_totales'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas");
    $r['pedidos_pendientes'] = (int)q1($pdo, "SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'");
    $r['clientes_activos'] = (int)q1($pdo, "SELECT COUNT(*) FROM clientes WHERE estado = 'activo'");
    $r['stock_bajo'] = (int)q1($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 5 AND stock > 0");
    $r['agotados'] = (int)q1($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 0 AND active = 1");
    $r['ticket_promedio'] = (float)q1($pdo, "SELECT COALESCE(ROUND(AVG(total), 2), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE)");
    $r['tasa_conversion'] = (float)q1($pdo, "SELECT COUNT(*) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE)");
    $ventasMes = $r['ventas_mes'];
    $ventasMesAnt = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH))");
    $r['crecimiento'] = $ventasMesAnt > 0 ? round((($ventasMes - $ventasMesAnt) / $ventasMesAnt) * 100, 1) : 0;
    return $r;
}

function ventasPorMes($pdo) {
    return q($pdo, "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, DATE_FORMAT(fecha_emision, '%M %Y') as mes_nombre, COUNT(*) as total_facturas, ROUND(SUM(total), 2) as total_ventas, ROUND(AVG(total), 2) as ticket_promedio FROM facturas WHERE fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m') ORDER BY mes ASC");
}

function topProductos($pdo) {
    if (!tablaExiste($pdo, 'pedido_detalles')) return [];
    return q($pdo, "SELECT p.id, p.name, p.sku, p.category, p.stock, SUM(pd.cantidad) as total_vendido, COUNT(DISTINCT pd.pedido_id) as veces_comprado, ROUND(SUM(pd.subtotal), 2) as ingresos_totales FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pe.estado NOT IN ('cancelado') GROUP BY pd.producto_id ORDER BY SUM(pd.cantidad) DESC LIMIT 10");
}

function topClientes($pdo) {
    return q($pdo, "SELECT c.id, c.nombre, c.email, c.telefono, COUNT(DISTINCT pe.id) as total_compras, ROUND(COALESCE(SUM(pe.total), 0), 2) as monto_total, MAX(pe.fecha_pedido) as ultima_compra FROM clientes c LEFT JOIN pedidos pe ON c.id = pe.cliente_id AND pe.estado NOT IN ('cancelado') GROUP BY c.id HAVING total_compras > 0 ORDER BY monto_total DESC LIMIT 10");
}

function ventasPorCategoria($pdo) {
    if (!tablaExiste($pdo, 'pedido_detalles')) return [];
    return q($pdo, "SELECT p.category, COUNT(DISTINCT pd.pedido_id) as total_pedidos, SUM(pd.cantidad) as total_unidades, ROUND(SUM(pd.subtotal), 2) as ingresos, ROUND(AVG(pd.precio_unitario), 2) as precio_promedio FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pe.estado NOT IN ('cancelado') GROUP BY p.category ORDER BY ingresos DESC");
}

function metodosPago($pdo) {
    return q($pdo, "SELECT COALESCE(NULLIF(metodo_pago, ''), 'No especificado') as metodo_pago, COUNT(*) as total, ROUND(SUM(total), 2) as monto FROM facturas WHERE estado != 'anulada' GROUP BY metodo_pago ORDER BY monto DESC");
}

function crecimiento($pdo) {
    $filas = q($pdo, "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, ROUND(SUM(total), 2) as ventas FROM facturas WHERE fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m') ORDER BY mes ASC");
    $result = [];
    foreach ($filas as $i => $f) {
        $anterior = $i > 0 ? $filas[$i - 1]['ventas'] : 0;
        $crec = $anterior > 0 ? round((($f['ventas'] - $anterior) / $anterior) * 100, 1) : 0;
        $result[] = ['mes' => $f['mes'], 'ventas' => (float)$f['ventas'], 'ventas_mes_anterior' => (float)$anterior, 'crecimiento' => $crec];
    }
    return $result;
}

function distribucionVentas($pdo) {
    return [
        'matutino' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 6 AND 12"),
        'vespertino' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 12 AND 18"),
        'nocturno' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE (HOUR(fecha_emision) BETWEEN 18 AND 23) OR (HOUR(fecha_emision) BETWEEN 0 AND 6)")
    ];
}

function tendenciaClientes($pdo) {
    return q($pdo, "SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as nuevos_clientes FROM clientes WHERE fecha_registro >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m') ORDER BY mes ASC");
}

function stockPorCategoria($pdo) {
    return q($pdo, "SELECT category, COUNT(*) as total_productos, SUM(stock) as stock_total, ROUND(AVG(stock), 1) as stock_promedio, SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as stock_critico, SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados FROM products WHERE active = 1 AND deleted_at IS NULL GROUP BY category ORDER BY stock_total DESC");
}
