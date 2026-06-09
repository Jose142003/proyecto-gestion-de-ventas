<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/../telegram/helpers.php';

use Dompdf\Dompdf;
use Dompdf\Options;

session_start();
$usuarioId = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_GET;
$accion = $input['accion'] ?? '';

try {
    $db = obtenerDb();

    if ($accion === 'generar') {
        if (!$usuarioId) responder(['error' => 'Debe iniciar sesión'], 401);

        $productoId = intval($input['producto_id'] ?? 0);
        $intervaloDias = intval($input['intervalo_dias'] ?? 90);
        $fechaCompra = $input['fecha_compra'] ?? date('Y-m-d');

        if (!$productoId) responder(['error' => 'Producto requerido'], 400);

        $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) responder(['error' => 'Producto no encontrado'], 404);

        $proximo = date('Y-m-d', strtotime("+$intervaloDias days"));

        $stmt = $db->prepare("
            INSERT INTO alertas_mantenimiento (producto_id, producto_nombre, usuario_id, fecha_compra, intervalo_dias, proximo_mantenimiento)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$productoId, $producto['name'], $usuarioId, $fechaCompra, $intervaloDias, $proximo]);

        $id = $db->lastInsertId();

        // Enviar notificacion a Telegram con PDF y Excel adjuntos
        $telegramEnviado = false;
        $telegramError = '';
        try {
            $tg = telegramObtenerConfig($db);
            if (!empty($tg['token']) && !empty($tg['chat_id'])) {
                $tmpDir = sys_get_temp_dir();
                $pdfPath = $tmpDir . '/reporte_mantenimiento_' . date('Y-m-d') . '.pdf';
                $xlsxPath = $tmpDir . '/reporte_mantenimiento_' . date('Y-m-d') . '.xlsx';

                // Generar PDF
                try {
                    $stmtReporte = $db->query("
                        SELECT am.*, COALESCE(p.name, am.producto_nombre) AS producto_nombre,
                               p.price AS producto_precio, p.stock AS producto_stock,
                               DATEDIFF(am.proximo_mantenimiento, CURDATE()) AS dias_restantes,
                               CASE 
                                   WHEN am.estado = 'completado' THEN 'Completado'
                                   WHEN am.proximo_mantenimiento < CURDATE() THEN 'Vencido'
                                   WHEN am.proximo_mantenimiento <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Proximo (7 dias)'
                                   ELSE 'Programado'
                               END AS estado_texto
                        FROM alertas_mantenimiento am
                        LEFT JOIN products p ON am.producto_id = p.id
                        ORDER BY am.proximo_mantenimiento ASC
                    ");
                    $alertasReporte = $stmtReporte->fetchAll();

                    $rows = '';
                    foreach ($alertasReporte as $a) {
                        $estado = $a['estado_texto'];
                        $color = '#007bff';
                        if ($estado === 'Vencido') $color = '#dc3545';
                        elseif (strpos($estado, 'Proximo') === 0) $color = '#ffc107';
                        elseif ($estado === 'Completado') $color = '#28a745';
                        $dias = $a['dias_restantes'] !== null ? ($a['dias_restantes'] < 0 ? abs($a['dias_restantes']) . ' dias vencido' : $a['dias_restantes'] . ' dias') : '-';
                        $rows .= "<tr>
                            <td style='padding:4px 6px;border:1px solid #ddd;text-align:center'>{$a['id']}</td>
                            <td style='padding:4px 6px;border:1px solid #ddd'>{$a['producto_nombre']}</td>
                            <td style='padding:4px 6px;border:1px solid #ddd;text-align:center'>{$a['intervalo_dias']}</td>
                            <td style='padding:4px 6px;border:1px solid #ddd;text-align:center'>{$a['proximo_mantenimiento']}</td>
                            <td style='padding:4px 6px;border:1px solid #ddd;text-align:center'>{$dias}</td>
                            <td style='padding:4px 6px;border:1px solid #ddd;text-align:center;color:{$color};font-weight:bold'>{$estado}</td>
                        </tr>";
                    }

                    $html = "<html><head><meta charset='UTF-8'>
                        <style>
                            body { font-family: Helvetica, sans-serif; font-size: 11px; color: #333; }
                            h1 { color: #2c3e50; font-size: 18px; text-align: center; }
                            .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #2c3e50; padding-bottom: 10px; }
                            .header p { color: #666; font-size: 10px; margin: 2px 0; }
                            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                            th { background: #2c3e50; color: white; padding: 6px; text-align: left; font-size: 10px; }
                            tr:nth-child(even) { background: #f8f9fa; }
                            .footer { margin-top: 15px; text-align: center; color: #999; font-size: 9px; border-top: 1px solid #ddd; padding-top: 8px; }
                        </style></head><body>
                        <div class='header'>
                            <h1>Reporte de Mantenimiento</h1>
                            <p>Generado: " . date('d/m/Y H:i') . " | Total alertas: " . count($alertasReporte) . "</p>
                        </div>
                        <table>
                            <thead><tr><th>ID</th><th>Producto</th><th>Intervalo</th><th>Proximo</th><th>Dias</th><th>Estado</th></tr></thead>
                            <tbody>{$rows}</tbody>
                        </table>
                        <div class='footer'>Generado automaticamente por PIC</div>
                        </body></html>";

                    $opts = new Options();
                    $opts->set('defaultFont', 'Helvetica');
                    $opts->set('isRemoteEnabled', true);
                    $dompdf = new Dompdf($opts);
                    $dompdf->loadHtml($html);
                    $dompdf->setPaper('A4', 'landscape');
                    $dompdf->render();
                    file_put_contents($pdfPath, $dompdf->output());
                } catch (Throwable $e) {
                    $pdfPath = null;
                    error_log("Error generando PDF mantenimiento: " . $e->getMessage());
                }

                // Generar Excel
                try {
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('Mantenimiento');
                    $sheet->fromArray(['ID', 'Producto', 'Intervalo (dias)', 'Proximo Mantenimiento', 'Dias Restantes', 'Estado'], null, 'A1');
                    $rowNum = 2;
                    foreach ($alertasReporte as $a) {
                        $dias = $a['dias_restantes'] !== null ? ($a['dias_restantes'] < 0 ? abs($a['dias_restantes']) . ' dias vencido' : $a['dias_restantes'] . ' dias') : '-';
                        $sheet->fromArray([$a['id'], $a['producto_nombre'], $a['intervalo_dias'], $a['proximo_mantenimiento'], $dias, $a['estado_texto']], null, "A{$rowNum}");
                        $rowNum++;
                    }
                    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                    $writer->save($xlsxPath);
                } catch (Throwable $e) {
                    $xlsxPath = null;
                    error_log("Error generando Excel mantenimiento: " . $e->getMessage());
                }

                // Enviar mensaje de texto
                $mensaje = "🛠 <b>Nueva Alerta de Mantenimiento</b>\n\n"
                    . "Producto: <b>{$producto['name']}</b>\n"
                    . "Proximo: <b>{$proximo}</b>\n"
                    . "Intervalo: <b>{$intervaloDias} dias</b>\n"
                    . "ID Alerta: <b>#{$id}</b>";

                $msgResult = telegramEnviar($tg['token'], $tg['chat_id'], $mensaje);
                if (!$msgResult['success']) {
                    error_log("Error enviando mensaje Telegram: " . ($msgResult['error'] ?? 'unknown'));
                }

                // Enviar PDF
                if ($pdfPath && file_exists($pdfPath)) {
                    $pdfResult = telegramEnviarDocumento($tg['token'], $tg['chat_id'], $pdfPath, "Reporte PDF - Mantenimiento");
                    if (!$pdfResult['success']) {
                        error_log("Error enviando PDF a Telegram: " . ($pdfResult['error'] ?? 'unknown'));
                    }
                    @unlink($pdfPath);
                }

                // Enviar Excel
                if ($xlsxPath && file_exists($xlsxPath)) {
                    $xlsResult = telegramEnviarDocumento($tg['token'], $tg['chat_id'], $xlsxPath, "Reporte Excel - Mantenimiento");
                    if (!$xlsResult['success']) {
                        error_log("Error enviando Excel a Telegram: " . ($xlsResult['error'] ?? 'unknown'));
                    }
                    @unlink($xlsxPath);
                }

                $telegramEnviado = true;
            }
        } catch (Throwable $e) {
            $telegramError = $e->getMessage();
            error_log("Error envio Telegram mantenimiento: " . $e->getMessage());
        }

        responder([
            'success' => true,
            'id' => $id,
            'proximo_mantenimiento' => $proximo,
            'telegram_notificado' => $telegramEnviado,
            'telegram_error' => $telegramError ?: null
        ]);
    }

    elseif ($accion === 'pendientes') {
        $stmt = $db->prepare("
            SELECT a.*, p.name as producto_nombre, p.price as producto_precio
            FROM alertas_mantenimiento a
            LEFT JOIN products p ON a.producto_id = p.id
            WHERE (a.usuario_id = ? OR ? = 0 OR ? IN (SELECT id FROM admin_users))
            AND a.estado = 'pendiente'
            ORDER BY a.proximo_mantenimiento ASC
            LIMIT 30
        ");
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $proximas = [];
        $vencidas = [];
        foreach ($alertas as $a) {
            if (strtotime($a['proximo_mantenimiento']) <= time()) {
                $a['vencida'] = true;
                $vencidas[] = $a;
            } else {
                $a['vencida'] = false;
                $proximas[] = $a;
            }
        }

        responder(['success' => true, 'vencidas' => $vencidas, 'proximas' => $proximas, 'total' => count($alertas)]);
    }

    elseif ($accion === 'notificar') {
        $id = intval($input['id'] ?? 0);

        $stmt = $db->prepare("SELECT a.*, u.correo as usuario_email FROM alertas_mantenimiento a 
            LEFT JOIN users u ON a.usuario_id = u.id WHERE a.id = ?");
        $stmt->execute([$id]);
        $alerta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alerta) responder(['error' => 'Alerta no encontrada'], 404);

        $stmt = $db->prepare("UPDATE alertas_mantenimiento SET estado = 'notificado' WHERE id = ?");
        $stmt->execute([$id]);

        logSistema("Alerta mantenimiento notificada: {$alerta['producto_nombre']} (ID: $id)", 'INFO');

        responder(['success' => true, 'message' => 'Alerta notificada']);
    }

    elseif ($accion === 'completar') {
        $id = intval($input['id'] ?? 0);

        $stmt = $db->prepare("UPDATE alertas_mantenimiento SET estado = 'completado' WHERE id = ?");
        $stmt->execute([$id]);

        responder(['success' => true, 'message' => 'Mantenimiento completado']);
    }

    elseif ($accion === 'intervalos') {
        responder([
            'success' => true,
            'intervalos' => [
                ['dias' => 30, 'label' => 'Cada mes'],
                ['dias' => 90, 'label' => 'Cada 3 meses'],
                ['dias' => 180, 'label' => 'Cada 6 meses'],
                ['dias' => 365, 'label' => 'Cada año'],
            ],
            'recomendaciones' => [
                'Contactores' => 90,
                'Variadores' => 180,
                'Sensores' => 180,
                'Fuentes de Poder' => 365,
                'Instrumentos de Medición' => 365,
                'Relés' => 180,
                'Motores' => 90,
            ]
        ]);
    }

    else {
        $stmt = $db->query("
            SELECT estado, COUNT(*) as total FROM alertas_mantenimiento GROUP BY estado
        ");
        $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtVencidas = $db->query("
            SELECT COUNT(*) as total FROM alertas_mantenimiento 
            WHERE estado = 'pendiente' AND proximo_mantenimiento <= CURDATE()
        ");
        $vencidas = $stmtVencidas->fetch(PDO::FETCH_ASSOC)['total'];

        responder(['success' => true, 'resumen' => $resumen, 'vencidas_ahora' => $vencidas]);
    }

} catch (Exception $e) {
    responder(['error' => 'Error interno: ' . $e->getMessage()], 500);
}
