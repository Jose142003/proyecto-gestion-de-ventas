<?php
// pedidos_pendientes.php
session_start();
require_once '../conexion.php'; // Tu archivo de conexión

// Verificar si es admin
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'ventas') {
    header("Location: panel_admin.html");
    exit();
}

// Obtener pedidos pendientes de facturación
$sql = "SELECT p.*, u.nombre as cliente_nombre, u.email, u.telefono
        FROM pedidos p 
        JOIN usuarios u ON p.user_id = u.id 
        WHERE p.estado = 'pendiente_factura'
        ORDER BY p.fecha_creacion DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pedidos_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Pendientes - Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Estilos similares al panel_admin */
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--light);
        }
        
        .pedido-card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }
        
        .pedido-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .pedido-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .pedido-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #666;
        }
        
        .productos-list {
            background: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .producto-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .producto-item:last-child {
            border-bottom: none;
        }
        
        .acciones {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--secondary);
            color: white;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-clock"></i> Pedidos Pendientes de Facturación</h1>
            <a href="panel_admin.html" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Volver al Panel
            </a>
        </div>
        
        <?php if (empty($pedidos_pendientes)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>¡No hay pedidos pendientes!</h3>
            <p>Todos los pedidos han sido facturados.</p>
        </div>
        <?php else: ?>
        
        <?php foreach ($pedidos_pendientes as $pedido): ?>
        <div class="pedido-card" data-pedido-id="<?php echo $pedido['id']; ?>">
            <div class="pedido-header">
                <div>
                    <h3>Pedido #<?php echo htmlspecialchars($pedido['numero_pedido']); ?></h3>
                    <span class="badge badge-warning">
                        <i class="fas fa-clock"></i> Esperando Factura
                    </span>
                </div>
                <div class="acciones">
                    <button class="btn btn-success" onclick="generarFactura(<?php echo $pedido['id']; ?>)">
                        <i class="fas fa-file-invoice-dollar"></i> Generar Factura
                    </button>
                    <button class="btn btn-danger" onclick="cancelarPedido(<?php echo $pedido['id']; ?>)">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button class="btn btn-primary" onclick="verDetalles(<?php echo $pedido['id']; ?>)">
                        <i class="fas fa-eye"></i> Ver Detalles
                    </button>
                </div>
            </div>
            
            <div class="pedido-info">
                <div class="info-item">
                    <span class="info-label">Cliente</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Teléfono</span>
                    <span class="info-value"><?php echo htmlspecialchars($pedido['telefono']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Método de Pago</span>
                    <span class="info-value"><?php echo strtoupper($pedido['metodo_pago']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Fecha</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total</span>
                    <span class="info-value" style="font-weight: bold; color: var(--success);">
                        $<?php echo number_format($pedido['total'], 2); ?>
                    </span>
                </div>
            </div>
            
            <div class="productos-list">
                <h4><i class="fas fa-shopping-cart"></i> Productos</h4>
                <?php 
                // Obtener productos del pedido
                $sql_items = "SELECT pi.*, p.name as product_name 
                              FROM pedido_items pi 
                              JOIN products p ON pi.product_id = p.id 
                              WHERE pi.pedido_id = ?";
                $stmt_items = $pdo->prepare($sql_items);
                $stmt_items->execute([$pedido['id']]);
                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($items as $item):
                ?>
                <div class="producto-item">
                    <span><?php echo htmlspecialchars($item['product_name']); ?></span>
                    <span>Cantidad: <?php echo $item['quantity']; ?></span>
                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php endif; ?>
    </div>
    
    <script>
        async function generarFactura(pedidoId) {
            if (!confirm('¿Generar factura para este pedido? El cliente será notificado.')) {
                return;
            }
            
            try {
                const response = await fetch('/proyecto/proceso compra/generar_factura_admin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ pedido_id: pedidoId })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Factura generada exitosamente. Factura #' + data.numero_factura);
                    // Remover el pedido de la lista
                    document.querySelector(`[data-pedido-id="${pedidoId}"]`).remove();
                    
                    // Si no quedan pedidos, mostrar estado vacío
                    if (document.querySelectorAll('.pedido-card').length === 0) {
                        location.reload();
                    }
                } else {
                    alert('❌ Error: ' + data.message);
                }
            } catch (error) {
                alert('Error de conexión: ' + error.message);
            }
        }
        
  // En pedidos_pendientes.php, actualizar la función cancelarPedido:
async function cancelarPedido(pedidoId) {
    const motivo = prompt('Ingrese el motivo de la cancelación:', 'Cancelado por administrador');
    
    if (motivo === null) return; // Usuario canceló
    
    if (!motivo.trim()) {
        alert('Debe ingresar un motivo');
        return;
    }
    
    if (!confirm('¿Está seguro de cancelar este pedido? Se notificará al cliente.')) {
        return;
    }
    
    try {
        const response = await fetch('/proyecto/proceso compra/cancelar_pedido_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                pedido_id: pedidoId,
                motivo: motivo
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`✅ Pedido #${data.pedido_numero} cancelado exitosamente.`);
            // Remover de la lista
            document.querySelector(`[data-pedido-id="${pedidoId}"]`).remove();
            
            // Mostrar estado vacío si no hay más pedidos
            if (document.querySelectorAll('.pedido-card').length === 0) {
                location.reload();
            }
        } else {
            alert('❌ Error: ' + data.message);
        }
    } catch (error) {
        alert('Error de conexión: ' + error.message);
    }
}
    </script>
</body>
</html>