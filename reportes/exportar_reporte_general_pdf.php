<?php
// /proyecto/reportes/exportar_reporte_general_pdf.php
session_start();

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

require_once '../conexion/conexion.php';

function formatMoney($value) {
    return 'Bs. ' . number_format($value, 2, ',', '.');
}

function getMetodoPagoTexto($metodo) {
    $metodos = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia Bancaria',
        'pago_movil' => 'Pago Móvil',
        'mixto' => 'Pago Mixto',
        'tarjeta' => 'Tarjeta'
    ];
    $metodo = strtolower(trim($metodo));
    foreach ($metodos as $key => $texto) {
        if (strpos($metodo, $key) !== false) {
            return $texto;
        }
    }
    return ucfirst($metodo) ?: 'No especificado';
}

$database = new Database();
$db = $database->getConnection();

// Ventas totales
$query = "SELECT SUM(total) as total FROM pedidos WHERE estado = 'completado' OR estado = 'facturado'";
$stmt = $db->prepare($query);
$stmt->execute();
$ventas_totales = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ventas del mes
$query = "SELECT SUM(total) as total FROM pedidos WHERE (estado = 'completado' OR estado = 'facturado') AND MONTH(fecha_pedido) = MONTH(CURDATE()) AND YEAR(fecha_pedido) = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$ventas_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ventas de la semana
$query = "SELECT SUM(total) as total FROM pedidos WHERE (estado = 'completado' OR estado = 'facturado') AND YEARWEEK(fecha_pedido) = YEARWEEK(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$ventas_semana = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ticket promedio
$query = "SELECT AVG(total) as promedio FROM pedidos WHERE estado = 'completado' OR estado = 'facturado'";
$stmt = $db->prepare($query);
$stmt->execute();
$ticket_promedio = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0;

// Clientes activos
$query = "SELECT COUNT(DISTINCT usuario_id) as total FROM pedidos WHERE estado = 'completado' OR estado = 'facturado'";
$stmt = $db->prepare($query);
$stmt->execute();
$clientes_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Ventas por mes (últimos 6 meses) - CORREGIDO para only_full_group_by
$query = "SELECT 
            DATE_FORMAT(fecha_pedido, '%Y-%m') as mes, 
            DATE_FORMAT(fecha_pedido, '%b %Y') as mes_nombre, 
            SUM(total) as total 
          FROM pedidos 
          WHERE (estado = 'completado' OR estado = 'facturado') 
            AND fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
          GROUP BY DATE_FORMAT(fecha_pedido, '%Y-%m'), DATE_FORMAT(fecha_pedido, '%b %Y')
          ORDER BY mes ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$ventas_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 5 productos
$query = "SELECT p.name as nombre, SUM(pd.cantidad) as unidades, SUM(pd.subtotal) as ingresos 
          FROM pedido_detalles pd 
          JOIN products p ON pd.producto_id = p.id 
          JOIN pedidos ped ON pd.pedido_id = ped.id 
          WHERE ped.estado = 'completado' OR ped.estado = 'facturado'
          GROUP BY p.id, p.name
          ORDER BY unidades DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$top_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 5 clientes
$query = "SELECT u.nombre, COUNT(p.id) as total_compras, SUM(p.total) as monto_total 
          FROM pedidos p 
          JOIN users u ON p.usuario_id = u.id 
          WHERE p.estado = 'completado' OR p.estado = 'facturado'
          GROUP BY u.id, u.nombre
          ORDER BY monto_total DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Métodos de pago
$query = "SELECT metodo_pago, COUNT(*) as cantidad, SUM(total) as total 
          FROM pedidos 
          WHERE estado = 'completado' OR estado = 'facturado' 
          GROUP BY metodo_pago";
$stmt = $db->prepare($query);
$stmt->execute();
$metodos_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular crecimiento
$query_mes_anterior = "SELECT SUM(total) as total FROM pedidos WHERE (estado = 'completado' OR estado = 'facturado') 
                       AND MONTH(fecha_pedido) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
                       AND YEAR(fecha_pedido) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
$stmt = $db->prepare($query_mes_anterior);
$stmt->execute();
$ventas_mes_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$crecimiento = 0;
if ($ventas_mes_anterior > 0) {
    $crecimiento = (($ventas_mes - $ventas_mes_anterior) / $ventas_mes_anterior) * 100;
}

// Generar HTML del PDF
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte General Ejecutivo</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #3C91ED; }
        .header h1 { color: #050C18; margin: 0; }
        .header p { color: #666; margin: 5px 0 0; }
        .kpi-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 30px; }
        .kpi-card { background: linear-gradient(135deg, #050C18, #294E90); color: white; border-radius: 10px; padding: 15px; text-align: center; }
        .kpi-value { font-size: 20px; font-weight: bold; margin: 10px 0; }
        .kpi-label { font-size: 11px; opacity: 0.9; }
        .section-title { background: #3C91ED; color: white; padding: 8px 12px; margin: 20px 0 10px; font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PIC - Reporte General Ejecutivo</h1>
        <p>Fecha de generación: ' . date('d/m/Y H:i:s') . '</p>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card"><div>💰</div><div class="kpi-value">' . formatMoney($ventas_totales) . '</div><div class="kpi-label">Ventas Totales</div></div>
        <div class="kpi-card"><div>📈</div><div class="kpi-value">' . formatMoney($ventas_mes) . '</div><div class="kpi-label">Ventas del Mes</div></div>
        <div class="kpi-card"><div>📊</div><div class="kpi-value">' . formatMoney($ventas_semana) . '</div><div class="kpi-label">Ventas de la Semana</div></div>
        <div class="kpi-card"><div>📈</div><div class="kpi-value">' . number_format($crecimiento, 1) . '%</div><div class="kpi-label">Crecimiento vs Mes Anterior</div></div>
        <div class="kpi-card"><div>🎫</div><div class="kpi-value">' . formatMoney($ticket_promedio) . '</div><div class="kpi-label">Ticket Promedio</div></div>
        <div class="kpi-card"><div>👥</div><div class="kpi-value">' . $clientes_activos . '</div><div class="kpi-label">Clientes Activos</div></div>
    </div>

    <div class="section-title">📅 Ventas por Mes</div>
    <table><thead><tr><th>Mes</th><th class="text-right">Monto</th></tr></thead><tbody>';
foreach ($ventas_por_mes as $v) {
    $html .= '<tr><td>' . htmlspecialchars($v['mes_nombre']) . '</td><td class="text-right">' . formatMoney($v['total']) . '</td></tr>';
}
$html .= '</tbody></table>

    <div class="section-title">🏆 Top 5 Productos Más Vendidos</div>
    <table><thead><tr><th>Producto</th><th class="text-right">Unidades</th><th class="text-right">Ingresos</th></tr></thead><tbody>';
foreach ($top_productos as $p) {
    $html .= '<tr><td>' . htmlspecialchars($p['nombre']) . '</td><td class="text-right">' . $p['unidades'] . '</td><td class="text-right">' . formatMoney($p['ingresos']) . '</td></tr>';
}
$html .= '</tbody></table>

    <div class="section-title">⭐ Top 5 Clientes</div>
    <table><thead><tr><th>Cliente</th><th class="text-right">Compras</th><th class="text-right">Monto Total</th></tr></thead><tbody>';
foreach ($top_clientes as $c) {
    $html .= '<tr><td>' . htmlspecialchars($c['nombre']) . '</td><td class="text-right">' . $c['total_compras'] . '</td><td class="text-right">' . formatMoney($c['monto_total']) . '</td></tr>';
}
$html .= '</tbody></table>

    <div class="section-title">💳 Distribución por Método de Pago</div>
    <table><thead><tr><th>Método de Pago</th><th class="text-right">Cantidad</th><th class="text-right">Total</th></tr></thead><tbody>';
foreach ($metodos_pago as $m) {
    $html .= '<tr><td>' . getMetodoPagoTexto($m['metodo_pago']) . '</td><td class="text-right">' . $m['cantidad'] . '</td><td class="text-right">' . formatMoney($m['total']) . '</td></tr>';
}
$html .= '</tbody></table>

    <div class="footer"><p>Este reporte fue generado automáticamente por el sistema PIC</p></div>
</body>
</html>';

echo $html;
echo '<div style="text-align:center; margin-top:20px;">
        <button onclick="window.print()" style="padding:10px 20px; background:#3C91ED; color:white; border:none; border-radius:5px; cursor:pointer;">📄 Imprimir / Guardar como PDF</button>
      </div>';
?>