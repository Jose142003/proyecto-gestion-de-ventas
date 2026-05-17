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

// Obtener ID de factura
$factura_id = $_GET['id'] ?? 0;
if (!$factura_id) {
    die("<h2>Error: Factura no especificada</h2><p><a href='listar_facturas.php'>Volver a facturas</a></p>");
}

// Verificar que la factura se pueda editar (solo pendientes o borradores)
try {
    $stmt = $pdo->prepare("SELECT estado FROM facturas WHERE id = ?");
    $stmt->execute([$factura_id]);
    $estado = $stmt->fetchColumn();
    
    if (!$estado) {
        die("<h2>Error: Factura no encontrada</h2>");
    }
    
    if (!in_array($estado, ['pendiente', 'borrador'])) {
        die("<h2>Error: Esta factura no se puede editar</h2><p>Solo facturas pendientes o borradores pueden ser editadas.</p>");
    }
    
} catch (PDOException $e) {
    die("Error al verificar factura: " . $e->getMessage());
}

// Obtener datos para el formulario
$clientes = [];
$productos = [];
$factura_data = [];
$detalles_factura = [];

try {
    // Obtener clientes
    $stmt = $pdo->query("SELECT id, documento, nombre, email, telefono FROM clientes WHERE estado = 'activo' ORDER BY nombre");
    $clientes = $stmt->fetchAll();
    
    // Obtener productos con stock
    $stmt = $pdo->query("SELECT id, sku, name, price, stock, category FROM products WHERE stock > 0 ORDER BY name");
    $productos = $stmt->fetchAll();
    
    // Obtener datos de la factura
    $stmt = $pdo->prepare("
        SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email
        FROM facturas f
        LEFT JOIN clientes c ON f.cliente_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$factura_id]);
    $factura_data = $stmt->fetch();
    
    if (!$factura_data) {
        die("<h2>Error: Factura no encontrada</h2>");
    }
    
    // Obtener detalles actuales de la factura
    $stmt = $pdo->prepare("
        SELECT fd.*, p.name as producto_nombre, p.sku, p.stock
        FROM factura_detalles fd
        LEFT JOIN products p ON fd.producto_id = p.id
        WHERE fd.factura_id = ?
    ");
    $stmt->execute([$factura_id]);
    $detalles_factura = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error al cargar datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Factura - PIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            --bush-black: #050C18;
            --yishin-blue: #294E90;
            --chefcheorem-blue: #3C91ED;
            --maya-blue: #7EBDE9;
            --white-smoke: #F3F3F3;
            --success: #2ed573;
            --warning: #ffa502;
            --danger: #ff4757;
            --info: #3498db;
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
        
        .edit-container {
            max-width: 1200px;
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
        
        .invoice-number {
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            padding: 15px 25px;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(60, 145, 237, 0.3);
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
            margin-left: 15px;
        }
        
        .status-pendiente { background: linear-gradient(135deg, var(--warning), #e59400); color: white; }
        .status-borrador { background: linear-gradient(135deg, #666, #555); color: white; }
        
        .edit-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 2px solid #e9ecef;
        }
        
        .info-card h3 {
            color: var(--bush-black);
            margin-bottom: 15px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h3 i {
            color: var(--chefcheorem-blue);
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .select2-container .select2-selection--single {
            height: 46px !important;
            border: 2px solid #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 10px !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 24px !important;
            padding-left: 0 !important;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 44px !important;
        }
        
        .products-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: var(--bush-black);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--white-smoke);
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--chefcheorem-blue);
        }
        
        .current-products {
            margin-bottom: 30px;
        }
        
        .current-products-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .current-products-title h4 {
            color: var(--bush-black);
            font-size: 1.2rem;
        }
        
        .current-table {
            width: 100%;
            border-collapse: collapse;
            background: #f8f9fa;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .current-table th {
            padding: 15px 20px;
            text-align: left;
            background: var(--chefcheorem-blue);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .current-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            color: #333;
            font-size: 0.95rem;
        }
        
        .current-table tr:last-child td {
            border-bottom: none;
        }
        
        .btn-remove {
            background: linear-gradient(135deg, var(--danger), #ff2e43);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }
        
        .btn-remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 71, 87, 0.3);
        }
        
        .add-product-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .btn-add-product {
            background: linear-gradient(135deg, var(--success), #26c46a);
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
            box-shadow: 0 4px 10px rgba(46, 213, 115, 0.3);
        }
        
        .btn-add-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 213, 115, 0.4);
        }
        
        .new-products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .new-products-table th {
            padding: 15px 20px;
            text-align: left;
            background: linear-gradient(135deg, var(--success), #26c46a);
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .new-products-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .summary-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            border: 2px solid #e9ecef;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--bush-black);
        }
        
        .summary-row.total {
            color: var(--chefcheorem-blue);
            font-size: 1.4rem;
        }
        
        .edit-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(60, 145, 237, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(60, 145, 237, 0.4);
        }
        
        .btn-secondary {
            background-color: #666;
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #555;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--success), #26c46a);
            color: white;
            border: none;
            padding: 15px 35px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(46, 213, 115, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 213, 115, 0.4);
        }
        
        .notification {
            position: fixed;
            top: 30px;
            right: 30px;
            padding: 20px 25px;
            border-radius: 10px;
            color: white;
            z-index: 3000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
            max-width: 400px;
            word-wrap: break-word;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: none;
        }
        
        .notification.success {
            background: linear-gradient(135deg, var(--success), #26c46a);
        }
        
        .notification.error {
            background: linear-gradient(135deg, var(--danger), #ff2e43);
        }
        
        .notification.warning {
            background: linear-gradient(135deg, var(--warning), #e59400);
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
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
        
        @media (max-width: 992px) {
            .edit-info-grid {
                grid-template-columns: 1fr;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .edit-container {
                padding: 15px;
            }
            
            .header, .products-section, .summary-section {
                padding: 20px;
            }
            
            .header-title {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .edit-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="edit-container">
        <a href="listar_facturas.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver a Facturas
        </a>
        
        <div class="header">
            <div class="header-title">
                <h1><i class="fas fa-edit"></i> Editar Factura 
                    <span class="status-badge status-<?php echo $factura_data['estado']; ?>">
                        <?php echo ucfirst($factura_data['estado']); ?>
                    </span>
                </h1>
                <div class="invoice-number">
                    <?php echo htmlspecialchars($factura_data['numero_factura']); ?>
                </div>
            </div>
            
            <div class="edit-info-grid">
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Cliente</h3>
                    <div class="form-group">
                        <label class="form-label">Cliente *</label>
                        <select id="clienteSelect" class="form-control select2" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo $cliente['id'] == $factura_data['cliente_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['documento'] . ' - ' . $cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="clienteInfo">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" id="clienteEmail" class="form-control" 
                                   value="<?php echo htmlspecialchars($factura_data['cliente_email']); ?>" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-calendar-alt"></i> Fechas</h3>
                    <div class="form-group">
                        <label class="form-label">Fecha de Emisión *</label>
                        <input type="date" id="fechaEmision" class="form-control" 
                               value="<?php echo $factura_data['fecha_emision']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de Vencimiento *</label>
                        <input type="date" id="fechaVencimiento" class="form-control" 
                               value="<?php echo $factura_data['fecha_vencimiento']; ?>" required>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="products-section">
            <h3 class="section-title"><i class="fas fa-boxes"></i> Productos</h3>
            
            <!-- Productos actuales de la factura -->
            <div class="current-products">
                <div class="current-products-title">
                    <h4>Productos Actuales en la Factura</h4>
                    <small style="color: #666;">Estos productos ya están asociados a la factura</small>
                </div>
                
                <table class="current-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio Unit.</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody id="currentProductsBody">
                        <?php foreach ($detalles_factura as $detalle): ?>
                        <tr data-product-id="<?php echo $detalle['producto_id']; ?>" 
                            data-cantidad="<?php echo $detalle['cantidad']; ?>"
                            data-precio="<?php echo $detalle['precio_unitario']; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($detalle['producto_nombre']); ?></strong><br>
                                <small>SKU: <?php echo htmlspecialchars($detalle['sku']); ?></small>
                            </td>
                            <td>Bs. <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                            <td><?php echo $detalle['cantidad']; ?></td>
                            <td>Bs. <?php echo number_format($detalle['subtotal'], 2); ?></td>
                            <td>
                                <button class="btn-remove" onclick="eliminarProductoActual(this)">
                                    <i class="fas fa-trash"></i> Quitar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Formulario para agregar nuevos productos -->
            <div class="add-product-form">
                <h4 style="margin-bottom: 20px; color: var(--bush-black);">Agregar Nuevos Productos</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Producto *</label>
                        <select id="productoSelect" class="form-control select2">
                            <option value="">Buscar producto...</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo $producto['id']; ?>" 
                                        data-precio="<?php echo $producto['price']; ?>"
                                        data-stock="<?php echo $producto['stock']; ?>">
                                    <?php echo htmlspecialchars($producto['sku'] . ' - ' . $producto['name'] . ' (Stock: ' . $producto['stock'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Precio Unitario</label>
                        <input type="number" id="precioUnitario" class="form-control" step="0.01" min="0" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Stock Disponible</label>
                        <input type="number" id="stockDisponible" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Cantidad *</label>
                        <input type="number" id="cantidadProducto" class="form-control" min="1" value="1">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Subtotal</label>
                        <input type="text" id="subtotalProducto" class="form-control" readonly>
                    </div>
                    
                    <button type="button" id="btnAgregarProducto" class="btn-add-product">
                        <i class="fas fa-plus"></i> Agregar
                    </button>
                </div>
            </div>
            
            <!-- Tabla de nuevos productos a agregar -->
            <table class="new-products-table" id="newProductsTable" style="display: none;">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Precio Unit.</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="newProductsBody">
                    <!-- Los nuevos productos se agregarán aquí dinámicamente -->
                </tbody>
            </table>
        </div>
        
        <div class="summary-section">
            <h3 class="section-title"><i class="fas fa-calculator"></i> Resumen</h3>
            
            <div class="summary-grid">
                <div class="summary-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Totales</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotalTotal">Bs. <?php echo number_format($factura_data['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>IVA (16%):</span>
                        <span id="ivaTotal">Bs. <?php echo number_format($factura_data['iva'], 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>TOTAL:</span>
                        <span id="totalFactura">Bs. <?php echo number_format($factura_data['total'], 2); ?></span>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h3><i class="fas fa-credit-card"></i> Información Adicional</h3>
                    <div class="form-group">
                        <label class="form-label">Método de Pago *</label>
                        <select id="metodoPago" class="form-control" required>
                            <option value="">Seleccionar método...</option>
                            <option value="efectivo" <?php echo $factura_data['metodo_pago'] == 'efectivo' ? 'selected' : ''; ?>>Efectivo</option>
                            <option value="tarjeta" <?php echo $factura_data['metodo_pago'] == 'tarjeta' ? 'selected' : ''; ?>>Tarjeta</option>
                            <option value="transferencia" <?php echo $factura_data['metodo_pago'] == 'transferencia' ? 'selected' : ''; ?>>Transferencia</option>
                            <option value="paypal" <?php echo $factura_data['metodo_pago'] == 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observaciones</label>
                        <textarea id="observaciones" class="form-control" rows="3"><?php echo htmlspecialchars($factura_data['observaciones'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="edit-actions">
                <button type="button" id="btnCancelar" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btnGuardarCambios" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
                <button type="button" id="btnActualizarFactura" class="btn-success">
                    <i class="fas fa-check"></i> Actualizar Factura
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p id="loadingText" style="font-size: 1.2rem; color: var(--chefcheorem-blue);">Procesando...</p>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Variables globales
            let productosActuales = <?php echo json_encode($detalles_factura); ?>;
            let productosNuevos = [];
            let productosEliminados = [];
            let subtotal = <?php echo $factura_data['subtotal']; ?>;
            let iva = <?php echo $factura_data['iva']; ?>;
            let total = <?php echo $factura_data['total']; ?>;
            
            // Inicializar Select2
            $('.select2').select2({
                placeholder: 'Seleccionar...',
                allowClear: true,
                width: '100%'
            });
            
            // Cargar información del cliente seleccionado
            $('#clienteSelect').on('change', function() {
                const clienteId = $(this).val();
                if (clienteId) {
                    // Buscar email del cliente seleccionado
                    const option = $(this).find('option:selected');
                    const text = option.text();
                    const partes = text.split(' - ');
                    if (partes.length > 1) {
                        // Extraer email si está en el texto (podrías hacer AJAX para datos reales)
                        $('#clienteInfo').show();
                    }
                } else {
                    $('#clienteInfo').hide();
                }
            });
            
            // Actualizar precio y stock al seleccionar producto
            $('#productoSelect').on('change', function() {
                const option = $(this).find('option:selected');
                const precio = parseFloat(option.data('precio')) || 0;
                const stock = parseInt(option.data('stock')) || 0;
                
                $('#precioUnitario').val(precio.toFixed(2));
                $('#stockDisponible').val(stock);
                $('#cantidadProducto').val(1).attr('max', stock);
                calcularSubtotalProducto();
            });
            
            // Calcular subtotal del producto
            function calcularSubtotalProducto() {
                const precio = parseFloat($('#precioUnitario').val()) || 0;
                const cantidad = parseInt($('#cantidadProducto').val()) || 0;
                const subtotalProducto = precio * cantidad;
                
                $('#subtotalProducto').val('Bs. ' + subtotalProducto.toFixed(2));
            }
            
            $('#cantidadProducto').on('input', calcularSubtotalProducto);
            
            // Agregar nuevo producto
            $('#btnAgregarProducto').click(function() {
                const productoId = $('#productoSelect').val();
                const productoText = $('#productoSelect option:selected').text();
                const precio = parseFloat($('#precioUnitario').val());
                const cantidad = parseInt($('#cantidadProducto').val());
                const stock = parseInt($('#stockDisponible').val());
                
                if (!productoId || productoId === '') {
                    mostrarNotificacion('Selecciona un producto', 'warning');
                    return;
                }
                
                if (cantidad < 1) {
                    mostrarNotificacion('La cantidad debe ser mayor a 0', 'warning');
                    return;
                }
                
                if (cantidad > stock) {
                    mostrarNotificacion('Cantidad excede el stock disponible', 'error');
                    return;
                }
                
                // Verificar si el producto ya está en la lista
                const indexNuevo = productosNuevos.findIndex(p => p.id === productoId);
                const indexActual = productosActuales.findIndex(p => p.producto_id === parseInt(productoId));
                
                if (indexNuevo !== -1) {
                    productosNuevos[indexNuevo].cantidad += cantidad;
                    productosNuevos[indexNuevo].subtotal += precio * cantidad;
                    actualizarFilaNuevoProducto(productosNuevos[indexNuevo], indexNuevo);
                } else if (indexActual !== -1) {
                    // Si ya está en productos actuales, agregar cantidad
                    const cantidadActual = productosActuales[indexActual].cantidad;
                    productosActuales[indexActual].cantidad += cantidad;
                    productosActuales[indexActual].subtotal += precio * cantidad;
                    actualizarFilaProductoActual(indexActual);
                } else {
                    // Producto nuevo
                    const producto = {
                        id: productoId,
                        nombre: productoText,
                        precio: precio,
                        cantidad: cantidad,
                        subtotal: precio * cantidad,
                        es_nuevo: true
                    };
                    
                    productosNuevos.push(producto);
                    agregarFilaNuevoProducto(producto, productosNuevos.length - 1);
                }
                
                actualizarTotales();
                limpiarFormularioProducto();
                
                // Mostrar tabla de nuevos productos si hay
                if (productosNuevos.length > 0) {
                    $('#newProductsTable').show();
                }
            });
            
            // Agregar fila a la tabla de nuevos productos
            function agregarFilaNuevoProducto(producto, index) {
                const row = `
                    <tr id="nuevoProducto-${index}">
                        <td>${producto.nombre}</td>
                        <td>Bs. ${producto.precio.toFixed(2)}</td>
                        <td>${producto.cantidad}</td>
                        <td>Bs. ${producto.subtotal.toFixed(2)}</td>
                        <td>
                            <button class="btn-remove" onclick="eliminarNuevoProducto(${index})">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </td>
                    </tr>
                `;
                
                $('#newProductsBody').append(row);
            }
            
            // Actualizar fila de nuevo producto
            function actualizarFilaNuevoProducto(producto, index) {
                $(`#nuevoProducto-${index}`).html(`
                    <td>${producto.nombre}</td>
                    <td>Bs. ${producto.precio.toFixed(2)}</td>
                    <td>${producto.cantidad}</td>
                    <td>Bs. ${producto.subtotal.toFixed(2)}</td>
                    <td>
                        <button class="btn-remove" onclick="eliminarNuevoProducto(${index})">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </td>
                `);
            }
            
            // Eliminar producto actual de la factura
            window.eliminarProductoActual = function(button) {
                const row = $(button).closest('tr');
                const productoId = row.data('product-id');
                const cantidad = row.data('cantidad');
                const precio = row.data('precio');
                
                // Agregar a lista de eliminados
                productosEliminados.push({
                    producto_id: productoId,
                    cantidad: cantidad,
                    precio: precio
                });
                
                // Eliminar de productos actuales
                const index = productosActuales.findIndex(p => p.producto_id == productoId);
                if (index !== -1) {
                    productosActuales.splice(index, 1);
                }
                
                row.remove();
                actualizarTotales();
            };
            
            // Eliminar nuevo producto
            window.eliminarNuevoProducto = function(index) {
                productosNuevos.splice(index, 1);
                $(`#nuevoProducto-${index}`).remove();
                
                // Ocultar tabla si no hay productos nuevos
                if (productosNuevos.length === 0) {
                    $('#newProductsTable').hide();
                }
                
                actualizarTotales();
            };
            
            // Actualizar fila de producto actual
            function actualizarFilaProductoActual(index) {
                const producto = productosActuales[index];
                const rows = $('#currentProductsBody tr');
                const row = rows.eq(index);
                
                row.html(`
                    <td>
                        <strong>${producto.producto_nombre}</strong><br>
                        <small>SKU: ${producto.sku}</small>
                    </td>
                    <td>Bs. ${parseFloat(producto.precio_unitario).toFixed(2)}</td>
                    <td>${producto.cantidad}</td>
                    <td>Bs. ${parseFloat(producto.subtotal).toFixed(2)}</td>
                    <td>
                        <button class="btn-remove" onclick="eliminarProductoActual(this)">
                            <i class="fas fa-trash"></i> Quitar
                        </button>
                    </td>
                `);
                
                row.data('product-id', producto.producto_id);
                row.data('cantidad', producto.cantidad);
                row.data('precio', producto.precio_unitario);
            }
            
            // Actualizar totales
            function actualizarTotales() {
                // Calcular subtotal de productos actuales
                let subtotalActuales = productosActuales.reduce((sum, producto) => sum + parseFloat(producto.subtotal), 0);
                
                // Calcular subtotal de productos nuevos
                let subtotalNuevos = productosNuevos.reduce((sum, producto) => sum + producto.subtotal, 0);
                
                subtotal = subtotalActuales + subtotalNuevos;
                iva = subtotal * 0.16;
                total = subtotal + iva;
                
                $('#subtotalTotal').text('Bs. ' + subtotal.toFixed(2));
                $('#ivaTotal').text('Bs. ' + iva.toFixed(2));
                $('#totalFactura').text('Bs. ' + total.toFixed(2));
            }
            
            // Limpiar formulario de producto
            function limpiarFormularioProducto() {
                $('#productoSelect').val('').trigger('change');
                $('#precioUnitario').val('');
                $('#stockDisponible').val('');
                $('#cantidadProducto').val(1);
                $('#subtotalProducto').val('');
            }
            
            // Mostrar notificación
            function mostrarNotificacion(mensaje, tipo = 'success') {
                const notification = $('#notification');
                notification.removeClass('success error warning')
                          .addClass(tipo)
                          .html(`<i class="fas fa-${tipo === 'error' ? 'exclamation-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'check-circle'}"></i><span>${mensaje}</span>`)
                          .show();
                
                setTimeout(() => {
                    notification.fadeOut();
                }, 5000);
            }
            
            // Mostrar/ocultar loading
            function mostrarLoading(texto = 'Procesando...') {
                $('#loadingText').text(texto);
                $('#loadingOverlay').show();
            }
            
            function ocultarLoading() {
                $('#loadingOverlay').hide();
            }
            
            // Validar formulario
            function validarFormulario() {
                if (!$('#clienteSelect').val()) {
                    mostrarNotificacion('Seleccione un cliente', 'warning');
                    return false;
                }
                
                if (!$('#fechaEmision').val() || !$('#fechaVencimiento').val()) {
                    mostrarNotificacion('Complete las fechas', 'warning');
                    return false;
                }
                
                if (!$('#metodoPago').val()) {
                    mostrarNotificacion('Seleccione un método de pago', 'warning');
                    return false;
                }
                
                if (productosActuales.length === 0 && productosNuevos.length === 0) {
                    mostrarNotificacion('Debe haber al menos un producto en la factura', 'warning');
                    return false;
                }
                
                return true;
            }
            
            // Guardar cambios (sin actualizar aún)
            $('#btnGuardarCambios').click(function() {
                if (!validarFormulario()) return;
                
                // Guardar en localStorage como borrador temporal
                const cambios = {
                    factura_id: <?php echo $factura_id; ?>,
                    cliente_id: $('#clienteSelect').val(),
                    fecha_emision: $('#fechaEmision').val(),
                    fecha_vencimiento: $('#fechaVencimiento').val(),
                    metodo_pago: $('#metodoPago').val(),
                    observaciones: $('#observaciones').val(),
                    productos_actuales: productosActuales,
                    productos_nuevos: productosNuevos,
                    productos_eliminados: productosEliminados,
                    subtotal: subtotal,
                    iva: iva,
                    total: total,
                    timestamp: new Date().toISOString()
                };
                
                localStorage.setItem('borrador_factura_<?php echo $factura_id; ?>', JSON.stringify(cambios));
                mostrarNotificacion('Cambios guardados temporalmente', 'success');
            });
            
            // Actualizar factura definitivamente
            $('#btnActualizarFactura').click(function() {
                if (!validarFormulario()) return;
                
                if (!confirm('¿Está seguro de actualizar esta factura?\nEsta acción actualizará los registros permanentemente.')) {
                    return;
                }
                
                const datos = {
                    accion: 'editar_factura',
                    factura_id: <?php echo $factura_id; ?>,
                    cliente_id: $('#clienteSelect').val(),
                    fecha_emision: $('#fechaEmision').val(),
                    fecha_vencimiento: $('#fechaVencimiento').val(),
                    metodo_pago: $('#metodoPago').val(),
                    observaciones: $('#observaciones').val(),
                    productos_actuales: productosActuales,
                    productos_nuevos: productosNuevos,
                    productos_eliminados: productosEliminados,
                    subtotal: subtotal,
                    iva: iva,
                    total: total
                };
                
                mostrarLoading('Actualizando factura...');
                
                $.ajax({
                    url: 'procesar_factura.php',
                    method: 'POST',
                    data: JSON.stringify(datos),
                    contentType: 'application/json',
                    success: function(response) {
                        try {
                            const result = JSON.parse(response);
                            if (result.success) {
                                mostrarNotificacion('Factura actualizada exitosamente', 'success');
                                // Limpiar borrador temporal
                                localStorage.removeItem('borrador_factura_<?php echo $factura_id; ?>');
                                // Redirigir después de 2 segundos
                                setTimeout(() => {
                                    window.location.href = 'ver_factura.php?id=<?php echo $factura_id; ?>';
                                }, 2000);
                            } else {
                                mostrarNotificacion(result.message || 'Error al actualizar factura', 'error');
                                ocultarLoading();
                            }
                        } catch (e) {
                            mostrarNotificacion('Error procesando la respuesta del servidor', 'error');
                            ocultarLoading();
                        }
                    },
                    error: function(xhr, status, error) {
                        mostrarNotificacion('Error de conexión: ' + error, 'error');
                        ocultarLoading();
                    }
                });
            });
            
            // Cancelar edición
            $('#btnCancelar').click(function() {
                if (confirm('¿Cancelar la edición? Los cambios no guardados se perderán.')) {
                    window.location.href = 'ver_factura.php?id=<?php echo $factura_id; ?>';
                }
            });
            
            // Cargar borrador temporal al cargar la página
            const borrador = localStorage.getItem('borrador_factura_<?php echo $factura_id; ?>');
            if (borrador) {
                try {
                    const cambios = JSON.parse(borrador);
                    if (confirm('Hay cambios guardados temporalmente. ¿Desea recuperarlos?')) {
                        // Aquí podrías cargar los cambios del borrador
                        console.log('Cargando borrador:', cambios);
                        mostrarNotificacion('Borrador recuperado', 'info');
                    }
                } catch (e) {
                    console.error('Error al cargar borrador:', e);
                }
            }
            
            // Calcular subtotal inicial
            calcularSubtotalProducto();
        });
    </script>
</body>
</html>