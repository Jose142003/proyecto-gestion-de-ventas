<?php
// enviar_factura_email.php - VERSIÓN CORREGIDA Y MEJORADA
session_start();
require_once 'PHPMailer.php';
require_once 'SMTP.php';
require_once 'Exception.php';
require_once 'config_email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
$pdo = conectarDB();

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$factura_id = $data['factura_id'] ?? 0;
$email_personalizado = $data['email'] ?? null;

if (!$factura_id) {
    echo json_encode(['success' => false, 'message' => 'ID de factura no proporcionado']);
    exit;
}

try {
    // Obtener información de la factura
    $stmt = $pdo->prepare("
        SELECT f.*, 
               c.nombre as cliente_nombre, 
               c.email as cliente_email,
               c.documento as cliente_documento,
               c.telefono as cliente_telefono,
               c.direccion as cliente_direccion,
               c.ciudad as cliente_ciudad,
               u.nombre as vendedor_nombre,
               u.correo as vendedor_email
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN users u ON f.usuario_id = u.id
        WHERE f.id = ?
    ");
    $stmt->execute([$factura_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        echo json_encode(['success' => false, 'message' => 'Factura no encontrada']);
        exit;
    }

    // Obtener detalles de la factura
    $stmt = $pdo->prepare("
        SELECT fd.*, p.name as producto_nombre, p.sku, p.category as categoria
        FROM factura_detalles fd
        LEFT JOIN products p ON fd.producto_id = p.id
        WHERE fd.factura_id = ?
    ");
    $stmt->execute([$factura_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay detalles en factura_detalles, buscar en pedido_detalles
    if (empty($detalles) && !empty($factura['pedido_id'])) {
        $stmt_pedido = $pdo->prepare("
            SELECT pd.*, p.name as producto_nombre, p.sku, p.category as categoria
            FROM pedido_detalles pd
            LEFT JOIN products p ON pd.producto_id = p.id
            WHERE pd.pedido_id = ?
        ");
        $stmt_pedido->execute([$factura['pedido_id']]);
        $detalles = $stmt_pedido->fetchAll(PDO::FETCH_ASSOC);
    }

    // Determinar destinatario
    $destinatario = null;
    $destinatario_nombre = null;
    
    if ($email_personalizado && filter_var($email_personalizado, FILTER_VALIDATE_EMAIL)) {
        $destinatario = $email_personalizado;
        $destinatario_nombre = 'Cliente';
    } elseif ($factura['cliente_email'] && filter_var($factura['cliente_email'], FILTER_VALIDATE_EMAIL)) {
        $destinatario = $factura['cliente_email'];
        $destinatario_nombre = $factura['cliente_nombre'] ?? 'Cliente';
    } elseif ($factura['vendedor_email'] && filter_var($factura['vendedor_email'], FILTER_VALIDATE_EMAIL)) {
        $destinatario = $factura['vendedor_email'];
        $destinatario_nombre = $factura['vendedor_nombre'] ?? 'Vendedor';
    }
    
    if (!$destinatario) {
        echo json_encode(['success' => false, 'message' => 'No hay una dirección de correo válida disponible']);
        exit;
    }
    
    // Registrar en log el intento de envío
    error_log("Intentando enviar factura #{$factura['numero_factura']} a: $destinatario");
    
    // Generar HTML de la factura
    $htmlFactura = generarHTMLFactura($factura, $detalles);
    $subject = 'Factura Electrónica #' . $factura['numero_factura'] . ' - PIC Sistema';
    
    // Usar la clase EmailSender
    $emailSender = new EmailSender($destinatario);
    $result = $emailSender->send($destinatario, $subject, $htmlFactura, 'PIC Sistema de Facturación');
    
    if ($result['success']) {
        // Registrar en auditoría
        registrarEnvioFactura($pdo, $factura_id, $destinatario, $result['provider'] ?? 'desconocido');
        
        echo json_encode([
            'success' => true, 
            'message' => 'Factura enviada correctamente a ' . $destinatario,
            'provider' => $result['provider'] ?? 'desconocido'
        ]);
    } else {
        error_log("Error al enviar factura #{$factura['numero_factura']}: " . $result['message']);
        echo json_encode(['success' => false, 'message' => 'Error al enviar: ' . $result['message']]);
    }

} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
} catch (PDOException $e) {
    error_log("Error en la base de datos: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Registra el envío de la factura en la auditoría
 */
function registrarEnvioFactura($pdo, $factura_id, $destinatario, $provider) {
    try {
        // Verificar si existe la tabla auditoria_logs
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'auditoria_logs'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO auditoria_logs (usuario_id, usuario_nombre, usuario_rol, accion, modulo, descripcion, ip_address, tabla_afectada, registro_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $usuario_id = $_SESSION['user_id'] ?? null;
            $usuario_nombre = $_SESSION['user_nombre'] ?? 'Sistema';
            $usuario_rol = $_SESSION['user_rol'] ?? 'sistema';
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
            
            $stmt->execute([
                $usuario_id,
                $usuario_nombre,
                $usuario_rol,
                'enviar_email',
                'facturas',
                "Factura #{$factura_id} enviada a {$destinatario} (proveedor: {$provider})",
                $ip_address,
                'facturas',
                $factura_id
            ]);
        }
    } catch (Exception $e) {
        error_log("Error al registrar en auditoría: " . $e->getMessage());
    }
}

/**
 * Genera el HTML de la factura para el correo electrónico
 */
function generarHTMLFactura($factura, $detalles) {
    // Determinar método de pago legible
    $metodos_pago = [
        'tarjeta' => 'TARJETA DE CRÉDITO/DÉBITO',
        'transferencia' => 'TRANSFERENCIA BANCARIA',
        'transferencia_bancaria' => 'TRANSFERENCIA BANCARIA',
        'efectivo' => 'EFECTIVO',
        'pago_movil' => 'PAGO MÓVIL',
        'pago movil' => 'PAGO MÓVIL',
        'mixto' => 'PAGO MIXTO',
        'paypal' => 'PAYPAL',
        'zelle' => 'ZELLE'
    ];
    
    $metodo_pago = $metodos_pago[$factura['metodo_pago']] ?? strtoupper($factura['metodo_pago'] ?? 'NO ESPECIFICADO');
    
    $subtotal = floatval($factura['subtotal'] ?? 0);
    $iva = floatval($factura['iva'] ?? 0);
    $total = floatval($factura['total'] ?? 0);
    
    // Recalcular si es necesario
    if ($total == 0 && !empty($detalles)) {
        $subtotal = 0;
        foreach ($detalles as $detalle) {
            $subtotal += floatval($detalle['subtotal'] ?? 0);
        }
        $iva = $subtotal * 0.16;
        $total = $subtotal + $iva;
    }
    
    // Formatear fechas
    $fecha_emision = !empty($factura['fecha_emision']) ? date('d/m/Y', strtotime($factura['fecha_emision'])) : date('d/m/Y');
    $fecha_vencimiento = !empty($factura['fecha_vencimiento']) ? date('d/m/Y', strtotime($factura['fecha_vencimiento'])) : date('d/m/Y', strtotime('+30 days'));
    
    $estado_class = $factura['estado'] == 'pagada' ? 'status-paid' : ($factura['estado'] == 'pendiente' ? 'status-pending' : 'status-cancelled');
    $estado_texto = strtoupper($factura['estado'] ?? 'PENDIENTE');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Factura Electrónica</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .invoice-container {
                background: white;
                border-radius: 10px;
                overflow: hidden;
                box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            }
            .header {
                background: linear-gradient(135deg, #1e3c72, #2a5298);
                color: white;
                padding: 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
            }
            .company h1 {
                margin: 0 0 10px 0;
                font-size: 24px;
            }
            .company p {
                margin: 5px 0;
                opacity: 0.9;
                font-size: 12px;
            }
            .invoice-title {
                text-align: right;
            }
            .invoice-title h2 {
                margin: 0;
                font-size: 32px;
            }
            .invoice-number {
                font-size: 18px;
                color: #ffd700;
                margin: 10px 0;
            }
            .status {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                font-weight: bold;
                font-size: 12px;
            }
            .status-paid {
                background: #28a745;
                color: white;
            }
            .status-pending {
                background: #ffc107;
                color: #333;
            }
            .status-cancelled {
                background: #dc3545;
                color: white;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                padding: 30px;
                background: #f8f9fa;
            }
            .info-card {
                background: white;
                padding: 20px;
                border-radius: 10px;
                border: 1px solid #dee2e6;
            }
            .info-card h3 {
                color: #1e3c72;
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #2a5298;
            }
            .info-item {
                margin-bottom: 10px;
                font-size: 14px;
            }
            .info-label {
                font-weight: bold;
                color: #666;
                display: inline-block;
                min-width: 100px;
            }
            .products-section {
                padding: 30px;
            }
            .products-section h3 {
                color: #1e3c72;
                margin-top: 0;
                margin-bottom: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }
            th {
                background: #2a5298;
                color: white;
                padding: 12px;
                text-align: left;
                font-size: 14px;
            }
            td {
                padding: 12px;
                border-bottom: 1px solid #dee2e6;
                font-size: 14px;
            }
            .totals {
                background: #f8f9fa;
                padding: 30px;
                text-align: right;
            }
            .totals-table {
                width: 300px;
                margin-left: auto;
            }
            .totals-table td {
                padding: 8px;
                border: none;
            }
            .total-row td {
                font-size: 20px;
                font-weight: bold;
                border-top: 2px solid #dee2e6;
                padding-top: 15px;
            }
            .footer {
                background: #1e3c72;
                color: white;
                padding: 30px;
                text-align: center;
                font-size: 12px;
            }
            .footer p {
                margin: 5px 0;
            }
            .amount-words {
                background: #e9ecef;
                padding: 15px;
                border-radius: 5px;
                margin-top: 20px;
                font-size: 13px;
                text-align: left;
            }
            @media (max-width: 600px) {
                .header {
                    flex-direction: column;
                    text-align: center;
                }
                .invoice-title {
                    text-align: center;
                    margin-top: 15px;
                }
                .info-grid {
                    grid-template-columns: 1fr;
                    padding: 20px;
                }
                .totals {
                    text-align: center;
                }
                .totals-table {
                    margin: 0 auto;
                }
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="header">
                <div class="company">
                    <h1>PIC - Productos Industriales y Comerciales</h1>
                    <p>RIF: J-12345678-9</p>
                    <p>Av. Principal, Zona Industrial, Caracas, Venezuela</p>
                    <p>Teléfono: 0212-5551234 / 0424-8393902</p>
                    <p>Email: picca.ventas@gmail.com</p>
                </div>
                <div class="invoice-title">
                    <h2>FACTURA</h2>
                    <div class="invoice-number">Nº ' . htmlspecialchars($factura['numero_factura']) . '</div>
                    <div class="status ' . $estado_class . '">' . $estado_texto . '</div>
                </div>
            </div>
            
            <div class="info-grid">
                <div class="info-card">
                    <h3>DATOS DEL CLIENTE</h3>
                    <div class="info-item"><span class="info-label">Nombre:</span> ' . htmlspecialchars($factura['cliente_nombre'] ?? 'No especificado') . '</div>
                    <div class="info-item"><span class="info-label">Documento:</span> ' . htmlspecialchars($factura['cliente_documento'] ?? 'No especificado') . '</div>
                    <div class="info-item"><span class="info-label">Email:</span> ' . htmlspecialchars($factura['cliente_email'] ?? 'No especificado') . '</div>
                    <div class="info-item"><span class="info-label">Teléfono:</span> ' . htmlspecialchars($factura['cliente_telefono'] ?? 'No especificado') . '</div>
                    <div class="info-item"><span class="info-label">Dirección:</span> ' . htmlspecialchars($factura['cliente_direccion'] ?? 'No especificada') . '</div>
                    ' . (!empty($factura['cliente_ciudad']) ? '<div class="info-item"><span class="info-label">Ciudad:</span> ' . htmlspecialchars($factura['cliente_ciudad']) . '</div>' : '') . '
                </div>
                
                <div class="info-card">
                    <h3>DATOS DE LA FACTURA</h3>
                    <div class="info-item"><span class="info-label">Fecha Emisión:</span> ' . $fecha_emision . '</div>
                    <div class="info-item"><span class="info-label">Fecha Vencimiento:</span> ' . $fecha_vencimiento . '</div>
                    <div class="info-item"><span class="info-label">Método de Pago:</span> ' . $metodo_pago . '</div>
                    <div class="info-item"><span class="info-label">Vendedor:</span> ' . htmlspecialchars($factura['vendedor_nombre'] ?? 'Sistema') . '</div>
                </div>
            </div>
            
            <div class="products-section">
                <h3>DETALLE DE PRODUCTOS</h3>
                <table>
                    <thead>
                        <tr><th>#</th><th>Producto</th><th>SKU</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>';
    
    if (empty($detalles)) {
        $html .= '<tr><td colspan="6" style="text-align: center;">No hay productos registrados en esta factura</td></tr>';
    } else {
        foreach ($detalles as $index => $detalle) {
            $precio = floatval($detalle['precio_unitario'] ?? 0);
            $cantidad = intval($detalle['cantidad'] ?? 0);
            $subtotal = floatval($detalle['subtotal'] ?? ($precio * $cantidad));
            
            $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible') . '
                        ' . (!empty($detalle['categoria']) ? '<br><small style="color: #666;">' . htmlspecialchars($detalle['categoria']) . '</small>' : '') . '
                    </td>
                    <td>' . htmlspecialchars($detalle['sku'] ?? 'N/A') . '</td>
                    <td>' . number_format($cantidad) . '</td>
                    <td>Bs. ' . number_format($precio, 2, ',', '.') . '</td>
                    <td><strong>Bs. ' . number_format($subtotal, 2, ',', '.') . '</strong></td>
                </tr>';
        }
    }
    
    $html .= '
                    </tbody>
                </table>
                
                <div class="totals">
                    <table class="totals-table">
                        <tr><td><strong>SUBTOTAL:</strong></td><td style="text-align: right;">Bs. ' . number_format($subtotal, 2, ',', '.') . '</td></tr>
                        <tr><td><strong>IVA (16%):</strong></td><td style="text-align: right;">Bs. ' . number_format($iva, 2, ',', '.') . '</td></tr>
                        <tr class="total-row"><td><strong>TOTAL:</strong></td><td style="text-align: right; font-size: 18px;"><strong>Bs. ' . number_format($total, 2, ',', '.') . '</strong></td></tr>
                    </table>
                    
                    <div class="amount-words">
                        <strong>SON:</strong> ' . numeroALetras($total) . '
                    </div>
                </div>
            </div>
            
            ' . (!empty($factura['observaciones']) ? '
            <div style="padding: 0 30px 30px 30px;">
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <strong>Observaciones:</strong><br>
                    ' . nl2br(htmlspecialchars($factura['observaciones'])) . '
                </div>
            </div>
            ' : '') . '
            
            <div class="footer">
                <p>¡Gracias por su preferencia!</p>
                <p>Esta factura es un documento de carácter fiscal y representa un comprobante válido de venta.</p>
                <p>Para consultas o aclaraciones, contacte a nuestro departamento de atención al cliente.</p>
                <p>Email: picca.ventas@gmail.com | Teléfono: (+58) 0424-8393902</p>
                <p style="margin-top: 20px; opacity: 0.7;">Documento generado electrónicamente el ' . date('d/m/Y H:i:s') . '</p>
                <p style="opacity: 0.5;">© ' . date('Y') . ' PIC - Productos Industriales y Comerciales. Todos los derechos reservados.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Convierte un número a letras
 */
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
?>