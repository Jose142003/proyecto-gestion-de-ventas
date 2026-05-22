<?php
// /proyecto/reportes/exportar_especifico_pdf.php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

require_once '../conexion/conexion.php';

function formatMoney($value) {
    return 'Bs. ' . number_format($value, 2, ',', '.');
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

$db = Database::getConnection();

$desde = $_GET['desde'] ?? '';
$hasta = $_GET['hasta'] ?? '';
$tipo = $_GET['tipo'] ?? 'ventas';
$estado = $_GET['estado'] ?? '';
$buscar = $_GET['buscar'] ?? '';

$data = [];
$titulo = '';
$params = [];

switch ($tipo) {
    case 'ventas':
        $titulo = 'Reporte de Ventas';
        $query = "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.fecha_pedido as fecha, p.total, p.estado, p.metodo_pago,
                         (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
                  FROM pedidos p
                  JOIN users u ON p.usuario_id = u.id
                  WHERE 1=1";
        if ($desde) { $query .= " AND DATE(p.fecha_pedido) >= ?"; $params[] = $desde; }
        if ($hasta) { $query .= " AND DATE(p.fecha_pedido) <= ?"; $params[] = $hasta; }
        if ($estado) { $query .= " AND p.estado = ?"; $params[] = $estado; }
        if ($buscar) { $query .= " AND (u.nombre LIKE ? OR p.numero_pedido LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
        $query .= " ORDER BY p.fecha_pedido DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'compras':
        $titulo = 'Reporte de Compras';
        $query = "SELECT c.id, c.numero_orden, p.nombre_comercial as proveedor, c.fecha_orden as fecha,
                         c.subtotal, c.total, c.estado
                  FROM compras c
                  JOIN proveedores p ON c.proveedor_id = p.id
                  WHERE 1=1";
        $params = [];
        if ($desde) { $query .= " AND DATE(c.fecha_orden) >= ?"; $params[] = $desde; }
        if ($hasta) { $query .= " AND DATE(c.fecha_orden) <= ?"; $params[] = $hasta; }
        if ($estado) { $query .= " AND c.estado = ?"; $params[] = $estado; }
        if ($buscar) { $query .= " AND (p.nombre_comercial LIKE ? OR c.numero_orden LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
        $query .= " ORDER BY c.fecha_orden DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;
        
    case 'pedidos':
        $titulo = 'Reporte de Pedidos';
        $query = "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.fecha_pedido as fecha, p.total, p.estado, p.metodo_pago,
                         (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as productos
                  FROM pedidos p
                  JOIN users u ON p.usuario_id = u.id
                  WHERE 1=1";
        $params = [];
        if ($desde) { $query .= " AND DATE(p.fecha_pedido) >= ?"; $params[] = $desde; }
        if ($hasta) { $query .= " AND DATE(p.fecha_pedido) <= ?"; $params[] = $hasta; }
        if ($estado) { $query .= " AND p.estado = ?"; $params[] = $estado; }
        if ($buscar) { $query .= " AND (u.nombre LIKE ? OR p.numero_pedido LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
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

$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $titulo . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #3C91ED; }
        .header h1 { color: #050C18; margin: 0; }
        .filtros { background: #f5f5f5; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 10px; }
        .resumen { display: flex; justify-content: space-between; background: linear-gradient(135deg, #050C18, #294E90); color: white; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .resumen-item { text-align: center; flex: 1; }
        .resumen-valor { font-size: 18px; font-weight: bold; }
        .resumen-label { font-size: 10px; opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #3C91ED; color: white; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 9px; color: #666; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header"><h1>' . $titulo . '</h1><p>Fecha: ' . date('d/m/Y H:i:s') . '</p></div>
    <div class="filtros"><strong>Filtros:</strong> Período: ' . ($desde ? date('d/m/Y', strtotime($desde)) : 'Todo') . ' - ' . ($hasta ? date('d/m/Y', strtotime($hasta)) : 'Todo') . ' | Estado: ' . ($estado ? getEstadoTexto($estado) : 'Todos') . ' | Búsqueda: ' . ($buscar ? htmlspecialchars($buscar) : 'Ninguna') . '</div>
    <div class="resumen">
        <div class="resumen-item"><div class="resumen-valor">' . $total_registros . '</div><div class="resumen-label">Total Registros</div></div>
        <div class="resumen-item"><div class="resumen-valor">' . formatMoney($total_monto) . '</div><div class="resumen-label">Monto Total</div></div>
        <div class="resumen-item"><div class="resumen-valor">' . formatMoney($promedio) . '</div><div class="resumen-label">Promedio</div></div>
    </div>
    <table><thead><tr>';
if ($tipo == 'ventas' || $tipo == 'pedidos') {
    $html .= '<th>ID</th><th>N° Pedido</th><th>Cliente</th><th>Fecha</th><th>Total</th><th>Estado</th><th>Método Pago</th><th>Productos</th>';
} elseif ($tipo == 'compras') {
    $html .= '<th>ID</th><th>N° Orden</th><th>Proveedor</th><th>Fecha</th><th>Subtotal</th><th>Total</th><th>Estado</th>';
}
$html .= '</tr></thead><tbody>';

foreach ($data as $row) {
    $html .= '<tr>';
    if ($tipo == 'ventas' || $tipo == 'pedidos') {
        $html .= '<td>' . $row['id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($row['numero_pedido']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['cliente']) . '</td>';
        $html .= '<td>' . formatDate($row['fecha']) . '</td>';
        $html .= '<td class="text-right">' . formatMoney($row['total']) . '</td>';
        $html .= '<td>' . getEstadoTexto($row['estado']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['metodo_pago'] ?? 'N/A') . '</td>';
        $html .= '<td class="text-right">' . ($row['productos'] ?? 0) . '</td>';
    } elseif ($tipo == 'compras') {
        $html .= '<td>' . $row['id'] . '</td>';
        $html .= '<td>' . htmlspecialchars($row['numero_orden']) . '</td>';
        $html .= '<td>' . htmlspecialchars($row['proveedor']) . '</td>';
        $html .= '<td>' . formatDate($row['fecha']) . '</td>';
        $html .= '<td class="text-right">' . formatMoney($row['subtotal']) . '</td>';
        $html .= '<td class="text-right">' . formatMoney($row['total']) . '</td>';
        $html .= '<td>' . getEstadoTexto($row['estado']) . '</td>';
    }
    $html .= '</tr>';
}

$html .= '</tbody></table>
    <div class="footer"><p>Reporte generado por el sistema PIC</p></div>
</body>
</html>';

echo $html;
echo '<div style="text-align:center; margin-top:20px;">
        <button onclick="window.print()" style="padding:10px 20px; background:#3C91ED; color:white; border:none; border-radius:5px; cursor:pointer;">📄 Imprimir / Guardar como PDF</button>
      </div>';
?>