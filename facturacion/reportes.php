<?php
session_start();

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Obtener parámetros de fechas (mes actual por defecto)
$mes_actual = date('m');
$anio_actual = date('Y');
$mes = $_GET['mes'] ?? $mes_actual;
$anio = $_GET['anio'] ?? $anio_actual;

// Validar parámetros
$mes = filter_var($mes, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 12]]) ? $mes : $mes_actual;
$anio = filter_var($anio, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2020, 'max_range' => 2030]]) ? $anio : $anio_actual;

// Obtener estadísticas del mes
$stats = [];
try {
    // Ventas del mes
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_facturas,
            SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as ventas_totales,
            SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as facturas_pagadas,
            SUM(CASE WHEN estado = 'pendiente' THEN total ELSE 0 END) as pendientes_total,
            SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as facturas_pendientes
        FROM facturas 
        WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ?
    ");
    $stmt->execute([$mes, $anio]);
    $stats = $stmt->fetch();
    
    if (!$stats) {
        $stats = [
            'total_facturas' => 0,
            'ventas_totales' => 0,
            'facturas_pagadas' => 0,
            'pendientes_total' => 0,
            'facturas_pendientes' => 0
        ];
    }
    
    // Productos más vendidos del mes
    $stmt = $pdo->prepare("
        SELECT 
            p.name as producto,
            p.sku,
            SUM(fd.cantidad) as cantidad_vendida,
            SUM(fd.subtotal) as total_ventas,
            p.category
        FROM factura_detalles fd
        JOIN products p ON fd.producto_id = p.id
        JOIN facturas f ON fd.factura_id = f.id
        WHERE MONTH(f.fecha_emision) = ? AND YEAR(f.fecha_emision) = ? AND f.estado = 'pagada'
        GROUP BY p.id, p.name, p.sku, p.category
        ORDER BY cantidad_vendida DESC
        LIMIT 10
    ");
    $stmt->execute([$mes, $anio]);
    $productos_top = $stmt->fetchAll();
    
    // Clientes top del mes
    $stmt = $pdo->prepare("
        SELECT 
            c.nombre as cliente,
            c.documento,
            COUNT(f.id) as total_compras,
            SUM(f.total) as total_gastado
        FROM facturas f
        JOIN clientes c ON f.cliente_id = c.id
        WHERE MONTH(f.fecha_emision) = ? AND YEAR(f.fecha_emision) = ? AND f.estado = 'pagada'
        GROUP BY c.id, c.nombre, c.documento
        ORDER BY total_gastado DESC
        LIMIT 10
    ");
    $stmt->execute([$mes, $anio]);
    $clientes_top = $stmt->fetchAll();
    
    // Ventas por día del mes
    $stmt = $pdo->prepare("
        SELECT 
            DAY(fecha_emision) as dia,
            COUNT(*) as cantidad_facturas,
            SUM(total) as total_ventas
        FROM facturas
        WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ? AND estado = 'pagada'
        GROUP BY DAY(fecha_emision)
        ORDER BY dia
    ");
    $stmt->execute([$mes, $anio]);
    $ventas_por_dia = $stmt->fetchAll();
    
    // Métodos de pago más usados
    $stmt = $pdo->prepare("
        SELECT 
            metodo_pago,
            COUNT(*) as cantidad,
            SUM(total) as total
        FROM facturas
        WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ? AND estado = 'pagada'
        GROUP BY metodo_pago
        ORDER BY cantidad DESC
    ");
    $stmt->execute([$mes, $anio]);
    $metodos_pago = $stmt->fetchAll();
    
    // Total general histórico
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_facturas_historico,
            SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as ventas_totales_historico,
            SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as facturas_pagadas_historico
        FROM facturas
    ");
    $stats_historico = $stmt->fetch();
    
    if (!$stats_historico) {
        $stats_historico = [
            'total_facturas_historico' => 0,
            'ventas_totales_historico' => 0,
            'facturas_pagadas_historico' => 0
        ];
    }
    
} catch (PDOException $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}

// Nombres de los meses
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$nombre_mes = $meses[$mes] ?? 'Mes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Estadísticas - PIC</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bush-black: #050C18;
            --yishin-blue: #294E90;
            --chefcheorem-blue: #3C91ED;
            --maya-blue: #7EBDE9;
            --white-smoke: #F3F3F3;
            --success: #2ed573;
            --warning: #ffa502;
            --danger: #ff4757;
            --info: #3498db;
            --purple: #9B59B6;
            --orange: #F39C12;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--white-smoke);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--chefcheorem-blue);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            transform: translateX(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .header-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .header-title h1 {
            color: var(--bush-black);
            font-size: 2rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title h1 i {
            color: var(--chefcheorem-blue);
        }
        
        .filtros-reports {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 2px solid #e9ecef;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 0.95rem;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--chefcheorem-blue);
            box-shadow: 0 0 0 3px rgba(60, 145, 237, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(60, 145, 237, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(60, 145, 237, 0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
        }
        
        .stat-card.total::before { background: linear-gradient(90deg, var(--chefcheorem-blue), var(--maya-blue)); }
        .stat-card.ventas::before { background: linear-gradient(90deg, var(--success), #26c46a); }
        .stat-card.pagadas::before { background: linear-gradient(90deg, var(--info), #2980b9); }
        .stat-card.pendientes::before { background: linear-gradient(90deg, var(--warning), #e59400); }
        .stat-card.historico::before { background: linear-gradient(90deg, var(--purple), #8e44ad); }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .stat-card.total .stat-icon { background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue)); }
        .stat-card.ventas .stat-icon { background: linear-gradient(135deg, var(--success), #26c46a); }
        .stat-card.pagadas .stat-icon { background: linear-gradient(135deg, var(--info), #2980b9); }
        .stat-card.pendientes .stat-icon { background: linear-gradient(135deg, var(--warning), #e59400); }
        .stat-card.historico .stat-icon { background: linear-gradient(135deg, var(--purple), #8e44ad); }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: var(--bush-black);
        }
        
        .stat-label {
            font-size: 1rem;
            color: #666;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .stat-period {
            font-size: 0.85rem;
            color: #999;
        }
        
        .charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 1100px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .chart-title {
            color: var(--bush-black);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--white-smoke);
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title i {
            color: var(--chefcheorem-blue);
        }
        
        .chart-wrapper {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 1100px) {
            .tables-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .table-header {
            padding: 25px 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table-header h3 {
            font-size: 1.4rem;
            color: var(--bush-black);
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-header h3 i {
            color: var(--chefcheorem-blue);
        }
        
        .table-content {
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }
        
        .data-table th {
            padding: 18px 20px;
            text-align: left;
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .data-table th:last-child {
            border-right: none;
        }
        
        .data-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #dee2e6;
            color: #333;
            font-size: 0.95rem;
        }
        
        .data-table tr:hover td {
            background-color: rgba(60, 145, 237, 0.05);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .badge-categoria {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
            text-transform: uppercase;
        }
        
        .badge-electronica { background: rgba(41, 128, 185, 0.2); color: #2980b9; }
        .badge-accesorios { background: rgba(46, 204, 113, 0.2); color: #27ae60; }
        .badge-componentes { background: rgba(155, 89, 182, 0.2); color: #8e44ad; }
        .badge-general { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        
        .export-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .export-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .export-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .export-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            margin: 0 auto 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .export-pdf .export-icon { background: linear-gradient(135deg, #dc3545, #c82333); }
        .export-excel .export-icon { background: linear-gradient(135deg, #198754, #157347); }
        .export-print .export-icon { background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue)); }
        
        .export-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--bush-black);
        }
        
        .export-desc {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .btn-export {
            background: white;
            color: var(--bush-black);
            border: 2px solid #dee2e6;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            text-decoration: none;
        }
        
        .export-pdf .btn-export:hover { background: #dc3545; color: white; border-color: #dc3545; }
        .export-excel .btn-export:hover { background: #198754; color: white; border-color: #198754; }
        .export-print .btn-export:hover { background: var(--chefcheorem-blue); color: white; border-color: var(--chefcheorem-blue); }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(5px);
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--chefcheorem-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header, .export-section, .chart-container, .table-container {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-grid, .tables-grid {
                grid-template-columns: 1fr;
            }
            
            .chart-wrapper {
                height: 250px;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }
        
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .text-warning { color: var(--warning); }
        .text-info { color: var(--info); }
    </style>
</head>
<body>
    <div class="container">
        <a href="/proyecto/panel admin/panel_admin.html" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
        
        <div class="header">
            <div class="header-title">
                <h1><i class="fas fa-chart-bar"></i> Reportes y Estadísticas</h1>
            </div>
            
            <div class="filtros-reports">
                <form method="GET" action="" class="filtros-grid">
                    <div class="form-group">
                        <label class="form-label">Mes</label>
                        <select name="mes" class="form-control">
                            <?php foreach ($meses as $num => $nombre): ?>
                                <option value="<?php echo $num; ?>" <?php echo $mes == $num ? 'selected' : ''; ?>>
                                    <?php echo $nombre; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Año</label>
                        <select name="anio" class="form-control">
                            <?php for ($i = 2020; $i <= 2030; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $anio == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_facturas']; ?></div>
                <div class="stat-label">Total Facturas</div>
                <div class="stat-period"><?php echo $nombre_mes . ' ' . $anio; ?></div>
            </div>
            
            <div class="stat-card ventas">
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-value">Bs. <?php echo number_format($stats['ventas_totales'] ?? 0, 2); ?></div>
                <div class="stat-label">Ventas Totales</div>
                <div class="stat-period"><?php echo $nombre_mes . ' ' . $anio; ?></div>
            </div>
            
            <div class="stat-card pagadas">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['facturas_pagadas']; ?></div>
                <div class="stat-label">Facturas Pagadas</div>
                <div class="stat-period"><?php echo $nombre_mes . ' ' . $anio; ?></div>
            </div>
            
            <div class="stat-card pendientes">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['facturas_pendientes']; ?></div>
                <div class="stat-label">Facturas Pendientes</div>
                <div class="stat-period"><?php echo $nombre_mes . ' ' . $anio; ?></div>
            </div>
            
            <div class="stat-card historico">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-value">Bs. <?php echo number_format($stats_historico['ventas_totales_historico'] ?? 0, 2); ?></div>
                <div class="stat-label">Ventas Históricas</div>
                <div class="stat-period">Total general</div>
            </div>
        </div>
        
        <div class="charts-grid">
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-line"></i> Ventas por Día</h3>
                <div class="chart-wrapper">
                    <canvas id="ventasDiaChart"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <h3 class="chart-title"><i class="fas fa-chart-pie"></i> Métodos de Pago</h3>
                <div class="chart-wrapper">
                    <canvas id="metodosPagoChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="tables-grid">
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-boxes"></i> Productos Más Vendidos</h3>
                </div>
                <div class="table-content">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Cantidad</th>
                                <th>Total Ventas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($productos_top) > 0): ?>
                                <?php foreach ($productos_top as $index => $producto): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($producto['producto']); ?></strong><br>
                                            <small style="color: #666;"><?php echo htmlspecialchars($producto['sku']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-categoria badge-<?php echo strtolower($producto['category']); ?>">
                                                <?php echo htmlspecialchars($producto['category']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $producto['cantidad_vendida']; ?></td>
                                        <td class="text-success">Bs. <?php echo number_format($producto['total_ventas'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-box-open"></i>
                                            <p>No hay datos de productos vendidos para este período</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h3><i class="fas fa-users"></i> Clientes Top</h3>
                </div>
                <div class="table-content">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Compras</th>
                                <th>Total Gastado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($clientes_top) > 0): ?>
                                <?php foreach ($clientes_top as $index => $cliente): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($cliente['cliente']); ?></td>
                                        <td><?php echo htmlspecialchars($cliente['documento']); ?></td>
                                        <td><?php echo $cliente['total_compras']; ?></td>
                                        <td class="text-success">Bs. <?php echo number_format($cliente['total_gastado'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5">
                                        <div class="empty-state">
                                            <i class="fas fa-user-friends"></i>
                                            <p>No hay datos de clientes para este período</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="export-section">
            <h3 class="chart-title" style="margin-bottom: 30px;"><i class="fas fa-download"></i> Exportar Reportes</h3>
            <div class="export-grid">
                <div class="export-card export-pdf">
                    <div class="export-icon">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <div class="export-title">Exportar a PDF</div>
                    <div class="export-desc">Reporte completo en formato PDF</div>
                    <a href="exportar_reporte.php?type=pdf&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>" class="btn-export">
                        <i class="fas fa-download"></i> Descargar PDF
                    </a>
                </div>
                
                <div class="export-card export-excel">
                    <div class="export-icon">
                        <i class="fas fa-file-excel"></i>
                    </div>
                    <div class="export-title">Exportar a Excel</div>
                    <div class="export-desc">Datos detallados en formato Excel</div>
                    <a href="exportar_reporte.php?type=excel&mes=<?php echo $mes; ?>&anio=<?php echo $anio; ?>" class="btn-export">
                        <i class="fas fa-download"></i> Descargar Excel
                    </a>
                </div>
                
                <div class="export-card export-print">
                    <div class="export-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="export-title">Imprimir Reporte</div>
                    <div class="export-desc">Versión optimizada para impresión</div>
                    <button onclick="window.print()" class="btn-export">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p id="loadingText" style="font-size: 1.2rem; color: var(--chefcheorem-blue);">Generando reporte...</p>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Datos para ventas por día
            const ventasDiaData = <?php 
                $dias_data = [];
                for ($i = 1; $i <= 31; $i++) {
                    $dias_data[$i] = 0;
                }
                foreach ($ventas_por_dia as $venta) {
                    $dias_data[$venta['dia']] = (float)$venta['total_ventas'];
                }
                echo json_encode(array_values($dias_data));
            ?>;
            
            // Datos para métodos de pago
            const metodosPagoLabels = <?php echo json_encode(array_column($metodos_pago, 'metodo_pago')); ?>;
            const metodosPagoData = <?php echo json_encode(array_column($metodos_pago, 'cantidad')); ?>;
            const metodosPagoTotales = <?php echo json_encode(array_column($metodos_pago, 'total')); ?>;
            
            // Colores para gráficos
            const chartColors = {
                primary: '#3C91ED',
                success: '#2ed573',
                warning: '#ffa502',
                danger: '#ff4757',
                info: '#3498db',
                purple: '#9B59B6',
                orange: '#F39C12'
            };
            
            // Gráfico de ventas por día
            const ventasDiaCtx = document.getElementById('ventasDiaChart').getContext('2d');
            new Chart(ventasDiaCtx, {
                type: 'line',
                data: {
                    labels: Array.from({length: 31}, (_, i) => i + 1),
                    datasets: [{
                        label: 'Ventas por Día',
                        data: ventasDiaData,
                        borderColor: chartColors.success,
                        backgroundColor: 'rgba(46, 213, 115, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: chartColors.success,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Ventas: Bs. ' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Bs. ' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            title: {
                                display: true,
                                text: 'Día del Mes'
                            }
                        }
                    }
                }
            });
            
            // Gráfico de métodos de pago
            const metodosPagoCtx = document.getElementById('metodosPagoChart').getContext('2d');
            new Chart(metodosPagoCtx, {
                type: 'doughnut',
                data: {
                    labels: metodosPagoLabels.map(label => {
                        const nombres = {
                            'efectivo': 'Efectivo',
                            'tarjeta': 'Tarjeta',
                            'transferencia': 'Transferencia',
                            'paypal': 'PayPal',
                            'cheque': 'Cheque'
                        };
                        return nombres[label] || label;
                    }),
                    datasets: [{
                        data: metodosPagoData,
                        backgroundColor: [
                            chartColors.success,
                            chartColors.primary,
                            chartColors.info,
                            chartColors.purple,
                            chartColors.orange
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = metodosPagoTotales[context.dataIndex] || 0;
                                    return [
                                        label,
                                        `Cantidad: ${value} facturas`,
                                        `Total: Bs. ${total.toFixed(2)}`
                                    ];
                                }
                            }
                        }
                    }
                }
            });
            
            // Mostrar/ocultar loading
            window.mostrarLoading = function(texto = 'Generando reporte...') {
                document.getElementById('loadingText').textContent = texto;
                document.getElementById('loadingOverlay').style.display = 'flex';
            };
            
            window.ocultarLoading = function() {
                document.getElementById('loadingOverlay').style.display = 'none';
            };
            
            // Manejar clic en exportar
            document.querySelectorAll('a[href*="exportar_reporte"]').forEach(link => {
                link.addEventListener('click', function(e) {
                    mostrarLoading('Preparando descarga...');
                });
            });
        });
    </script>
</body>
</html>