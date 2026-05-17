<?php
// informacion_producto.php - Muestra información técnica y estadísticas del producto

// Configuración de conexión
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

// Obtener ID del producto
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Variables
$producto = null;
$error = '';
$ventas_recientes = [];
$productos_similares = [];

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $conn->set_charset("utf8");

    // 1. INFORMACIÓN BÁSICA DEL PRODUCTO
    $sql = "SELECT 
                p.id, 
                p.sku,
                p.name as nombre, 
                p.description as descripcion,
                p.price as precio, 
                p.stock,
                p.category as categoria,
                p.image_url as imagen,
                p.rating,
                p.specs,
                p.weight,
                p.dimensions,
                p.currency,
                p.is_featured as destacado,
                p.views_count as visitas
            FROM products p 
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error = '❌ Producto no encontrado en la base de datos';
    } else {
        $producto = $result->fetch_assoc();
        
        // 2. ESTADÍSTICAS DE VENTAS DE LA TABLA factura_detalles
        $sql_ventas = "SELECT 
                SUM(fd.cantidad) as total_vendido,
                SUM(fd.subtotal) as total_ventas,
                COUNT(DISTINCT fd.factura_id) as facturas_con_producto
            FROM factura_detalles fd
            WHERE fd.producto_id = ?";
        
        $stmt_ventas = $conn->prepare($sql_ventas);
        $stmt_ventas->bind_param("i", $id);
        $stmt_ventas->execute();
        $result_ventas = $stmt_ventas->get_result();
        $estadisticas_ventas = $result_ventas->fetch_assoc();
        
        // 3. VENTAS RECIENTES (si existe la tabla factura_detalles)
        $sql_ventas_recientes = "SELECT 
                fd.cantidad,
                fd.precio_unitario,
                fd.subtotal,
                f.numero_factura,
                DATE_FORMAT(f.fecha_emision, '%d/%m/%Y') as fecha_venta,
                c.nombre as cliente,
                f.estado
            FROM factura_detalles fd
            JOIN facturas f ON fd.factura_id = f.id
            JOIN clientes c ON f.cliente_id = c.id
            WHERE fd.producto_id = ?
            ORDER BY f.fecha_emision DESC
            LIMIT 5";
        
        $stmt_ventas_recientes = $conn->prepare($sql_ventas_recientes);
        $stmt_ventas_recientes->bind_param("i", $id);
        $stmt_ventas_recientes->execute();
        $result_ventas_recientes = $stmt_ventas_recientes->get_result();
        
        while ($row = $result_ventas_recientes->fetch_assoc()) {
            $ventas_recientes[] = $row;
        }
        
        // 4. MOVIMIENTOS DE INVENTARIO
        $sql_movimientos = "SELECT 
                tipo_movimiento,
                COUNT(*) as total_movimientos,
                SUM(cantidad) as cantidad_total,
                DATE_FORMAT(MAX(fecha_movimiento), '%d/%m/%Y %H:%i') as ultimo_movimiento
            FROM movimientos_inventario 
            WHERE producto_id = ?
            GROUP BY tipo_movimiento";
        
        $stmt_movimientos = $conn->prepare($sql_movimientos);
        $stmt_movimientos->bind_param("i", $id);
        $stmt_movimientos->execute();
        $result_movimientos = $stmt_movimientos->get_result();
        
        $movimientos_inventario = [];
        while ($row = $result_movimientos->fetch_assoc()) {
            $movimientos_inventario[$row['tipo_movimiento']] = $row;
        }
        
        // 5. PRODUCTOS SIMILARES EN LA MISMA CATEGORÍA
        $sql_similares = "SELECT 
            id, name, price, stock, sku, image_url, rating,
            CASE 
                WHEN stock = 0 THEN 'Agotado'
                WHEN stock <= 5 THEN 'Bajo Stock'
                WHEN stock <= 10 THEN 'Stock Medio'
                ELSE 'En Stock'
            END as estado_stock
        FROM products 
        WHERE category = ? AND id != ?
        ORDER BY stock DESC, name ASC
        LIMIT 5";
        
        $stmt_sim = $conn->prepare($sql_similares);
        $stmt_sim->bind_param("si", $producto['categoria'], $id);
        $stmt_sim->execute();
        $result_sim = $stmt_sim->get_result();
        
        while ($row = $result_sim->fetch_assoc()) {
            $productos_similares[] = $row;
        }
        
        // 6. CÁLCULO DE VALORES
        $valor_inventario = $producto['precio'] * $producto['stock'];
        
        // Calcular entradas y salidas de inventario
        $entradas = isset($movimientos_inventario['entrada']) ? $movimientos_inventario['entrada']['cantidad_total'] : 0;
        $salidas = isset($movimientos_inventario['salida']) ? $movimientos_inventario['salida']['cantidad_total'] : 0;
        $stock_inicial = $entradas - $salidas + $producto['stock'];
        
        // Estadísticas de ventas
        $total_vendido = $estadisticas_ventas['total_vendido'] ?: 0;
        $total_ventas = $estadisticas_ventas['total_ventas'] ?: 0;
        $facturas_con_producto = $estadisticas_ventas['facturas_con_producto'] ?: 0;
        
        // Nivel de stock
        if ($producto['stock'] == 0) {
            $nivel_stock = '❌ CRÍTICO (Agotado)';
            $color_stock = 'rojo';
        } elseif ($producto['stock'] <= 3) {
            $nivel_stock = '⚠️ MUY BAJO';
            $color_stock = 'naranja';
        } elseif ($producto['stock'] <= 10) {
            $nivel_stock = '⚠️ BAJO';
            $color_stock = 'amarillo';
        } elseif ($producto['stock'] <= 20) {
            $nivel_stock = '✅ ADECUADO';
            $color_stock = 'verde-claro';
        } elseif ($producto['stock'] <= 50) {
            $nivel_stock = '✅ ÓPTIMO';
            $color_stock = 'verde';
        } else {
            $nivel_stock = '✅ ALTO';
            $color_stock = 'verde-oscuro';
        }
        
    }
    
} catch (Exception $e) {
    $error = 'Error en el sistema: ' . $e->getMessage();
}

// Cerrar conexión
if (isset($conn)) {
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Información Técnica - <?php echo $producto ? htmlspecialchars($producto['nombre']) : 'Producto'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        /* HEADER */
        .header {
            background: linear-gradient(90deg, #1a237e 0%, #283593 100%);
            color: white;
            padding: 30px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 300;
        }
        
        .header h1 strong {
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            gap: 15px;
        }
        
        .btn-volver {
            background: #4caf50;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }
        
        .btn-volver:hover {
            background: #388e3c;
        }
        
        .badge {
            background: #4caf50;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* CONTENIDO PRINCIPAL */
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            min-height: 800px;
        }
        
        /* PANEL IZQUIERDO - INFORMACIÓN BÁSICA */
        .left-panel {
            padding: 40px;
            background: #f8f9fa;
            border-right: 1px solid #e0e0e0;
        }
        
        .product-header {
            display: flex;
            align-items: center;
            gap: 25px;
            margin-bottom: 40px;
            padding-bottom: 25px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .product-image {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 12px;
            background: white;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border: 1px solid #ddd;
        }
        
        .product-basic-info h2 {
            font-size: 24px;
            color: #1a237e;
            margin-bottom: 10px;
        }
        
        .product-sku {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        /* INFORMACIÓN TÉCNICA */
        .tech-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .section-title {
            color: #1a237e;
            font-size: 18px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            font-size: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #4caf50;
        }
        
        .info-label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .info-value {
            font-weight: 500;
            color: #555;
            text-align: right;
            font-size: 14px;
        }
        
        /* PANEL DERECHO - ESTADÍSTICAS */
        .right-panel {
            padding: 40px;
            background: white;
        }
        
        /* ESTADÍSTICAS DE INVENTARIO */
        .stats-section {
            margin-bottom: 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            color: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-unit {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* NIVEL DE STOCK */
        .stock-level {
            padding: 20px;
            border-radius: 12px;
            margin: 25px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .stock-level.rojo { background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%); color: white; }
        .stock-level.naranja { background: linear-gradient(135deg, #ffa726 0%, #f57c00 100%); color: white; }
        .stock-level.amarillo { background: linear-gradient(135deg, #fff176 0%, #fdd835 100%); color: #333; }
        .stock-level.verde-claro { background: linear-gradient(135deg, #a5d6a7 0%, #81c784 100%); color: #333; }
        .stock-level.verde { background: linear-gradient(135deg, #66bb6a 0%, #4caf50 100%); color: white; }
        .stock-level.verde-oscuro { background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%); color: white; }
        
        /* TABLAS */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        thead {
            background: linear-gradient(90deg, #1a237e 0%, #283593 100%);
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 500;
            font-size: 14px;
        }
        
        tbody tr {
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background-color: #f5f5f5;
        }
        
        td {
            padding: 15px;
            font-size: 13px;
            color: #555;
        }
        
        /* PRODUCTOS SIMILARES */
        .similar-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .similar-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .similar-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        
        .similar-name {
            font-size: 13px;
            font-weight: 600;
            color: #1a237e;
            margin-bottom: 8px;
        }
        
        .similar-stock {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            display: inline-block;
            margin-top: 5px;
        }
        
        .stock-agotado { background: #ffebee; color: #c62828; }
        .stock-bajo { background: #fff3e0; color: #ef6c00; }
        .stock-medio { background: #fff8e1; color: #f9a825; }
        .stock-ok { background: #e8f5e9; color: #2e7d32; }
        
        /* PIE DE PÁGINA */
        .footer {
            padding: 25px 40px;
            background: #f8f9fa;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 14px;
        }
        
        .timestamp {
            font-style: italic;
        }
        
        .print-btn {
            background: #4caf50;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .print-btn:hover {
            background: #388e3c;
        }
        
        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .left-panel {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
        }
        
        @media (max-width: 768px) {
            .info-grid, .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .product-header {
                flex-direction: column;
                text-align: center;
            }
            
            .header-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-volver {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* ERROR */
        .error-container {
            text-align: center;
            padding: 80px 40px;
        }
        
        .error-container h2 {
            color: #d32f2f;
            margin-bottom: 20px;
        }
        
        /* COLORES PARA ESTADÍSTICAS */
        .entrada { background: linear-gradient(135deg, #4caf50 0%, #2e7d32 100%); }
        .salida { background: linear-gradient(135deg, #f44336 0%, #c62828 100%); }
        .inventario { background: linear-gradient(135deg, #2196f3 0%, #0d47a1 100%); }
        .valor { background: linear-gradient(135deg, #ff9800 0%, #ef6c00 100%); }
        .ventas { background: linear-gradient(135deg, #9c27b0 0%, #6a1b9a 100%); }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($error): ?>
            <div class="error-container">
                <h2><?php echo htmlspecialchars($error); ?></h2>
                <p>ID de producto: <?php echo $id; ?></p>
                <a href="/proyecto/panel%20admin/panel_admin.php" class="btn-volver">
                    ← Volver al Panel de Administración
                </a>
            </div>
            
        <?php elseif ($producto): ?>
            <!-- HEADER -->
            <div class="header">
                <h1>📊 <strong>INFORMACIÓN TÉCNICA</strong> DEL PRODUCTO</h1>
                <div class="header-actions">
                    <a href="/proyecto/panel%20admin/panel_admin.html" class="btn-volver">
                        ← Volver al Panel Admin
                    </a>
                    <div class="badge">ID: <?php echo $producto['id']; ?> | <?php echo date('d/m/Y H:i'); ?></div>
                </div>
            </div>
            
            <!-- CONTENIDO PRINCIPAL -->
            <div class="content">
                <!-- PANEL IZQUIERDO - INFORMACIÓN BÁSICA -->
                <div class="left-panel">
                    <!-- ENCABEZADO DEL PRODUCTO -->
                    <div class="product-header">
                        <img src="<?php echo htmlspecialchars($producto['imagen']); ?>" 
                             alt="<?php echo htmlspecialchars($producto['nombre']); ?>"
                             class="product-image"
                             onerror="this.src='https://via.placeholder.com/150x150?text=Imagen+no+disponible'">
                        <div class="product-basic-info">
                            <h2><?php echo htmlspecialchars($producto['nombre']); ?></h2>
                            <div class="product-sku">📋 SKU: <?php echo htmlspecialchars($producto['sku']); ?></div>
                            <div style="margin-top: 10px;">
                                <span style="background: <?php echo $producto['destacado'] ? '#4caf50' : '#9e9e9e'; ?>; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px;">
                                    <?php echo $producto['destacado'] ? '⭐ PRODUCTO DESTACADO' : 'Producto regular'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- INFORMACIÓN TÉCNICA DETALLADA -->
                    <div class="tech-section">
                        <div class="section-title">
                            <span>🔧</span> ESPECIFICACIONES TÉCNICAS
                        </div>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ID del Producto</span>
                                <span class="info-value">#<?php echo $producto['id']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Categoría</span>
                                <span class="info-value">🏷️ <?php echo htmlspecialchars($producto['categoria']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Stock Actual</span>
                                <span class="info-value">📦 <?php echo $producto['stock']; ?> unidades</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Precio Unitario</span>
                                <span class="info-value">💰 <?php echo htmlspecialchars($producto['currency']); ?> <?php echo number_format($producto['precio'], 2, ',', '.'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Valor del Inventario</span>
                                <span class="info-value">💵 <?php echo htmlspecialchars($producto['currency']); ?> <?php echo number_format($valor_inventario, 2, ',', '.'); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Visitas</span>
                                <span class="info-value">👁️ <?php echo $producto['visitas']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Calificación</span>
                                <span class="info-value">⭐ <?php echo $producto['rating']; ?>/5</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Peso</span>
                                <span class="info-value">⚖️ <?php echo $producto['weight'] ? $producto['weight'] . ' kg' : 'No especificado'; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Dimensiones</span>
                                <span class="info-value">📐 <?php echo $producto['dimensions'] ? htmlspecialchars($producto['dimensions']) : 'No especificado'; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- DESCRIPCIÓN -->
                    <div class="tech-section">
                        <div class="section-title">
                            <span>📝</span> DESCRIPCIÓN DEL PRODUCTO
                        </div>
                        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; line-height: 1.6; color: #555;">
                            <?php echo nl2br(htmlspecialchars($producto['descripcion'])); ?>
                        </div>
                    </div>
                    
                    <!-- ESPECIFICACIONES ADICIONALES -->
                    <?php if ($producto['specs']): ?>
                    <div class="tech-section">
                        <div class="section-title">
                            <span>⚙️</span> ESPECIFICACIONES ADICIONALES
                        </div>
                        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px; font-family: monospace; font-size: 13px; white-space: pre-wrap;">
                            <?php echo htmlspecialchars($producto['specs']); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- PANEL DERECHO - ESTADÍSTICAS -->
                <div class="right-panel">
                    <!-- ESTADÍSTICAS PRINCIPALES -->
                    <div class="stats-section">
                        <div class="section-title">
                            <span>📈</span> ESTADÍSTICAS DEL PRODUCTO
                        </div>
                        <div class="stats-grid">
                            <div class="stat-card ventas">
                                <h3>VENTAS TOTALES</h3>
                                <div class="stat-value"><?php echo $total_vendido; ?></div>
                                <div class="stat-unit">Unidades Vendidas</div>
                            </div>
                            
                            <div class="stat-card entrada">
                                <h3>ENTRADAS DE INVENTARIO</h3>
                                <div class="stat-value"><?php echo $entradas; ?></div>
                                <div class="stat-unit">Unidades Ingresadas</div>
                            </div>
                            
                            <div class="stat-card valor">
                                <h3>INGRESOS POR VENTAS</h3>
                                <div class="stat-value"><?php echo htmlspecialchars($producto['currency']); ?> <?php echo number_format($total_ventas, 0, ',', '.'); ?></div>
                                <div class="stat-unit">Total Facturado</div>
                            </div>
                            
                            <div class="stat-card inventario">
                                <h3>STOCK INICIAL</h3>
                                <div class="stat-value"><?php echo $stock_inicial; ?></div>
                                <div class="stat-unit">Unidades Estimadas</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- NIVEL DE STOCK -->
                    <div class="stock-level <?php echo $color_stock; ?>">
                        <div>
                            <h3 style="margin-bottom: 5px; font-size: 16px;">NIVEL DE STOCK ACTUAL</h3>
                            <p style="font-size: 14px; opacity: 0.9;">Stock: <?php echo $producto['stock']; ?> unidades | Valor: <?php echo htmlspecialchars($producto['currency']); ?> <?php echo number_format($valor_inventario, 2, ',', '.'); ?></p>
                        </div>
                        <div style="font-size: 20px; font-weight: bold;">
                            <?php echo $nivel_stock; ?>
                        </div>
                    </div>
                    
                    <!-- VENTAS RECIENTES -->
                    <?php if (!empty($ventas_recientes)): ?>
                    <div class="tech-section">
                        <div class="section-title">
                            <span>💰</span> VENTAS RECIENTES DEL PRODUCTO
                        </div>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Factura</th>
                                        <th>Cliente</th>
                                        <th>Cantidad</th>
                                        <th>Total</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_recientes as $venta): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($venta['numero_factura']); ?></td>
                                        <td><?php echo htmlspecialchars($venta['cliente']); ?></td>
                                        <td><?php echo $venta['cantidad']; ?> unid.</td>
                                        <td><?php echo htmlspecialchars($producto['currency']); ?> <?php echo number_format($venta['subtotal'], 2, ',', '.'); ?></td>
                                        <td><?php echo $venta['fecha_venta']; ?></td>
                                        <td>
                                            <span style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; 
                                                background: <?php echo $venta['estado'] == 'pagada' ? '#4caf50' : '#ff9800'; ?>; 
                                                color: white;">
                                                <?php echo strtoupper($venta['estado']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- PRODUCTOS SIMILARES -->
                    <?php if (!empty($productos_similares)): ?>
                    <div class="tech-section">
                        <div class="section-title">
                            <span>🔗</span> PRODUCTOS SIMILARES (MISMA CATEGORÍA)
                        </div>
                        <div class="similar-products">
                            <?php foreach ($productos_similares as $similar): 
                                $clase_stock = 'stock-ok';
                                if ($similar['estado_stock'] == 'Agotado') $clase_stock = 'stock-agotado';
                                elseif ($similar['estado_stock'] == 'Bajo Stock') $clase_stock = 'stock-bajo';
                                elseif ($similar['estado_stock'] == 'Stock Medio') $clase_stock = 'stock-medio';
                            ?>
                            <div class="similar-card">
                                <div class="similar-name"><?php echo htmlspecialchars($similar['name']); ?></div>
                                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">SKU: <?php echo htmlspecialchars($similar['sku']); ?></div>
                                <div style="font-size: 14px; font-weight: bold; color: #e74c3c;">
                                    <?php echo htmlspecialchars($producto['currency']); ?> <?php echo number_format($similar['price'], 2, ',', '.'); ?>
                                </div>
                                <div style="font-size: 11px; color: #666; margin: 5px 0;">⭐ <?php echo $similar['rating']; ?>/5</div>
                                <div class="similar-stock <?php echo $clase_stock; ?>">
                                    Stock: <?php echo $similar['stock']; ?> unid.
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- PIE DE PÁGINA -->
            <div class="footer">
                <div class="timestamp">
                    📅 Consulta generada: <?php echo date('d/m/Y H:i:s'); ?>
                </div>
                <div>
                    <button class="print-btn" onclick="window.print()">🖨️ Imprimir Informe</button>
                </div>
            </div>
            
        <?php endif; ?>
    </div>

    <script>
        // Funciones JavaScript adicionales
        function exportToPDF() {
            alert('Función de exportación a PDF en desarrollo');
            // Aquí iría la lógica para generar PDF
        }
        
        function sendReport() {
            const email = prompt('Ingrese el email para enviar el reporte:');
            if (email) {
                alert(`Reporte enviado a: ${email}`);
                // Aquí iría la lógica AJAX para enviar el reporte
            }
        }
        
        // Auto-refresh cada 5 minutos para datos en tiempo real
        setTimeout(() => {
            if (confirm('¿Desea actualizar la información del producto?')) {
                location.reload();
            }
        }, 300000); // 5 minutos
        
        console.log('Información técnica cargada para producto ID: <?php echo $id; ?>');
    </script>
</body>
</html>