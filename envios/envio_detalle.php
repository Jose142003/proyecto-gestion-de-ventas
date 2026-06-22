<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    $loginUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/interfaz_usuario/login.html' : '/interfaz_usuario/login.html';
    header('Location: ' . $loginUrl);
    exit;
}

$pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
$envio_id = isset($_GET['envio_id']) ? intval($_GET['envio_id']) : 0;

if ($pedido_id <= 0 && $envio_id <= 0) {
    header('Location: gestionar_envios.php');
    exit;
}

try {
    $pdo = conectarDB();

    if ($envio_id > 0) {
        $stmt = $pdo->prepare("SELECT e.*, p.numero_pedido, p.estado as pedido_estado, u.nombre as cliente_nombre, u.correo as cliente_email FROM envios e LEFT JOIN pedidos p ON e.pedido_id = p.id LEFT JOIN users u ON p.usuario_id = u.id WHERE e.id = ?");
        $stmt->execute([$envio_id]);
    } else {
        $stmt = $pdo->prepare("SELECT e.*, p.numero_pedido, p.estado as pedido_estado, u.nombre as cliente_nombre, u.correo as cliente_email FROM envios e LEFT JOIN pedidos p ON e.pedido_id = p.id LEFT JOIN users u ON p.usuario_id = u.id WHERE e.pedido_id = ? ORDER BY e.id DESC LIMIT 1");
        $stmt->execute([$pedido_id]);
    }

    $envio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$envio) {
        header('Location: gestionar_envios.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM envios_historial WHERE envio_id = ? ORDER BY created_at ASC");
    $stmt->execute([$envio['id']]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error en envio_detalle: " . $e->getMessage());
    die('Error interno del servidor');
}

$estados_envio = [
    'preparando' => ['label' => 'Preparando', 'color' => 'warning', 'icon' => 'fa-box'],
    'en_transito' => ['label' => 'En Tránsito', 'color' => 'info', 'icon' => 'fa-truck'],
    'en_reparto' => ['label' => 'En Reparto', 'color' => 'primary', 'icon' => 'fa-shipping-fast'],
    'entregado' => ['label' => 'Entregado', 'color' => 'success', 'icon' => 'fa-check-circle'],
    'fallido' => ['label' => 'Fallido', 'color' => 'danger', 'icon' => 'fa-times-circle']
];
$estado_actual = $envio['estado'] ?? 'preparando';
$estado_info = $estados_envio[$estado_actual] ?? ['label' => $estado_actual, 'color' => 'secondary', 'icon' => 'fa-question'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Envío - PIC</title>
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
        .container { max-width: 1200px; margin: 0 auto; }
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
        .estado-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .estado-preparando { background: #fff3e0; color: #e67e22; }
        .estado-en_transito { background: #e3f2fd; color: #1976d2; }
        .estado-en_reparto { background: #e3f2fd; color: #1565c0; }
        .estado-entregado { background: #e8f5e9; color: #2e7d32; }
        .estado-fallido { background: #ffebee; color: #c62828; }
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
        .timeline-dot.entregado { background: var(--success); }
        .timeline-dot.fallido { background: var(--danger); }
        .timeline-date { font-size: 0.7rem; color: var(--gray); margin-bottom: 5px; }
        .timeline-title { font-weight: 600; margin-bottom: 5px; }
        .timeline-desc { font-size: 0.85rem; color: var(--gray); }
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
        .tracking-link { color: var(--accent); text-decoration: none; font-weight: 500; }
        .tracking-link:hover { text-decoration: underline; }
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
        .cliente-detalle-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .cliente-detalle-label { width: 140px; font-weight: 600; color: var(--gray); }
        .cliente-detalle-value { flex: 1; color: var(--dark); }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 15px; }
            .card-body { padding: 15px; }
            .cliente-detalle-row { flex-direction: column; }
            .cliente-detalle-label { width: 100%; margin-bottom: 5px; }
            .btn { padding: 8px 15px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-truck"></i> Envío #<?php echo htmlspecialchars($envio['id']); ?></h1>
                <p>Pedido: #<?php echo htmlspecialchars($envio['pedido_numero']); ?> | Cliente: <?php echo htmlspecialchars($envio['cliente_nombre'] ?? 'N/A'); ?></p>
            </div>
            <div class="header-actions">
                <a href="gestionar_envios.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
                <a href="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/proceso_compra/ver_pedido.php?id=<?php echo $envio['pedido_id']; ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Ver Pedido</a>
            </div>
        </div>

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
                        <label><i class="fas fa-box"></i> Pedido</label>
                        <div class="value">
                            <a href="<?php echo defined('BASE_URL') ? rtrim(BASE_URL, '/') : ''; ?>/proceso_compra/ver_pedido.php?id=<?php echo $envio['pedido_id']; ?>" style="color: var(--accent); text-decoration: none;">
                                #<?php echo htmlspecialchars($envio['pedido_numero']); ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-group">
                        <label><i class="fas fa-calendar"></i> Fecha Envío</label>
                        <div class="value"><?php echo $envio['fecha_envio'] ? date('d/m/Y H:i', strtotime($envio['fecha_envio'])) : 'N/A'; ?></div>
                    </div>
                    <div class="info-group">
                        <label><i class="fas fa-calendar-check"></i> Est. Entrega</label>
                        <div class="value"><?php echo $envio['fecha_estimada_entrega'] ? date('d/m/Y', strtotime($envio['fecha_estimada_entrega'])) : 'N/A'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-shipping-fast"></i> Información de Envío</h2>
                </div>
                <div class="card-body">
                    <div class="cliente-detalle-row">
                        <div class="cliente-detalle-label">Transportista:</div>
                        <div class="cliente-detalle-value"><?php echo htmlspecialchars($envio['transportista']); ?></div>
                    </div>
                    <div class="cliente-detalle-row">
                        <div class="cliente-detalle-label">N° Guía:</div>
                        <div class="cliente-detalle-value"><?php echo htmlspecialchars($envio['numero_guia']); ?></div>
                    </div>
                    <div class="cliente-detalle-row">
                        <div class="cliente-detalle-label">URL Rastreo:</div>
                        <div class="cliente-detalle-value">
                            <?php if (!empty($envio['url_rastreo'])): ?>
                                <a href="<?php echo htmlspecialchars($envio['url_rastreo']); ?>" target="_blank" class="tracking-link">
                                    <i class="fas fa-external-link-alt"></i> Rastrear envío
                                </a>
                            <?php else: ?>
                                <span class="text-muted">No disponible</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($envio['fecha_entrega']): ?>
                    <div class="cliente-detalle-row">
                        <div class="cliente-detalle-label">Fecha Entrega:</div>
                        <div class="cliente-detalle-value"><?php echo date('d/m/Y H:i', strtotime($envio['fecha_entrega'])); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($envio['notas'])): ?>
                    <div class="cliente-detalle-row" style="flex-direction: column; gap: 5px;">
                        <div class="cliente-detalle-label">Notas:</div>
                        <div class="cliente-detalle-value"><?php echo nl2br(htmlspecialchars($envio['notas'])); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2><i class="fas fa-edit"></i> Actualizar Estado</h2>
                </div>
                <div class="card-body">
                    <form id="formActualizar">
                        <input type="hidden" name="id" value="<?php echo $envio['id']; ?>">
                        <div class="form-group">
                            <label>Nuevo Estado</label>
                            <select class="form-select" name="estado" id="selectEstado">
                                <?php foreach ($estados_envio as $key => $est): ?>
                                <option value="<?php echo $key; ?>" <?php echo $key === $estado_actual ? 'selected' : ''; ?>>
                                    <?php echo $est['label']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Ubicación (opcional)</label>
                            <input type="text" class="form-control" name="ubicacion" placeholder="Ej: Centro de distribución Caracas">
                        </div>
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" name="descripcion" placeholder="Descripción del cambio de estado..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-sync"></i> Actualizar Estado
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Timeline del Envío</h2>
            </div>
            <div class="card-body">
                <?php if (count($historial) > 0): ?>
                <div class="timeline">
                    <?php foreach ($historial as $h): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot <?php echo $h['estado_nuevo']; ?>"></div>
                        <div class="timeline-date"><?php echo date('d/m/Y H:i:s', strtotime($h['created_at'])); ?></div>
                        <div class="timeline-title">
                            <?php echo htmlspecialchars($estados_envio[$h['estado_nuevo']]['label'] ?? $h['estado_nuevo']); ?>
                            <?php if ($h['estado_anterior']): ?>
                            <small style="color: var(--gray);">
                                (desde: <?php echo htmlspecialchars($estados_envio[$h['estado_anterior']]['label'] ?? $h['estado_anterior']); ?>)
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($h['ubicacion'])): ?>
                        <div class="timeline-desc">
                            <i class="fas fa-map-marker-alt" style="color: var(--accent);"></i>
                            <?php echo htmlspecialchars($h['ubicacion']); ?>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($h['descripcion'])): ?>
                        <div class="timeline-desc"><?php echo htmlspecialchars($h['descripcion']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted" style="text-align: center; padding: 20px; color: var(--gray);">
                    <i class="fas fa-info-circle"></i> No hay registros en el timeline.
                </p>
                <?php endif; ?>
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
            notif.className = 'notification ' + tipo;
            const icon = tipo === 'success' ? 'fa-check-circle' : (tipo === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle');
            notif.innerHTML = '<i class="fas ' + icon + '"></i> ' + mensaje;
            document.body.appendChild(notif);
            setTimeout(function() { notif.remove(); }, 3000);
        }

        document.getElementById('formActualizar').addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!confirm('¿Estás seguro de actualizar el estado del envío?')) return;

            mostrarLoading();
            const formData = new FormData(this);
            const data = {
                id: parseInt(formData.get('id')),
                estado: formData.get('estado'),
                ubicacion: formData.get('ubicacion'),
                descripcion: formData.get('descripcion')
            };

            try {
                const response = await fetch('actualizar_envio.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                    credentials: 'include'
                });

                const result = await response.json();
                if (result.success) {
                    mostrarNotificacion('Estado actualizado correctamente', 'success');
                    setTimeout(function() { location.reload(); }, 1000);
                } else {
                    mostrarNotificacion(result.message || 'Error al actualizar', 'error');
                }
            } catch (error) {
                mostrarNotificacion('Error al conectar con el servidor', 'error');
            } finally {
                ocultarLoading();
            }
        });
    </script>
</body>
</html>
