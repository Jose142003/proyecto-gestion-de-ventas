<?php
session_start();

// Establecer zona horaria de Venezuela
date_default_timezone_set('America/Caracas');

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    $pdo->exec("SET time_zone = '-04:00'");
} catch (PDOException $e) {
    die("Error interno del servidor");
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
                f.observaciones as factura_observaciones,
                c.nombre as cliente_nombre, 
                c.documento as cliente_documento,
                c.email as cliente_email,
                c.telefono as cliente_telefono,
                c.direccion as cliente_direccion,
                c.ciudad as cliente_ciudad,
                a.nombre as vendedor_nombre,
                a.correo as vendedor_email,
                p.numero_pedido,
                p.metodo_pago as pedido_metodo_pago,
                p.created_at as fecha_pedido,
                p.notas_internas as pedido_notas,
                p.observaciones as pedido_observaciones,
                p.referencia_pago as pedido_referencia_pago
          FROM facturas f
          LEFT JOIN clientes c ON f.cliente_id = c.id
          LEFT JOIN admin_users a ON f.usuario_id = a.id
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
    $obs_factura = $factura['factura_observaciones'] ?? $factura['observaciones'] ?? null;
    if (!empty($obs_factura)) {
        $notas = json_decode($obs_factura, true);
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
    die("Error interno del servidor");
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
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generarTokenCSRF()); ?>">
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
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .invoice-container {
            max-width: 100%;
        }
        
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
        
        .back-button:active {
            transform: scale(0.96);
        }
        
        .invoice {
            background: white;
            border-radius: 0;
            overflow: hidden;
        }
        
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
            word-break: break-word;
        }
        
        .company-info p {
            font-size: 0.65rem;
            opacity: 0.9;
            margin: 3px 0;
            word-break: break-word;
            overflow-wrap: break-word;
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
            word-break: break-word;
        }
        
        .invoice-number {
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 5px;
            word-break: break-all;
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
        
        .info-block h3 i { font-size: 0.85rem; }
        
        .info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 8px;
            font-size: 0.7rem;
            line-height: 1.4;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--gray-600);
            min-width: 80px;
            font-size: 0.7rem;
            flex-shrink: 0;
        }
        
        .info-value {
            color: var(--gray-800);
            flex: 1;
            min-width: 0;
            word-break: break-word;
            font-size: 0.7rem;
        }
        
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
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .product-name {
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--gray-800);
            flex: 1;
            word-break: break-word;
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
            flex-shrink: 0;
        }
        
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px solid var(--gray-200);
            flex-wrap: wrap;
            gap: 8px;
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
        
        .products-table-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.7rem;
            min-width: 380px;
        }
        
        .products-table th {
            background: var(--primary);
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: 600;
            font-size: 0.65rem;
            white-space: nowrap;
        }
        
        .products-table td {
            padding: 8px 6px;
            border-bottom: 1px solid var(--gray-200);
            word-break: break-word;
        }
        
        .products-table th:first-child,
        .products-table td:first-child {
            width: 28px;
            text-align: center;
        }
        
        .products-table th:nth-child(4),
        .products-table td:nth-child(4) {
            text-align: center;
            white-space: nowrap;
        }
        
        .products-table th:nth-child(5),
        .products-table td:nth-child(5),
        .products-table th:nth-child(6),
        .products-table td:nth-child(6) {
            text-align: right;
            white-space: nowrap;
        }
        
        .totals-section {
            padding: 16px;
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
            overflow: hidden;
        }
        
        .total-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-200);
            flex-wrap: wrap;
            gap: 4px;
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
            text-align: right;
            word-break: break-word;
        }
        
        .total-line.grand-total {
            margin-top: 8px;
            padding: 12px 16px;
            border-top: 2px solid var(--gray-300);
            background: var(--primary);
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
        
        .total-line.saldo-pendiente {
            background: var(--warning);
            padding: 10px 16px;
            border-radius: 10px;
            margin-top: 8px;
        }
        
        .total-line.saldo-pendiente span {
            color: var(--gray-900);
        }
        
        .total-line.saldo-pendiente span:first-child {
            font-weight: 600;
        }
        
        .total-line.saldo-pendiente span:last-child {
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
            word-break: break-word;
        }
        
        .footer-copyright {
            font-size: 0.55rem;
            opacity: 0.7;
        }
        
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
            flex-wrap: wrap;
        }
        
        .btn {
            flex: 1;
            min-width: calc(50% - 8px);
            padding: 12px;
            border-radius: 30px;
            border: none;
            font-weight: 600;
            font-size: 0.7rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        
        .btn:active { transform: scale(0.97); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-success { background: var(--success); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-warning { background: var(--warning); color: var(--gray-900); }
        .btn:disabled { opacity: 0.6; transform: none; }
        
        .action-buttons-spacer {
            height: 80px;
        }
        
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
        
        @media (max-width: 480px) {
            .info-label {
                min-width: 70px;
                font-size: 0.65rem;
            }
            
            .info-value { font-size: 0.65rem; }
            .info-block { padding: 12px; }
            .invoice-header { padding: 14px 10px; }
            .company-info h1 { font-size: 0.85rem; }
            .invoice-title h2 { font-size: 1rem; }
            .invoice-number { font-size: 0.65rem; }
            .badge { font-size: 0.6rem; }
            
            .btn {
                font-size: 0.6rem;
                padding: 10px 6px;
                min-width: calc(50% - 4px);
            }
            
            .btn i { font-size: 0.7rem; }
            .product-name { font-size: 0.75rem; }
            .product-subtotal { font-size: 0.75rem; }
            
            .total-line.grand-total span:first-child { font-size: 0.75rem; }
            .total-line.grand-total span:last-child { font-size: 0.85rem; }
            
            .products-table-container {
                margin: 0;
            }
            
            .products-table {
                min-width: 300px;
                font-size: 0.6rem;
            }
            
            .products-table th,
            .products-table td { padding: 5px 3px; }
            .products-table th:nth-child(1),
            .products-table td:nth-child(1) { width: 20px; }
            .products-table th:nth-child(3),
            .products-table td:nth-child(3) { display: none; }
            .products-table th:nth-child(5),
            .products-table td:nth-child(5),
            .products-table th:nth-child(6),
            .products-table td:nth-child(6) { font-size: 0.55rem; }
            
            .action-buttons { padding: 8px; gap: 6px; }
        }
        
        @media (max-width: 360px) {
            .info-label {
                min-width: 60px;
                font-size: 0.6rem;
            }
            
            .info-value { font-size: 0.6rem; }
            .info-block { padding: 8px; }
            .invoice-header { padding: 10px 8px; }
            .company-info h1 { font-size: 0.75rem; }
            .invoice-title h2 { font-size: 0.9rem; }
            .invoice-number { font-size: 0.6rem; }
            .badge { font-size: 0.55rem; padding: 2px 6px; }
            
            .products-table {
                min-width: 260px;
                font-size: 0.55rem;
            }
            
            .products-table th,
            .products-table td { padding: 4px 2px; }
            .products-table th:nth-child(5),
            .products-table td:nth-child(5),
            .products-table th:nth-child(6),
            .products-table td:nth-child(6) { font-size: 0.5rem; }
            
            .btn { font-size: 0.55rem; padding: 8px 6px; }
            .product-name { font-size: 0.7rem; }
            .product-subtotal { font-size: 0.7rem; }
            
            .total-line.grand-total span:first-child { font-size: 0.7rem; }
            .total-line.grand-total span:last-child { font-size: 0.8rem; }
            
            .action-buttons { padding: 6px; gap: 4px; }
        }
        
        @media print {
            body { background: white; padding: 0; }
            
            .back-button,
            .action-buttons,
            .action-buttons-spacer { display: none !important; }
            
            .invoice { border-radius: 0; box-shadow: none; }
            
            .invoice-header {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            @page { size: A4; margin: 1cm; }
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
            
            <!-- INFORMACIÓN -->
            <div class="info-sections">
                <!-- VENDEDOR -->
                <div class="info-block">
                    <h3><i class="fas fa-user-check"></i> VENDEDOR</h3>
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value"><?php echo htmlspecialchars($factura['vendedor_nombre'] ?: 'Sistema'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars(!empty($factura['vendedor_email']) ? $factura['vendedor_email'] : 'No especificado'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha:</span>
                        <span class="info-value"><?php echo formatearFecha($factura['fecha_emision']); ?></span>
                    </div>
                     <div class="info-row">
                         <span class="info-label">Pago:</span>
                         <span class="info-value"><?php echo $metodo_pago_mostrar; ?></span>
                     </div>
                      <?php 
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
                      if (($metodo_check === 'pago_movil' || $metodo_check === 'pago movil' || $metodo_check === 'transferencia' || $metodo_check === 'transferencia_bancaria') && $referencia_pago): 
                      ?>
                     <div class="info-row">
                         <span class="info-label" style="color:#3498db; font-weight:bold;"><i class="fas fa-hashtag"></i> Referencia:</span>
                         <span class="info-value" style="font-weight:bold; font-size:0.8rem;"><?php echo htmlspecialchars($referencia_pago); ?></span>
                     </div>
                     <?php endif; ?>
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
                        <span class="info-value"><?php echo htmlspecialchars($factura['cliente_documento'] ?: 'No especificado'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($factura['cliente_email'] ?: 'No especificado'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Teléfono:</span>
                        <span class="info-value"><?php echo htmlspecialchars(!empty($factura['cliente_telefono']) ? $factura['cliente_telefono'] : 'No especificado'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dirección:</span>
                        <span class="info-value"><?php echo htmlspecialchars(!empty($factura['cliente_direccion']) ? $factura['cliente_direccion'] : 'No especificada'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- PRODUCTOS -->
            <div class="products-section">
                <h3 class="section-title"><i class="fas fa-boxes"></i> PRODUCTOS</h3>
                
                <?php if (empty($detalles)): ?>
                    <div style="text-align: center; padding: 30px; color: var(--gray-500);">
                        <i class="fas fa-box-open" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                        Sin productos registrados
                    </div>
                <?php elseif (count($detalles) <= 5): ?>
                    <!-- Vista de lista para pocos productos -->
                    <div class="products-list">
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
                    </div>
                <?php else: ?>
                    <!-- Tabla para muchos productos (con scroll horizontal) -->
                    <div class="products-table-container">
                        <table class="products-table">
                            <thead>
                                <tr><th>#</th><th>Producto</th><th>SKU</th><th>Cant.</th><th>Precio</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $index => $detalle): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($detalle['producto_nombre'] ?? 'Producto no disponible'); ?></td>
                                    <td><?php echo htmlspecialchars($detalle['sku'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($detalle['cantidad'] ?? 0); ?></td>
                                    <td>Bs. <?php echo number_format($detalle['precio_unitario'] ?? 0, 2); ?></td>
                                    <td>Bs. <?php echo number_format($detalle['subtotal'] ?? 0, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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
                <div class="total-line saldo-pendiente">
                    <span>SALDO PENDIENTE</span>
                    <span>Bs. <?php echo number_format($saldo_pendiente, 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="amount-words">
                    <i class="fas fa-file-signature"></i> <strong>SON:</strong> <?php echo numeroALetras($factura['total'] ?? 0); ?>
                </div>
            </div>
            
            <!-- OBSERVACIONES si existen -->
            <?php if (!empty($factura['observaciones'])): ?>
            <div style="padding: 0 16px 16px 16px;">
                <div style="background: #fff3cd; padding: 12px; border-radius: 8px; border-left: 3px solid var(--warning);">
                    <strong style="font-size: 0.7rem;"><i class="fas fa-comment"></i> OBSERVACIONES:</strong>
                    <p style="font-size: 0.7rem; margin-top: 5px; word-break: break-word;"><?php echo nl2br(htmlspecialchars($factura['observaciones'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
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
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function fetchConCSRF(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify(body)
        });
    }

    function marcarComoPagada(id) {
        if (!confirm('¿Marcar esta factura como PAGADA?')) return;
        const btn = event.currentTarget;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetchConCSRF('procesar_factura.php', { accion: 'marcar_pagada', factura_id: id })
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
        
        fetchConCSRF('procesar_factura.php', { accion: 'anular', factura_id: id, motivo: motivo })
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
        
        fetchConCSRF('/proyecto/usuarios/enviar_factura_email.php', { factura_id: id, email: emailCliente })
        .then(response => response.json())
        .then(data => { alert(data.success ? '✅ ' + data.message : '❌ ' + data.message); })
        .catch(() => { alert('❌ Error de conexión'); })
        .finally(() => { btn.innerHTML = originalText; btn.disabled = false; });
    }
    </script>
</body>
</html>