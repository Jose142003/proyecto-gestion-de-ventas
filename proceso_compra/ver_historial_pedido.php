<?php
session_start();

// Verificar que el usuario esté logueado y sea admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('/interfaz_usuario/login.html'));
    exit();
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    die("Error interno del servidor");
}

// Obtener el ID del pedido
$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($pedido_id == 0) {
    die("ID de pedido no válido");
}

// Consultar el pedido con los nombres CORRECTOS de productos desde la tabla products
$sql = "
    SELECT 
        p.id as pedido_id,
        p.numero_pedido,
        p.subtotal,
        p.iva,
        p.total,
        p.metodo_pago,
        p.estado,
        p.created_at,
        p.direccion_envio,
        p.observaciones,
        u.nombre as cliente_nombre,
        u.correo as cliente_email,
        u.telefono as cliente_telefono,
        pd.id as detalle_id,
        pd.producto_id,
        pd.cantidad,
        pd.precio_unitario,
        pd.subtotal as item_subtotal,
        -- USAR EL NOMBRE REAL DESDE LA TABLA products, NO el guardado en pedido_detalles
        prod.name as producto_nombre_real,
        prod.sku as producto_sku,
        prod.category as producto_categoria
    FROM pedidos p
    INNER JOIN users u ON p.usuario_id = u.id
    LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
    LEFT JOIN products prod ON pd.producto_id = prod.id
    WHERE p.id = :pedido_id
    ORDER BY pd.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':pedido_id' => $pedido_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    die("Pedido no encontrado");
}

// Organizar datos del pedido
$pedido = [
    'id' => $rows[0]['pedido_id'],
    'numero_pedido' => $rows[0]['numero_pedido'],
    'subtotal' => $rows[0]['subtotal'],
    'iva' => $rows[0]['iva'],
    'total' => $rows[0]['total'],
    'metodo_pago' => $rows[0]['metodo_pago'],
    'estado' => $rows[0]['estado'],
    'created_at' => $rows[0]['created_at'],
    'direccion_entrega' => $rows[0]['direccion_envio'],
    'observaciones' => $rows[0]['observaciones'],
    'cliente_nombre' => $rows[0]['cliente_nombre'],
    'cliente_email' => $rows[0]['cliente_email'],
    'cliente_telefono' => $rows[0]['cliente_telefono'],
    'productos' => []
];

foreach ($rows as $row) {
    if ($row['detalle_id']) {
        $pedido['productos'][] = [
            'id' => $row['detalle_id'],
            'producto_id' => $row['producto_id'],
            'nombre' => $row['producto_nombre_real'] ?: 'Producto no disponible', // ← NOMBRE REAL
            'sku' => $row['producto_sku'],
            'categoria' => $row['producto_categoria'],
            'cantidad' => $row['cantidad'],
            'precio_unitario' => $row['precio_unitario'],
            'subtotal' => $row['item_subtotal']
        ];
    }
}

// Función para formato de moneda
function formatMoney($value) {
    return 'Bs. ' . number_format(floatval($value), 2, ',', '.');
}

// Función para formato de fecha
function formatDateTime($dateStr) {
    if (!$dateStr) return 'N/A';
    return date('d/m/Y H:i:s', strtotime($dateStr));
}

function getEstadoBadge($estado) {
    $badges = [
        'pendiente' => '<span style="background: #ffa502; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;"><i class="fas fa-clock"></i> Pendiente</span>',
        'facturado' => '<span style="background: #2ed573; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;"><i class="fas fa-check-circle"></i> Facturado</span>',
        'completado' => '<span style="background: #2ed573; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;"><i class="fas fa-check-double"></i> Completado</span>',
        'cancelado' => '<span style="background: #ff4757; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;"><i class="fas fa-times-circle"></i> Cancelado</span>'
    ];
    return $badges[$estado] ?? '<span style="background: #666; color: white; padding: 4px 12px; border-radius: 20px;">' . $estado . '</span>';
}

function getMetodoPagoBadge($metodo) {
    $metodos = [
        'efectivo' => '<span style="background: #2ed573; color: white; padding: 4px 12px; border-radius: 20px;"><i class="fas fa-money-bill-wave"></i> Efectivo</span>',
        'transferencia' => '<span style="background: #3498db; color: white; padding: 4px 12px; border-radius: 20px;"><i class="fas fa-university"></i> Transferencia</span>',
        'pago_movil' => '<span style="background: #9b59b6; color: white; padding: 4px 12px; border-radius: 20px;"><i class="fas fa-mobile-alt"></i> Pago Móvil</span>',
        'tarjeta' => '<span style="background: #e74c3c; color: white; padding: 4px 12px; border-radius: 20px;"><i class="fas fa-credit-card"></i> Tarjeta</span>'
    ];
    return $metodos[$metodo] ?? '<span style="background: #666; color: white; padding: 4px 12px; border-radius: 20px;">' . $metodo . '</span>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Pedido #<?php echo htmlspecialchars($pedido['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?> - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #050C18, #294E90);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .header h1 {
            font-size: 1.5rem;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .btn-primary {
            background: #3C91ED;
            color: white;
        }
        
        .btn-secondary {
            background: #666;
            color: white;
        }
        
        .btn-danger {
            background: #ff4757;
            color: white;
        }
        
        .btn-success {
            background: #2ed573;
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #3C91ED, #7EBDE9);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 1rem;
            color: #333;
        }
        
        .productos-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .productos-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .productos-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .productos-table tr:hover {
            background: #f8f9fa;
        }
        
        .totales {
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .total-row {
            padding: 5px 0;
        }
        
        .total-grande {
            font-size: 1.3rem;
            font-weight: bold;
            color: #050C18;
            border-top: 2px solid #3C91ED;
            margin-top: 10px;
            padding-top: 10px;
        }
        
        .acciones {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .card {
                box-shadow: none;
            }
            .btn {
                display: none;
            }
            .acciones {
                display: none;
            }
        }
        
        .badge-estado {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <h1><i class="fas fa-shopping-cart"></i> Detalle del Pedido</h1>
            <p>N° Pedido: <?php echo htmlspecialchars($pedido['numero_pedido']); ?></p>
        </div>
        <div class="no-print">
            <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir</button>
        </div>
    </div>
    
    <!-- Información del Cliente -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user"></i> Información del Cliente
        </div>
        <div class="card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nombre</div>
                    <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Teléfono</div>
                    <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_telefono']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fecha del Pedido</div>
                    <div class="info-value"><?php echo formatDateTime($pedido['created_at']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Estado</div>
                    <div class="info-value"><?php echo getEstadoBadge($pedido['estado']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Método de Pago</div>
                    <div class="info-value"><?php echo getMetodoPagoBadge($pedido['metodo_pago']); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Dirección de Entrega -->
    <?php if (!empty($pedido['direccion_entrega'])): ?>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-location-dot"></i> Dirección de Entrega
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($pedido['direccion_entrega'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Productos -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-box"></i> Productos
        </div>
        <div class="card-body">
            <table class="productos-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedido['productos'])): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No hay productos registrados</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedido['productos'] as $producto): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                    <?php if (!empty($producto['sku'])): ?>
                                        <br><small style="color: #666;">SKU: <?php echo htmlspecialchars($producto['sku']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $producto['cantidad']; ?></td>
                                <td><?php echo formatMoney($producto['precio_unitario']); ?></td>
                                <td><?php echo formatMoney($producto['subtotal']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="totales">
                <div class="total-row"><strong>Subtotal:</strong> <?php echo formatMoney($pedido['subtotal']); ?></div>
                <div class="total-row"><strong>IVA (16%):</strong> <?php echo formatMoney($pedido['iva']); ?></div>
                <div class="total-row total-grande"><strong>TOTAL:</strong> <?php echo formatMoney($pedido['total']); ?></div>
            </div>
        </div>
    </div>
    
    <!-- Observaciones -->
    <?php if (!empty($pedido['observaciones'])): ?>
    <div class="card">
        <div class="card-header">
            <i class="fas fa-comment"></i> Observaciones
        </div>
        <div class="card-body">
            <p><?php echo nl2br(htmlspecialchars($pedido['observaciones'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="acciones no-print">
        <a href="javascript:history.back()" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
        <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Imprimir</button>
        <a href="exportar_pedido_pdf.php?id=<?php echo $pedido['id']; ?>" class="btn btn-danger" target="_blank"><i class="fas fa-file-pdf"></i> Exportar PDF</a>
    </div>
</div>
</body>
</html>