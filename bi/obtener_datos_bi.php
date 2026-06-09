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

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

try {
    $pdo = conectarDB();
    $response = ['success' => true];

    $response['kpis'] = kpis($pdo, $fecha_desde, $fecha_hasta);
    $response['ventas_por_mes'] = ventasPorMes($pdo, $fecha_desde, $fecha_hasta);
    $response['top_productos'] = topProductos($pdo, $fecha_desde, $fecha_hasta);
    $response['top_clientes'] = topClientes($pdo, $fecha_desde, $fecha_hasta);
    $response['ventas_por_categoria'] = ventasPorCategoria($pdo, $fecha_desde, $fecha_hasta);
    $response['metodos_pago'] = metodosPago($pdo, $fecha_desde, $fecha_hasta);
    $response['crecimiento'] = crecimiento($pdo, $fecha_desde, $fecha_hasta);
    $response['distribucion_ventas'] = distribucionVentas($pdo, $fecha_desde, $fecha_hasta);
    $response['tendencia_clientes'] = tendenciaClientes($pdo, $fecha_desde, $fecha_hasta);
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

function whereFecha($pdo, $campo, $fecha_desde, $fecha_hasta) {
    $w = '';
    if ($fecha_desde) $w .= " AND $campo >= " . (is_numeric($fecha_desde) ? $fecha_desde : $pdo->quote($fecha_desde));
    if ($fecha_hasta) $w .= " AND $campo <= " . (is_numeric($fecha_hasta) ? $fecha_hasta : $pdo->quote($fecha_hasta . ' 23:59:59'));
    return $w;
}

function kpis($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $r = [];
    $wf = whereFecha($pdo, 'fecha_emision', $fecha_desde, $fecha_hasta);
    $wp = whereFecha($pdo, 'fecha_pedido', $fecha_desde, $fecha_hasta);

    $r['facturas_hoy'] = (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE DATE(fecha_emision) = CURDATE() AND estado != 'anulada'");
    $r['ventas_hoy'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(fecha_emision) = CURDATE() AND estado != 'anulada'");
    if ($r['ventas_hoy'] == 0) {
        $r['ventas_hoy'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado NOT IN ('cancelado')");
    }
    $r['ventas_mes'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE) AND estado != 'anulada'");
    if ($r['ventas_mes'] == 0) {
        $r['ventas_mes'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE) AND estado NOT IN ('cancelado')");
    }
    $r['ventas_totales'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE estado != 'anulada' $wf");
    if ($r['ventas_totales'] == 0) {
        $r['ventas_totales'] = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado NOT IN ('cancelado') $wp");
    }
    $r['pedidos_pendientes'] = (int)q1($pdo, "SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'");
    $r['clientes_activos'] = (int)q1($pdo, "SELECT COUNT(*) FROM clientes WHERE estado = 'activo'");
    $r['stock_bajo'] = (int)q1($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 5 AND stock > 0");
    $r['agotados'] = (int)q1($pdo, "SELECT COUNT(*) FROM products WHERE stock <= 0 AND active = 1");
    $r['ticket_promedio'] = (float)q1($pdo, "SELECT COALESCE(ROUND(AVG(total), 2), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURRENT_DATE) AND YEAR(fecha_emision) = YEAR(CURRENT_DATE) AND estado != 'anulada'");
    if ($r['ticket_promedio'] == 0) {
        $r['ticket_promedio'] = (float)q1($pdo, "SELECT COALESCE(ROUND(AVG(total), 2), 0) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE) AND estado NOT IN ('cancelado')");
    }
    $r['tasa_conversion'] = (float)q1($pdo, "SELECT COUNT(*) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURRENT_DATE) AND YEAR(fecha_pedido) = YEAR(CURRENT_DATE)");
    $ventasMes = $r['ventas_mes'];
    $ventasMesAnt = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND estado != 'anulada'");
    if ($ventasMesAnt == 0) {
        $ventasMesAnt = (float)q1($pdo, "SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND YEAR(fecha_pedido) = YEAR(DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)) AND estado NOT IN ('cancelado')");
    }
    $r['crecimiento'] = $ventasMesAnt > 0 ? round((($ventasMes - $ventasMesAnt) / $ventasMesAnt) * 100, 1) : 0;
    return $r;
}

function ventasPorMes($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $wf = whereFecha($pdo, 'fecha_emision', $fecha_desde, $fecha_hasta);
    if (!$fecha_desde) {
        $wf = " AND fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)";
    }
    $data = q($pdo, "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, ANY_VALUE(DATE_FORMAT(fecha_emision, '%M %Y')) as mes_nombre, COUNT(*) as total_facturas, ROUND(SUM(total), 2) as total_ventas, ROUND(AVG(total), 2) as ticket_promedio FROM facturas WHERE estado != 'anulada' $wf GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m') ORDER BY mes ASC");
    if (empty($data)) {
        $wp = whereFecha($pdo, 'fecha_pedido', $fecha_desde, $fecha_hasta);
        if (!$fecha_desde) {
            $wp = " AND fecha_pedido >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)";
        }
        $data = q($pdo, "SELECT DATE_FORMAT(fecha_pedido, '%Y-%m') as mes, ANY_VALUE(DATE_FORMAT(fecha_pedido, '%M %Y')) as mes_nombre, COUNT(*) as total_facturas, ROUND(SUM(total), 2) as total_ventas, ROUND(AVG(total), 2) as ticket_promedio FROM pedidos WHERE estado NOT IN ('cancelado') $wp GROUP BY DATE_FORMAT(fecha_pedido, '%Y-%m') ORDER BY mes ASC");
    }
    $meses = [];
    $start = $fecha_desde ? new DateTime($fecha_desde) : (new DateTime())->modify('-12 months');
    $start->modify('first day of this month');
    $end = $fecha_hasta ? new DateTime($fecha_hasta) : new DateTime();
    $end->modify('first day of this month');
    $interval = $start->diff($end);
    $months = $interval->m + ($interval->y * 12);
    for ($i = 0; $i <= $months; $i++) {
        $fecha = clone $start;
        $fecha->modify("+{$i} months");
        $mesKey = $fecha->format('Y-m');
        $meses[$mesKey] = ['mes' => $mesKey, 'mes_nombre' => ucfirst($fecha->format('F Y')), 'total_facturas' => 0, 'total_ventas' => 0, 'ticket_promedio' => 0];
    }
    foreach ($data as $row) {
        if (isset($meses[$row['mes']])) {
            $meses[$row['mes']] = array_merge($meses[$row['mes']], $row);
        }
    }
    return array_values($meses);
}

function topProductos($pdo, $fecha_desde = null, $fecha_hasta = null) {
    if (!tablaExiste($pdo, 'pedido_detalles')) return [];
    $wp = whereFecha($pdo, 'pe.fecha_pedido', $fecha_desde, $fecha_hasta);
    return q($pdo, "SELECT p.id, ANY_VALUE(p.name) as name, ANY_VALUE(p.sku) as sku, ANY_VALUE(p.category) as category, ANY_VALUE(p.stock) as stock, SUM(pd.cantidad) as total_vendido, COUNT(DISTINCT pd.pedido_id) as veces_comprado, ROUND(SUM(pd.subtotal), 2) as ingresos_totales FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pe.estado NOT IN ('cancelado') $wp GROUP BY pd.producto_id ORDER BY SUM(pd.cantidad) DESC LIMIT 10");
}

function topClientes($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $wp = whereFecha($pdo, 'pe.fecha_pedido', $fecha_desde, $fecha_hasta);
    return q($pdo, "SELECT c.id, ANY_VALUE(c.nombre) as nombre, ANY_VALUE(c.email) as email, ANY_VALUE(c.telefono) as telefono, COUNT(DISTINCT pe.id) as total_compras, ROUND(COALESCE(SUM(pe.total), 0), 2) as monto_total, MAX(pe.fecha_pedido) as ultima_compra FROM clientes c LEFT JOIN pedidos pe ON c.id = pe.cliente_id AND pe.estado NOT IN ('cancelado') $wp GROUP BY c.id HAVING total_compras > 0 ORDER BY monto_total DESC LIMIT 10");
}

function ventasPorCategoria($pdo, $fecha_desde = null, $fecha_hasta = null) {
    if (!tablaExiste($pdo, 'pedido_detalles')) return [];
    $wp = whereFecha($pdo, 'pe.fecha_pedido', $fecha_desde, $fecha_hasta);
    return q($pdo, "SELECT p.category, COUNT(DISTINCT pd.pedido_id) as total_pedidos, SUM(pd.cantidad) as total_unidades, ROUND(SUM(pd.subtotal), 2) as ingresos, ROUND(AVG(pd.precio_unitario), 2) as precio_promedio FROM pedido_detalles pd JOIN products p ON pd.producto_id = p.id JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pe.estado NOT IN ('cancelado') $wp GROUP BY p.category ORDER BY ingresos DESC");
}

function metodosPago($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $wf = whereFecha($pdo, 'fecha_emision', $fecha_desde, $fecha_hasta);
    $data = q($pdo, "SELECT COALESCE(NULLIF(metodo_pago, ''), 'No especificado') as metodo_pago, COUNT(*) as total, ROUND(SUM(total), 2) as monto FROM facturas WHERE estado != 'anulada' $wf GROUP BY COALESCE(NULLIF(metodo_pago, ''), 'No especificado') ORDER BY monto DESC");
    if (empty($data)) {
        $wp = whereFecha($pdo, 'fecha_pedido', $fecha_desde, $fecha_hasta);
        $data = q($pdo, "SELECT COALESCE(NULLIF(metodo_pago, ''), 'No especificado') as metodo_pago, COUNT(*) as total, ROUND(SUM(total), 2) as monto FROM pedidos WHERE estado NOT IN ('cancelado') $wp GROUP BY COALESCE(NULLIF(metodo_pago, ''), 'No especificado') ORDER BY monto DESC");
    }
    return $data;
}

function crecimiento($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $wf = whereFecha($pdo, 'fecha_emision', $fecha_desde, $fecha_hasta);
    if (!$fecha_desde) {
        $wf = " AND fecha_emision >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)";
    }
    $filas = q($pdo, "SELECT DATE_FORMAT(fecha_emision, '%Y-%m') as mes, ROUND(SUM(total), 2) as ventas FROM facturas WHERE estado != 'anulada' $wf GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m') ORDER BY mes ASC");
    if (empty($filas)) {
        $wp = whereFecha($pdo, 'fecha_pedido', $fecha_desde, $fecha_hasta);
        if (!$fecha_desde) {
            $wp = " AND fecha_pedido >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)";
        }
        $filas = q($pdo, "SELECT DATE_FORMAT(fecha_pedido, '%Y-%m') as mes, ROUND(SUM(total), 2) as ventas FROM pedidos WHERE estado NOT IN ('cancelado') $wp GROUP BY DATE_FORMAT(fecha_pedido, '%Y-%m') ORDER BY mes ASC");
    }
    $result = [];
    foreach ($filas as $i => $f) {
        $anterior = $i > 0 ? $filas[$i - 1]['ventas'] : 0;
        $crec = $anterior > 0 ? round((($f['ventas'] - $anterior) / $anterior) * 100, 1) : 0;
        $result[] = ['mes' => $f['mes'], 'ventas' => (float)$f['ventas'], 'ventas_mes_anterior' => (float)$anterior, 'crecimiento' => $crec];
    }
    return $result;
}

function distribucionVentas($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $wf = whereFecha($pdo, 'fecha_emision', $fecha_desde, $fecha_hasta);
    return [
        'matutino' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 6 AND 12 $wf"),
        'vespertino' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE HOUR(fecha_emision) BETWEEN 12 AND 18 $wf"),
        'nocturno' => (int)q1($pdo, "SELECT COUNT(*) FROM facturas WHERE (HOUR(fecha_emision) BETWEEN 18 AND 23) OR (HOUR(fecha_emision) BETWEEN 0 AND 6) $wf")
    ];
}

function tendenciaClientes($pdo, $fecha_desde = null, $fecha_hasta = null) {
    $wf = whereFecha($pdo, 'fecha_registro', $fecha_desde, $fecha_hasta);
    if (!$fecha_desde) {
        $wf = " AND fecha_registro >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)";
    }
    $data = q($pdo, "SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as nuevos_clientes FROM clientes WHERE 1=1 $wf GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m') ORDER BY mes ASC");
    $meses = [];
    $start = $fecha_desde ? new DateTime($fecha_desde) : (new DateTime())->modify('-12 months');
    $start->modify('first day of this month');
    $end = $fecha_hasta ? new DateTime($fecha_hasta) : new DateTime();
    $end->modify('first day of this month');
    $interval = $start->diff($end);
    $months = $interval->m + ($interval->y * 12);
    for ($i = 0; $i <= $months; $i++) {
        $fecha = clone $start;
        $fecha->modify("+{$i} months");
        $mesKey = $fecha->format('Y-m');
        $meses[$mesKey] = ['mes' => $mesKey, 'nuevos_clientes' => 0];
    }
    foreach ($data as $row) {
        if (isset($meses[$row['mes']])) {
            $meses[$row['mes']]['nuevos_clientes'] = (int)$row['nuevos_clientes'];
        }
    }
    return array_values($meses);
}

function stockPorCategoria($pdo) {
    return q($pdo, "SELECT category, COUNT(*) as total_productos, SUM(stock) as stock_total, ROUND(AVG(stock), 1) as stock_promedio, SUM(CASE WHEN stock <= 5 THEN 1 ELSE 0 END) as stock_critico, SUM(CASE WHEN stock = 0 THEN 1 ELSE 0 END) as agotados FROM products WHERE active = 1 AND deleted_at IS NULL GROUP BY category ORDER BY stock_total DESC");
}
