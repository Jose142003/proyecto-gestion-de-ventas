<?php
// /proyecto/reportes/exportar_reporte_general_excel.php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

require_once '../conexion/conexion.php';

function formatMoney($value) {
    return number_format($value, 2, ',', '.');
}

$db = Database::getConnection();

// Obtener todos los datos - CORREGIDO: usa pedido_detalles
$query = "SELECT 
    p.id as pedido_id,
    p.numero_pedido,
    u.nombre as cliente,
    p.fecha_pedido as fecha,
    p.total,
    p.metodo_pago,
    p.estado,
    (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
FROM pedidos p
JOIN users u ON p.usuario_id = u.id
WHERE p.estado = 'completado' OR p.estado = 'facturado'
ORDER BY p.fecha_pedido DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Productos más vendidos - CORREGIDO: usa pedido_detalles
$query_top = "SELECT ANY_VALUE(p.name) as nombre, SUM(pd.cantidad) as unidades, SUM(pd.subtotal) as ingresos 
              FROM pedido_detalles pd 
              JOIN products p ON pd.producto_id = p.id 
              JOIN pedidos ped ON pd.pedido_id = ped.id 
              WHERE ped.estado = 'completado' OR ped.estado = 'facturado'
              GROUP BY p.id ORDER BY unidades DESC LIMIT 10";
$stmt = $db->prepare($query_top);
$stmt->execute();
$top_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas
$query_stats = "SELECT 
    (SELECT SUM(total) FROM pedidos WHERE estado = 'completado' OR estado = 'facturado') as ventas_totales,
    (SELECT SUM(total) FROM pedidos WHERE (estado = 'completado' OR estado = 'facturado') AND MONTH(fecha_pedido) = MONTH(CURDATE()) AND YEAR(fecha_pedido) = YEAR(CURDATE())) as ventas_mes,
    (SELECT COUNT(DISTINCT usuario_id) FROM pedidos WHERE estado = 'completado' OR estado = 'facturado') as clientes_activos,
    (SELECT AVG(total) FROM pedidos WHERE estado = 'completado' OR estado = 'facturado') as ticket_promedio";
$stmt = $db->prepare($query_stats);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Crear contenido CSV
$filename = "reporte_general_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['REPORTE GENERAL EJECUTIVO']);
fputcsv($output, ['Fecha de generación:', date('d/m/Y H:i:s')]);
fputcsv($output, []);
fputcsv($output, ['ESTADÍSTICAS PRINCIPALES']);
fputcsv($output, ['Ventas Totales', 'Bs. ' . formatMoney($stats['ventas_totales'])]);
fputcsv($output, ['Ventas del Mes', 'Bs. ' . formatMoney($stats['ventas_mes'])]);
fputcsv($output, ['Clientes Activos', $stats['clientes_activos']]);
fputcsv($output, ['Ticket Promedio', 'Bs. ' . formatMoney($stats['ticket_promedio'])]);
fputcsv($output, []);

fputcsv($output, ['TOP 10 PRODUCTOS MÁS VENDIDOS']);
fputcsv($output, ['Producto', 'Unidades Vendidas', 'Ingresos']);
foreach ($top_productos as $p) {
    fputcsv($output, [$p['nombre'], $p['unidades'], 'Bs. ' . formatMoney($p['ingresos'])]);
}
fputcsv($output, []);

fputcsv($output, ['DETALLE DE PEDIDOS']);
fputcsv($output, ['ID Pedido', 'N° Pedido', 'Cliente', 'Fecha', 'Total', 'Método Pago', 'Estado', 'Productos']);
foreach ($pedidos as $pedido) {
    fputcsv($output, [
        $pedido['pedido_id'],
        $pedido['numero_pedido'],
        $pedido['cliente'],
        date('d/m/Y', strtotime($pedido['fecha'])),
        'Bs. ' . formatMoney($pedido['total']),
        $pedido['metodo_pago'],
        $pedido['estado'],
        $pedido['productos']
    ]);
}

fclose($output);
exit();
?>