<?php
// /proyecto/compras/generar_pdf_compra.php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Acceso no autorizado');
}

$compra_id = $_GET['id'] ?? 0;
if (!$compra_id) {
    die('ID de compra no válido');
}

require_once '../conexion/conexion.php';

function formatMoney($value) {
    return 'Bs. ' . number_format($value, 2, ',', '.');
}

function formatDate($date) {
    if (!$date || $date == '0000-00-00') return 'N/A';
    return date('d/m/Y', strtotime($date));
}

$db = conectarDB();

// Obtener datos de la compra
$query = "SELECT c.*, p.nombre_comercial as proveedor_nombre, p.ruc as proveedor_ruc, 
                 p.telefono_principal as proveedor_telefono, p.email_principal as proveedor_email
          FROM compras c
          JOIN proveedores p ON c.proveedor_id = p.id
          WHERE c.id = :id";
$stmt = $db->prepare($query);
$stmt->execute([':id' => $compra_id]);
$compra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compra) {
    die('Compra no encontrada');
}

// Obtener detalles de la compra - CORREGIDO: usa compra_detalles
$query_detalle = "SELECT dc.*, pr.name as producto_nombre
                  FROM compra_detalles dc
                  JOIN products pr ON dc.producto_id = pr.id
                  WHERE dc.compra_id = :id";
$stmt = $db->prepare($query_detalle);
$stmt->execute([':id' => $compra_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Generar HTML
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra #' . $compra['numero_orden'] . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #3C91ED; }
        .header h1 { color: #050C18; margin: 0; }
        .compra-header { background: linear-gradient(135deg, #050C18, #294E90); color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .info-section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .info-row { display: flex; justify-content: space-between; padding: 5px 0; border-bottom: 1px dashed #eee; }
        .info-label { font-weight: bold; width: 120px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #3C91ED; color: white; }
        .text-right { text-align: right; }
        .totales { text-align: right; padding: 15px; background: #f5f5f5; border-radius: 8px; margin-top: 15px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PIC - Sistema de Gestión</h1>
        <p>Orden de Compra</p>
    </div>
    
    <div class="compra-header">
        <h2>Orden de Compra N°: ' . htmlspecialchars($compra['numero_orden']) . '</h2>
        <p>Fecha: ' . formatDate($compra['fecha_orden']) . '</p>
    </div>
    
    <div class="info-section">
        <h3>Información del Proveedor</h3>
        <div class="info-row"><span class="info-label">Nombre:</span><span>' . htmlspecialchars($compra['proveedor_nombre']) . '</span></div>
        <div class="info-row"><span class="info-label">RUC:</span><span>' . htmlspecialchars($compra['proveedor_ruc'] ?? 'N/A') . '</span></div>
        <div class="info-row"><span class="info-label">Teléfono:</span><span>' . htmlspecialchars($compra['proveedor_telefono'] ?? 'N/A') . '</span></div>
        <div class="info-row"><span class="info-label">Email:</span><span>' . htmlspecialchars($compra['proveedor_email'] ?? 'N/A') . '</span></div>
    </div>
    
    <div class="info-section">
        <h3>Detalle de la Compra</h3>
        <table>
            <thead><tr><th>Producto</th><th class="text-right">Cantidad</th><th class="text-right">Precio Unitario</th><th class="text-right">Subtotal</th></tr></thead>
            <tbody>';
            
foreach ($detalles as $d) {
    $html .= '<tr>
                <td>' . htmlspecialchars($d['producto_nombre']) . '</td>
                <td class="text-right">' . $d['cantidad'] . '</td>
                <td class="text-right">' . formatMoney($d['precio_unitario']) . '</td>
                <td class="text-right">' . formatMoney($d['subtotal']) . '</td>
              </tr>';
}

$html .= '
            </tbody>
        </table>
        
        <div class="totales">
            <div class="info-row"><span class="info-label">Subtotal:</span><span>' . formatMoney($compra['subtotal']) . '</span></div>
            <div class="info-row"><span class="info-label">IVA (16%):</span><span>' . formatMoney($compra['iva'] ?? 0) . '</span></div>
            <div class="info-row" style="font-size: 16px; font-weight: bold;"><span class="info-label">TOTAL:</span><span>' . formatMoney($compra['total']) . '</span></div>
        </div>
    </div>
    
    <div class="info-section">
        <h3>Información Adicional</h3>
        <div class="info-row"><span class="info-label">Estado:</span><span>' . ($compra['estado'] == 'recibida_total' ? 'Completada' : ucfirst($compra['estado'])) . '</span></div>
        <div class="info-row"><span class="info-label">Observaciones:</span><span>' . htmlspecialchars($compra['observaciones'] ?? 'Ninguna') . '</span></div>
    </div>
    
    <div class="footer">
        <p>Documento generado automáticamente por el sistema PIC</p>
        <p>Fecha de impresión: ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';

echo $html;
echo '<div style="text-align:center; margin-top:20px; position:fixed; bottom:20px; left:0; right:0;">
        <button onclick="window.print()" style="padding:10px 20px; background:#3C91ED; color:white; border:none; border-radius:5px; cursor:pointer;">
            🖨️ Imprimir / Guardar como PDF
        </button>
        <button onclick="window.close()" style="padding:10px 20px; background:#666; color:white; border:none; border-radius:5px; cursor:pointer; margin-left:10px;">
            ❌ Cerrar
        </button>
      </div>';
?>