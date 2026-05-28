<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../usuarios/config_email.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$exportExcel = isset($_GET['excel']);

try {
    $pdo = conectarDB();

    $stmt = $pdo->query("
        SELECT am.*, p.nombre AS producto_nombre, 
               p.precio AS producto_precio, p.stock AS producto_stock,
               DATEDIFF(am.proximo_mantenimiento, CURDATE()) AS dias_restantes,
               CASE 
                   WHEN am.estado = 'completado' THEN 'Completado'
                   WHEN am.proximo_mantenimiento < CURDATE() THEN 'Vencido'
                   WHEN am.proximo_mantenimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Próximo (7 días)'
                   ELSE 'Programado'
               END AS estado_texto
        FROM alertas_mantenimiento am
        LEFT JOIN productos p ON am.producto_id = p.id
        ORDER BY 
            CASE WHEN am.proximo_mantenimiento < CURDATE() THEN 0 ELSE 1 END,
            am.proximo_mantenimiento ASC
    ");
    $alertas = $stmt->fetchAll();

    if ($exportExcel) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_mantenimiento_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID', 'Producto', 'Precio', 'Stock', 'Intervalo (días)', 'Último Mantenimiento', 'Próximo Mantenimiento', 'Días Restantes', 'Estado']);

        foreach ($alertas as $a) {
            fputcsv($output, [
                $a['id'],
                $a['producto_nombre'],
                $a['producto_precio'] ? 'Bs ' . number_format($a['producto_precio'], 2) : '-',
                $a['producto_stock'] ?? '-',
                $a['intervalo_dias'],
                $a['ultimo_mantenimiento'] ?? '-',
                $a['proximo_mantenimiento'],
                $a['dias_restantes'] !== null ? ($a['dias_restantes'] < 0 ? abs($a['dias_restantes']) . ' días vencido' : $a['dias_restantes'] . ' días') : '-',
                $a['estado_texto']
            ]);
        }
        fclose($output);
        exit;
    }

    require_once __DIR__ . '/../vendor/autoload.php';

    $options = new Options();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $vencidas = array_filter($alertas, fn($a) => $a['estado_texto'] === 'Vencido');
    $proximas = array_filter($alertas, fn($a) => $a['estado_texto'] !== 'Vencido' && $a['estado_texto'] !== 'Completado');

    $rows = '';
    foreach ($alertas as $a) {
        $estado = $a['estado_texto'];
        $color = '#007bff';
        if ($estado === 'Vencido') $color = '#dc3545';
        elseif ($estado === 'Próximo (7 días)') $color = '#ffc107';
        elseif ($estado === 'Completado') $color = '#28a745';
        $ultimo = $a['ultimo_mantenimiento'] ?: '-';
        $dias = $a['dias_restantes'] !== null ? ($a['dias_restantes'] < 0 ? abs($a['dias_restantes']) . ' días vencido' : $a['dias_restantes'] . ' días') : '-';
        $rows .= "<tr>
            <td style='padding:6px 8px;border:1px solid #ddd;text-align:center'>{$a['id']}</td>
            <td style='padding:6px 8px;border:1px solid #ddd'>{$a['producto_nombre']}</td>
            <td style='padding:6px 8px;border:1px solid #ddd;text-align:center'>{$a['intervalo_dias']}</td>
            <td style='padding:6px 8px;border:1px solid #ddd;text-align:center'>{$ultimo}</td>
            <td style='padding:6px 8px;border:1px solid #ddd;text-align:center'>{$a['proximo_mantenimiento']}</td>
            <td style='padding:6px 8px;border:1px solid #ddd;text-align:center'>{$dias}</td>
            <td style='padding:6px 8px;border:1px solid #ddd;text-align:center;color:{$color};font-weight:bold'>{$estado}</td>
        </tr>";
    }

    $html = "
    <html><head><meta charset='UTF-8'>
    <style>
        body { font-family: Helvetica, sans-serif; font-size: 12px; color: #333; line-height: 1.5; }
        h1 { color: #2c3e50; font-size: 20px; margin-bottom: 5px; }
        h2 { color: #2c3e50; font-size: 14px; margin-top: 20px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #2c3e50; }
        .header p { color: #666; font-size: 11px; margin: 2px 0; }
        .summary { display: flex; gap: 15px; margin-bottom: 20px; }
        .summary-box { flex: 1; padding: 10px; border-radius: 6px; text-align: center; color: white; font-weight: bold; font-size: 13px; }
        .summary-box .num { font-size: 22px; display: block; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #2c3e50; color: white; padding: 7px 8px; text-align: left; font-size: 11px; }
        tr:nth-child(even) { background: #f8f9fa; }
        .footer { margin-top: 20px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
        .badge { padding: 2px 6px; border-radius: 3px; color: white; font-size: 10px; }
    </style></head><body>
    <div class='header'>
        <h1>Reporte de Mantenimiento</h1>
        <p>Proyectos Industriales del Centro</p>
        <p>Generado: " . date('d/m/Y H:i') . "</p>
    </div>
    <div class='summary'>
        <div class='summary-box' style='background:#dc3545'><span class='num'>" . count($vencidas) . "</span>Vencidas</div>
        <div class='summary-box' style='background:#ffc107;color:#333'><span class='num'>" . count($proximas) . "</span>Próximas</div>
        <div class='summary-box' style='background:#28a745'><span class='num'>" . count($alertas) . "</span>Total Alertas</div>
    </div>
    <h2>Listado de Alertas</h2>
    <table>
        <thead><tr><th>ID</th><th>Producto</th><th>Intervalo</th><th>Último</th><th>Próximo</th><th>Días</th><th>Estado</th></tr></thead>
        <tbody>{$rows}</tbody>
    </table>
    <div class='footer'>Proyectos Industriales del Centro &copy; " . date('Y') . " | Este reporte es generado automáticamente por el sistema.</div>
    </body></html>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream("reporte_mantenimiento_" . date('Y-m-d') . ".pdf", ['Attachment' => false]);

} catch (Throwable $e) {
    http_response_code(500);
    echo "Error generando reporte: " . $e->getMessage();
}
