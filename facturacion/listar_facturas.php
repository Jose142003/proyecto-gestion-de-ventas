<?php
// /proyecto/facturacion/listar_facturas.php
session_start();
require_once '../conexion/conexion.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
setcookie('lang', $locale, time()+31536000, '/');
\I18n::load($locale);

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/interfaz_usuario/login.html'));
    exit;
}

$pdo = conectarDB();
$pdo->exec("SET time_zone = '-04:00'");
date_default_timezone_set('America/Caracas');

// Obtener el rol del usuario - PRIMERO verificar en admin_users
$user_id = $_SESSION['user_id'] ?? 0;
$rol = '';

// Verificar si el usuario es admin (está en admin_users)
try {
    $query_admin = "SELECT rol FROM admin_users WHERE id = :user_id AND activo = 1";
    $stmt_admin = $pdo->prepare($query_admin);
    $stmt_admin->execute([':user_id' => $user_id]);
    $admin = $stmt_admin->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Es un usuario administrador
        $rol = $admin['rol'];
        $_SESSION['rol'] = $rol;
        $_SESSION['es_admin'] = true;
    } else {
        // Verificar en users (clientes normales)
        $query_user = "SELECT rol FROM users WHERE id = :user_id AND is_active = 1";
        $stmt_user = $pdo->prepare($query_user);
        $stmt_user->execute([':user_id' => $user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $rol = $user['rol'];
            $_SESSION['rol'] = $rol;
            $_SESSION['es_admin'] = false;
        }
    }
} catch (PDOException $e) {
    error_log("Error al obtener rol: " . $e->getMessage());
}

// Definir roles permitidos para facturación
$roles_permitidos = ['superadmin', 'admin', 'facturador'];

// Si no tiene rol permitido, mostrar mensaje amigable
if (!in_array($rol, $roles_permitidos)) {
    ?>
    <!DOCTYPE html>
<html lang="<?php echo $locale; ?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso Denegado - PIC</title>
        <style>
            :root {
                --primary-color: #050C18;
                --secondary-color: #294E90;
                --accent-color: #3C91ED;
                --light-color: #7EBDE9;
                --danger: #ff4757;
            }
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .denied-container {
                background: white;
                border-radius: 20px;
                padding: 40px;
                text-align: center;
                max-width: 500px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                animation: slideIn 0.5s ease;
            }
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-50px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            .denied-icon {
                font-size: 80px;
                margin-bottom: 20px;
            }
            h1 {
                color: var(--danger);
                margin-bottom: 15px;
                font-size: 1.8rem;
            }
            p {
                color: #666;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .user-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: left;
            }
            .user-info p {
                margin: 5px 0;
                font-size: 0.9rem;
            }
            .btn {
                display: inline-block;
                padding: 12px 25px;
                margin: 5px;
                border-radius: 10px;
                text-decoration: none;
                font-weight: 600;
                transition: all 0.3s;
            }
            .btn-primary {
                background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            @media (max-width: 768px) {
                .denied-container {
                    padding: 25px;
                }
                .denied-icon {
                    font-size: 60px;
                }
                h1 {
                    font-size: 1.5rem;
                }
            }
        </style>
    </head>
    <body>
        <div class="denied-container">
            <div class="denied-icon">🔒</div>
            <h1>Acceso Denegado</h1>
            <p>No tienes permisos suficientes para acceder a la sección de facturación.</p>
            
            <div class="user-info">
                <p><strong>Usuario ID:</strong> <?php echo htmlspecialchars($user_id ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Rol actual:</strong> <?php echo htmlspecialchars($rol ?: 'No definido'); ?></p>
                <p><strong>Roles requeridos:</strong> <?php echo implode(', ', $roles_permitidos); ?></p>
                <p><strong>Tipo de usuario:</strong> <?php echo isset($_SESSION['es_admin']) && $_SESSION['es_admin'] ? 'Administrador' : 'Cliente normal'; ?></p>
            </div>
            
            <div>
                <a href='<?= url('/panel_admin/panel_admin.php') ?>' class="btn btn-primary">🏠 Ir al Panel Admin</a>
                <a href="javascript:history.back()" class="btn btn-secondary">← Volver</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Si llegamos aquí, el usuario tiene permisos
// Continuar con la consulta de facturas...
?>
<!DOCTYPE html>
<html lang="<?php echo $locale; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Facturas - PIC</title>
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="<?= url('/manifest.json') ?>">
    <meta name="theme-color" content="#050C18">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PIC Industrial">
    <link rel="apple-touch-icon" href="<?= url('/img/pic.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= url('/img/pic.png') ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?= url('/img/pic.png') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #050C18;
            --secondary-color: #294E90;
            --accent-color: #3C91ED;
            --light-color: #7EBDE9;
            --bg-color: #F3F3F3;
            --text-color: #050C18;
            --card-bg: #ffffff;
            --header-bg: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            --success: #2ed573;
            --warning: #ffa502;
            --danger: #ff4757;
            --info: #3498db;
            --border-color: #d1d8e6;
            --table-hover: rgba(60, 145, 237, 0.05);
        }

        body.dark-mode {
            --primary-color: #0a0e1a;
            --secondary-color: #1a1f2e;
            --accent-color: #3C91ED;
            --light-color: #5aa9e6;
            --bg-color: #0f1219;
            --text-color: #e4e6eb;
            --card-bg: #1e2436;
            --header-bg: linear-gradient(135deg, #0a0e1a, #1a1f2e);
            --border-color: #2c3348;
            --table-hover: rgba(60, 145, 237, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header con estilo del panel_admin */
        .header {
            background: var(--header-bg);
            color: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1 i {
            font-size: 1.8rem;
        }

        .user-role {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            margin-top: 5px;
            display: inline-block;
        }

        .btn-nueva {
            background: white;
            color: var(--secondary-color);
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-nueva:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-volver {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-volver:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Filtros - estilo consistente */
        .filtros {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .filtros-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filtro-group {
            flex: 1;
            min-width: 150px;
        }

        .filtro-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.85rem;
            color: var(--text-color);
            opacity: 0.7;
            font-weight: 600;
        }

        .filtro-group input,
        .filtro-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s;
            font-family: inherit;
        }

        .filtro-group input:focus,
        .filtro-group select:focus {
            outline: none;
            border-color: var(--accent-color);
        }

        .btn-filtrar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-filtrar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-limpiar {
            background: #6c757d;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-limpiar:hover {
            transform: translateY(-2px);
        }

        /* Tabla - estilo consistente con panel_admin */
        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .data-table th {
            padding: 15px;
            text-align: left;
            background: linear-gradient(135deg, var(--accent-color), var(--light-color));
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-color);
            font-size: 0.85rem;
        }

        .data-table tr:hover td {
            background-color: rgba(60, 145, 237, 0.05);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-pendiente { background: var(--warning); color: #856404; }
        .badge-pagada { background: var(--success); color: white; }
        .badge-vencida { background: var(--danger); color: white; }
        .badge-anulada { background: #6c757d; color: white; }

        /* Botones de acción */
        .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-accion {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.75rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-ver {
            background: var(--info);
            color: white;
        }

        .btn-editar {
            background: var(--warning);
            color: white;
        }

        .btn-pdf {
            background: var(--danger);
            color: white;
        }

        .btn-accion:hover {
            transform: translateY(-2px);
        }

        .empty-state {
            text-align: center;
            padding: 50px;
            color: var(--text-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
            opacity: 0.3;
        }

        /* Resumen */
        .resumen-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 15px 25px;
            margin-top: 20px;
            text-align: right;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .resumen-card strong {
            font-size: 1rem;
        }

        .resumen-card small {
            color: var(--text-color);
            opacity: 0.6;
        }

        /* Responsive */
        @media (max-width: 992px) {
            body {
                padding: 15px;
            }

            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .data-table th,
            .data-table td {
                padding: 10px;
                font-size: 0.75rem;
            }

            .acciones {
                flex-direction: column;
            }

            .btn-accion {
                text-align: center;
                justify-content: center;
            }

            .filtro-group {
                min-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .header > div:last-child {
                display: flex;
                justify-content: center;
                width: 100%;
            }
        }

        @media print {
            .header, .filtros, .acciones {
                display: none;
            }
            body {
                background: white;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-file-invoice-dollar"></i> Listado de Facturas</h1>
                <div class="user-role"><i class="fas fa-user-shield"></i> Rol: <?php echo htmlspecialchars($rol); ?></div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button id="themeToggle" style="background:rgba(255,255,255,0.2); border:none; color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-moon"></i></button>
                <a href='<?= url('/panel_admin/panel_admin.php') ?>' class="btn-volver"><i class="fas fa-arrow-left"></i> Panel Admin</a>
                <?php if (in_array($rol, ['superadmin', 'admin', 'facturador'])): ?>
                    <a href='<?= url('/facturacion/nueva_factura.php') ?>' class="btn-nueva"><i class="fas fa-plus"></i> Nueva Factura</a>
                <?php endif; ?>
            </div>
        </div>

        <?php
        // Obtener facturas
        try {
            // Obtener filtros
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
            $fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
            $fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

            // Construir consulta
            $query = "SELECT 
                        f.*,
                        c.nombre as cliente_nombre,
                        c.email as cliente_email,
                        c.documento as cliente_documento,
                        c.telefono as cliente_telefono
                      FROM facturas f
                      LEFT JOIN clientes c ON f.cliente_id = c.id
                      WHERE 1=1";

            $params = [];

            if ($search) {
                $query .= " AND (f.numero_factura LIKE :search OR c.nombre LIKE :search OR c.email LIKE :search OR c.documento LIKE :search)";
                $params[':search'] = "%$search%";
            }

            if ($estado) {
                $query .= " AND f.estado = :estado";
                $params[':estado'] = $estado;
            }

            if ($fecha_desde) {
                $query .= " AND DATE(f.fecha_emision) >= :fecha_desde";
                $params[':fecha_desde'] = $fecha_desde;
            }

            if ($fecha_hasta) {
                $query .= " AND DATE(f.fecha_emision) <= :fecha_hasta";
                $params[':fecha_hasta'] = $fecha_hasta;
            }

            $query .= " ORDER BY f.id DESC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $facturas = [];
            echo '<div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px;">Error al cargar facturas: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // Función segura para formatear fechas
        function formatearFecha($fecha, $formato = 'd/m/Y') {
            if (empty($fecha) || $fecha === null) {
                return 'No disponible';
            }
            try {
                $dateTime = new DateTime($fecha);
                return $dateTime->format($formato);
            } catch (Exception $e) {
                return 'Fecha inválida';
            }
        }

        // Estados de factura
        $estados_factura = [
            'pendiente' => 'Pendiente',
            'pagada' => 'Pagada',
            'vencida' => 'Vencida',
            'anulada' => 'Anulada'
        ];

        // Métodos de pago
        $metodos_pago = [
            'efectivo' => 'Efectivo',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'tarjeta_debito' => 'Tarjeta de Débito',
            'transferencia' => 'Transferencia',
            'paypal' => 'PayPal',
            'mercadopago' => 'Mercado Pago'
        ];
        ?>

        <!-- Filtros -->
        <div class="filtros">
            <form method="GET" class="filtros-form">
                <div class="filtro-group">
                    <label><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" name="search" placeholder="N° factura, cliente..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                </div>
                <div class="filtro-group">
                    <label><i class="fas fa-tag"></i> Estado</label>
                    <select name="estado">
                        <option value="">Todos</option>
                        <?php foreach ($estados_factura as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($estado ?? '') == $key ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filtro-group">
                    <label><i class="fas fa-calendar-alt"></i> Fecha desde</label>
                    <input type="date" name="fecha_desde" value="<?php echo htmlspecialchars($fecha_desde ?? ''); ?>">
                </div>
                <div class="filtro-group">
                    <label><i class="fas fa-calendar-alt"></i> Fecha hasta</label>
                    <input type="date" name="fecha_hasta" value="<?php echo htmlspecialchars($fecha_hasta ?? ''); ?>">
                </div>
                <div class="filtro-group">
                    <button type="submit" class="btn-filtrar"><i class="fas fa-filter"></i> Filtrar</button>
                    <a href="listar_facturas.php" class="btn-limpiar"><i class="fas fa-trash-alt"></i> Limpiar</a>
                </div>
            </form>
        </div>

        <!-- Tabla de facturas -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> N° Factura</th>
                        <th><i class="fas fa-user"></i> Cliente</th>
                        <th><i class="fas fa-id-card"></i> Documento</th>
                        <th><i class="fas fa-calendar-day"></i> Fecha Emisión</th>
                        <th><i class="fas fa-calendar-times"></i> Fecha Vencimiento</th>
                        <th><i class="fas fa-dollar-sign"></i> Total</th>
                        <th><i class="fas fa-chart-simple"></i> Estado</th>
                        <th><i class="fas fa-credit-card"></i> Método Pago</th>
                        <th width="150"><i class="fas fa-cogs"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($facturas)): ?>
                        <tr>
                            <td colspan="9" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                No se encontraron facturas
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($facturas as $factura): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($factura['numero_factura']); ?></strong>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($factura['cliente_nombre'] ?? 'N/A'); ?>
                                    <br>
                                    <small style="opacity:0.6;"><?php echo htmlspecialchars($factura['cliente_email'] ?? ''); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($factura['cliente_documento'] ?? 'N/A'); ?></td>
                                <td><?php echo formatearFecha($factura['fecha_emision'] ?? null); ?></td>
                                <td>
                                    <?php 
                                    $fecha_vencimiento = $factura['fecha_vencimiento'] ?? null;
                                    echo formatearFecha($fecha_vencimiento);
                                    ?>
                                </td>
                                <td>
                                    <strong>$<?php echo number_format(floatval($factura['total'] ?? 0), 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $estado_actual = $factura['estado'] ?? 'pendiente';
                                    $badge_class = '';
                                    switch ($estado_actual) {
                                        case 'pendiente': $badge_class = 'badge-pendiente'; break;
                                        case 'pagada': $badge_class = 'badge-pagada'; break;
                                        case 'vencida': $badge_class = 'badge-vencida'; break;
                                        case 'anulada': $badge_class = 'badge-anulada'; break;
                                        default: $badge_class = 'badge-pendiente';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $estados_factura[$estado_actual] ?? ucfirst($estado_actual); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $metodo = $factura['metodo_pago'] ?? 'efectivo';
                                    echo $metodos_pago[$metodo] ?? ucfirst($metodo); 
                                    ?>
                                </td>
                                <td class="acciones">
                                    <a href="ver_factura.php?id=<?php echo $factura['id']; ?>" class="btn-accion btn-ver"><i class="fas fa-eye"></i> Ver</a>
                                    <?php if (in_array($rol, ['superadmin', 'admin', 'facturador']) && ($factura['estado'] ?? '') !== 'pagada' && ($factura['estado'] ?? '') !== 'anulada'): ?>
                                        <a href="editar_factura.php?id=<?php echo $factura['id']; ?>" class="btn-accion btn-editar"><i class="fas fa-edit"></i> Editar</a>
                                    <?php endif; ?>
                                    <a href="generar_pdf.php?id=<?php echo $factura['id']; ?>" class="btn-accion btn-pdf" target="_blank"><i class="fas fa-file-pdf"></i> PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen -->
        <?php if (!empty($facturas)): ?>
            <div class="resumen-card">
                <strong><i class="fas fa-chart-line"></i> Total facturas: <?php echo count($facturas); ?></strong>
                <br>
                <small><i class="fas fa-dollar-sign"></i> Monto total: $<?php 
                    $total_general = array_sum(array_map(function($f) {
                        return floatval($f['total'] ?? 0);
                    }, $facturas));
                    echo number_format($total_general, 2);
                ?></small>
            </div>
        <?php endif; ?>
    </div>
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