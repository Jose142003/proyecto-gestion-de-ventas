<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id']) || !esAdmin()) {
    $loginUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/interfaz_usuario/login.html' : '/interfaz_usuario/login.html';
    header('Location: ' . $loginUrl);
    exit;
}

try {
    $pdo = conectarDB();

    $estado_filtro = isset($_GET['estado']) ? trim($_GET['estado']) : '';
    $transportista_filtro = isset($_GET['transportista']) ? trim($_GET['transportista']) : '';
    $fecha_desde = isset($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : '';
    $fecha_hasta = isset($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if ($estado_filtro !== '' && $estado_filtro !== 'todos') {
        $where[] = "e.estado = ?";
        $params[] = $estado_filtro;
    }
    if ($transportista_filtro !== '') {
        $where[] = "e.transportista LIKE ?";
        $params[] = "%$transportista_filtro%";
    }
    if ($fecha_desde !== '') {
        $where[] = "e.fecha_envio >= ?";
        $params[] = $fecha_desde . ' 00:00:00';
    }
    if ($fecha_hasta !== '') {
        $where[] = "e.fecha_envio <= ?";
        $params[] = $fecha_hasta . ' 23:59:59';
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM envios e $whereClause");
    $stmt->execute($params);
    $total = intval($stmt->fetch(PDO::FETCH_ASSOC)['total']);
    $pages = max(1, ceil($total / $limit));

    $sql = "SELECT e.*, p.numero_pedido, u.nombre as cliente_nombre, u.correo as cliente_email
            FROM envios e
            LEFT JOIN pedidos p ON e.pedido_id = p.id
            LEFT JOIN users u ON p.usuario_id = u.id
            $whereClause
            ORDER BY e.created_at DESC
            LIMIT $limit OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT DISTINCT transportista FROM envios ORDER BY transportista");
    $transportistas = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Error en gestionar_envios: " . $e->getMessage());
    die('Error interno del servidor');
}

$estados_envio = [
    'preparando' => ['label' => 'Preparando', 'color' => '#ffa502', 'bg' => '#fff3e0'],
    'en_transito' => ['label' => 'En Tránsito', 'color' => '#1976d2', 'bg' => '#e3f2fd'],
    'en_reparto' => ['label' => 'En Reparto', 'color' => '#1565c0', 'bg' => '#e3f2fd'],
    'entregado' => ['label' => 'Entregado', 'color' => '#2e7d32', 'bg' => '#e8f5e9'],
    'fallido' => ['label' => 'Fallido', 'color' => '#c62828', 'bg' => '#ffebee']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Envíos - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="manifest" href="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/manifest.json">
    <meta name="theme-color" content="#050C18">
    <link rel="icon" type="image/png" href="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/img/pic.png">
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
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #2d3748;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header {
            background: white;
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
        .header-left h1 { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
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
        .btn-sm { padding: 6px 12px; font-size: 0.8rem; }
        .card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
        }
        .card-header {
            padding: 20px 25px;
            background: white;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .card-header h2 { font-size: 1.2rem; font-weight: 600; color: var(--primary); display: flex; align-items: center; gap: 10px; }
        .card-body { padding: 25px; }
        .table-responsive { overflow-x: auto; }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            text-align: left;
            padding: 12px;
            background: var(--light-gray);
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--dark);
            white-space: nowrap;
        }
        .data-table td { padding: 12px; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .data-table tr:hover td { background: rgba(60, 145, 237, 0.05); }
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .pagination a {
            padding: 8px 16px;
            border-radius: 8px;
            background: var(--light-gray);
            color: var(--dark);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        .pagination a:hover { background: var(--accent); color: white; }
        .pagination a.active { background: var(--accent); color: white; }
        .pagination a.disabled { opacity: 0.5; pointer-events: none; }
        .filtros-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filtro-group { display: flex; flex-direction: column; }
        .filtro-group label { font-size: 0.75rem; font-weight: 600; margin-bottom: 5px; color: var(--gray); }
        .form-control, .form-select {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s;
            background: white;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(60, 145, 237, 0.1);
        }
        .total-count { font-size: 0.85rem; color: var(--gray); margin-top: 10px; }
        .text-muted { color: var(--gray); }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .no-data { text-align: center; padding: 40px; color: var(--gray); }
        .no-data i { font-size: 3rem; margin-bottom: 15px; display: block; }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 15px; flex-direction: column; text-align: center; }
            .header-actions { justify-content: center; }
            .card-body { padding: 15px; }
            .data-table { font-size: 0.8rem; }
            .data-table th, .data-table td { padding: 8px; }
            .filtros-row { flex-direction: column; }
            .action-buttons { flex-direction: column; }
            .btn-sm { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-truck"></i> Gestionar Envíos</h1>
                <p><?php echo htmlspecialchars($total ?? '', ENT_QUOTES, 'UTF-8'); ?> envíos registrados</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/panel_admin/panel_admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Panel</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-filter"></i> Filtros</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="filtros-row">
                        <div class="filtro-group">
                            <label>Estado</label>
                            <select name="estado" class="form-select">
                                <option value="todos">Todos</option>
                                <?php foreach ($estados_envio as $key => $est): ?>
                                <option value="<?php echo htmlspecialchars($key ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $estado_filtro === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($est['label'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filtro-group">
                            <label>Transportista</label>
                            <select name="transportista" class="form-select">
                                <option value="">Todos</option>
                                <?php foreach ($transportistas as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $transportista_filtro === $t ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filtro-group">
                            <label>Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?php echo htmlspecialchars($fecha_desde); ?>">
                        </div>
                        <div class="filtro-group">
                            <label>Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                        </div>
                        <div class="filtro-group" style="align-self: flex-end;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Listado de Envíos</h2>
                <span class="total-count">Mostrando <?php echo count($envios); ?> de <?php echo $total; ?></span>
            </div>
            <div class="card-body">
                <?php if (count($envios) > 0): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Pedido</th>
                                <th>Cliente</th>
                                <th>Transportista</th>
                                <th>N° Guía</th>
                                <th>Estado</th>
                                <th>Fecha Envío</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($envios as $e): 
                                $est_info = $estados_envio[$e['estado']] ?? ['label' => $e['estado'], 'color' => '#6c757d', 'bg' => '#f8f9fa'];
                            ?>
                            <tr>
                                <td><strong>#<?php echo $e['id']; ?></strong></td>
                                <td>
                                    <a href="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/proceso_compra/ver_pedido.php?id=<?php echo $e['pedido_id']; ?>" style="color: var(--accent); text-decoration: none; font-weight: 500;">
                                        #<?php echo htmlspecialchars($e['pedido_numero']); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($e['cliente_nombre'] ?? 'N/A'); ?></strong>
                                    <?php if (!empty($e['cliente_email'])): ?>
                                    <br><small style="color: var(--gray);"><?php echo htmlspecialchars($e['cliente_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($e['transportista']); ?></td>
                                <td>
                                    <span style="font-family: monospace;"><?php echo htmlspecialchars($e['numero_guia']); ?></span>
                                    <?php if (!empty($e['url_rastreo'])): ?>
                                    <br><a href="<?php echo htmlspecialchars($e['url_rastreo']); ?>" target="_blank" style="color: var(--accent); font-size: 0.75rem;">
                                        <i class="fas fa-external-link-alt"></i> Rastrear
                                    </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="estado-badge" style="background: <?php echo $est_info['bg']; ?>; color: <?php echo $est_info['color']; ?>;">
                                        <?php echo $est_info['label']; ?>
                                    </span>
                                </td>
                                <td><?php echo $e['fecha_envio'] ? date('d/m/Y', strtotime($e['fecha_envio'])) : '-'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="envio_detalle.php?envio_id=<?php echo $e['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> Detalle
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($pages > 1): ?>
                <div class="pagination">
                    <a href="?page=<?php echo max(1, $page - 1); ?>&estado=<?php echo urlencode($estado_filtro); ?>&transportista=<?php echo urlencode($transportista_filtro); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>" class="<?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                    <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&estado=<?php echo urlencode($estado_filtro); ?>&transportista=<?php echo urlencode($transportista_filtro); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>" class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    <a href="?page=<?php echo min($pages, $page + 1); ?>&estado=<?php echo urlencode($estado_filtro); ?>&transportista=<?php echo urlencode($transportista_filtro); ?>&fecha_desde=<?php echo urlencode($fecha_desde); ?>&fecha_hasta=<?php echo urlencode($fecha_hasta); ?>" class="<?php echo $page >= $pages ? 'disabled' : ''; ?>">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-truck"></i>
                    <h3>No hay envíos registrados</h3>
                    <p>No se encontraron envíos con los filtros seleccionados.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
