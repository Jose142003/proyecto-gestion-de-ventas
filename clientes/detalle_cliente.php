<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$cliente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cliente_id <= 0) {
    die("ID de cliente no válido");
}

// Obtener datos del cliente
$stmt = $pdo->prepare("SELECT id, nombre, correo as email, telefono, cedula as documento, direccion, estado, created_at as fecha_registro 
                        FROM users WHERE id = ? AND rol = 'usuario'");
$stmt->execute([$cliente_id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente no encontrado");
}

// Obtener resumen de compras del cliente
$stmtResumen = $pdo->prepare("SELECT 
    COUNT(DISTINCT p.id) as total_pedidos,
    COALESCE(SUM(p.total), 0) as total_gastado,
    COALESCE(SUM(pd.cantidad), 0) as total_productos,
    MAX(p.created_at) as ultima_compra,
    COUNT(DISTINCT CASE WHEN p.estado = 'pendiente' THEN p.id END) as pedidos_pendientes,
    COUNT(DISTINCT CASE WHEN p.estado = 'completado' THEN p.id END) as pedidos_completados,
    COUNT(DISTINCT CASE WHEN p.estado = 'facturado' THEN p.id END) as pedidos_facturados,
    COUNT(DISTINCT CASE WHEN p.estado = 'cancelado' THEN p.id END) as pedidos_cancelados
FROM pedidos p
LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
WHERE p.usuario_id = ?");
$stmtResumen->execute([$cliente_id]);
$resumen = $stmtResumen->fetch(PDO::FETCH_ASSOC);

// Obtener historial de pedidos del cliente
$stmtPedidos = $pdo->prepare("SELECT 
    p.id,
    p.numero_pedido,
    p.total,
    p.estado,
    p.metodo_pago,
    p.created_at as fecha,
    (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as total_productos
FROM pedidos p
WHERE p.usuario_id = ?
ORDER BY p.created_at DESC
LIMIT 20");
$stmtPedidos->execute([$cliente_id]);
$pedidos = $stmtPedidos->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Cliente - PIC</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #050C18, #294E90);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-back:hover {
            background: rgba(255,255,255,0.3);
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3C91ED;
            color: #050C18;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        .info-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .info-label {
            font-size: 0.8rem;
            color: #666;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: linear-gradient(135deg, #3C91ED, #7EBDE9);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
        }
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #3C91ED;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .data-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .data-table tr:hover td {
            background: #f0f7ff;
        }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-block;
        }
        .badge-pendiente { background: #ffa502; color: white; }
        .badge-completado { background: #2ed573; color: white; }
        .badge-facturado { background: #3498db; color: white; }
        .badge-cancelado { background: #ff4757; color: white; }
        .btn-view {
            background: #3498db;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.75rem;
        }
        .btn-view:hover {
            background: #2980b9;
        }
        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .data-table { font-size: 0.8rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h2><i class="fas fa-user"></i> Detalle del Cliente</h2>
            <p>Información completa del cliente y su historial de compras</p>
        </div>
        <a href="javascript:history.back()" class="btn-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <!-- Información Personal -->
    <div class="card">
        <h3 class="card-title"><i class="fas fa-address-card"></i> Información Personal</h3>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label"><i class="fas fa-user"></i> Nombre completo</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['nombre']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-envelope"></i> Correo electrónico</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['email']); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-phone"></i> Teléfono</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['telefono'] ?? 'No registrado'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-id-card"></i> Documento/Cédula</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['documento'] ?? 'No registrado'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-map-marker-alt"></i> Dirección</div>
                <div class="info-value"><?php echo htmlspecialchars($cliente['direccion'] ?? 'No registrada'); ?></div>
            </div>
            <div class="info-item">
                <div class="info-label"><i class="fas fa-calendar-alt"></i> Cliente desde</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($cliente['fecha_registro'])); ?></div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de Compras -->
    <div class="card">
        <h3 class="card-title"><i class="fas fa-chart-line"></i> Estadísticas de Compras</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $resumen['total_pedidos'] ?? 0; ?></div>
                <div class="stat-label">Total Pedidos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">Bs. <?php echo number_format($resumen['total_gastado'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Gastado</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $resumen['total_productos'] ?? 0; ?></div>
                <div class="stat-label">Productos Comprados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $resumen['pedidos_pendientes'] ?? 0; ?></div>
                <div class="stat-label">Pedidos Pendientes</div>
            </div>
        </div>
    </div>

    <!-- Historial de Pedidos -->
    <div class="card">
        <h3 class="card-title"><i class="fas fa-history"></i> Historial de Pedidos</h3>
        <?php if (empty($pedidos)): ?>
            <p style="text-align:center; padding: 40px;">No hay pedidos registrados para este cliente</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>N° Pedido</th>
                        <th>Fecha</th>
                        <th>Productos</th>
                        <th>Total</th>
                        <th>Método Pago</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td><?php echo $pedido['id']; ?></td>
                            <td><?php echo htmlspecialchars($pedido['numero_pedido']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha'])); ?></td>
                            <td><?php echo $pedido['total_productos']; ?></td>
                            <td>Bs. <?php echo number_format($pedido['total'], 2); ?></td>
                            <td><?php echo htmlspecialchars($pedido['metodo_pago']); ?></td>
                            <td><span class="badge badge-<?php echo $pedido['estado']; ?>"><?php echo ucfirst($pedido['estado']); ?></span></td>
                            <td>
                                <button class="btn-view" onclick="verPedido(<?php echo $pedido['id']; ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
function verPedido(pedidoId) {
    window.open(`/proyecto/proceso compra/ver_pedido.php?id=${pedidoId}`, '_blank');
}
</script>
</body>
</html>