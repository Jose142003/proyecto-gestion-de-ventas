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
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';

// Obtener facturas
$sql = "SELECT f.*, c.nombre as cliente_nombre, c.documento as cliente_documento 
        FROM facturas f 
        LEFT JOIN clientes c ON f.cliente_id = c.id 
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (f.numero_factura LIKE ? OR c.nombre LIKE ? OR c.documento LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($estado) && $estado != 'todos') {
    $sql .= " AND f.estado = ?";
    $params[] = $estado;
}

$sql .= " ORDER BY f.fecha_emision DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facturas = $stmt->fetchAll();

if ($type === 'excel') {
    exportarExcel($facturas);
} else {
    exportarPDF($facturas);
}

function exportarExcel($facturas) {
    // Crear archivo Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="facturas_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr>';
    echo '<th colspan="8" style="background-color: #3C91ED; color: white; padding: 10px; font-size: 16px;">LISTADO DE FACTURAS</th>';
    echo '</tr>';
    echo '<tr>';
    echo '<th>N° Factura</th>';
    echo '<th>Cliente</th>';
    echo '<th>Documento</th>';
    echo '<th>Fecha Emisión</th>';
    echo '<th>Total</th>';
    echo '<th>Estado</th>';
    echo '<th>Método Pago</th>';
    echo '<th>Observaciones</th>';
    echo '</tr>';
    
    $total_general = 0;
    foreach ($facturas as $factura) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($factura['numero_factura']) . '</td>';
        echo '<td>' . htmlspecialchars($factura['cliente_nombre']) . '</td>';
        echo '<td>' . htmlspecialchars($factura['cliente_documento']) . '</td>';
        echo '<td>' . date('d/m/Y', strtotime($factura['fecha_emision'])) . '</td>';
        echo '<td>Bs. ' . number_format($factura['total'], 2) . '</td>';
        echo '<td>' . ucfirst($factura['estado']) . '</td>';
        echo '<td>' . ucfirst($factura['metodo_pago']) . '</td>';
        echo '<td>' . htmlspecialchars(substr($factura['observaciones'] ?? '', 0, 50)) . '</td>';
        echo '</tr>';
        
        if ($factura['estado'] === 'pagada') {
            $total_general += $factura['total'];
        }
    }
    
    echo '<tr>';
    echo '<td colspan="4" style="text-align: right; font-weight: bold;">TOTAL VENTAS:</td>';
    echo '<td colspan="4" style="font-weight: bold; color: green;">Bs. ' . number_format($total_general, 2) . '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td colspan="8" style="font-size: 11px; color: #666; padding-top: 20px;">';
    echo 'Generado el: ' . date('d/m/Y H:i:s') . ' | Total registros: ' . count($facturas);
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    exit;
}

function exportarPDF($facturas) {
    // Para PDF necesitarías una librería como TCPDF o FPDF
    // Aquí un ejemplo básico que crea HTML que se puede guardar como PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="facturas_' . date('Y-m-d') . '.html"');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Facturas</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #3C91ED; }
            .info { margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            th { background-color: #3C91ED; color: white; padding: 10px; text-align: left; }
            td { padding: 8px; border-bottom: 1px solid #ddd; }
            .total { font-weight: bold; color: green; }
            .footer { margin-top: 30px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>LISTADO DE FACTURAS</h1>
            <p>Sistema de Facturación PIC</p>
        </div>
        
        <div class="info">
            <p><strong>Fecha de generación:</strong> ' . date('d/m/Y H:i:s') . '</p>
            <p><strong>Total de registros:</strong> ' . count($facturas) . '</p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>N° Factura</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Método Pago</th>
                </tr>
            </thead>
            <tbody>';
    
    $total_general = 0;
    foreach ($facturas as $factura) {
        $html .= '
                <tr>
                    <td>' . htmlspecialchars($factura['numero_factura']) . '</td>
                    <td>' . htmlspecialchars($factura['cliente_nombre']) . '</td>
                    <td>' . date('d/m/Y', strtotime($factura['fecha_emision'])) . '</td>
                    <td>Bs. ' . number_format($factura['total'], 2) . '</td>
                    <td>' . ucfirst($factura['estado']) . '</td>
                    <td>' . ucfirst($factura['metodo_pago']) . '</td>
                </tr>';
        
        if ($factura['estado'] === 'pagada') {
            $total_general += $factura['total'];
        }
    }
    
    $html .= '
            </tbody>
        </table>
        
        <div class="footer">
            <p><strong>Total ventas:</strong> <span class="total">Bs. ' . number_format($total_general, 2) . '</span></p>
            <p>Sistema de Facturación PIC - ' . date('Y') . '</p>
        </div>
    </body>
    </html>';
    
    echo $html;
    exit;
}
?>