<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error interno del servidor");
}

// Obtener parámetros
$type = $_GET['type'] ?? 'pdf';
$mes = $_GET['mes'] ?? date('m');
$anio = $_GET['anio'] ?? date('Y');

// Validar parámetros
$mes = filter_var($mes, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ? $mes : date('m');
$anio = filter_var($anio, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2030]]) ? $anio : date('Y');

// Nombres de los meses
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
$nombre_mes = $meses[$mes] ?? 'Mes';

// Obtener estadísticas
$stats = [];
$productos_top = [];
$clientes_top = [];
$ventas_por_dia = [];
$metodos_pago = [];

try {
    // Estadísticas básicas
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_facturas,
            SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as ventas_totales,
            SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as facturas_pagadas,
            SUM(CASE WHEN estado = 'pendiente' THEN total ELSE 0 END) as pendientes_total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as facturas_pendientes
        FROM facturas 
        WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ?
    ");
    $stmt->execute([$mes, $anio]);
    $stats = $stmt->fetch();
    
    // Productos más vendidos
    $stmt = $pdo->prepare("
        SELECT 
            p.name as producto,
            p.sku,
            SUM(fd.cantidad) as cantidad_vendida,
            SUM(fd.subtotal) as total_ventas,
            p.category
        FROM factura_detalles fd
        JOIN products p ON fd.producto_id = p.id
        JOIN facturas f ON fd.factura_id = f.id
        WHERE MONTH(f.fecha_emision) = ? AND YEAR(f.fecha_emision) = ? AND f.estado = 'pagada'
        GROUP BY p.id, p.name, p.sku, p.category
        ORDER BY cantidad_vendida DESC
        LIMIT 10
    ");
    $stmt->execute([$mes, $anio]);
    $productos_top = $stmt->fetchAll();
    
    // Clientes top
    $stmt = $pdo->prepare("
        SELECT 
            c.nombre as cliente,
            c.documento,
            COUNT(f.id) as total_compras,
            SUM(f.total) as total_gastado
        FROM facturas f
        JOIN clientes c ON f.cliente_id = c.id
        WHERE MONTH(f.fecha_emision) = ? AND YEAR(f.fecha_emision) = ? AND f.estado = 'pagada'
        GROUP BY c.id, c.nombre, c.documento
        ORDER BY total_gastado DESC
        LIMIT 10
    ");
    $stmt->execute([$mes, $anio]);
    $clientes_top = $stmt->fetchAll();
    
    // Ventas por día
    $stmt = $pdo->prepare("
        SELECT 
            DAY(fecha_emision) as dia,
            COUNT(*) as cantidad_facturas,
            SUM(total) as total_ventas
        FROM facturas
        WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ? AND estado = 'pagada'
        GROUP BY DAY(fecha_emision)
        ORDER BY dia
    ");
    $stmt->execute([$mes, $anio]);
    $ventas_por_dia = $stmt->fetchAll();
    
    // Métodos de pago
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(total) as total
        FROM facturas
        WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ? AND estado = 'pagada'
        GROUP BY metodo_pago
        ORDER BY cantidad DESC
    ");
    $stmt->execute([$mes, $anio]);
    $metodos_pago = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error obteniendo datos para exportación: " . $e->getMessage());
}

if ($type === 'excel') {
    exportarReporteExcel($mes, $anio, $nombre_mes, $stats, $productos_top, $clientes_top, $ventas_por_dia, $metodos_pago);
} else {
    exportarReportePDF($mes, $anio, $nombre_mes, $stats, $productos_top, $clientes_top, $ventas_por_dia, $metodos_pago);
}

function exportarReporteExcel($mes, $anio, $nombre_mes, $stats, $productos_top, $clientes_top, $ventas_por_dia, $metodos_pago) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="reporte_' . $mes . '_' . $anio . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    
    // Encabezado
    echo '<h1 style="color: #3C91ED;">REPORTE DE VENTAS</h1>';
    echo '<h2>' . $nombre_mes . ' ' . $anio . '</h2>';
    echo '<p>Generado el: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<hr>';
    
    // Estadísticas
    echo '<h3>ESTADÍSTICAS DEL MES</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>Total Facturas</th>';
    echo '<th>Ventas Totales</th>';
    echo '<th>Facturas Pagadas</th>';
    echo '<th>Facturas Pendientes</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<td>' . ($stats['total_facturas'] ?? 0) . '</td>';
    echo '<td>Bs. ' . number_format($stats['ventas_totales'] ?? 0, 2) . '</td>';
    echo '<td>' . ($stats['facturas_pagadas'] ?? 0) . '</td>';
    echo '<td>' . ($stats['facturas_pendientes'] ?? 0) . '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<br>';
    
    // Productos más vendidos
    echo '<h3>PRODUCTOS MÁS VENDIDOS</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Producto</th>';
    echo '<th>Categoría</th>';
    echo '<th>Cantidad Vendida</th>';
    echo '<th>Total Ventas</th>';
    echo '</tr>';
    
    foreach ($productos_top as $index => $producto) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($producto['producto']) . '</td>';
        echo '<td>' . htmlspecialchars($producto['category']) . '</td>';
        echo '<td>' . $producto['cantidad_vendida'] . '</td>';
        echo '<td>Bs. ' . number_format($producto['total_ventas'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<br>';
    
    // Clientes top
    echo '<h3>CLIENTES TOP</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>Cliente</th>';
    echo '<th>Documento</th>';
    echo '<th>Compras</th>';
    echo '<th>Total Gastado</th>';
    echo '</tr>';
    
    foreach ($clientes_top as $index => $cliente) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($cliente['cliente']) . '</td>';
        echo '<td>' . htmlspecialchars($cliente['documento']) . '</td>';
        echo '<td>' . $cliente['total_compras'] . '</td>';
        echo '<td>Bs. ' . number_format($cliente['total_gastado'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<br>';
    
    // Ventas por día
    echo '<h3>VENTAS POR DÍA</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>Día</th>';
    echo '<th>Cantidad Facturas</th>';
    echo '<th>Total Ventas</th>';
    echo '</tr>';
    
    foreach ($ventas_por_dia as $venta) {
        echo '<tr>';
        echo '<td>' . $venta['dia'] . '</td>';
        echo '<td>' . $venta['cantidad_facturas'] . '</td>';
        echo '<td>Bs. ' . number_format($venta['total_ventas'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '<br>';
    
    // Métodos de pago
    echo '<h3>MÉTODOS DE PAGO</h3>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>Método</th>';
    echo '<th>Cantidad</th>';
    echo '<th>Total</th>';
    echo '</tr>';
    
    foreach ($metodos_pago as $metodo) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars(ucfirst($metodo['metodo_pago'])) . '</td>';
        echo '<td>' . $metodo['cantidad'] . '</td>';
        echo '<td>Bs. ' . number_format($metodo['total'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '</body></html>';
    exit;
}

function exportarReportePDF($mes, $anio, $nombre_mes, $stats, $productos_top, $clientes_top, $ventas_por_dia, $metodos_pago) {
    // Este sería para PDF real con TCPDF/FPDF
    // Por ahora devolvemos HTML que puede convertirse a PDF
    header('Content-Type: text/html');
    header('Content-Disposition: attachment; filename="reporte_' . $mes . '_' . $anio . '.html"');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Ventas ' . htmlspecialchars($nombre_mes) . ' ' . htmlspecialchars($anio) . '</title>
          <!-- PWA Meta Tags -->
    <link rel="manifest" href="/proyecto/manifest.json">
    <meta name="theme-color" content="#050C18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIC Industrial">
    <link rel="apple-touch-icon" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/proyecto/img/pic.png">
        <style>
            body { font-family: Arial, sans-serif; margin: 30px; }
            .header { text-align: center; margin-bottom: 40px; }
            .header h1 { color: #3C91ED; margin-bottom: 10px; }
            .header h2 { color: #666; }
            .section { margin-bottom: 30px; }
            .section h3 { color: #294E90; border-bottom: 2px solid #3C91ED; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #3C91ED; color: white; padding: 12px; text-align: left; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            .total { font-weight: bold; color: green; }
            .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
            .stat-box { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; padding: 20px; text-align: center; }
            .stat-value { font-size: 24px; font-weight: bold; color: #3C91ED; margin-bottom: 10px; }
            .stat-label { color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>REPORTE DE VENTAS</h1>
            <h2>' . $nombre_mes . ' ' . $anio . '</h2>
            <p>Generado el: ' . date('d/m/Y H:i:s') . '</p>
        </div>
        
        <div class="section">
            <h3>ESTADÍSTICAS DEL MES</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-value">' . ($stats['total_facturas'] ?? 0) . '</div>
                    <div class="stat-label">Total Facturas</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">Bs. ' . number_format($stats['ventas_totales'] ?? 0, 2) . '</div>
                    <div class="stat-label">Ventas Totales</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">' . ($stats['facturas_pagadas'] ?? 0) . '</div>
                    <div class="stat-label">Facturas Pagadas</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">' . ($stats['facturas_pendientes'] ?? 0) . '</div>
                    <div class="stat-label">Facturas Pendientes</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h3>PRODUCTOS MÁS VENDIDOS</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Cantidad</th>
                        <th>Total Ventas</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($productos_top as $index => $producto) {
        $html .= '
                    <tr>
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($producto['producto']) . '</td>
                        <td>' . htmlspecialchars($producto['category']) . '</td>
                        <td>' . $producto['cantidad_vendida'] . '</td>
                        <td class="total">Bs. ' . number_format($producto['total_ventas'], 2) . '</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h3>CLIENTES TOP</h3>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Documento</th>
                        <th>Compras</th>
                        <th>Total Gastado</th>
                    </tr>
                </thead>
                <tbody>';
    
    foreach ($clientes_top as $index => $cliente) {
        $html .= '
                    <tr>
                        <td>' . ($index + 1) . '</td>
                        <td>' . htmlspecialchars($cliente['cliente']) . '</td>
                        <td>' . htmlspecialchars($cliente['documento']) . '</td>
                        <td>' . $cliente['total_compras'] . '</td>
                        <td class="total">Bs. ' . number_format($cliente['total_gastado'], 2) . '</td>
                    </tr>';
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Sistema de Facturación PIC - Reporte generado automáticamente</p>
            <p>© ' . date('Y') . ' Todos los derechos reservados</p>
        </div>
    </body>
    </html>';
    
    echo $html;
    exit;
}
?>