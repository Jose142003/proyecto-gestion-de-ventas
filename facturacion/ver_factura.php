<?php
session_start();
require_once '../conexion/conexion.php';

// Establecer zona horaria de Venezuela
date_default_timezone_set('America/Caracas');

try {
    $pdo = conectarDB();
    $pdo->exec("SET time_zone = '-04:00'");
} catch (PDOException $e) {
    die("Error de conexión: " . htmlspecialchars($e->getMessage()));
}

// Obtener ID de factura
$factura_id = $_GET['id'] ?? 0;
if (!$factura_id) {
    die("<h2>Error: Factura no especificada</h2>");
}

// Obtener información de la factura
try {
    $stmt = $pdo->prepare("
        SELECT f.*, 
               c.nombre as cliente_nombre, 
               c.documento as cliente_documento,
               c.email as cliente_email,
               c.telefono as cliente_telefono,
               c.direccion as cliente_direccion,
               c.ciudad as cliente_ciudad,
               u.nombre as vendedor_nombre,
               u.correo as vendedor_email,
               p.numero_pedido,
               p.metodo_pago as pedido_metodo_pago,
               p.created_at as fecha_pedido,
               p.notas_internas as pedido_notas,
               p.observaciones
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        LEFT JOIN users u ON f.usuario_id = u.id
        LEFT JOIN pedidos p ON f.pedido_id = p.id
        WHERE f.id = ?
    ");
    $stmt->execute([$factura_id]);
    $factura = $stmt->fetch();
    
    if (!$factura) {
        die("<h2>Error: Factura no encontrada</h2>");
    }
    
    // Determinar método de pago
    $metodo_pago_real = $factura['metodo_pago'];
    if (!empty($factura['pedido_metodo_pago']) && $factura['pedido_metodo_pago'] !== 'transferencia') {
        $metodo_pago_real = $factura['pedido_metodo_pago'];
    }
    
    // Verificar pago mixto
    $es_pago_mixto = false;
    $detalles_mixto = null;
    if (!empty($factura['observaciones'])) {
        $notas = json_decode($factura['observaciones'], true);
        if ($notas && isset($notas['transferencia']) && isset($notas['efectivo'])) {
            $es_pago_mixto = true;
            $detalles_mixto = $notas;
        }
    }
    
    // Obtener detalles de la factura
    $stmt = $pdo->prepare("
        SELECT fd.*, 
               p.name as producto_nombre, 
               p.sku,
               p.category as categoria,
               p.description as producto_descripcion
        FROM factura_detalles fd
        LEFT JOIN products p ON fd.producto_id = p.id
        WHERE fd.factura_id = ?
        ORDER BY fd.id
    ");
    $stmt->execute([$factura_id]);
    $detalles = $stmt->fetchAll();
    
    // Si no hay detalles pero hay pedido_id
    if (empty($detalles) && !empty($factura['pedido_id'])) {
        $stmt_pedido = $pdo->prepare("
            SELECT pd.*, 
                   p.name as producto_nombre,
                   p.sku,
                   p.category as categoria
            FROM pedido_detalles pd
            LEFT JOIN products p ON pd.producto_id = p.id
            WHERE pd.pedido_id = ?
        ");
        $stmt_pedido->execute([$factura['pedido_id']]);
        $detalles = $stmt_pedido->fetchAll();
    }
    
    // Calcular totales
    $total_pagado = ($factura['estado'] === 'pagada') ? $factura['total'] : 0;
    $saldo_pendiente = $factura['total'] - $total_pagado;
    
    // Verificar permisos
    $es_admin = isset($_SESSION['user_rol']) && ($_SESSION['user_rol'] === 'admin' || $_SESSION['user_rol'] === 'superadmin');
    $es_propietario = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $factura['usuario_id'];
    $viene_de_admin = isset($_GET['admin']) && $_GET['admin'] === 'true';
    $mostrar_acciones = $es_admin || $viene_de_admin;
    
} catch (PDOException $e) {
    die("Error: " . htmlspecialchars($e->getMessage()));
}

// Funciones auxiliares
function getMetodoPagoLegible($metodo) {
    if (empty($metodo)) return 'NO ESPECIFICADO';
    $metodo = strtolower(trim($metodo));
    $mapa = [
        'efectivo' => 'EFECTIVO',
        'transferencia' => 'TRANSFERENCIA',
        'transferencia_bancaria' => 'TRANSFERENCIA',
        'pago_movil' => 'PAGO MÓVIL',
        'pago movil' => 'PAGO MÓVIL',
        'mixto' => 'MIXTO',
        'tarjeta' => 'TARJETA',
        'paypal' => 'PAYPAL',
        'zelle' => 'ZELLE',
        'cheque' => 'CHEQUE'
    ];
    return $mapa[$metodo] ?? strtoupper($metodo);
}

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

function formatearFecha($fecha) {
    if (empty($fecha)) return 'No especificada';
    return date('d/m/Y', strtotime($fecha));
}

function getEstadoBadge($estado) {
    $estados = [
        'pagada' => ['class' => 'success', 'text' => 'PAGADA'],
        'pendiente' => ['class' => 'warning', 'text' => 'PENDIENTE'],
        'anulada' => ['class' => 'danger', 'text' => 'ANULADA']
    ];
    $estado_key = strtolower($estado);
    $info = $estados[$estado_key] ?? ['class' => 'secondary', 'text' => strtoupper($estado)];
    return '<span class="badge badge-' . $info['class'] . '">' . $info['text'] . '</span>';
}

$metodo_pago_mostrar = getMetodoPagoLegible($metodo_pago_real);
$empresa_nombre = "PIC - Productos Industriales y Comerciales";
$empresa_rif = "J-12345678-9";
$empresa_direccion = "Av. Principal, Zona Industrial, Caracas, Venezuela";
$empresa_telefono = "0212-5551234 / 0424-8393902";
$empresa_email = "picca.ventas@gmail.com";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>Factura <?php echo htmlspecialchars($factura['numero_factura']); ?> - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
      <!-- PWA Meta Tags -->
    <link rel="manifest" href="/proyecto/manifest.json">
    <meta name="theme-color" content="#050C18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIC Industrial">
    <link rel="apple-touch-icon" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/proyecto/img/pic.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/proyecto/img/pic.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }
        
        :root {
            --primary: #1e3c72;
            --primary-dark: #0a1a3a;
            --secondary: #2a5298;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --gray-50: #fafafa;
            --gray-100: #f5f5f5;
            --gray-200: #eeeeee;
            --gray-300: #e0e0e0;
            --gray-400: #bdbdbd;
            --gray-500: #9e9e9e;
            --gray-600: #757575;
            --gray-700: #616161;
            --gray-800: #424242;
            --gray-900: #212121;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background: var(--gray-100);
            padding: 0;
            margin: 0;
            min-height: 100vh;
        }
        
        /* Contenedor principal - ancho completo en móvil */
        .invoice-container {
            max-width: 100%;
            margin: 0;
            padding: 0;
        }
        
        /* Botón volver - flotante en móvil */
        .back-button {
            position: sticky;
            top: 10px;
            left: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(30, 60, 114, 0.95);
            backdrop-filter: blur(10px);
            padding: 10px 16px;
            border-radius: 30px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            margin: 10px;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
        }
        
        .back-button i {
            font-size: 0.9rem;
        }
        
        .back-button:active {
            transform: scale(0.96);
        }
        
        /* Tarjeta principal de factura */
        .invoice {
            background: white;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
        }
        
        /* HEADER - Compacto y claro */
        .invoice-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 20px 16px;
        }
        
        .company-info {
            text-align: center;
            margin-bottom: 16px;
        }
        
        .company-info h1 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .company-info p {
            font-size: 0.65rem;
            opacity: 0.9;
            margin: 3px 0;
        }
        
        .invoice-title {
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 12px;
        }
        
        .invoice-title h2 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .invoice-number {
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
            margin-left: 8px;
        }
        
        .badge-success { background: var(--success); color: white; }
        .badge-warning { background: var(--warning); color: var(--gray-900); }
        .badge-danger { background: var(--danger); color: white; }
        
        /* Secciones de información - diseño vertical */
        .info-sections {
            padding: 16px;
            background: white;
        }
        
        .info-block {
            background: var(--gray-50);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
            border: 1px solid var(--gray-200);
        }
        
        .info-block h3 {
            font-size: 0.8rem;
            color: var(--primary);
            margin-bottom: 10px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--gray-300);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        
        .info-block h3 i {
            font-size: 0.85rem;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
            font-size: 0.7rem;
            line-height: 1.4;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--gray-600);
            min-width: 80px;
            font-size: 0.7rem;
        }
        
        .info-value {
            color: var(--gray-800);
            flex: 1;
            word-break: break-word;
            font-size: 0.7rem;
        }
        
        /* Productos - estilo lista limpia */
        .products-section {
            padding: 16px;
            background: white;
            border-top: 1px solid var(--gray-200);
        }
        
        .section-title {
            font-size: 0.85rem;
            color: var(--primary);
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
        }
        
        .products-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .product-item {
            background: var(--gray-50);
            border-radius: 10px;
            padding: 10px;
            border: 1px solid var(--gray-200);
        }
        
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-800);
            flex: 1;
        }
        
        .product-sku {
            font-size: 0.55rem;
            color: var(--gray-500);
            margin-top: 2px;
        }
        
        .product-qty {
            background: var(--primary);
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            min-width: 40px;
            text-align: center;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid var(--gray-200);
        }
        
        .product-price {
            font-size: 0.7rem;
            color: var(--gray-600);
        }
        
        .product-subtotal {
            font-weight: 700;
            font-size: 0.8rem;
            color: var(--primary);
        }
        
        /* Totales - claro y legible */
        .totals-section {
            padding: 16px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .total-line:last-child {
            border-bottom: none;
        }
        
        .total-line span:first-child {
            font-size: 0.75rem;
            color: var(--gray-600);
        }
        
        .total-line span:last-child {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .total-line.grand-total {
            margin-top: 8px;
            padding-top: 12px;
            border-top: 2px solid var(--gray-300);
            background: var(--primary);
            margin: 8px -8px -8px -8px;
            padding: 12px 16px;
            border-radius: 10px;
        }
        
        .total-line.grand-total span:first-child {
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .total-line.grand-total span:last-child {
            color: white;
            font-size: 1rem;
            font-weight: 700;
        }
        
        .amount-words {
            background: white;
            padding: 10px;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
            font-size: 0.65rem;
            margin-top: 12px;
            word-break: break-word;
        }
        
        /* Footer */
        .invoice-footer {
            padding: 16px;
            background: var(--gray-800);
            color: white;
            text-align: center;
        }
        
        .footer-notes {
            font-size: 0.65rem;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .footer-copyright {
            font-size: 0.55rem;
            opacity: 0.7;
        }
        
        /* Botones de acción - fijos en móvil */
        .action-buttons {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            display: flex;
            gap: 8px;
            padding: 12px;
            border-top: 1px solid var(--gray-200);
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .btn {
            flex: 1;
            padding: 12px;
            border-radius: 30px;
            border: none;
            font-weight: 600;
            font-size: 0.75rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        
        .btn:active {
            transform: scale(0.97);
        }
        
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: var(--gray-900); }
        
        .btn:disabled {
            opacity: 0.6;
            transform: none;
        }
        
        /* Espacio para el footer fijo */
        .action-buttons-spacer {
            height: 70px;
        }
        
        /* Media query para tablets y desktop */
        @media (min-width: 768px) {
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                padding: 20px;
            }
            
            .invoice-container {
                max-width: 600px;
                margin: 0 auto;
            }
            
            .invoice {
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            
            .action-buttons {
                position: static;
                box-shadow: none;
                border-top: 1px solid var(--gray-200);
                border-radius: 0 0 20px 20px;
            }
            
            .action-buttons-spacer {
                display: none;
            }
            
            .back-button {
                position: static;
                display: inline-flex;
                margin: 0 0 15px 0;
                background: rgba(255,255,255,0.95);
                color: var(--primary);
            }
        }
        
        /* Impresión */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .back-button,
            .action-buttons,
            .action-buttons-spacer {
                display: none !important;
            }
            
            .invoice {
                border-radius: 0;
                box-shadow: none;
            }
            
            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            @page {
                size: A4;
                margin: 1cm;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <a href="<?php echo $mostrar_acciones ? 'listar_facturas.php' : 'javascript:history.back()'; ?>" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
        
        <div class="invoice">
            <!-- HEADER -->
            <div class="invoice-header">
                <div class="company-info">
                    <h1><i class="fas fa-industry"></i> <?php echo htmlspecialchars($empresa_nombre); ?></h1>
                    <p>RIF: <?php echo htmlspecialchars($empresa_rif); ?></p>
                    <p><?php echo htmlspecialchars($empresa_direccion); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($empresa_telefono); ?></p>
                </div>
                <div class="invoice-title">
                    <h2>FACTURA</h2>
                    <div>
                        <span class="invoice-number">Nº <?php echo htmlspecialchars($factura['numero_factura']); ?></span>
                        <?php echo getEstadoBadge($factura['estado']); ?>
                    </div>
                </div>
            </div>
            
            <!-- INFORMACIÓN - VENDEDOR ARRIBA, CLIENTE ABAJO -->
            <div class="info-sections">
                <!-- VENDEDOR -->
                <div class="info-block">
                    <h3><i class="fas fa-user-check"></i> VENDEDOR</h3>
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?php echo htmlspecialchars($factura['vendedor_nombre'] ?? 'Sistema'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($factura['vendedor_email'] ?? 'No especificado'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha:</span>
                        <span class="info-value"><?php echo formatearFecha($factura['fecha_emision']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Pago:</span>
                        <span class="info-value"><?php echo $metodo_pago_mostrar; ?></span>
                    </div>
                    <?php if (!empty($factura['numero_pedido'])): ?>
                    <div class="info-row">
                        <span class="info-label">Pedido:</span>
                        <span class="info-value"><?php echo htmlspecialchars($factura['numero_pedido']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- CLIENTE -->
                <div class="info-block">
                    <h3><i class="fas fa-user-circle"></i> CLIENTE</h3>
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
            </div>
            
            <!-- PRODUCTOS -->
            <div class="products-section">
                <h3 class="section-title"><i class="fas fa-boxes"></i> PRODUCTOS</h3>
                <div class="products-list">
                    <?php if (empty($detalles)): ?>
                    <div style="text-align: center; padding: 30px; color: var(--gray-500);">
                        <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        Sin productos registrados
                    </div>
                    <?php else: ?>
                        <?php foreach ($detalles as $index => $detalle): ?>
                        <div class="product-item">
                            <div class="product-header">
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible'); ?></div>
                                    <?php if (!empty($detalle['sku'])): ?>
                                    <div class="product-sku">SKU: <?php echo htmlspecialchars($detalle['sku']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-qty">x<?php echo number_format($detalle['cantidad'] ?? 0); ?></div>
                            </div>
                            <div class="product-footer">
                                <div class="product-price">Bs. <?php echo number_format($detalle['precio_unitario'] ?? 0, 2); ?> c/u</div>
                                <div class="product-subtotal">Bs. <?php echo number_format($detalle['subtotal'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- TOTALES -->
            <div class="totals-section">
                <div class="total-line">
                    <span>SUBTOTAL</span>
                    <span>Bs. <?php echo number_format($factura['subtotal'] ?? 0, 2); ?></span>
                </div>
                <div class="total-line">
                    <span>IVA (16%)</span>
                    <span>Bs. <?php echo number_format($factura['iva'] ?? 0, 2); ?></span>
                </div>
                <div class="total-line grand-total">
                    <span>TOTAL A PAGAR</span>
                    <span>Bs. <?php echo number_format($factura['total'] ?? 0, 2); ?></span>
                </div>
                <div class="total-line">
                    <span>MONTO PAGADO</span>
                    <span>Bs. <?php echo number_format($total_pagado, 2); ?></span>
                </div>
                <?php if ($factura['estado'] === 'pendiente' && $saldo_pendiente > 0): ?>
                <div class="total-line" style="background: var(--warning); margin: 8px -8px -8px -8px; padding: 10px 16px; border-radius: 10px;">
                    <span style="font-weight: 600; color: var(--gray-900);">SALDO PENDIENTE</span>
                    <span style="font-weight: 700; color: var(--gray-900);">Bs. <?php echo number_format($saldo_pendiente, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="amount-words">
                    <i class="fas fa-file-signature"></i> <strong>SON:</strong> <?php echo numeroALetras($factura['total'] ?? 0); ?>
                </div>
            </div>
            
            <!-- FOOTER -->
            <div class="invoice-footer">
                <div class="footer-notes">
                    <i class="fas fa-info-circle"></i> Comprobante válido de venta.<br>
                    <i class="fas fa-envelope"></i> Consultas: <?php echo $empresa_email; ?>
                </div>
                <div class="footer-copyright">
                    © <?php echo date('Y'); ?> <?php echo htmlspecialchars($empresa_nombre); ?>
                </div>
            </div>
            
            <!-- ESPACIADOR PARA BOTONES FIJOS -->
            <div class="action-buttons-spacer"></div>
        </div>
        
        <!-- BOTONES DE ACCIÓN FIJOS -->
        <?php if ($mostrar_acciones): ?>
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Imprimir
            </button>
            <button onclick="enviarPorCorreo(<?php echo $factura_id; ?>)" class="btn btn-success">
                <i class="fas fa-envelope"></i> Enviar
            </button>
            <?php if ($factura['estado'] === 'pendiente'): ?>
            <button onclick="marcarComoPagada(<?php echo $factura_id; ?>)" class="btn btn-warning">
                <i class="fas fa-check-circle"></i> Pagar
            </button>
            <?php endif; ?>
            <?php if ($factura['estado'] !== 'anulada'): ?>
            <button onclick="anularFactura(<?php echo $factura_id; ?>)" class="btn btn-danger">
                <i class="fas fa-ban"></i> Anular
            </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    const facturaId = <?php echo $factura_id; ?>;

    function marcarComoPagada(id) {
        if (!confirm('¿Marcar esta factura como PAGADA?')) return;
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch('procesar_factura.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'marcar_pagada', factura_id: id })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? '✅ ' + data.message : '❌ ' + data.message);
            if (data.success) setTimeout(() => location.reload(), 1000);
            else { btn.innerHTML = originalText; btn.disabled = false; }
        })
        .catch(() => { alert('❌ Error de conexión'); btn.innerHTML = originalText; btn.disabled = false; });
    }

    function anularFactura(id) {
        const motivo = prompt('Motivo de anulación:');
        if (!motivo || motivo.trim() === '') return;
        if (!confirm('¿Anular esta factura? No se puede deshacer.')) return;
        
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch('procesar_factura.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'anular', factura_id: id, motivo: motivo })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? '✅ ' + data.message : '❌ ' + data.message);
            if (data.success) setTimeout(() => location.reload(), 1000);
            else { btn.innerHTML = originalText; btn.disabled = false; }
        })
        .catch(() => { alert('❌ Error de conexión'); btn.innerHTML = originalText; btn.disabled = false; });
    }

    function enviarPorCorreo(id) {
        const emailCliente = "<?php echo htmlspecialchars($factura['cliente_email'] ?? ''); ?>";
        if (!emailCliente || emailCliente === 'No especificado') {
            alert('⚠️ No hay correo registrado para este cliente.');
            return;
        }
        
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch('/proyecto/usuarios/enviar_factura_email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ factura_id: id, email: emailCliente })
        })
        .then(response => response.json())
        .then(data => { alert(data.success ? '✅ ' + data.message : '❌ ' + data.message); })
        .catch(() => { alert('❌ Error de conexión'); })
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }
    </script>
</body>
</html>