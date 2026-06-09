<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

require_once __DIR__ . '/../conexion/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

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
        'cancelado' => 'Cancelado',
        'recibido' => 'Recibido',
        'en_proceso' => 'En Proceso',
    ];
    return $estados[$estado] ?? $estado;
}

function setHeaderStyle($sheet, $row, $columns) {
    $headerFill = [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '2F5496'],
    ];
    $headerFont = [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF'],
        'size' => 11,
    ];
    foreach ($columns as $col) {
        $cell = $col . $row;
        $sheet->getStyle($cell)->getFill()->applyFromArray($headerFill);
        $sheet->getStyle($cell)->getFont()->applyFromArray($headerFont);
        $sheet->getStyle($cell)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}

$db = Database::getConnection();

$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$tipo = $_GET['tipo'] ?? 'general';
$estado = $_GET['estado'] ?? '';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

if ($tipo === 'general') {
    // ========== REPORTE GENERAL ==========
    $sheet->setTitle('Reporte General');

    $sheet->setCellValue('A1', 'REPORTE GENERAL EJECUTIVO');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->mergeCells('A1:F1');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:F2');

    // Estadísticas
    $query_stats = "SELECT
        (SELECT SUM(total) FROM pedidos WHERE estado = 'completado' OR estado = 'facturado') as ventas_totales,
        (SELECT SUM(total) FROM pedidos WHERE (estado = 'completado' OR estado = 'facturado') AND MONTH(fecha_pedido) = MONTH(CURDATE()) AND YEAR(fecha_pedido) = YEAR(CURDATE())) as ventas_mes,
        (SELECT COUNT(DISTINCT usuario_id) FROM pedidos WHERE estado = 'completado' OR estado = 'facturado') as clientes_activos,
        (SELECT AVG(total) FROM pedidos WHERE estado = 'completado' OR estado = 'facturado') as ticket_promedio";
    $stmt = $db->prepare($query_stats);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $sheet->setCellValue('A4', 'ESTADÍSTICAS PRINCIPALES');
    $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
    setHeaderStyle($sheet, 5, ['A', 'B']);

    $sheet->setCellValue('A5', 'Indicador');
    $sheet->setCellValue('B5', 'Valor');

    $sheet->setCellValue('A6', 'Ventas Totales');
    $sheet->setCellValue('B6', 'Bs. ' . formatMoney($stats['ventas_totales']));
    $sheet->setCellValue('A7', 'Ventas del Mes');
    $sheet->setCellValue('B7', 'Bs. ' . formatMoney($stats['ventas_mes']));
    $sheet->setCellValue('A8', 'Clientes Activos');
    $sheet->setCellValue('B8', $stats['clientes_activos']);
    $sheet->setCellValue('A9', 'Ticket Promedio');
    $sheet->setCellValue('B9', 'Bs. ' . formatMoney($stats['ticket_promedio']));

    // Top productos
    $query_top = "SELECT ANY_VALUE(p.name) as nombre, SUM(pd.cantidad) as unidades, SUM(pd.subtotal) as ingresos
                  FROM pedido_detalles pd
                  JOIN products p ON pd.producto_id = p.id
                  JOIN pedidos ped ON pd.pedido_id = ped.id
                  WHERE ped.estado = 'completado' OR ped.estado = 'facturado'
                  GROUP BY p.id ORDER BY unidades DESC LIMIT 10";
    $stmt = $db->prepare($query_top);
    $stmt->execute();
    $top_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $sheet->setCellValue('A11', 'TOP 10 PRODUCTOS MÁS VENDIDOS');
    $sheet->getStyle('A11')->getFont()->setBold(true)->setSize(12);
    setHeaderStyle($sheet, 12, ['A', 'B', 'C']);

    $sheet->setCellValue('A12', 'Producto');
    $sheet->setCellValue('B12', 'Unidades');
    $sheet->setCellValue('C12', 'Ingresos');
    $row = 13;
    foreach ($top_productos as $p) {
        $sheet->setCellValue('A' . $row, $p['nombre']);
        $sheet->setCellValue('B' . $row, $p['unidades']);
        $sheet->setCellValue('C' . $row, 'Bs. ' . formatMoney($p['ingresos']));
        $row++;
    }

    // Pedidos
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

    $dataRow = $row + 1;
    $sheet->setCellValue('A' . $dataRow, 'DETALLE DE PEDIDOS');
    $sheet->getStyle('A' . $dataRow)->getFont()->setBold(true)->setSize(12);

    $headerRow = $dataRow + 1;
    setHeaderStyle($sheet, $headerRow, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
    $sheet->setCellValue('A' . $headerRow, 'ID');
    $sheet->setCellValue('B' . $headerRow, 'N° Pedido');
    $sheet->setCellValue('C' . $headerRow, 'Cliente');
    $sheet->setCellValue('D' . $headerRow, 'Fecha');
    $sheet->setCellValue('E' . $headerRow, 'Total');
    $sheet->setCellValue('F' . $headerRow, 'Método Pago');
    $sheet->setCellValue('G' . $headerRow, 'Estado');
    $sheet->setCellValue('H' . $headerRow, 'Productos');

    $dataRow = $headerRow + 1;
    foreach ($pedidos as $pedido) {
        $sheet->setCellValue('A' . $dataRow, $pedido['pedido_id']);
        $sheet->setCellValue('B' . $dataRow, $pedido['numero_pedido']);
        $sheet->setCellValue('C' . $dataRow, $pedido['cliente']);
        $sheet->setCellValue('D' . $dataRow, formatDate($pedido['fecha']));
        $sheet->setCellValue('E' . $dataRow, 'Bs. ' . formatMoney($pedido['total']));
        $sheet->setCellValue('F' . $dataRow, $pedido['metodo_pago']);
        $sheet->setCellValue('G' . $dataRow, getEstadoTexto($pedido['estado']));
        $sheet->setCellValue('H' . $dataRow, $pedido['productos']);
        $dataRow++;
    }

    $sheet->getColumnDimension('A')->setAutoSize(true);
    $sheet->getColumnDimension('B')->setAutoSize(true);
    $sheet->getColumnDimension('C')->setAutoSize(true);
    $sheet->getColumnDimension('D')->setAutoSize(true);
    $sheet->getColumnDimension('E')->setAutoSize(true);
    $sheet->getColumnDimension('F')->setAutoSize(true);
    $sheet->getColumnDimension('G')->setAutoSize(true);
    $sheet->getColumnDimension('H')->setAutoSize(true);

} else {
    // ========== REPORTE ESPECÍFICO (ventas/compras/pedidos) ==========
    $titulos = [
        'ventas' => 'Reporte de Ventas',
        'compras' => 'Reporte de Compras',
        'pedidos' => 'Reporte de Pedidos',
    ];
    $titulo = $titulos[$tipo] ?? 'Reporte';
    $sheet->setTitle($titulo);

    $params = [];
    $data = [];

    switch ($tipo) {
        case 'ventas':
            $query = "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.fecha_pedido as fecha, p.total, p.estado, p.metodo_pago,
                             (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
                      FROM pedidos p
                      JOIN users u ON p.usuario_id = u.id
                      WHERE 1=1";
            if ($desde) { $query .= " AND DATE(p.fecha_pedido) >= ?"; $params[] = $desde; }
            if ($hasta) { $query .= " AND DATE(p.fecha_pedido) <= ?"; $params[] = $hasta; }
            if ($estado) { $query .= " AND p.estado = ?"; $params[] = $estado; }
            $query .= " ORDER BY p.fecha_pedido DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'compras':
            $query = "SELECT c.id, c.numero_orden, p.nombre_comercial as proveedor, c.fecha_orden as fecha,
                             c.subtotal, c.total, c.estado
                      FROM compras c
                      JOIN proveedores p ON c.proveedor_id = p.id
                      WHERE 1=1";
            $params = [];
            if ($desde) { $query .= " AND DATE(c.fecha_orden) >= ?"; $params[] = $desde; }
            if ($hasta) { $query .= " AND DATE(c.fecha_orden) <= ?"; $params[] = $hasta; }
            if ($estado) { $query .= " AND c.estado = ?"; $params[] = $estado; }
            $query .= " ORDER BY c.fecha_orden DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'pedidos':
            $query = "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.fecha_pedido as fecha, p.total, p.estado, p.metodo_pago,
                             (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
                      FROM pedidos p
                      JOIN users u ON p.usuario_id = u.id
                      WHERE 1=1";
            $params = [];
            if ($desde) { $query .= " AND DATE(p.fecha_pedido) >= ?"; $params[] = $desde; }
            if ($hasta) { $query .= " AND DATE(p.fecha_pedido) <= ?"; $params[] = $hasta; }
            if ($estado) { $query .= " AND p.estado = ?"; $params[] = $estado; }
            $query .= " ORDER BY p.fecha_pedido DESC";
            $stmt = $db->prepare($query);
            $stmt->execute($params);
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

    // Título
    $sheet->setCellValue('A1', strtoupper($titulo));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('A2:H2');

    $sheet->setCellValue('A3', 'Período: ' . ($desde ? formatDate($desde) : 'Todo') . ' a ' . ($hasta ? formatDate($hasta) : 'Todo'));
    $sheet->mergeCells('A3:H3');

    $sheet->setCellValue('A4', 'Estado: ' . ($estado ? getEstadoTexto($estado) : 'Todos'));

    // Resumen
    $sheet->setCellValue('A6', 'RESUMEN');
    $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(12);

    setHeaderStyle($sheet, 7, ['A', 'B']);
    $sheet->setCellValue('A7', 'Indicador');
    $sheet->setCellValue('B7', 'Valor');
    $sheet->setCellValue('A8', 'Total Registros');
    $sheet->setCellValue('B8', $total_registros);
    $sheet->setCellValue('A9', 'Monto Total');
    $sheet->setCellValue('B9', 'Bs. ' . formatMoney($total_monto));
    $sheet->setCellValue('A10', 'Promedio');
    $sheet->setCellValue('B10', 'Bs. ' . formatMoney($promedio));

    // Detalle
    $detalleRow = 12;
    if ($tipo === 'compras') {
        $sheet->setCellValue('A' . $detalleRow, 'DETALLE DE COMPRAS');
        $sheet->getStyle('A' . $detalleRow)->getFont()->setBold(true)->setSize(12);

        $headerRow = $detalleRow + 1;
        setHeaderStyle($sheet, $headerRow, ['A', 'B', 'C', 'D', 'E', 'F', 'G']);
        $sheet->setCellValue('A' . $headerRow, 'ID');
        $sheet->setCellValue('B' . $headerRow, 'N° Orden');
        $sheet->setCellValue('C' . $headerRow, 'Proveedor');
        $sheet->setCellValue('D' . $headerRow, 'Fecha');
        $sheet->setCellValue('E' . $headerRow, 'Subtotal');
        $sheet->setCellValue('F' . $headerRow, 'Total');
        $sheet->setCellValue('G' . $headerRow, 'Estado');

        $dataRow = $headerRow + 1;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $dataRow, $row['id']);
            $sheet->setCellValue('B' . $dataRow, $row['numero_orden']);
            $sheet->setCellValue('C' . $dataRow, $row['proveedor']);
            $sheet->setCellValue('D' . $dataRow, formatDate($row['fecha']));
            $sheet->setCellValue('E' . $dataRow, 'Bs. ' . formatMoney($row['subtotal']));
            $sheet->setCellValue('F' . $dataRow, 'Bs. ' . formatMoney($row['total']));
            $sheet->setCellValue('G' . $dataRow, getEstadoTexto($row['estado']));
            $dataRow++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);

    } else {
        $sheet->setCellValue('A' . $detalleRow, 'DETALLE DE ' . strtoupper($tipo));
        $sheet->getStyle('A' . $detalleRow)->getFont()->setBold(true)->setSize(12);

        $headerRow = $detalleRow + 1;
        setHeaderStyle($sheet, $headerRow, ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
        $sheet->setCellValue('A' . $headerRow, 'ID');
        $sheet->setCellValue('B' . $headerRow, 'N° Pedido');
        $sheet->setCellValue('C' . $headerRow, 'Cliente');
        $sheet->setCellValue('D' . $headerRow, 'Fecha');
        $sheet->setCellValue('E' . $headerRow, 'Total');
        $sheet->setCellValue('F' . $headerRow, 'Estado');
        $sheet->setCellValue('G' . $headerRow, 'Método Pago');
        $sheet->setCellValue('H' . $headerRow, 'Productos');

        $dataRow = $headerRow + 1;
        foreach ($data as $row) {
            $sheet->setCellValue('A' . $dataRow, $row['id']);
            $sheet->setCellValue('B' . $dataRow, $row['numero_pedido']);
            $sheet->setCellValue('C' . $dataRow, $row['cliente']);
            $sheet->setCellValue('D' . $dataRow, formatDate($row['fecha']));
            $sheet->setCellValue('E' . $dataRow, 'Bs. ' . formatMoney($row['total']));
            $sheet->setCellValue('F' . $dataRow, getEstadoTexto($row['estado']));
            $sheet->setCellValue('G' . $dataRow, $row['metodo_pago'] ?? 'N/A');
            $sheet->setCellValue('H' . $dataRow, $row['productos'] ?? 0);
            $dataRow++;
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);
        $sheet->getColumnDimension('F')->setAutoSize(true);
        $sheet->getColumnDimension('G')->setAutoSize(true);
        $sheet->getColumnDimension('H')->setAutoSize(true);
    }
}

$filename = 'reporte_' . $tipo . '_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
