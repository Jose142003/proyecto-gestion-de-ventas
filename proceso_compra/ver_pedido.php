<?php
// ver_pedido.php - Visualización detallada de pedido (con datos del cliente desde users)
session_start();

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
setcookie('lang', $locale, time()+31536000, '/');
\I18n::load($locale);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/interfaz_usuario/login.html'));
    exit;
}

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pedido_id <= 0) {
    header('Location: panel_admin.php');
    exit;
}

try {
    $pdo = conectarDB();
    
    $user_id = $_SESSION['user_id'];
    $es_admin = false;
    $usuario_actual = [];
    
    // Verificar si es administrador (tabla users con rol admin)
    $stmt = $pdo->prepare("SELECT id, nombre, rol FROM users WHERE id = ? AND rol IN ('admin', 'superadmin', 'ceo')");
    $stmt->execute([$user_id]);
    $admin_check = $stmt->fetch();
    
    if ($admin_check) {
        $es_admin = true;
        $usuario_actual = $admin_check;
    } else {
        // Verificar en admin_users
        $stmt = $pdo->prepare("SELECT id, nombre, rol FROM admin_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $admin_user = $stmt->fetch();
        if ($admin_user) {
            $es_admin = true;
            $usuario_actual = $admin_user;
        } else {
            // Usuario normal - verificar que el pedido le pertenezca
            $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$pedido_id, $user_id]);
            if (!$stmt->fetch()) {
                header('Location: ' . url('/interfaz_usuario/pagina_modernizada.php'));
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, nombre, rol FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $usuario_actual = $stmt->fetch();
        }
    }
    
    // Obtener datos del pedido con datos del cliente desde users
    $stmt = $pdo->prepare("
        SELECT p.*, 
               u.nombre as cliente_nombre, 
               u.correo as cliente_email, 
               u.telefono as cliente_telefono,
               u.cedula as cliente_cedula,
               u.direccion as cliente_direccion,
               u.estado as cliente_estado,
               DATE_FORMAT(u.created_at, '%d/%m/%Y') as cliente_registro
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        header('Location: panel_admin.php');
        exit;
    }
    
    // Obtener detalles del pedido
    $stmt = $pdo->prepare("
        SELECT pd.*, 
               pr.name as producto_nombre, 
               pr.sku as producto_sku, 
               pr.category as producto_categoria,
               pr.image_url as producto_imagen
        FROM pedido_detalles pd
        LEFT JOIN products pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener factura asociada
    $stmt = $pdo->prepare("SELECT * FROM facturas WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener historial de cambios
    $stmt = $pdo->prepare("
        SELECT * FROM auditoria_logs 
        WHERE tabla_afectada = 'pedidos' AND registro_id = ?
        ORDER BY fecha_creacion DESC
    ");
    $stmt->execute([$pedido_id]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error en ver_pedido: " . $e->getMessage());
    die("Error interno del servidor");
}

$subtotal = $pedido['subtotal'] ?? array_sum(array_column($detalles, 'subtotal'));
$iva = $pedido['iva'] ?? $subtotal * obtenerIvaPorcentaje($pdo) / 100;
$total = $pedido['total'] ?? $subtotal + $iva;

$estados = [
    'pendiente' => ['label' => 'Pendiente', 'color' => 'warning', 'icon' => 'fa-clock'],
    'procesando' => ['label' => 'Procesando', 'color' => 'info', 'icon' => 'fa-spinner'],
    'enviado' => ['label' => 'Enviado', 'color' => 'primary', 'icon' => 'fa-truck'],
    'entregado' => ['label' => 'Entregado', 'color' => 'success', 'icon' => 'fa-check-circle'],
    'cancelado' => ['label' => 'Cancelado', 'color' => 'danger', 'icon' => 'fa-times-circle'],
    'facturado' => ['label' => 'Facturado', 'color' => 'success', 'icon' => 'fa-file-invoice']
];

$estado_actual = $pedido['estado'] ?? 'pendiente';
$estado_info = $estados[$estado_actual] ?? ['label' => $estado_actual, 'color' => 'secondary', 'icon' => 'fa-question'];

$metodos_pago = [
    'efectivo' => ['label' => 'Efectivo', 'icon' => 'fa-money-bill-wave', 'color' => 'success'],
    'transferencia' => ['label' => 'Transferencia', 'icon' => 'fa-university', 'color' => 'info'],
    'pago_movil' => ['label' => 'Pago Móvil', 'icon' => 'fa-mobile-alt', 'color' => 'purple'],
    'mixto' => ['label' => 'Pago Mixto', 'icon' => 'fa-sync-alt', 'color' => 'warning'],
    'tarjeta' => ['label' => 'Tarjeta', 'icon' => 'fa-credit-card', 'color' => 'danger']
];

$metodo_info = $metodos_pago[$pedido['metodo_pago'] ?? ''] ?? ['label' => $pedido['metodo_pago'] ?? 'No especificado', 'icon' => 'fa-question', 'color' => 'secondary'];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido #<?php echo htmlspecialchars($pedido['numero_pedido']); ?> - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
      <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="theme-color" content="#050C18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIC Industrial">
    <link rel="apple-touch-icon" href="<?= url('/img/pic.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= url('/img/pic.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= url('/img/pic.png') ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary: #050C18;
            --secondary: #294E90;
            --accent: #3C91ED;
            --light: #7EBDE9;
            --success: #2ed573;
            --warning: #ffa502;
            --danger: #ff4757;
            --info: #3498db;
            --purple: #9B59B6;
            --dark: #1e2a3a;
            --gray: #6c757d;
            --light-gray: #f8f9fa;
            --border: #e9ecef;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
            --bg-color: #f0f2f5;
            --text-color: #2d3748;
            --card-bg: #ffffff;
        }
        body.dark-mode {
            --primary: #0a0e1a;
            --secondary: #1a1f2e;
            --accent: #3C91ED;
            --light: #5aa9e6;
            --bg-color: #0f1219;
            --text-color: #e4e6eb;
            --card-bg: #1e2436;
            --border: #2c3348;
            --shadow: 0 4px 6px rgba(0,0,0,0.3);
            --light-gray: #1a1f2e;
            --gray: #aaa;
            --dark: #e4e6eb;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-color);
            min-height: 100vh;
            padding: 20px;
            color: var(--text-color);
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 20px 30px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: var(--shadow);
        }
        .header-left h1 { font-size: 1.5rem; font-weight: 700; color: var(--primary); margin-bottom: 5px; }
        .header-left p { color: var(--gray); font-size: 0.85rem; }
        .header-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        .btn-primary { background: linear-gradient(135deg, var(--accent), var(--secondary)); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-secondary { background: var(--light-gray); color: var(--dark); border: 1px solid var(--border); }
        .btn-secondary:hover { background: var(--border); }
        .btn-success { background: var(--success); color: white; }
        .btn-warning { background: var(--warning); color: white; }
        .btn-danger { background: var(--danger); color: white; }
        .btn-info { background: var(--info); color: white; }
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .estado-pendiente { background: #fff3e0; color: #e67e22; }
        .estado-procesando { background: #e3f2fd; color: #1976d2; }
        .estado-enviado { background: #e8f5e9; color: #388e3c; }
        .estado-entregado { background: #e8f5e9; color: #2e7d32; }
        .estado-cancelado { background: #ffebee; color: #c62828; }
        .estado-facturado { background: #e8f5e9; color: #2e7d32; }
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        .card-header {
            padding: 20px 25px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h2 { font-size: 1.2rem; font-weight: 600; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 25px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        @media (max-width: 992px) { .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; } }
        .info-group {
            background: var(--light-gray);
            border-radius: 12px;
            padding: 15px;
        }
        .info-group label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--gray);
            display: block;
            margin-bottom: 5px;
        }
        .info-group .value { font-size: 1rem; font-weight: 600; color: var(--dark); }
        .table-responsive { overflow-x: auto; }
        .productos-table {
            width: 100%;
            border-collapse: collapse;
        }
        .productos-table th {
            text-align: left;
            padding: 12px;
            background: var(--light-gray);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--dark);
        }
        .productos-table td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .productos-table tr:hover td { background: rgba(60, 145, 237, 0.05); }
        .producto-imagen {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .totales {
            background: var(--light-gray);
            border-radius: 12px;
            padding: 20px;
            text-align: right;
        }
        .total-row {
            display: flex;
            justify-content: flex-end;
            gap: 30px;
            padding: 8px 0;
        }
        .total-grande {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--accent);
            border-top: 2px solid var(--border);
            margin-top: 10px;
            padding-top: 10px;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .timeline-dot {
            position: absolute;
            left: -26px;
            top: 0;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent);
        }
        .timeline-date { font-size: 0.7rem; color: var(--gray); margin-bottom: 5px; }
        .timeline-title { font-weight: 600; margin-bottom: 5px; }
        .timeline-desc { font-size: 0.85rem; color: var(--gray); }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--card-bg);
            border-radius: 16px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body { padding: 25px; }
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 0.85rem; }
        .form-control, .form-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(60, 145, 237, 0.1);
        }
        textarea.form-control { resize: vertical; min-height: 80px; }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 10px;
            color: white;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .notification.success { background: var(--success); }
        .notification.error { background: var(--danger); }
        .notification.warning { background: var(--warning); }
        .notification.info { background: var(--info); }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @media print {
            body { background: white; padding: 0; }
            .no-print { display: none !important; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
        .cliente-detalle-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .cliente-detalle-label {
            width: 140px;
            font-weight: 600;
            color: var(--gray);
        }
        .cliente-detalle-value {
            flex: 1;
            color: var(--dark);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header no-print">
            <div class="header-left">
                <h1><i class="fas fa-shopping-cart"></i> Pedido #<?php echo htmlspecialchars($pedido['numero_pedido']); ?></h1>
                <p>Fecha: <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'] ?? $pedido['created_at'] ?? 'now')); ?></p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" id="themeToggle" style="min-width:44px;"><i class="fas fa-moon"></i></button>
                <button class="btn btn-secondary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
                <button class="btn btn-primary" onclick="generarPDF()"><i class="fas fa-file-pdf"></i> PDF</button>
                <a href="<?= url('/panel_admin/panel_admin.php') ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            </div>
        </div>

        <!-- Estado del Pedido -->
        <div class="card">
            <div class="card-body">
                <div class="grid-4">
                    <div class="info-group">
                        <label><i class="fas fa-tag"></i> Estado</label>
                        <div class="value">
                            <span class="estado-badge estado-<?php echo $estado_actual; ?>">
                                <i class="fas <?php echo $estado_info['icon']; ?>"></i>
                                <?php echo $estado_info['label']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-group">
                        <label><i class="fas fa-credit-card"></i> Método de Pago</label>
                        <div class="value">
                            <span style="color: var(--<?php echo $metodo_info['color']; ?>);">
                                <i class="fas <?php echo $metodo_info['icon']; ?>"></i>
                                <?php echo $metodo_info['label']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-group">
                        <label><i class="fas fa-money-bill-wave"></i> Total</label>
                        <div class="value" style="font-size: 1.3rem; color: var(--accent);">
                            Bs. <?php echo number_format($total, 2, ',', '.'); ?>
                        </div>
                    </div>
                    <div class="info-group">
                        <label><i class="fas fa-file-invoice"></i> Factura</label>
                        <div class="value">
                            <?php if ($factura): ?>
                                <i class="fas fa-check-circle" style="color: var(--success);"></i>
                                <?php echo htmlspecialchars($factura['numero_factura']); ?>
                            <?php else: ?>
                                <span class="text-muted">No facturado</span>
                                <?php if ($es_admin && $estado_actual != 'cancelado'): ?>
                                    <button class="btn-success" onclick="facturarPedido()" style="margin-left: 10px; padding: 5px 10px; font-size: 0.75rem; border: none; border-radius: 5px; cursor: pointer;">
                                        <i class="fas fa-file-invoice"></i> Facturar
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información del Cliente (desde users) -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user"></i> Información del Cliente</h2>
            </div>
            <div class="card-body">
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Nombre completo:</div>
                    <div class="cliente-detalle-value"><?php echo htmlspecialchars($pedido['cliente_nombre'] ?? 'N/A'); ?></div>
                </div>
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Correo electrónico:</div>
                    <div class="cliente-detalle-value"><?php echo htmlspecialchars($pedido['cliente_email'] ?? 'N/A'); ?></div>
                </div>
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Teléfono:</div>
                    <div class="cliente-detalle-value"><?php echo htmlspecialchars($pedido['cliente_telefono'] ?? 'No registrado'); ?></div>
                </div>
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Cédula / RIF:</div>
                    <div class="cliente-detalle-value"><?php echo htmlspecialchars($pedido['cliente_cedula'] ?? 'No registrado'); ?></div>
                </div>
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Dirección:</div>
                    <div class="cliente-detalle-value"><?php echo nl2br(htmlspecialchars($pedido['cliente_direccion'] ?? 'No registrada')); ?></div>
                </div>
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Estado del cliente:</div>
                    <div class="cliente-detalle-value">
                        <span class="estado-badge estado-<?php echo ($pedido['cliente_estado'] ?? 'activo') == 'activo' ? 'entregado' : 'cancelado'; ?>" style="padding: 4px 10px; font-size: 0.75rem;">
                            <?php echo ucfirst($pedido['cliente_estado'] ?? 'Activo'); ?>
                        </span>
                    </div>
                </div>
                <div class="cliente-detalle-row">
                    <div class="cliente-detalle-label">Cliente desde:</div>
                    <div class="cliente-detalle-value"><?php echo htmlspecialchars($pedido['cliente_registro'] ?? 'No disponible', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <!-- Dirección de Envío -->
        <?php if (!empty($pedido['direccion_envio'])): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-map-marker-alt"></i> Dirección de Envío</h2>
            </div>
            <div class="card-body">
                <p><?php echo nl2br(htmlspecialchars($pedido['direccion_envio'])); ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Información de Envío / Rastreo -->
        <?php
        $envio_data = null;
        $envio_historial = [];
        $estados_envio = [
            'preparando' => ['label' => 'Preparando', 'color' => '#ffa502', 'bg' => '#fff3e0', 'icon' => 'fa-box'],
            'en_transito' => ['label' => 'En Tránsito', 'color' => '#1976d2', 'bg' => '#e3f2fd', 'icon' => 'fa-truck'],
            'en_reparto' => ['label' => 'En Reparto', 'color' => '#1565c0', 'bg' => '#e3f2fd', 'icon' => 'fa-shipping-fast'],
            'entregado' => ['label' => 'Entregado', 'color' => '#2e7d32', 'bg' => '#e8f5e9', 'icon' => 'fa-check-circle'],
            'fallido' => ['label' => 'Fallido', 'color' => '#c62828', 'bg' => '#ffebee', 'icon' => 'fa-times-circle']
        ];
        if (!empty($pedido['transportista']) || !empty($pedido['numero_guia'])) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM envios WHERE pedido_id = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$pedido_id]);
                $envio_data = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($envio_data) {
                    $stmt = $pdo->prepare("SELECT * FROM envios_historial WHERE envio_id = ? ORDER BY created_at ASC");
                    $stmt->execute([$envio_data['id']]);
                    $envio_historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                error_log("Error al obtener envio: " . $e->getMessage());
            }
        }
        ?>
        <?php if ($envio_data || !empty($pedido['transportista'])): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-truck"></i> Información de Envío</h2>
                <?php if ($envio_data): ?>
                <a href="<?= url('/envios/envio_detalle.php?envio_id=') ?><?php echo $envio_data['id']; ?>" class="btn btn-primary btn-sm" style="padding: 5px 10px; font-size: 0.75rem; text-decoration: none;">
                    <i class="fas fa-external-link-alt"></i> Ver detalle completo
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Transportista:</div>
                            <div class="cliente-detalle-value">
                                <?php echo htmlspecialchars($envio_data['transportista'] ?? $pedido['transportista']); ?>
                            </div>
                        </div>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">N° Guía:</div>
                            <div class="cliente-detalle-value" style="font-family: monospace;">
                                <?php echo htmlspecialchars($envio_data['numero_guia'] ?? $pedido['numero_guia']); ?>
                            </div>
                        </div>
                        <?php if (!empty($envio_data['url_rastreo'])): ?>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Rastreo:</div>
                            <div class="cliente-detalle-value">
                                <a href="<?php echo htmlspecialchars($envio_data['url_rastreo']); ?>" target="_blank" style="color: var(--accent);">
                                    <i class="fas fa-external-link-alt"></i> Rastrear envío
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($pedido['costo_envio']) && floatval($pedido['costo_envio']) > 0): ?>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Costo Envío:</div>
                            <div class="cliente-detalle-value">Bs. <?php echo number_format(floatval($pedido['costo_envio']), 2, ',', '.'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($envio_data): ?>
                    <div>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Estado:</div>
                            <div class="cliente-detalle-value">
                                <?php $ee = $estados_envio[$envio_data['estado']] ?? ['label' => $envio_data['estado'], 'color' => '#6c757d', 'bg' => '#f8f9fa']; ?>
                                <span class="estado-badge" style="background: <?php echo $ee['bg']; ?>; color: <?php echo $ee['color']; ?>; padding: 5px 12px; border-radius: 50px; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fas <?php echo $ee['icon']; ?>"></i> <?php echo $ee['label']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Fecha Envío:</div>
                            <div class="cliente-detalle-value">
                                <?php echo $envio_data['fecha_envio'] ? date('d/m/Y H:i', strtotime($envio_data['fecha_envio'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Est. Entrega:</div>
                            <div class="cliente-detalle-value">
                                <?php echo $envio_data['fecha_estimada_entrega'] ? date('d/m/Y', strtotime($envio_data['fecha_estimada_entrega'])) : 'N/A'; ?>
                            </div>
                        </div>
                        <?php if ($envio_data['fecha_entrega']): ?>
                        <div class="cliente-detalle-row">
                            <div class="cliente-detalle-label">Entregado:</div>
                            <div class="cliente-detalle-value">
                                <?php echo date('d/m/Y H:i', strtotime($envio_data['fecha_entrega'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (count($envio_historial) > 0): ?>
                <h3 style="font-size: 0.95rem; font-weight: 600; color: var(--primary); margin-top: 20px; margin-bottom: 15px;">
                    <i class="fas fa-history"></i> Timeline del Envío
                </h3>
                <div class="timeline">
                    <?php foreach ($envio_historial as $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-date"><?php echo date('d/m/Y H:i:s', strtotime($h['created_at'])); ?></div>
                        <div class="timeline-title">
                            <?php echo htmlspecialchars($estados_envio[$h['estado_nuevo']]['label'] ?? $h['estado_nuevo']); ?>
                        </div>
                        <?php if (!empty($h['ubicacion'])): ?>
                        <div class="timeline-desc"><i class="fas fa-map-marker-alt" style="color: var(--accent);"></i> <?php echo htmlspecialchars($h['ubicacion']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($h['descripcion'])): ?>
                        <div class="timeline-desc"><?php echo htmlspecialchars($h['descripcion']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notas -->
        <?php if (!empty($pedido['notas_cliente']) || !empty($pedido['notas_internas'])): ?>
        <div class="grid-2">
            <?php if (!empty($pedido['notas_cliente'])): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-comment"></i> Notas del Cliente</h2>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($pedido['notas_cliente'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($pedido['notas_internas']) && $es_admin): ?>
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-sticky-note"></i> Notas Internas</h2>
                    <button class="btn btn-secondary btn-sm" onclick="editarNotas()" style="padding: 5px 10px; font-size: 0.75rem;">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                </div>
                <div class="card-body">
                    <p><?php echo nl2br(htmlspecialchars($pedido['notas_internas'])); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Productos del Pedido -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-boxes"></i> Productos del Pedido</h2>
                <span class="text-muted">Total de productos: <?php echo count($detalles); ?></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="productos-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Código</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $contador = 1; ?>
                            <?php foreach ($detalles as $detalle): ?>
                            <tr>
                                <td><?php echo $contador++; ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <?php if (!empty($detalle['producto_imagen'])): ?>
                                            <img src="<?php echo htmlspecialchars($detalle['producto_imagen']); ?>" alt="Producto" class="producto-imagen" onerror="this.style.display='none'">
                                        <?php else: ?>
                                            <div style="width: 50px; height: 50px; background: var(--light-gray); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-box" style="color: var(--gray);"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($detalle['producto_nombre'] ?? $detalle['producto_id']); ?></strong>
                                            <?php if (!empty($detalle['producto_categoria'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($detalle['producto_categoria']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($detalle['producto_sku'] ?? 'N/A'); ?></td>
                                <td><?php echo $detalle['cantidad']; ?></td>
                                <td>Bs. <?php echo number_format($detalle['precio_unitario'], 2, ',', '.'); ?></td>
                                <td>Bs. <?php echo number_format($detalle['subtotal'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="totales" style="margin-top: 20px;">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <strong>Bs. <?php echo number_format($subtotal, 2, ',', '.'); ?></strong>
                    </div>
                    <div class="total-row">
                        <span>IVA (16%):</span>
                        <strong>Bs. <?php echo number_format($iva, 2, ',', '.'); ?></strong>
                    </div>
                    <?php if (!empty($pedido['descuento']) && $pedido['descuento'] > 0): ?>
                    <div class="total-row">
                        <span>Descuento:</span>
                        <strong>- Bs. <?php echo number_format($pedido['descuento'], 2, ',', '.'); ?></strong>
                    </div>
                    <?php endif; ?>
                    <div class="total-row total-grande">
                        <span>TOTAL:</span>
                        <strong>Bs. <?php echo number_format($total, 2, ',', '.'); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Historial de Estados -->
        <?php if (!empty($historial) && $es_admin): ?>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Historial de Cambios</h2>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <?php foreach ($historial as $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-date"><?php echo date('d/m/Y H:i:s', strtotime($h['fecha_creacion'])); ?></div>
                        <div class="timeline-title">
                            <?php 
                            if ($h['accion'] == 'CAMBIO_ESTADO') {
                                echo 'Cambio de estado';
                            } elseif ($h['accion'] == 'ACTUALIZAR') {
                                echo 'Actualización del pedido';
                            } else {
                                echo htmlspecialchars($h['accion']);
                            }
                            ?>
                            <small style="color: var(--gray);"> - <?php echo htmlspecialchars($h['usuario_nombre'] ?? 'Sistema'); ?></small>
                        </div>
                        <div class="timeline-desc">
                            <?php echo htmlspecialchars($h['descripcion'] ?? 'Sin descripción'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botones de Acción para Administradores -->
        <?php if ($es_admin): ?>
        <div class="card no-print">
            <div class="card-header">
                <h2><i class="fas fa-cog"></i> Acciones del Pedido</h2>
            </div>
            <div class="card-body">
                <div class="grid-4" style="gap: 15px;">
                    <button class="btn btn-warning" onclick="cambiarEstado('pendiente')" <?php echo $estado_actual == 'pendiente' ? 'disabled' : ''; ?>>
                        <i class="fas fa-clock"></i> Pendiente
                    </button>
                    <button class="btn btn-info" onclick="cambiarEstado('procesando')" <?php echo $estado_actual == 'procesando' ? 'disabled' : ''; ?>>
                        <i class="fas fa-spinner"></i> Procesando
                    </button>
                    <button class="btn btn-primary" onclick="cambiarEstado('enviado')" <?php echo $estado_actual == 'enviado' ? 'disabled' : ''; ?>>
                        <i class="fas fa-truck"></i> Enviado
                    </button>
                    <button class="btn btn-success" onclick="cambiarEstado('entregado')" <?php echo $estado_actual == 'entregado' ? 'disabled' : ''; ?>>
                        <i class="fas fa-check-circle"></i> Entregado
                    </button>
                    <button class="btn btn-danger" onclick="cambiarEstado('cancelado')" <?php echo $estado_actual == 'cancelado' ? 'disabled' : ''; ?>>
                        <i class="fas fa-times-circle"></i> Cancelar
                    </button>
                    <?php if (!$factura && $estado_actual != 'cancelado'): ?>
                    <button class="btn btn-success" onclick="facturarPedido()">
                        <i class="fas fa-file-invoice"></i> Generar Factura
                    </button>
                    <?php endif; ?>
                    <button class="btn btn-info" onclick="enviarNotificacion()">
                        <i class="fas fa-bell"></i> Notificar Cliente
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal para editar notas internas -->
    <div id="notasModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Editar Notas Internas</h3>
                <button class="modal-close" onclick="cerrarModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Notas internas</label>
                    <textarea id="notasInternas" class="form-control" rows="4"><?php echo htmlspecialchars($pedido['notas_internas'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarNotas()">Guardar</button>
            </div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-spinner"></div>
        <p style="color: white; margin-top: 15px;">Procesando...</p>
    </div>

    <script>
        function mostrarLoading() { document.getElementById('loadingOverlay').style.display = 'flex'; }
        function ocultarLoading() { document.getElementById('loadingOverlay').style.display = 'none'; }
        
        function mostrarNotificacion(mensaje, tipo) {
            const notif = document.createElement('div');
            notif.className = `notification ${tipo}`;
            notif.innerHTML = `<i class="fas ${tipo === 'success' ? 'fa-check-circle' : (tipo === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle')}"></i> ${mensaje}`;
            document.body.appendChild(notif);
            setTimeout(() => notif.remove(), 3000);
        }
        
        function cerrarModal() { document.getElementById('notasModal').classList.remove('active'); }
        function editarNotas() { document.getElementById('notasModal').classList.add('active'); }
        
        async function guardarNotas() {
            const notas = document.getElementById('notasInternas').value;
            mostrarLoading();
            
            try {
                const response = await fetch('<?= url('/proceso_compra/actualizar_pedido.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: <?php echo $pedido_id; ?>, notas_internas: notas }),
                    credentials: 'include'
                });
                
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('Notas actualizadas correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarNotificacion(data.message || 'Error al actualizar notas', 'error');
                    cerrarModal();
                }
            } catch (error) {
                mostrarNotificacion('Error al conectar con el servidor', 'error');
                cerrarModal();
            } finally {
                ocultarLoading();
            }
        }
        
        async function cambiarEstado(estado) {
            if (!confirm(`¿Estás seguro de cambiar el estado a "${estado.toUpperCase()}"?`)) return;
            mostrarLoading();
            
            try {
                const response = await fetch('<?= url('/proceso_compra/actualizar_pedido.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: <?php echo $pedido_id; ?>, estado: estado }),
                    credentials: 'include'
                });
                
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('Estado actualizado correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarNotificacion(data.message || 'Error al cambiar estado', 'error');
                }
            } catch (error) {
                mostrarNotificacion('Error al conectar con el servidor', 'error');
            } finally {
                ocultarLoading();
            }
        }
        
        async function facturarPedido() {
            mostrarLoading();
            
            try {
                const response = await fetch('<?= url('/facturacion/facturar_pedido.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: <?php echo $pedido_id; ?> }),
                    credentials: 'include'
                });
                
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('Factura generada correctamente', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    mostrarNotificacion(data.message || 'Error al generar factura', 'error');
                }
            } catch (error) {
                mostrarNotificacion('Error al conectar con el servidor', 'error');
            } finally {
                ocultarLoading();
            }
        }
        
        function generarPDF() {
            window.open(`<?= url('/producto/generar_pdf_pedido.php') ?>?id=<?php echo $pedido_id; ?>`, '_blank');
        }
        
        async function enviarNotificacion() {
            mostrarLoading();
            
            try {
                const response = await fetch('<?= url('/usuarios/enviar_notificacion_pedido.php') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: <?php echo $pedido_id; ?> }),
                    credentials: 'include'
                });
                
                const data = await response.json();
                if (data.success) {
                    mostrarNotificacion('Notificación enviada al cliente', 'success');
                } else {
                    mostrarNotificacion(data.message || 'Error al enviar notificación', 'error');
                }
            } catch (error) {
                mostrarNotificacion('Error al conectar con el servidor', 'error');
            } finally {
                ocultarLoading();
            }
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') cerrarModal();
        });
        
        document.getElementById('notasModal').addEventListener('click', function(e) {
            if (e.target === this) cerrarModal();
        });
    </script>
<script>
(function() {
    const toggle = document.getElementById('themeToggle');
    function applyDark(isDark) {
        document.body.classList.toggle('dark-mode', isDark);
        if (toggle) {
            toggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }
    }
    const saved = localStorage.getItem('darkMode');
    if (saved === 'enabled') applyDark(true);
    else if (saved === 'disabled') applyDark(false);
    if (toggle) {
        toggle.addEventListener('click', function() {
            const isDark = !document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            applyDark(isDark);
        });
    }
})();
</script>
</body>
</html>