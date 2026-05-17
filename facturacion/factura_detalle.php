<?php
session_start();
require_once '../conexion/conexion.php';

$pdo = conectarDB();

// Obtener ID de factura
$invoice_id = $_GET['invoice_id'] ?? 0;

if (!$invoice_id) {
    die("<h2>Error: No se especificó la factura</h2>");
}

try {
    // Obtener factura
    $stmt = $pdo->prepare("
        SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email, 
               c.telefono as cliente_telefono, c.direccion as cliente_direccion
        FROM facturas f
        JOIN clientes c ON f.cliente_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$invoice_id]);
    $factura = $stmt->fetch();
    
    if (!$factura) {
        die("<h2>Factura no encontrada</h2>");
    }
    
    // Obtener detalles
    $stmt = $pdo->prepare("
        SELECT fd.*, p.name as producto_nombre, p.sku
        FROM factura_detalles fd
        JOIN products p ON fd.producto_id = p.id
        WHERE fd.factura_id = ?
    ");
    $stmt->execute([$invoice_id]);
    $detalles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?php echo $factura['numero_factura']; ?></title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f5f5f5; padding: 20px; }
        .invoice-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .invoice-header { background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 30px; text-align: center; }
        .invoice-body { padding: 30px; }
        .invoice-info { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #3498db; }
        .products-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .products-table th { background: #2c3e50; color: white; padding: 12px; text-align: left; }
        .products-table td { padding: 12px; border-bottom: 1px solid #dee2e6; }
        .total-section { background: #e8f5e9; padding: 20px; border-radius: 8px; text-align: right; margin-top: 30px; border: 2px solid #27ae60; }
        .total-amount { font-size: 1.8em; font-weight: bold; color: #27ae60; }
        .payment-info { background: #e3f2fd; padding: 20px; border-radius: 8px; margin-top: 20px; border: 2px solid #3498db; }
        .actions { display: flex; gap: 15px; margin-top: 30px; }
        .btn { padding: 12px 25px; border: none; border-radius: 5px; font-weight: 600; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .btn-print { background: #2c3e50; color: white; }
        .btn-home { background: #3498db; color: white; text-decoration: none; }
        @media print { body { background: white; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <h1><i class="fas fa-file-invoice"></i> FACTURA DE COMPRA</h1>
            <p>Proyectos Industriales C.A</p>
        </div>
        
        <div class="invoice-body">
            <div class="invoice-info">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3>Factura #<?php echo $factura['numero_factura']; ?></h3>
                        <p>Fecha: <?php echo date('d/m/Y', strtotime($factura['fecha_emision'])); ?></p>
                        <p>Vence: <?php echo date('d/m/Y', strtotime($factura['fecha_vencimiento'])); ?></p>
                    </div>
                    <div style="text-align: right;">
                        <p>Estado: <span style="color: <?php echo $factura['estado'] == 'pagada' ? '#27ae60' : '#f39c12'; ?>; font-weight: bold;">
                            <?php echo strtoupper($factura['estado']); ?>
                        </span></p>
                        <p>Método: <?php echo strtoupper($factura['metodo_pago']); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: 15px;">
                    <p><strong>Cliente:</strong> <?php echo $factura['cliente_nombre']; ?></p>
                    <p><strong>Email:</strong> <?php echo $factura['cliente_email']; ?></p>
                    <p><strong>Teléfono:</strong> <?php echo $factura['cliente_telefono']; ?></p>
                </div>
            </div>
            
            <h3><i class="fas fa-shopping-cart"></i> Productos Comprados</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cantidad</th>
                        <th>Precio Unitario</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?php echo $detalle['producto_nombre']; ?></td>
                        <td><?php echo $detalle['cantidad']; ?></td>
                        <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                        <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="total-section">
                <h3>Total a Pagar</h3>
                <div class="total-amount">$<?php echo number_format($factura['total'], 2); ?></div>
                <p>Subtotal: $<?php echo number_format($factura['subtotal'], 2); ?> | IVA (16%): $<?php echo number_format($factura['iva'], 2); ?></p>
            </div>
            
            <?php if ($factura['metodo_pago'] == 'transferencia'): ?>
            <div class="payment-info">
                <h3><i class="fas fa-university"></i> Datos para Transferencia</h3>
                <p><strong>Banco:</strong> Mercantil</p>
                <p><strong>Cuenta:</strong> 0105-0094-05-10.94.38.39.37</p>
                <p><strong>Titular:</strong> Proyectos Industriales C.A</p>
                <p><strong>RIF:</strong> J-29384799-0</p>
            </div>
            <?php endif; ?>
            
            <div class="actions">
                <button class="btn btn-print" onclick="window.print()">
                    <i class="fas fa-print"></i> Imprimir Factura
                </button>
                <a href="/proyecto/usuario/pagina_modernizada.html" class="btn btn-home">
                    <i class="fas fa-home"></i> Volver al Inicio
                </a>
            </div>
        </div>
    </div>
</body>
</html>