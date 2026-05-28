<?php
// enviar_factura_email.php - VERSIÓN CORREGIDA Y MEJORADA (RESPONSIVE PARA EMAIL)

require_once __DIR__ . '/config_email.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Solo ejecutar lógica principal si se accede directamente (no por include)
if (basename($_SERVER['SCRIPT_FILENAME']) === 'enviar_factura_email.php') {
    session_start();
    header('Content-Type: application/json');
    
    require_once __DIR__ . '/../conexion/conexion.php';
    verificarCSRF();
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
               f.observaciones as factura_observaciones,
               c.nombre as cliente_nombre, 
               c.email as cliente_email,
               c.documento as cliente_documento,
               c.telefono as cliente_telefono,
               c.direccion as cliente_direccion,
               c.ciudad as cliente_ciudad,
               a.nombre as vendedor_nombre,
               a.correo as vendedor_email,
               p.numero_pedido,
               p.metodo_pago as pedido_metodo_pago,
               p.referencia_pago as pedido_referencia_pago,
               p.observaciones as pedido_observaciones
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN admin_users a ON f.usuario_id = a.id
        LEFT JOIN pedidos p ON f.pedido_id = p.id
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
    
    // Generar HTML de la factura (optimizado para email y responsive)
    $htmlFactura = generarHTMLFacturaEmail($factura, $detalles);
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
 * Genera el HTML de la factura para el correo electrónico (VERSIÓN RESPONSIVE)
 */
function generarHTMLFacturaEmail($factura, $detalles) {
    // Determinar método de pago REAL (priorizar el del pedido como en ver_factura)
    $metodo_pago_real = $factura['metodo_pago'];
    if (!empty($factura['pedido_metodo_pago']) && $factura['pedido_metodo_pago'] !== 'transferencia') {
        $metodo_pago_real = $factura['pedido_metodo_pago'];
    }
    
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
    
    $metodo_pago = $metodos_pago[$metodo_pago_real] ?? strtoupper($metodo_pago_real ?? 'NO ESPECIFICADO');
    
    // Extraer referencia de pago
    $referencia_pago = null;
    $metodo_check = strtolower(trim($metodo_pago_real));
    if (!empty($factura['pedido_referencia_pago'])) {
        $referencia_pago = $factura['pedido_referencia_pago'];
    } else {
        $obs_a_buscar = [
            $factura['pedido_observaciones'] ?? null,
            $factura['factura_observaciones'] ?? $factura['observaciones'] ?? null
        ];
        foreach ($obs_a_buscar as $obs) {
            if ($referencia_pago === null && !empty($obs)) {
                if (preg_match('/Referencia[:\s]+([^\s]+)/i', $obs, $matches)) {
                    $referencia_pago = $matches[1];
                } elseif (preg_match('/Ref[:\s]+([^\s]+)/i', $obs, $matches)) {
                    $referencia_pago = $matches[1];
                }
            }
        }
    }
    $mostrar_referencia = ($metodo_check === 'pago_movil' || $metodo_check === 'pago movil' || $metodo_check === 'transferencia' || $metodo_check === 'transferencia_bancaria') && $referencia_pago;
    
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
    
    // Generar filas de productos para la tabla
    $productos_html = '';
    if (empty($detalles)) {
        $productos_html = '<tr><td colspan="6" style="text-align: center; padding: 20px;">No hay productos registrados en esta factura</td></tr>';
    } else {
        foreach ($detalles as $index => $detalle) {
            $precio = floatval($detalle['precio_unitario'] ?? 0);
            $cantidad = intval($detalle['cantidad'] ?? 0);
            $subtotal_item = floatval($detalle['subtotal'] ?? ($precio * $cantidad));
            
            $productos_html .= '
                <tr>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: center; width: 30px;">' . ($index + 1) . '</td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; word-break: break-word;">
                        ' . htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible') . '
                        ' . (!empty($detalle['categoria']) ? '<br><small style="color: #666;">' . htmlspecialchars($detalle['categoria']) . '</small>' : '') . '
                    </td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; font-size: 11px;">' . htmlspecialchars($detalle['sku'] ?? 'N/A') . '</td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: center;">' . number_format($cantidad) . '</td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right; white-space: nowrap;">Bs. ' . number_format($precio, 2, ',', '.') . '</td>
                    <td style="padding: 8px; border-bottom: 1px solid #dee2e6; text-align: right; white-space: nowrap; font-weight: bold;">Bs. ' . number_format($subtotal_item, 2, ',', '.') . '</td>
                </tr>';
        }
    }
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
        <title>Factura Electrónica</title>
        <style>
            /* Estilos base para todos los clientes de correo */
            body {
                margin: 0;
                padding: 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                line-height: 1.5;
                color: #333333;
                background-color: #f5f5f5;
            }
            
            /* Contenedor principal */
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f5f5f5;
            }
            
            /* Tarjeta de factura */
            .invoice-card {
                background: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            /* Header */
            .header {
                background: linear-gradient(135deg, #1e3c72, #2a5298);
                color: #ffffff;
                padding: 20px;
            }
            
            .company-info {
                text-align: center;
                margin-bottom: 15px;
            }
            
            .company-info h1 {
                margin: 0 0 5px 0;
                font-size: 16px;
                font-weight: 600;
            }
            
            .company-info p {
                margin: 3px 0;
                font-size: 11px;
                opacity: 0.9;
            }
            
            .invoice-title {
                text-align: center;
                border-top: 1px solid rgba(255,255,255,0.2);
                padding-top: 12px;
            }
            
            .invoice-title h2 {
                margin: 0;
                font-size: 24px;
                font-weight: 700;
            }
            
            .invoice-number {
                font-size: 12px;
                font-weight: 600;
                background: rgba(255,255,255,0.2);
                padding: 4px 12px;
                border-radius: 20px;
                display: inline-block;
                margin-top: 8px;
            }
            
            .status {
                display: inline-block;
                padding: 4px 12px;
                border-radius: 20px;
                font-weight: 600;
                font-size: 10px;
                margin-left: 8px;
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
            
            /* Bloques de información */
            .info-section {
                padding: 16px;
            }
            
            .info-block {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 12px;
                margin-bottom: 12px;
                border: 1px solid #e0e0e0;
            }
            
            .info-block h3 {
                font-size: 13px;
                color: #1e3c72;
                margin: 0 0 10px 0;
                padding-bottom: 6px;
                border-bottom: 2px solid #2a5298;
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .info-row {
                display: flex;
                flex-wrap: wrap;
                margin-bottom: 6px;
                font-size: 11px;
                line-height: 1.4;
            }
            
            .info-label {
                font-weight: 600;
                color: #666;
                min-width: 75px;
                font-size: 11px;
            }
            
            .info-value {
                color: #333;
                flex: 1;
                word-break: break-word;
                font-size: 11px;
            }
            
            /* Productos */
            .products-section {
                padding: 0 16px 16px 16px;
            }
            
            .products-title {
                font-size: 14px;
                color: #1e3c72;
                margin: 0 0 12px 0;
                padding-bottom: 6px;
                border-bottom: 2px solid #e0e0e0;
                font-weight: 600;
            }
            
            /* Tabla responsive */
            .table-wrapper {
                width: 100%;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 10px 0;
            }
            
            .product-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                min-width: 400px;
            }
            
            .product-table th {
                background: #2a5298;
                color: white;
                padding: 8px 6px;
                text-align: left;
                font-weight: 600;
                font-size: 10px;
            }
            
            .product-table td {
                padding: 8px 6px;
                border-bottom: 1px solid #dee2e6;
                vertical-align: top;
            }
            
            .product-table th:first-child,
            .product-table td:first-child {
                text-align: center;
                width: 30px;
            }
            
            .product-table th:nth-child(3),
            .product-table td:nth-child(3) {
                font-size: 9px;
            }
            
            .product-table th:nth-child(4),
            .product-table td:nth-child(4) {
                text-align: center;
                white-space: nowrap;
            }
            
            .product-table th:nth-child(5),
            .product-table td:nth-child(5),
            .product-table th:nth-child(6),
            .product-table td:nth-child(6) {
                text-align: right;
                white-space: nowrap;
            }
            
            /* Totales */
            .totals-section {
                background: #f8f9fa;
                padding: 16px;
                border-top: 1px solid #e0e0e0;
            }
            
            .total-line {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid #e0e0e0;
                flex-wrap: wrap;
            }
            
            .total-line span:first-child {
                font-size: 11px;
                color: #666;
            }
            
            .total-line span:last-child {
                font-size: 12px;
                font-weight: 600;
                color: #333;
            }
            
            .grand-total {
                margin-top: 8px;
                padding: 12px 16px;
                background: #1e3c72;
                border-radius: 10px;
                margin: 8px -16px -16px -16px;
            }
            
            .grand-total span:first-child {
                color: white;
                font-weight: 600;
                font-size: 13px;
            }
            
            .grand-total span:last-child {
                color: white;
                font-size: 15px;
                font-weight: 700;
            }
            
            .amount-words {
                background: white;
                padding: 10px;
                border-radius: 8px;
                border-left: 3px solid #1e3c72;
                font-size: 10px;
                margin-top: 12px;
                word-break: break-word;
            }
            
            /* Observaciones */
            .observations {
                padding: 12px 16px;
                background: #fff3cd;
                border-left: 3px solid #ffc107;
                margin: 0 16px 16px 16px;
                border-radius: 8px;
            }
            
            .observations strong {
                font-size: 11px;
            }
            
            .observations p {
                font-size: 10px;
                margin: 5px 0 0 0;
                word-break: break-word;
            }
            
            /* Footer */
            .footer {
                background: #212121;
                color: white;
                padding: 16px;
                text-align: center;
            }
            
            .footer p {
                margin: 5px 0;
                font-size: 10px;
            }
            
            /* Media queries para email (compatibles con la mayoría de clientes) */
            @media only screen and (max-width: 480px) {
                .container {
                    padding: 10px;
                }
                
                .info-label {
                    min-width: 70px;
                }
                
                .product-table {
                    font-size: 9px;
                    min-width: 350px;
                }
                
                .product-table th,
                .product-table td {
                    padding: 6px 4px;
                }
                
                .product-table td:nth-child(2) {
                    word-break: break-word;
                    white-space: normal;
                }
                
                .grand-total span:first-child {
                    font-size: 11px;
                }
                
                .grand-total span:last-child {
                    font-size: 13px;
                }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 0; background-color: #f5f5f5;">
        <div class="container" style="max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f5f5f5;">
            <div class="invoice-card" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                
                <!-- HEADER -->
                <div class="header" style="background: linear-gradient(135deg, #1e3c72, #2a5298); color: #ffffff; padding: 20px;">
                    <div class="company-info" style="text-align: center; margin-bottom: 15px;">
                        <h1 style="margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">PIC - Proyectos Industriales del centro</h1>
                        <p style="margin: 3px 0; font-size: 11px; opacity: 0.9;">RIF: J-12345678-9</p>
                        <p style="margin: 3px 0; font-size: 11px; opacity: 0.9;">prolongacion Av. Michelena, Zona Industrial, Valencia, Venezuela</p>
                        <p style="margin: 3px 0; font-size: 11px; opacity: 0.9;">Teléfono: 0424-8393902</p>
                    </div>
                    <div class="invoice-title" style="text-align: center; border-top: 1px solid rgba(255,255,255,0.2); padding-top: 12px;">
                        <h2 style="margin: 0; font-size: 24px; font-weight: 700;">FACTURA</h2>
                        <div>
                            <span class="invoice-number" style="font-size: 12px; font-weight: 600; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; display: inline-block; margin-top: 8px;">Nº ' . htmlspecialchars($factura['numero_factura']) . '</span>
                            <span class="status ' . $estado_class . '" style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 10px; margin-left: 8px;">' . $estado_texto . '</span>
                        </div>
                    </div>
                </div>
                
                <!-- INFORMACIÓN -->
                <div class="info-section" style="padding: 16px;">
                    <!-- VENDEDOR -->
                    <div class="info-block" style="background: #f8f9fa; border-radius: 10px; padding: 12px; margin-bottom: 12px; border: 1px solid #e0e0e0;">
                        <h3 style="font-size: 13px; color: #1e3c72; margin: 0 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #2a5298;">📋 VENDEDOR</h3>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Nombre:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . htmlspecialchars(!empty($factura['vendedor_nombre']) ? $factura['vendedor_nombre'] : 'Sistema') . '</span>
                        </div>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Fecha:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . $fecha_emision . '</span>
                        </div>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                             <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Pago:</span>
                             <span class="info-value" style="color: #333; flex: 1;">' . $metodo_pago . '</span>
                         </div>
                         ' . ($mostrar_referencia ? '
                         <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                             <span class="info-label" style="font-weight: 600; color: #3498db; min-width: 75px;"><i class="fas fa-hashtag"></i> Referencia:</span>
                             <span class="info-value" style="color: #333; flex: 1; font-weight: bold; font-size: 12px;">' . htmlspecialchars($referencia_pago) . '</span>
                         </div>
                         ' : '') . '
                     </div>
                     
                     <!-- CLIENTE -->
                    <div class="info-block" style="background: #f8f9fa; border-radius: 10px; padding: 12px; margin-bottom: 12px; border: 1px solid #e0e0e0;">
                        <h3 style="font-size: 13px; color: #1e3c72; margin: 0 0 10px 0; padding-bottom: 6px; border-bottom: 2px solid #2a5298;">👤 CLIENTE</h3>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Nombre:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . htmlspecialchars(!empty($factura['cliente_nombre']) ? $factura['cliente_nombre'] : 'No especificado') . '</span>
                        </div>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Documento:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . htmlspecialchars(!empty($factura['cliente_documento']) ? $factura['cliente_documento'] : 'No especificado') . '</span>
                        </div>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Email:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . htmlspecialchars(!empty($factura['cliente_email']) ? $factura['cliente_email'] : 'No especificado') . '</span>
                        </div>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Teléfono:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . htmlspecialchars(!empty($factura['cliente_telefono']) ? $factura['cliente_telefono'] : 'No especificado') . '</span>
                        </div>
                        <div class="info-row" style="display: flex; flex-wrap: wrap; margin-bottom: 6px; font-size: 11px;">
                            <span class="info-label" style="font-weight: 600; color: #666; min-width: 75px;">Dirección:</span>
                            <span class="info-value" style="color: #333; flex: 1;">' . htmlspecialchars(!empty($factura['cliente_direccion']) ? $factura['cliente_direccion'] : 'No especificada') . '</span>
                        </div>
                    </div>
                </div>
                
                <!-- PRODUCTOS -->
                <div class="products-section" style="padding: 0 16px 16px 16px;">
                    <h3 class="products-title" style="font-size: 14px; color: #1e3c72; margin: 0 0 12px 0; padding-bottom: 6px; border-bottom: 2px solid #e0e0e0;">📦 PRODUCTOS</h3>
                    <div class="table-wrapper" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; margin: 10px 0;">
                        <table class="product-table" style="width: 100%; border-collapse: collapse; font-size: 11px; min-width: 400px;">
                            <thead>
                                <tr>
                                    <th style="background: #2a5298; color: white; padding: 8px 6px; text-align: center; width: 30px;">#</th>
                                    <th style="background: #2a5298; color: white; padding: 8px 6px; text-align: left;">Producto</th>
                                    <th style="background: #2a5298; color: white; padding: 8px 6px; text-align: left;">SKU</th>
                                    <th style="background: #2a5298; color: white; padding: 8px 6px; text-align: center;">Cant.</th>
                                    <th style="background: #2a5298; color: white; padding: 8px 6px; text-align: right;">Precio</th>
                                    <th style="background: #2a5298; color: white; padding: 8px 6px; text-align: right;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $productos_html . '
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- TOTALES -->
                <div class="totals-section" style="background: #f8f9fa; padding: 16px; border-top: 1px solid #e0e0e0;">
                    <div class="total-line" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #e0e0e0;">
                        <span style="font-size: 11px; color: #666;">SUBTOTAL</span>
                        <span style="font-size: 12px; font-weight: 600; color: #333;">Bs. ' . number_format($subtotal, 2, ',', '.') . '</span>
                    </div>
                    <div class="total-line" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid #e0e0e0;">
                        <span style="font-size: 11px; color: #666;">IVA (16%)</span>
                        <span style="font-size: 12px; font-weight: 600; color: #333;">Bs. ' . number_format($iva, 2, ',', '.') . '</span>
                    </div>
                    <div class="grand-total" style="margin-top: 8px; padding: 12px 16px; background: #1e3c72; border-radius: 10px; margin: 8px -16px -16px -16px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: white; font-weight: 600; font-size: 13px;">TOTAL A PAGAR</span>
                        <span style="color: white; font-size: 15px; font-weight: 700;">Bs. ' . number_format($total, 2, ',', '.') . '</span>
                    </div>
                    
                    <div class="amount-words" style="background: white; padding: 10px; border-radius: 8px; border-left: 3px solid #1e3c72; font-size: 10px; margin-top: 12px;">
                        <strong>SON:</strong> ' . numeroALetras($total) . '
                    </div>
                </div>
                
                ' . (in_array($metodo_check, ['efectivo', 'mixto']) ? '
                <div class="observations" style="padding: 12px 16px; background: #fff3cd; border-left: 3px solid #ffc107; margin: 0 16px 16px 16px; border-radius: 8px;">
                    <strong style="font-size: 11px;">⚠️ PAGO PENDIENTE:</strong>
                    <p style="font-size: 10px; margin: 5px 0 0 0;">El método de pago es <strong>' . $metodo_pago . '</strong>. Debes culminar el pago en la empresa para recibir tu producto. De lo contrario no se entregará el pedido.</p>
                </div>
                ' : '') . '
                ' . (!empty($factura['observaciones']) ? '
                <div class="observations" style="padding: 12px 16px; background: #fff3cd; border-left: 3px solid #ffc107; margin: 0 16px 16px 16px; border-radius: 8px;">
                    <strong style="font-size: 11px;">📝 OBSERVACIONES:</strong>
                    <p style="font-size: 10px; margin: 5px 0 0 0;">' . nl2br(htmlspecialchars($factura['observaciones'])) . '</p>
                </div>
                ' : '') . '
                
                <!-- FOOTER -->
                <div class="footer" style="background: #212121; color: white; padding: 16px; text-align: center;">
                    <p style="margin: 5px 0; font-size: 10px;">✅ Comprobante válido de venta.</p>
                    <p style="margin: 5px 0; font-size: 10px;">📧 Consultas: picca.ventas@gmail.com</p>
                    <p style="margin: 5px 0; font-size: 10px;">📞 Teléfono: (+58) 0424-8393902</p>
                    <p style="margin: 15px 0 5px 0; font-size: 9px; opacity: 0.7;">Documento generado electrónicamente el ' . date('d/m/Y H:i:s') . '</p>
                    <p style="margin: 5px 0; font-size: 9px; opacity: 0.5;">© ' . date('Y') . ' PIC - Productos Industriales y Comerciales</p>
                </div>
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