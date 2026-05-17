<?php
session_start();
require_once '../conexion/conexion.php';

// Verificar si es admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || 
    !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
    header('Location: ../usuario/login.html');
    exit;
}

$pedido_id = $_GET['id'] ?? 0;

if (!$pedido_id) {
    header('Location: panel_admin.php');
    exit;
}

try {
    $pdo = conectarDB();
    
    // Obtener pedido
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre, u.correo, u.telefono, u.cedula 
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        die('Pedido no encontrado');
    }
    
    // Obtener detalles del pedido
    $stmt = $pdo->prepare("
        SELECT dp.*, pr.nombre as producto_nombre, pr.descripcion 
        FROM detalles_pedido dp
        LEFT JOIN productos pr ON dp.producto_id = pr.id
        WHERE dp.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Error de base de datos: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido - Admin</title>
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
        :root {
            --primary-blue: #294E90;
            --secondary-blue: #3C91ED;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F3F7FA;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .btn-back {
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-generate {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-blue);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th {
            background: var(--primary-blue);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .total {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-blue);
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="panel_admin.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Volver al Panel
            </a>
            <button onclick="generarFactura(<?php echo $pedido_id; ?>)" class="btn-generate">
                <i class="fas fa-file-invoice"></i> Generar Factura
            </button>
        </div>
        
        <h1><i class="fas fa-shopping-cart" style="color: var(--primary-blue);"></i> Pedido: <?php echo $pedido['numero_pedido']; ?></h1>
        
        <div class="info-grid">
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Cliente</h3>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($pedido['nombre'] ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['correo'] ?? 'N/A'); ?></p>
                <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['telefono'] ?? 'N/A'); ?></p>
                <p><strong>Cédula/RIF:</strong> <?php echo htmlspecialchars($pedido['cedula'] ?? 'N/A'); ?></p>
            </div>
            
            <div class="info-card">
                <h3><i class="fas fa-credit-card"></i> Detalles del Pedido</h3>
                <p><strong>Fecha:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['created_at'])); ?></p>
                <p><strong>Método de Pago:</strong> <?php echo ucfirst(str_replace('_', ' ', $pedido['metodo_pago'] ?? 'N/A')); ?></p>
                <p><strong>Estado:</strong> 
                    <span style="padding: 5px 10px; border-radius: 5px; background: <?php 
                        echo $pedido['estado'] == 'pendiente' ? '#fff3cd' : 
                            ($pedido['estado'] == 'confirmado' ? '#d4edda' : '#f8d7da'); 
                    ?>;">
                        <?php echo ucfirst($pedido['estado'] ?? 'Pendiente'); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <h3>Productos</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Descripción</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $subtotal = 0;
                foreach($detalles as $item): 
                    $precio = floatval($item['precio_unitario'] ?? 0);
                    $cantidad = intval($item['cantidad'] ?? 1);
                    $subtotal_item = $precio * $cantidad;
                    $subtotal += $subtotal_item;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($item['producto_nombre'] ?? 'Producto'); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['descripcion'] ?? ''); ?></td>
                    <td><?php echo $cantidad; ?></td>
                    <td>Bs. <?php echo number_format($precio, 2, ',', '.'); ?></td>
                    <td>Bs. <?php echo number_format($subtotal_item, 2, ',', '.'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total">
            <p>Subtotal: Bs. <?php echo number_format($subtotal, 2, ',', '.'); ?></p>
            <p>IVA (13%): Bs. <?php echo number_format($subtotal * 0.13, 2, ',', '.'); ?></p>
            <p style="font-size: 1.5rem;">TOTAL: Bs. <?php echo number_format($subtotal * 1.13, 2, ',', '.'); ?></p>
        </div>
        
        <?php if(!empty($pedido['observaciones'])): ?>
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px;">
            <strong>Observaciones:</strong><br>
            <?php echo htmlspecialchars($pedido['observaciones']); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function generarFactura(pedidoId) {
            if (confirm('¿Generar factura para este pedido?')) {
                fetch('../facturacion/generar_factura.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: pedidoId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Factura generada exitosamente');
                        window.location.href = '../facturacion/ver_factura.php?id=' + data.factura_id;
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al generar la factura');
                });
            }
        }
    </script>
</body>
</html>