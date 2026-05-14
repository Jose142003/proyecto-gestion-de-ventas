<?php
// /proyecto/reportes/exportar_especifico_excel.php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

require_once '../conexion/conexion.php';

function formatMoney($value) {
    return number_format($value, 2, ',', '.');
}

function formatDate($date) {
    if (!$date || $date == '0000-00-00') return 'N/A';
    return date('d/m/Y', strtotime($date));
}

function getEstadoTexto($estado) {
    $estados = [
        'pendiente' => 'Pendiente',
        'completado' => 'Completado',
        'facturado' => 'Facturado',
        'cancelado' => 'Cancelado'
    ];
    return $estados[$estado] ?? $estado;
}

$database = new Database();
$db = $database->getConnection();

$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$tipo = $_GET['tipo'] ?? 'ventas';
$estado = $_GET['estado'] ?? '';
$buscar = $_GET['buscar'] ?? '';

$data = [];
$titulo = '';

switch ($tipo) {
    case 'ventas':
        $titulo = 'Reporte de Ventas';
        $query = "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.fecha_pedido as fecha, p.total, p.estado, p.metodo_pago,
                         (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
                  FROM pedidos p
                  JOIN users u ON p.usuario_id = u.id
                  WHERE 1=1";
        if ($desde) $query .= " AND DATE(p.fecha_pedido) >= '$desde'";
        if ($hasta) $query .= " AND DATE(p.fecha_pedido) <= '$hasta'";
        if ($estado) $query .= " AND p.estado = '$estado'";
        if ($buscar) $query .= " AND (u.nombre LIKE '%$buscar%' OR p.numero_pedido LIKE '%$buscar%')";
        $query .= " ORDER BY p.fecha_pedido DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'compras':
        $titulo = 'Reporte de Compras';
        $query = "SELECT c.id, c.numero_orden, p.nombre_comercial as proveedor, c.fecha_orden as fecha,
                         c.subtotal, c.total, c.estado
                  FROM compras c
                  JOIN proveedores p ON c.proveedor_id = p.id
                  WHERE 1=1";
        if ($desde) $query .= " AND DATE(c.fecha_orden) >= '$desde'";
        if ($hasta) $query .= " AND DATE(c.fecha_orden) <= '$hasta'";
        if ($estado) $query .= " AND c.estado = '$estado'";
        if ($buscar) $query .= " AND (p.nombre_comercial LIKE '%$buscar%' OR c.numero_orden LIKE '%$buscar%')";
        $query .= " ORDER BY c.fecha_orden DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'pedidos':
        $titulo = 'Reporte de Pedidos';
        $query = "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.fecha_pedido as fecha, p.total, p.estado, p.metodo_pago,
                         (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
                  FROM pedidos p
                  JOIN users u ON p.usuario_id = u.id
                  WHERE 1=1";
        if ($desde) $query .= " AND DATE(p.fecha_pedido) >= '$desde'";
        if ($hasta) $query .= " AND DATE(p.fecha_pedido) <= '$hasta'";
        if ($estado) $query .= " AND p.estado = '$estado'";
        if ($buscar) $query .= " AND (u.nombre LIKE '%$buscar%' OR p.numero_pedido LIKE '%$buscar%')";
        $query .= " ORDER BY p.fecha_pedido DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    default:
        $data = [];
}

$total_registros = count($data);
$total_monto = 0;
foreach ($data as $row) {
    if (isset($row['total'])) $total_monto += floatval($row['total']);
}
$promedio = $total_registros > 0 ? $total_monto / $total_registros : 0;

$filename = "reporte_" . $tipo . "_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [strtoupper($titulo)]);
fputcsv($output, ['Fecha de generación:', date('d/m/Y H:i:s')]);
fputcsv($output, ['Período:', $desde ? date('d/m/Y', strtotime($desde)) : 'Todo', 'a', $hasta ? date('d/m/Y', strtotime($hasta)) : 'Todo']);
fputcsv($output, ['Estado:', $estado ? getEstadoTexto($estado) : 'Todos']);
fputcsv($output, ['Búsqueda:', $buscar ?: 'Ninguna']);
fputcsv($output, []);
fputcsv($output, ['RESUMEN']);
fputcsv($output, ['Total Registros', $total_registros]);
fputcsv($output, ['Monto Total', 'Bs. ' . formatMoney($total_monto)]);
fputcsv($output, ['Promedio', 'Bs. ' . formatMoney($promedio)]);
fputcsv($output, []);

if ($tipo == 'ventas' || $tipo == 'pedidos') {
    fputcsv($output, ['DETALLE DE ' . strtoupper($tipo)]);
    fputcsv($output, ['ID', 'N° Pedido', 'Cliente', 'Fecha', 'Total', 'Estado', 'Método Pago', 'Productos']);
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['numero_pedido'],
            $row['cliente'],
            formatDate($row['fecha']),
            'Bs. ' . formatMoney($row['total']),
            getEstadoTexto($row['estado']),
            $row['metodo_pago'] ?? 'N/A',
            $row['productos'] ?? 0
        ]);
    }
} elseif ($tipo == 'compras') {
    fputcsv($output, ['DETALLE DE COMPRAS']);
    fputcsv($output, ['ID', 'N° Orden', 'Proveedor', 'Fecha', 'Subtotal', 'Total', 'Estado']);
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['numero_orden'],
            $row['proveedor'],
            formatDate($row['fecha']),
            'Bs. ' . formatMoney($row['subtotal']),
            'Bs. ' . formatMoney($row['total']),
            getEstadoTexto($row['estado'])
        ]);
    }
}

fclose($output);
exit();
?>