<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    header('Location: ' . url('/interfaz_usuario/login.html'));
    exit;
}

$factura_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($factura_id <= 0) {
    die('ID de factura inválido');
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Obtener información de la factura
    $stmt = $pdo->prepare("
        SELECT f.*, 
               c.nombre as cliente_nombre, 
               c.documento as cliente_documento,
               c.email as cliente_email,
               c.telefono as cliente_telefono,
               c.direccion as cliente_direccion,
               c.ciudad as cliente_ciudad,
               u.nombre as vendedor_nombre
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN users u ON f.usuario_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$factura_id]);
    $factura = $stmt->fetch();
    
    if (!$factura) {
        die("<h2>Error: Factura no encontrada</h2>");
    }

    // Verificar propiedad: si no es admin, debe ser su factura
    $userId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? 0;
    if (!esAdmin() && (!isset($factura['usuario_id']) || $factura['usuario_id'] != $userId)) {
        die("<h2>Error: No autorizado</h2>");
    }
    
    // Obtener detalles de la factura
    $stmt = $pdo->prepare("
        SELECT fd.*, 
               p.name as producto_nombre, 
               p.sku,
               p.category as categoria
        FROM factura_detalles fd
        LEFT JOIN products p ON fd.producto_id = p.id
        WHERE fd.factura_id = ?
        ORDER BY fd.id
    ");
    $stmt->execute([$factura_id]);
    $detalles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Error en generar_pdf_pedido: " . $e->getMessage());
    die("<h2>Error interno del servidor</h2>");
}

// Función para número a letras
function numeroALetras($numero) {
    $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    $entero = floor($numero);
    $decimal = round(($numero - $entero) * 100);
    
    $texto = ucfirst($formatter->format($entero)) . " BOLÍVARES";
    
    if ($decimal > 0) {
        $texto .= " CON " . $formatter->format($decimal) . " CÉNTIMOS";
    }
    
    return $texto;
}

// Función para formatear fecha
function formatearFecha($fecha) {
    if (empty($fecha)) return 'No especificada';
    return date('d/m/Y', strtotime($fecha));
}

// Configurar cabeceras para PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura <?php echo htmlspecialchars($factura['numero_factura']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            padding: 20px;
        }
        
        .invoice {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
        }
        
        /* Header */
        .header {
            border-bottom: 3px solid #1e3c72;
            padding-bottom: 15px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .company-info {
            float: left;
            width: 60%;
        }
        
        .company-info h1 {
            font-size: 24px;
            color: #1e3c72;
            margin-bottom: 5px;
        }
        
        .company-info p {
            font-size: 10px;
            color: #666;
            margin: 2px 0;
        }
        
        .invoice-info {
            float: right;
            width: 35%;
            text-align: right;
        }
        
        .invoice-info h2 {
            font-size: 28px;
            color: #1e3c72;
            margin-bottom: 5px;
        }
        
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            background: #f0f0f0;
            padding: 4px 10px;
            display: inline-block;
            border-radius: 5px;
        }
        
        .estado {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .estado-pagada { background: #28a745; color: white; }
        .estado-pendiente { background: #ffc107; color: #333; }
        .estado-anulada { background: #dc3545; color: white; }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .info-box {
            border: 1px solid #ddd;
            padding: 12px;
            border-radius: 8px;
            background: #f9f9f9;
        }
        
        .info-box h3 {
            font-size: 12px;
            color: #1e3c72;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 6px;
            font-size: 10px;
        }
        
        .info-label {
            font-weight: bold;
            width: 100px;
            color: #666;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        /* Tabla de productos */
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .products-table th {
            background: #1e3c72;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        
        .products-table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            font-size: 10px;
        }
        
        .products-table tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        /* Totales */
        .totales {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #1e3c72;
            text-align: right;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }
        
        .total-label {
            font-weight: bold;
            width: 120px;
        }
        
        .total-value {
            width: 120px;
            text-align: right;
        }
        
        .total-grande {
            font-size: 14px;
            font-weight: bold;
            color: #1e3c72;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        
        .amount-words {
            margin-top: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-left: 3px solid #1e3c72;
            font-size: 10px;
            text-align: left;
        }
        
        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #999;
        }
        
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="invoice">
        <!-- HEADER -->
        <div class="header">
            <div class="company-info">
                <h1>PIC SISTEMA</h1>
                <p>Proyectos Industriales del Centro, C.A.</p>
                <p>RIF: J-12345678-9</p>
                <p>Av. Principal, Zona Industrial, Valencia, Venezuela</p>
                <p>Teléfono: 0424-8393902 | Email: picca.ventas@gmail.com</p>
            </div>
            <div class="invoice-info">
                <h2>FACTURA</h2>
                <div class="invoice-number">Nº <?php echo htmlspecialchars($factura['numero_factura']); ?></div>
                <div class="estado <?php echo 'estado-' . $factura['estado']; ?>">
                    <?php echo strtoupper($factura['estado']); ?>
                </div>
            </div>
        </div>
        
        <!-- INFORMACIÓN -->
        <div class="info-grid">
            <div class="info-box">
                <h3>DATOS DEL CLIENTE</h3>
                <div class="info-row">
                    <span class="info-label">Nombre:</span>
                    <span class="info-value"><?php echo htmlspecialchars($factura['cliente_nombre'] ?? 'No especificado'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Documento:</span>
                    <span class="info-value"><?php echo htmlspecialchars($factura['cliente_documento'] ?? 'No especificado'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?php echo htmlspecialchars($factura['cliente_email'] ?? 'No especificado'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Teléfono:</span>
                    <span class="info-value"><?php echo htmlspecialchars($factura['cliente_telefono'] ?? 'No especificado'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dirección:</span>
                    <span class="info-value"><?php echo htmlspecialchars($factura['cliente_direccion'] ?? 'No especificada'); ?></span>
                </div>
            </div>
            <div class="info-box">
                <h3>DATOS DE LA FACTURA</h3>
                <div class="info-row">
                    <span class="info-label">Fecha Emisión:</span>
                    <span class="info-value"><?php echo formatearFecha($factura['fecha_emision']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fecha Vencimiento:</span>
                    <span class="info-value"><?php echo formatearFecha($factura['fecha_vencimiento']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Método Pago:</span>
                    <span class="info-value"><?php echo strtoupper($factura['metodo_pago'] ?? 'No especificado'); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Vendedor:</span>
                    <span class="info-value"><?php echo htmlspecialchars($factura['vendedor_nombre'] ?? 'Sistema'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- PRODUCTOS -->
        <table class="products-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="45%">Descripción</th>
                    <th width="15%">Código</th>
                    <th width="10%">Cant.</th>
                    <th width="12%">Precio Unit.</th>
                    <th width="13%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detalles)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No hay productos registrados</td>
                </tr>
                <?php else: ?>
                    <?php foreach ($detalles as $index => $detalle): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td>
                            <?php echo htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible'); ?>
                            <?php if (!empty($detalle['categoria'])): ?>
                            <br><small style="color:#999;"><?php echo htmlspecialchars($detalle['categoria']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($detalle['sku'] ?? 'N/A'); ?></td>
                        <td class="text-right"><?php echo number_format($detalle['cantidad'] ?? 0); ?></td>
                        <td class="text-right">Bs. <?php echo number_format($detalle['precio_unitario'] ?? 0, 2); ?></td>
                        <td class="text-right">Bs. <?php echo number_format($detalle['subtotal'] ?? 0, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- TOTALES -->
        <div class="totales">
            <div class="total-row">
                <span class="total-label">SUBTOTAL:</span>
                <span class="total-value">Bs. <?php echo number_format($factura['subtotal'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row">
                <span class="total-label">IVA (16%):</span>
                <span class="total-value">Bs. <?php echo number_format($factura['iva'] ?? 0, 2); ?></span>
            </div>
            <div class="total-row total-grande">
                <span class="total-label">TOTAL:</span>
                <span class="total-value">Bs. <?php echo number_format($factura['total'] ?? 0, 2); ?></span>
            </div>
        </div>
        
        <div class="amount-words">
            <strong>SON:</strong> <?php echo numeroALetras($factura['total'] ?? 0); ?>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <p>Esta factura es un documento de carácter fiscal y representa un comprobante válido de venta.</p>
            <p>Documento generado electrónicamente el <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>© <?php echo date('Y'); ?> Proyectos Industriales del Centro (PIC). Todos los derechos reservados.</p>
        </div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; margin: 0 5px; cursor: pointer;">🖨️ Imprimir</button>
        <button onclick="window.close()" style="padding: 10px 20px; margin: 0 5px; cursor: pointer;">❌ Cerrar</button>
    </div>
    
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>