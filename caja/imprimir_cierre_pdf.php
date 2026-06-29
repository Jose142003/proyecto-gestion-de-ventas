<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    die('No autorizado');
}

require_once __DIR__ . '/../vendor/autoload.php';

$id = (int)($_GET['id'] ?? 0);

try {
    $pdo = conectarDB();

    if ($id) {
        $stmt = $pdo->prepare("SELECT ca.*, ua.nombre as usuario_apertura, uc.nombre as usuario_cierre
                               FROM caja_arqueos ca
                               LEFT JOIN users ua ON ca.usuario_apertura_id = ua.id
                               LEFT JOIN users uc ON ca.usuario_cierre_id = uc.id
                               WHERE ca.id = ?");
        $stmt->execute([$id]);
        $caja = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $stmt = $pdo->query("SELECT ca.*, ua.nombre as usuario_apertura, uc.nombre as usuario_cierre
                             FROM caja_arqueos ca
                             LEFT JOIN users ua ON ca.usuario_apertura_id = ua.id
                             LEFT JOIN users uc ON ca.usuario_cierre_id = uc.id
                             ORDER BY ca.id DESC LIMIT 1");
        $caja = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$caja) {
        die('No hay cierre de caja disponible');
    }

    $stmtMov = $pdo->prepare("SELECT m.*, u.nombre as usuario_nombre
                              FROM caja_movimientos m
                              LEFT JOIN users u ON m.usuario_id = u.id
                              WHERE m.arqueo_id = ?
                              ORDER BY m.fecha_movimiento ASC");
    $stmtMov->execute([$caja['id']]);
    $movimientos = $stmtMov->fetchAll(PDO::FETCH_ASSOC);

    $stmtTotales = $pdo->prepare("SELECT
        COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) as total_ingresos,
        COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) as total_egresos
        FROM caja_movimientos WHERE arqueo_id = ?");
    $stmtTotales->execute([$caja['id']]);
    $totales = $stmtTotales->fetch(PDO::FETCH_ASSOC);

    $empresaCfg = obtenerConfigEmpresa($pdo);
    $empresa = $empresaCfg['nombre'];
    $rif = $empresaCfg['rif'];
    $telefono = $empresaCfg['telefono'];
    $direccion = $empresaCfg['direccion'];

    $fecha_apertura = date('d/m/Y H:i', strtotime($caja['fecha_apertura']));
    $fecha_cierre = $caja['fecha_cierre'] ? date('d/m/Y H:i', strtotime($caja['fecha_cierre'])) : 'Pendiente';
    $monto_inicial = (float)$caja['monto_inicial'];
    $total_ingresos = (float)$totales['total_ingresos'];
    $total_egresos = (float)$totales['total_egresos'];
    $total_esperado = (float)($caja['monto_esperado'] ?? ($monto_inicial + $total_ingresos - $total_egresos));
    $total_real = (float)($caja['monto_real'] ?? $total_esperado);
    $diferencia = (float)($caja['diferencia'] ?? 0);

    $html = '
    <html><head><meta charset="UTF-8"><style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #050C18; padding-bottom: 15px; }
        .header h1 { color: #050C18; font-size: 22px; margin: 0 0 5px 0; }
        .header p { color: #666; margin: 2px 0; font-size: 11px; }
        .title { font-size: 18px; font-weight: bold; color: #050C18; text-align: center; margin: 20px 0; }
        .info { margin-bottom: 20px; }
        .info table { width: 100%; border-collapse: collapse; }
        .info td { padding: 4px 8px; }
        .info .label { font-weight: bold; width: 180px; background: #f0f0f0; }
        .resumen { margin: 20px 0; }
        .resumen table { width: 50%; margin: 0 auto; border-collapse: collapse; }
        .resumen td, .resumen th { padding: 8px 12px; border: 1px solid #ddd; }
        .resumen th { background: #050C18; color: white; text-align: center; }
        .resumen .total-row { font-weight: bold; background: #e8f4fd; }
        .resumen .diferencia { color: ' . ($diferencia != 0 ? 'red' : 'green') . '; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items th { background: #3C91ED; color: white; padding: 8px; text-align: left; font-size: 11px; }
        table.items td { padding: 6px 8px; border-bottom: 1px solid #ddd; font-size: 11px; }
        table.items tr:nth-child(even) { background: #f9f9f9; }
        .footer { margin-top: 50px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
        .firma { margin-top: 40px; display: flex; justify-content: space-between; }
        .firma .linea { width: 200px; border-top: 1px solid #333; padding-top: 5px; text-align: center; font-size: 11px; }
        .estado-abierta { color: #2ed573; font-weight: bold; }
        .estado-cerrada { color: #ff4757; font-weight: bold; }
    </style></head><body>
        <div class="header">
            <h1>REPORTE DE CIERRE DE CAJA</h1>
            <p><strong>' . $empresa . '</strong></p>
            <p>RIF: ' . $rif . ' | Tel: ' . $telefono . '</p>
            <p>' . $direccion . '</p>
        </div>

        <div class="info">
            <table>
                <tr><td class="label">N° Arqueo:</td><td>' . htmlspecialchars($caja['numero_arqueo']) . '</td>
                    <td class="label">Estado:</td><td><span class="estado-' . $caja['estado'] . '">' . ucfirst($caja['estado']) . '</span></td></tr>
                <tr><td class="label">Fecha Apertura:</td><td>' . $fecha_apertura . '</td>
                    <td class="label">Fecha Cierre:</td><td>' . $fecha_cierre . '</td></tr>
                <tr><td class="label">Abierto por:</td><td>' . htmlspecialchars($caja['usuario_apertura'] ?? 'Sistema') . '</td>
                    <td class="label">Cerrado por:</td><td>' . htmlspecialchars($caja['usuario_cierre'] ?? '—') . '</td></tr>
            </table>
        </div>

        <div class="resumen">
            <table>
                <tr><th colspan="2">RESUMEN DE CAJA</th></tr>
                <tr><td>Monto Inicial</td><td style="text-align:right">Bs. ' . number_format($monto_inicial, 2) . '</td></tr>
                <tr><td>Total Ingresos</td><td style="text-align:right">Bs. ' . number_format($total_ingresos, 2) . '</td></tr>
                <tr><td>Total Egresos</td><td style="text-align:right">Bs. ' . number_format($total_egresos, 2) . '</td></tr>
                <tr class="total-row"><td>Total Esperado</td><td style="text-align:right">Bs. ' . number_format($total_esperado, 2) . '</td></tr>
                <tr class="total-row"><td>Total Real</td><td style="text-align:right">Bs. ' . number_format($total_real, 2) . '</td></tr>
                <tr><td>Diferencia</td><td style="text-align:right" class="diferencia">Bs. ' . number_format($diferencia, 2) . '</td></tr>
            </table>
        </div>';

    if (!empty($movimientos)) {
        $html .= '<h3 style="margin-top:25px;color:#050C18">Detalle de Movimientos</h3>
        <table class="items">
            <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Categoría</th><th>Descripción</th><th>Monto</th><th>Usuario</th></tr></thead>
            <tbody>';
        $i = 1;
        foreach ($movimientos as $m) {
            $html .= '<tr>
                <td>' . $i++ . '</td>
                <td>' . date('d/m/Y H:i', strtotime($m['fecha_movimiento'])) . '</td>
                <td>' . ($m['tipo'] == 'ingreso' ? 'Ingreso' : 'Egreso') . '</td>
                <td>' . htmlspecialchars($m['categoria'] ?? 'General') . '</td>
                <td>' . htmlspecialchars($m['descripcion'] ?? '') . '</td>
                <td style="text-align:right">Bs. ' . number_format($m['monto'], 2) . '</td>
                <td>' . htmlspecialchars($m['usuario_nombre'] ?? 'Sistema') . '</td>
            </tr>';
        }
        $html .= '</tbody></table>';
    }

    $html .= '
        <div class="firma">
            <div class="linea">_________________________<br>Responsable de Caja</div>
            <div class="linea">_________________________<br>Supervisor</div>
            <div class="linea">_________________________<br>Administrador</div>
        </div>
        <div class="footer">
            <p>Documento generado el ' . date('d/m/Y H:i:s') . ' | PIC Sistema de Gestión Comercial</p>
            <p>Este documento es un reporte interno de cierre de caja</p>
        </div>
    </body></html>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $dompdf->stream("Cierre_Caja_" . $caja['numero_arqueo'] . ".pdf", ["Attachment" => true]);

} catch (Throwable $e) {
    die('Error generando PDF: ' . htmlspecialchars($e->getMessage()));
}
