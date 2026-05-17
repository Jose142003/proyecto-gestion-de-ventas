<?php
session_start();
require_once '../conexion/conexion.php';

$pdo = conectarDB();

// Configuración de la página
$page_title = 'Nueva Factura';

// Obtener información del usuario de la sesión
$usuario_id = $_SESSION['user_id'] ?? 1; // Default a admin si no hay sesión
$usuario_nombre = $_SESSION['user_name'] ?? 'Administrador';

// Obtener clientes
$clientes = [];
try {
    $stmt = $pdo->query("SELECT id, documento, nombre, email, telefono FROM clientes WHERE estado = 'activo' ORDER BY nombre");
    $clientes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error obteniendo clientes: " . $e->getMessage());
}

// Obtener productos
$productos = [];
try {
    $stmt = $pdo->query("SELECT id, sku, name, price, stock, category FROM products WHERE stock > 0 ORDER BY name");
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error obteniendo productos: " . $e->getMessage());
}

// Obtener siguiente número de factura
$numero_factura = '';
try {
    $anio_actual = date('Y');
    $stmt = $pdo->prepare("SELECT numero_factura FROM facturas WHERE YEAR(fecha_emision) = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$anio_actual]);
    $ultima_factura = $stmt->fetch();
    
    if ($ultima_factura && !empty($ultima_factura['numero_factura'])) {
        $partes = explode('-', $ultima_factura['numero_factura']);
        $ultimo_numero = end($partes);
        $siguiente = intval($ultimo_numero) + 1;
        $numero_factura = "FAC-{$anio_actual}-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
    } else {
        $numero_factura = "FAC-{$anio_actual}-000001";
    }
} catch (PDOException $e) {
    error_log("Error obteniendo número de factura: " . $e->getMessage());
    $numero_factura = "FAC-" . date('Y') . "-" . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Nueva Factura - PIC</title>
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
        
        .invoice-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .invoice-header {
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
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header-title h1 {
            color: var(--bush-black);
            font-size: 1.8rem;
            margin: 0;
        }
        
        .invoice-number {
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(60, 145, 237, 0.3);
            word-break: break-word;
            text-align: center;
        }
         
        .invoice-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            flex-wrap: wrap;
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
        
        .invoice-products {
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
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .section-title i {
            color: var(--chefcheorem-blue);
        }
        
        .product-add-form {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border: 2px solid #e9ecef;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        
        .form-row .form-group {
            flex: 1 1 150px;
            margin-bottom: 0;
            min-width: 120px;
        }
        
        .form-row .form-group:first-child {
            flex: 2 1 250px;
        }
        
        .btn-add-product {
            flex: 0 0 auto;
            white-space: nowrap;
            background: linear-gradient(135deg, var(--success), #26c46a);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 10px rgba(46, 213, 115, 0.3);
            min-width: 120px;
        }
        
        .btn-add-product:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 213, 115, 0.4);
        }
        
        .products-table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        .products-table th {
            padding: 15px 12px;
            text-align: left;
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .products-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            color: #333;
            font-size: 0.9rem;
        }
        
        .products-table tr:hover td {
            background-color: rgba(60, 145, 237, 0.05);
        }
        
        .btn-remove {
            background: linear-gradient(135deg, var(--danger), #ff2e43);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        
        .btn-remove:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255, 71, 87, 0.3);
        }
        
        .invoice-summary {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--bush-black);
        }
        
        .summary-row.total {
            color: var(--chefcheorem-blue);
            font-size: 1.3rem;
        }
        
        .invoice-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding-top: 30px;
            border-top: 2px solid #eee;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--chefcheorem-blue), var(--maya-blue));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
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
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
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
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
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
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            color: white;
            z-index: 3000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideIn 0.3s ease;
            max-width: 90%;
            width: 350px;
            word-wrap: break-word;
            display: flex;
            align-items: center;
            gap: 12px;
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
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--chefcheorem-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--chefcheorem-blue);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 18px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .back-button:hover {
            transform: translateX(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }
        
        /* Estilos responsivos */
        @media (max-width: 992px) {
            .invoice-info-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .summary-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .invoice-header,
            .invoice-products,
            .invoice-summary {
                padding: 20px;
            }
        }
        
        @media (max-width: 768px) {
            .invoice-container {
                padding: 10px;
            }
            
            .header-title {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-title h1 {
                font-size: 1.5rem;
            }
            
            .invoice-number {
                font-size: 0.85rem;
                padding: 8px 15px;
            }
            
            .product-add-form {
                padding: 15px;
            }
            
            .form-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-row .form-group,
            .form-row .form-group:first-child {
                width: 100%;
                flex: 1 1 auto;
            }
            
            .btn-add-product {
                width: 100%;
            }
            
            .info-card {
                padding: 15px;
            }
            
            .summary-card {
                padding: 15px;
            }
            
            .summary-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .invoice-actions {
                flex-direction: column;
            }
            
            .invoice-actions button {
                width: 100%;
                justify-content: center;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .products-table th,
            .products-table td {
                padding: 8px 10px;
                font-size: 0.8rem;
            }
            
            .btn-remove {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 480px) {
            .products-table {
                min-width: 500px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <a href="/proyecto/admin-panel/panel_admin.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Volver al Panel
        </a>
        
        <div class="invoice-header">
            <div class="header-title">
                <h1><i class="fas fa-file-invoice-dollar"></i> Nueva Factura</h1>
                <div class="invoice-number" id="numeroFactura"><?php echo htmlspecialchars($numero_factura); ?></div>
            </div>
            
            <div class="invoice-info-grid">
                <div class="info-card">
                    <h3><i class="fas fa-user"></i> Información del Cliente</h3>
                    <div class="form-group">
                        <label class="form-label">Cliente *</label>
                        <select id="clienteSelect" class="form-control select2" required>
                            <option value="">Seleccionar cliente...</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo htmlspecialchars($cliente['id']); ?>"
                                    data-email="<?php echo htmlspecialchars($cliente['email']); ?>"
                                    data-telefono="<?php echo htmlspecialchars($cliente['telefono']); ?>">
                                    <?php echo htmlspecialchars($cliente['documento'] . ' - ' . $cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="clienteInfo" style="display: none;">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" id="clienteEmail" class="form-control" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Teléfono</label>
                            <input type="text" id="clienteTelefono" class="form-control" readonly>
                        </div>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3><i class="fas fa-calendar-alt"></i> Fechas</h3>
                    <div class="form-group">
                        <label class="form-label">Fecha de Emisión *</label>
                        <input type="date" id="fechaEmision" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fecha de Vencimiento *</label>
                        <input type="date" id="fechaVencimiento" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="invoice-products">
            <h3 class="section-title"><i class="fas fa-boxes"></i> Productos</h3>
            
            <div class="product-add-form">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Producto *</label>
                        <select id="productoSelect" class="form-control select2">
                            <option value="">Buscar producto...</option>
                            <?php foreach ($productos as $producto): ?>
                                <option value="<?php echo htmlspecialchars($producto['id']); ?>" 
                                        data-precio="<?php echo htmlspecialchars($producto['price']); ?>"
                                        data-stock="<?php echo htmlspecialchars($producto['stock']); ?>">
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
                        <label class="form-label">Stock</label>
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
            
            <div class="products-table-container">
                <table class="products-table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Precio Unitario</th>
                            <th>Cantidad</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productosTableBody">
                        <tr id="emptyRow">
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                <i class="fas fa-box" style="font-size: 2.5rem; color: #ccc; margin-bottom: 10px; display: block;"></i>
                                <strong>No hay productos agregados</strong>
                                <p style="font-size: 0.85rem; margin-top: 5px;">Agrega productos usando el formulario superior</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="invoice-summary">
            <h3 class="section-title"><i class="fas fa-calculator"></i> Resumen de Factura</h3>
            
            <div class="summary-grid">
                <div class="summary-card">
                    <h3><i class="fas fa-money-bill-wave"></i> Totales</h3>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotalTotal">Bs. 0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>IVA (16%):</span>
                        <span id="ivaTotal">Bs. 0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>TOTAL:</span>
                        <span id="totalFactura">Bs. 0.00</span>
                    </div>
                </div>
                
                <div class="summary-card">
                    <h3><i class="fas fa-credit-card"></i> Método de Pago</h3>
                    <div class="form-group">
                        <label class="form-label">Método de Pago *</label>
                        <select id="metodoPago" class="form-control" required>
                            <option value="">Seleccionar método...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia Bancaria</option>
                            <option value="pago_movil">Pago Móvil</option>
                            <option value="mixto">Pago Mixto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Observaciones</label>
                        <textarea id="observaciones" class="form-control" rows="3" placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="invoice-actions">
                <button type="button" id="btnCancelar" class="btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" id="btnGuardarBorrador" class="btn-primary">
                    <i class="fas fa-save"></i> Guardar Borrador
                </button>
                <button type="button" id="btnGenerarFactura" class="btn-success">
                    <i class="fas fa-check"></i> Generar Factura
                </button>
            </div>
        </div>
    </div>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <p id="loadingText" style="font-size: 1rem; color: var(--chefcheorem-blue);">Procesando...</p>
    </div>
    
    <!-- Notification -->
    <div class="notification" id="notification"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
    // Variables globales
    let productosAgregados = [];
    let subtotal = 0;
    let iva = 0;
    let total = 0;

    $(document).ready(function() {
        // Inicializar Select2
        $('.select2').select2({
            placeholder: 'Seleccionar...',
            allowClear: true,
            width: '100%'
        });
        
        // Cargar datos del cliente al seleccionar
        $('#clienteSelect').on('change', function() {
            const selected = $(this).find('option:selected');
            const email = selected.data('email');
            const telefono = selected.data('telefono');
            
            if (selected.val()) {
                $('#clienteInfo').show();
                $('#clienteEmail').val(email || '');
                $('#clienteTelefono').val(telefono || '');
            } else {
                $('#clienteInfo').hide();
                $('#clienteEmail').val('');
                $('#clienteTelefono').val('');
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
        
        // Calcular subtotal del producto cuando cambia cantidad
        $('#cantidadProducto').on('input', function() {
            calcularSubtotalProducto();
        });
        
        // Agregar producto a la tabla
        $('#btnAgregarProducto').click(function() {
            const productoId = $('#productoSelect').val();
            const productoText = $('#productoSelect option:selected').text();
            const precio = parseFloat($('#precioUnitario').val()) || 0;
            const cantidad = parseInt($('#cantidadProducto').val()) || 0;
            const stock = parseInt($('#stockDisponible').val()) || 0;
            
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
            
            // Verificar si el producto ya está agregado
            const index = productosAgregados.findIndex(p => p.id == productoId);
            if (index !== -1) {
                productosAgregados[index].cantidad += cantidad;
                productosAgregados[index].subtotal += precio * cantidad;
                actualizarFilaProducto(productosAgregados[index], index);
            } else {
                const producto = {
                    id: productoId,
                    nombre: productoText,
                    precio: precio,
                    cantidad: cantidad,
                    subtotal: precio * cantidad,
                    stock: stock
                };
                
                productosAgregados.push(producto);
                agregarFilaProducto(producto, productosAgregados.length - 1);
            }
            
            actualizarTotales();
            limpiarFormularioProducto();
            $('#emptyRow').hide();
        });
        
        // Generar factura
        $('#btnGenerarFactura').click(function() {
            if (!validarFormulario()) return;
            
            if (productosAgregados.length === 0) {
                mostrarNotificacion('Debe agregar al menos un producto', 'warning');
                return;
            }
            
            if (!confirm('¿Está seguro de generar esta factura?')) {
                return;
            }
            
            const facturaData = {
                cliente_id: $('#clienteSelect').val(),
                fecha_emision: $('#fechaEmision').val(),
                fecha_vencimiento: $('#fechaVencimiento').val(),
                metodo_pago: $('#metodoPago').val(),
                observaciones: $('#observaciones').val(),
                productos: productosAgregados.map(p => ({
                    id: p.id,
                    cantidad: p.cantidad,
                    precio: p.precio,
                    subtotal: p.subtotal,
                    nombre: p.nombre
                })),
                subtotal: subtotal,
                iva: iva,
                total: total,
                usuario_id: <?php echo $usuario_id; ?>
            };
            
            mostrarLoading('Generando factura...');
            
            $.ajax({
                url: 'procesar_factura.php',
                method: 'POST',
                data: JSON.stringify(facturaData),
                contentType: 'application/json',
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            mostrarNotificacion('Factura generada exitosamente', 'success');
                            setTimeout(() => {
                                window.location.href = 'ver_factura.php?id=' + result.factura_id;
                            }, 2000);
                        } else {
                            mostrarNotificacion(result.message || 'Error al generar factura', 'error');
                            ocultarLoading();
                        }
                    } catch (e) {
                        console.error('Error parseando respuesta:', e, response);
                        mostrarNotificacion('Error procesando la respuesta del servidor', 'error');
                        ocultarLoading();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', error, xhr.responseText);
                    mostrarNotificacion('Error de conexión: ' + error, 'error');
                    ocultarLoading();
                }
            });
        });
        
        // Guardar borrador
        $('#btnGuardarBorrador').click(function() {
            if (!validarFormulario()) return;
            
            if (productosAgregados.length === 0) {
                mostrarNotificacion('Debe agregar al menos un producto', 'warning');
                return;
            }
            
            const facturaData = {
                cliente_id: $('#clienteSelect').val(),
                fecha_emision: $('#fechaEmision').val(),
                fecha_vencimiento: $('#fechaVencimiento').val(),
                metodo_pago: $('#metodoPago').val(),
                observaciones: $('#observaciones').val(),
                productos: productosAgregados.map(p => ({
                    id: p.id,
                    cantidad: p.cantidad,
                    precio: p.precio,
                    subtotal: p.subtotal,
                    nombre: p.nombre
                })),
                subtotal: subtotal,
                iva: iva,
                total: total,
                usuario_id: <?php echo $usuario_id; ?>,
                estado: 'borrador'
            };
            
            mostrarLoading('Guardando borrador...');
            
            $.ajax({
                url: 'procesar_factura.php',
                method: 'POST',
                data: JSON.stringify(facturaData),
                contentType: 'application/json',
                success: function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            mostrarNotificacion('Borrador guardado exitosamente', 'success');
                            ocultarLoading();
                        } else {
                            mostrarNotificacion(result.message || 'Error al guardar borrador', 'error');
                            ocultarLoading();
                        }
                    } catch (e) {
                        mostrarNotificacion('Error procesando la respuesta del servidor', 'error');
                        ocultarLoading();
                    }
                },
                error: function() {
                    mostrarNotificacion('Error de conexión', 'error');
                    ocultarLoading();
                }
            });
        });
        
        // Cancelar
        $('#btnCancelar').click(function() {
            if (confirm('¿Está seguro de cancelar? Se perderán los datos no guardados.')) {
                window.location.href = '/proyecto/admin-panel/panel_admin.php';
            }
        });
        
        // Calcular subtotal inicial
        calcularSubtotalProducto();
        
        // Cargar datos del pedido desde sessionStorage
        setTimeout(cargarDatosPedido, 500);
    });

    // Función para agregar fila a la tabla
    function agregarFilaProducto(producto, index) {
        const row = `
            <tr id="productoRow-${index}">
                <td>${escapeHtml(producto.nombre)}</td>
                <td>Bs. ${producto.precio.toFixed(2)}</td>
                <td><input type="number" value="${producto.cantidad}" min="1" onchange="actualizarCantidadProducto(${index}, this.value)" style="width:70px; padding:5px; border-radius:4px; border:1px solid #ddd;"></td>
                <td>Bs. ${producto.subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn-remove" onclick="eliminarProducto(${index})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </td>
            </tr>
        `;
        
        if ($('#emptyRow').is(':visible')) {
            $('#productosTableBody').html(row);
        } else {
            $('#productosTableBody').append(row);
        }
    }

    // Actualizar fila existente
    function actualizarFilaProducto(producto, index) {
        $(`#productoRow-${index}`).html(`
            <td>${escapeHtml(producto.nombre)}</td>
            <td>Bs. ${producto.precio.toFixed(2)}</td>
            <td><input type="number" value="${producto.cantidad}" min="1" onchange="actualizarCantidadProducto(${index}, this.value)" style="width:70px; padding:5px; border-radius:4px; border:1px solid #ddd;"></td>
            <td>Bs. ${producto.subtotal.toFixed(2)}</td>
            <td>
                <button class="btn-remove" onclick="eliminarProducto(${index})">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </td>
        `);
    }

    // Actualizar cantidad de producto
    function actualizarCantidadProducto(index, nuevaCantidad) {
        const cantidad = parseInt(nuevaCantidad);
        if (!isNaN(cantidad) && cantidad > 0) {
            productosAgregados[index].cantidad = cantidad;
            productosAgregados[index].subtotal = productosAgregados[index].precio * cantidad;
            actualizarFilaProducto(productosAgregados[index], index);
            actualizarTotales();
        }
    }

    // Eliminar producto
    function eliminarProducto(index) {
        productosAgregados.splice(index, 1);
        $('#productosTableBody').html('');
        
        if (productosAgregados.length === 0) {
            $('#emptyRow').show();
        } else {
            productosAgregados.forEach((producto, i) => {
                agregarFilaProducto(producto, i);
            });
        }
        
        actualizarTotales();
    }

    // Calcular subtotal del producto
    function calcularSubtotalProducto() {
        const precio = parseFloat($('#precioUnitario').val()) || 0;
        const cantidad = parseInt($('#cantidadProducto').val()) || 0;
        const subtotalProducto = precio * cantidad;
        
        $('#subtotalProducto').val('Bs. ' + subtotalProducto.toFixed(2));
    }

    // Actualizar totales
    function actualizarTotales() {
        subtotal = productosAgregados.reduce((sum, producto) => sum + producto.subtotal, 0);
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
        
        return true;
    }

    // Mostrar notificación
    function mostrarNotificacion(mensaje, tipo = 'success') {
        const notification = $('#notification');
        notification.removeClass('success error warning');
        
        let icono = 'check-circle';
        if (tipo === 'error') icono = 'exclamation-circle';
        if (tipo === 'warning') icono = 'exclamation-triangle';
        
        notification.addClass(tipo)
            .html(`<i class="fas fa-${icono}" style="font-size: 1.2rem;"></i><span>${mensaje}</span>`)
            .fadeIn(300);
        
        setTimeout(() => {
            notification.fadeOut(300);
        }, 5000);
    }

    // Mostrar loading
    function mostrarLoading(texto = 'Procesando...') {
        $('#loadingText').text(texto);
        $('#loadingOverlay').fadeIn(300);
    }

    function ocultarLoading() {
        $('#loadingOverlay').fadeOut(300);
    }

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Cargar datos del pedido
    function cargarDatosPedido() {
        // Verificar si hay datos en sessionStorage
        const pedidoGuardado = sessionStorage.getItem('pedido_para_facturar');
        
        if (pedidoGuardado) {
            try {
                const pedido = JSON.parse(pedidoGuardado);
                console.log('Cargando pedido:', pedido);
                
                // Seleccionar cliente
                if (pedido.cliente && pedido.cliente.id) {
                    $('#clienteSelect').val(pedido.cliente.id).trigger('change');
                }
                
                // Agregar productos
                if (pedido.productos && pedido.productos.length > 0) {
                    let i = 0;
                    function agregarSiguiente() {
                        if (i >= pedido.productos.length) {
                            actualizarTotales();
                            return;
                        }
                        
                        const producto = pedido.productos[i];
                        const optionExists = $(`#productoSelect option[value="${producto.id}"]`).length > 0;
                        
                        if (optionExists) {
                            $('#productoSelect').val(producto.id).trigger('change');
                            $('#cantidadProducto').val(producto.cantidad);
                            
                            setTimeout(() => {
                                const nuevoProducto = {
                                    id: producto.id,
                                    nombre: producto.nombre || producto.producto_nombre || 'Producto',
                                    precio: parseFloat(producto.precio) || 0,
                                    cantidad: parseInt(producto.cantidad) || 1,
                                    subtotal: (parseFloat(producto.precio) || 0) * (parseInt(producto.cantidad) || 1),
                                    stock: producto.stock || 999
                                };
                                
                                productosAgregados.push(nuevoProducto);
                                agregarFilaProducto(nuevoProducto, productosAgregados.length - 1);
                                $('#emptyRow').hide();
                                
                                i++;
                                setTimeout(agregarSiguiente, 200);
                            }, 200);
                        } else {
                            const nuevoProducto = {
                                id: producto.id,
                                nombre: producto.nombre || 'Producto',
                                precio: producto.precio || 0,
                                cantidad: producto.cantidad || 1,
                                subtotal: (producto.precio || 0) * (producto.cantidad || 1),
                                stock: 999
                            };
                            productosAgregados.push(nuevoProducto);
                            agregarFilaProducto(nuevoProducto, productosAgregados.length - 1);
                            $('#emptyRow').hide();
                            i++;
                            setTimeout(agregarSiguiente, 100);
                        }
                    }
                    
                    agregarSiguiente();
                }
                
                // Establecer método de pago
                if (pedido.metodo_pago) {
                    $('#metodoPago').val(pedido.metodo_pago);
                }
                
                // Establecer observaciones
                if (pedido.observaciones) {
                    $('#observaciones').val(pedido.observaciones);
                }
                
                mostrarNotificacion('Pedido cargado automáticamente', 'success');
                
            } catch (e) {
                console.error('Error cargando pedido:', e);
            }
        }
        
        // Verificar pedido_id en URL
        const urlParams = new URLSearchParams(window.location.search);
        const pedidoId = urlParams.get('pedido_id');
        
        if (pedidoId && !pedidoGuardado) {
            cargarPedidoPorId(pedidoId);
        }
    }

    function cargarPedidoPorId(pedidoId) {
        mostrarLoading('Cargando pedido...');
        
        fetch(`/proyecto/proceso-compra/obtener_detalles_pedido.php?pedido_id=${pedidoId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const pedido = data.pedido || data;
                    const productos = data.productos || data.items || [];
                    
                    if (pedido.cliente_id) {
                        $('#clienteSelect').val(pedido.cliente_id).trigger('change');
                    }
                    
                    if (productos.length > 0) {
                        let i = 0;
                        function agregarSiguiente() {
                            if (i >= productos.length) {
                                actualizarTotales();
                                return;
                            }
                            
                            const producto = productos[i];
                            const productoId = producto.producto_id || producto.id;
                            
                            if ($(`#productoSelect option[value="${productoId}"]`).length > 0) {
                                $('#productoSelect').val(productoId).trigger('change');
                                $('#cantidadProducto').val(producto.cantidad || 1);
                                
                                setTimeout(() => {
                                    const nuevoProducto = {
                                        id: productoId,
                                        nombre: producto.nombre || producto.producto_nombre || 'Producto',
                                        precio: parseFloat(producto.precio_unitario || producto.precio || 0),
                                        cantidad: parseInt(producto.cantidad || 1),
                                        subtotal: parseFloat(producto.subtotal || 0),
                                        stock: 999
                                    };
                                    
                                    productosAgregados.push(nuevoProducto);
                                    agregarFilaProducto(nuevoProducto, productosAgregados.length - 1);
                                    $('#emptyRow').hide();
                                    
                                    i++;
                                    setTimeout(agregarSiguiente, 200);
                                }, 200);
                            } else {
                                const nuevoProducto = {
                                    id: productoId,
                                    nombre: producto.nombre || producto.producto_nombre || 'Producto',
                                    precio: parseFloat(producto.precio_unitario || producto.precio || 0),
                                    cantidad: parseInt(producto.cantidad || 1),
                                    subtotal: parseFloat(producto.subtotal || 0),
                                    stock: 999
                                };
                                productosAgregados.push(nuevoProducto);
                                agregarFilaProducto(nuevoProducto, productosAgregados.length - 1);
                                $('#emptyRow').hide();
                                i++;
                                setTimeout(agregarSiguiente, 100);
                            }
                        }
                        
                        agregarSiguiente();
                    }
                    
                    if (pedido.metodo_pago) {
                        $('#metodoPago').val(pedido.metodo_pago);
                    }
                    
                    if (pedido.observaciones) {
                        $('#observaciones').val(pedido.observaciones);
                    }
                    
                    mostrarNotificacion('Pedido cargado correctamente', 'success');
                } else {
                    mostrarNotificacion('Error al cargar pedido', 'error');
                }
                ocultarLoading();
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarNotificacion('Error al cargar pedido', 'error');
                ocultarLoading();
            });
    }
    </script>
</body>
</html>