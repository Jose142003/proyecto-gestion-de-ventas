<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/database.php';
requerirAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die('ID requerido');
}

try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("SELECT c.*, u.nombre as usuario_nombre, u.correo as usuario_email FROM cotizaciones c LEFT JOIN admin_users u ON c.usuario_id = u.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$c) {
        die('Cotización no encontrada');
    }

    $stmtDet = $pdo->prepare("SELECT * FROM cotizacion_detalles WHERE cotizacion_id = ?");
    $stmtDet->execute([$id]);
    $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

    require_once __DIR__ . '/../vendor/autoload.php';

    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #050C18; font-size: 24px; margin: 0; }
            .header p { color: #666; margin: 5px 0; }
            .info { margin-bottom: 20px; }
            .info table { width: 100%; }
            .info td { padding: 3px 5px; }
            .info .label { font-weight: bold; width: 120px; }
            table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
            table.items th { background: #050C18; color: white; padding: 8px; text-align: left; }
            table.items td { padding: 6px 8px; border-bottom: 1px solid #ddd; }
            table.items tr:nth-child(even) { background: #f9f9f9; }
            .totals { margin-top: 20px; text-align: right; }
            .totals table { margin-left: auto; }
            .totals td { padding: 3px 10px; }
            .totals .grand-total { font-size: 16px; font-weight: bold; color: #050C18; }
            .footer { margin-top: 40px; text-align: center; color: #999; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
            .estado { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 11px; font-weight: bold; }
            .estado-pendiente { background: #ffa502; color: white; }
            .estado-aprobada { background: #2ed573; color: white; }
            .estado-rechazada { background: #ff4757; color: white; }
            .estado-vencida { background: #95a5a6; color: white; }
            .estado-convertida { background: #3498db; color: white; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>COTIZACIÓN</h1>
            <p>Proyectos Industriales del Centro (PIC)</p>
            <p>RIF: J-29384799-0 | Tel: 0414-3417373</p>
        </div>

        <div class="info">
            <table>
                <tr><td class="label">N° Cotización:</td><td>' . htmlspecialchars($c['numero_cotizacion']) . '</td></tr>
                <tr><td class="label">Fecha:</td><td>' . date('d/m/Y', strtotime($c['fecha_creacion'])) . '</td></tr>
                <tr><td class="label">Vencimiento:</td><td>' . ($c['fecha_vencimiento'] ? date('d/m/Y', strtotime($c['fecha_vencimiento'])) : 'N/A') . '</td></tr>
                <tr><td class="label">Estado:</td><td><span class="estado estado-' . $c['estado'] . '">' . ucfirst($c['estado']) . '</span></td></tr>
                <tr><td class="label">Vendedor:</td><td>' . htmlspecialchars($c['usuario_nombre'] ?? '') . '</td></tr>
            </table>
        </div>

        <div class="info">
            <h3>Cliente</h3>
            <table>
                <tr><td class="label">Nombre:</td><td>' . htmlspecialchars($c['cliente_nombre']) . '</td></tr>
                <tr><td class="label">Email:</td><td>' . htmlspecialchars($c['cliente_email'] ?? '') . '</td></tr>
                <tr><td class="label">Teléfono:</td><td>' . htmlspecialchars($c['cliente_telefono'] ?? '') . '</td></tr>
                <tr><td class="label">Dirección:</td><td>' . htmlspecialchars($c['cliente_direccion'] ?? '') . '</td></tr>
            </table>
        </div>

        <h3>Productos</h3>
        <table class="items">
            <thead>
                <tr><th>#</th><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr>
            </thead>
            <tbody>';
    $i = 1;
    foreach ($detalles as $d) {
        $html .= '<tr><td>' . $i++ . '</td><td>' . htmlspecialchars($d['producto_nombre']) . '</td><td>' . $d['cantidad'] . '</td><td>Bs. ' . number_format($d['precio_unitario'], 2) . '</td><td>Bs. ' . number_format($d['subtotal'], 2) . '</td></tr>';
    }
    $html .= '
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr><td>Subtotal:</td><td>Bs. ' . number_format($c['subtotal'], 2) . '</td></tr>
                <tr><td>IVA:</td><td>Bs. ' . number_format($c['iva'], 2) . '</td></tr>
                <tr class="grand-total"><td>Total:</td><td>Bs. ' . number_format($c['total'], 2) . '</td></tr>
            </table>
        </div>';

    if (!empty($c['notas'])) {
        $html .= '<div style="margin-top:20px;padding:10px;background:#f5f5f5;border-radius:4px"><strong>Notas:</strong><br>' . nl2br(htmlspecialchars($c['notas'])) . '</div>';
    }

    if (!empty($c['seguimiento'])) {
        $html .= '<div style="margin-top:20px"><strong>Seguimiento:</strong><br><pre style="font-size:11px;background:#f9f9f9;padding:10px;border-radius:4px">' . htmlspecialchars($c['seguimiento']) . '</pre></div>';
    }

    $html .= '
        <div class="footer">
            <p>Documento generado el ' . date('d/m/Y H:i:s') . ' | PIC Sistema de Gestión Comercial</p>
            <p>Este documento no constituye una factura hasta su conversión oficial</p>
        </div>
    </body></html>';

    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $dompdf->stream("Cotizacion_" . $c['numero_cotizacion'] . ".pdf", ["Attachment" => true]);

} catch (Throwable $e) {
    die('Error generando PDF: ' . $e->getMessage());
}
