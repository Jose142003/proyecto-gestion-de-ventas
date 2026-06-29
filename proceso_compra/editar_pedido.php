<?php
// /proyecto/proceso_compra/editar_pedido.php
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pedido_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0);

if ($pedido_id <= 0) {
    die("<div class='error-message'>ID de pedido no válido</div>");
}

$pdo = conectarDB();

// Procesar actualización del pedido
$mensaje = '';
$mensaje_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_pedido') {
    try {
        $estado = $_POST['estado'] ?? 'pendiente';
        $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // Validar valores permitidos
        $estados_permitidos = ['pendiente', 'procesando', 'enviado', 'entregado', 'cancelado', 'facturado'];
        if (!in_array($estado, $estados_permitidos)) {
            $estado = 'pendiente';
        }
        
        $query = "UPDATE pedidos SET 
                    estado = :estado, 
                    metodo_pago = :metodo_pago, 
                    observaciones = :observaciones,
                    updated_at = NOW()
                  WHERE id = :pedido_id";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':estado' => $estado,
            ':metodo_pago' => $metodo_pago,
            ':observaciones' => $observaciones,
            ':pedido_id' => $pedido_id
        ]);
        
        $mensaje = 'Pedido actualizado correctamente';
        $mensaje_tipo = 'success';
    } catch (PDOException $e) {
        $mensaje = 'Error al actualizar: ' . $e->getMessage();
        $mensaje_tipo = 'error';
    }
}

// Obtener datos del pedido
try {
    $query_pedido = "
        SELECT 
            p.*,
            COALESCE(u.nombre, CONCAT('Cliente #', p.usuario_id), 'Cliente') as cliente_nombre,
            u.correo as cliente_email,
            u.telefono as cliente_telefono,
            DATE_FORMAT(p.created_at, '%d/%m/%Y %H:%i') as fecha_formateada,
            DATE_FORMAT(p.created_at, '%Y-%m-%dT%H:%i') as fecha_iso,
            (SELECT COUNT(*) FROM facturas WHERE facturas.pedido_id = p.id) as tiene_factura
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE p.id = :pedido_id
    ";
    
    $stmt = $pdo->prepare($query_pedido);
    $stmt->execute([':pedido_id' => $pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        die("<div class='error-message'>Pedido no encontrado</div>");
    }
    
    // Obtener productos del pedido
    $query_productos = "
        SELECT 
            pd.id,
            pd.producto_id,
            pd.cantidad,
            pd.precio_unitario,
            pd.subtotal,
            COALESCE(p.name, pd.producto_nombre, 'Producto') as nombre,
            p.sku,
            p.stock as stock_actual
        FROM pedido_detalles pd
        LEFT JOIN products p ON pd.producto_id = p.id
        WHERE pd.pedido_id = :pedido_id
        ORDER BY pd.id ASC
    ";
    
    $stmt_productos = $pdo->prepare($query_productos);
    $stmt_productos->execute([':pedido_id' => $pedido_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear valores
    $pedido['total'] = floatval($pedido['total'] ?? 0);
    $pedido['subtotal'] = floatval($pedido['subtotal'] ?? 0);
    $pedido['iva'] = floatval($pedido['iva'] ?? 0);
    
    foreach ($productos as &$prod) {
        $prod['precio_unitario'] = floatval($prod['precio_unitario'] ?? 0);
        $prod['subtotal'] = floatval($prod['subtotal'] ?? 0);
    }
    
} catch (PDOException $e) {
    die("<div class='error-message'>Error de base de datos: " . htmlspecialchars($e->getMessage()) . "</div>");
}

// Estados disponibles
$estados_disponibles = [
    'pendiente' => 'Pendiente',
    'procesando' => 'Procesando',
    'enviado' => 'Enviado',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado',
    'facturado' => 'Facturado'
];

// Métodos de pago
$metodos_pago = [
    'efectivo' => 'Efectivo',
    'tarjeta_credito' => 'Tarjeta de Crédito',
    'tarjeta_debito' => 'Tarjeta de Débito',
    'transferencia' => 'Transferencia Bancaria',
    'paypal' => 'PayPal',
    'mercadopago' => 'Mercado Pago'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Editar Pedido #<?php echo htmlspecialchars($pedido_id ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            border-radius: 20px;
            padding: 20px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            color: #333;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header h1::before {
            content: "📝";
            font-size: 1.5rem;
        }

        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
        }

        .card-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
            display: inline-block;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.9rem;
        }

        select, textarea, input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: inherit;
        }

        select:focus, textarea:focus, input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Product Table */
        .product-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        tr:hover {
            background: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-pendiente { background: #ffc107; color: #856404; }
        .status-procesando { background: #17a2b8; color: white; }
        .status-enviado { background: #007bff; color: white; }
        .status-entregado { background: #28a745; color: white; }
        .status-cancelado { background: #dc3545; color: white; }
        .status-facturado { background: #6f42c1; color: white; }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
        }

        .info-label {
            font-size: 0.8rem;
            color: #777;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }

        /* Totals */
        .totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }

        .total-row.grand-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #667eea;
            border-top: 2px solid #e0e0e0;
            margin-top: 10px;
            padding-top: 15px;
        }

        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 30px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }

        /* Full Width */
        .full-width {
            grid-column: 1 / -1;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .header {
                padding: 15px 20px;
            }

            .header h1 {
                font-size: 1.3rem;
            }

            .grid-2 {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .card {
                padding: 20px;
            }

            .card-title {
                font-size: 1.2rem;
            }

            th, td {
                padding: 8px;
                font-size: 0.85rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .btn-back {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            th, td {
                font-size: 0.75rem;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 2px 8px;
            }

            .form-group select,
            .form-group textarea {
                font-size: 0.9rem;
            }
        }

        /* Loading State */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .header,
            .btn-submit,
            .alert {
                display: none;
            }
            .card {
                box-shadow: none;
                break-inside: avoid;
            }
        }
    
        :root {
            --primary-color: #050C18;
            --secondary-color: #294E90;
            --accent-color: #3C91ED;
            --bg-color: #f0f2f5;
            --card-bg: #fff;
            --text-color: #333;
            --text-secondary: #555;
            --text-muted: #666;
            --border-color: #ddd;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --error-color: #dc3545;
        }
        body.dark-mode {
            --primary-color: #0a0e1a;
            --secondary-color: #1a1f2e;
            --accent-color: #5aa9e6;
            --bg-color: #0f1219;
            --card-bg: #1e2436;
            --text-color: #e4e6eb;
            --text-secondary: #b0b3b8;
            --text-muted: #999;
            --border-color: #2c3348;
        }
        body.dark-mode { background: var(--bg-color); color: var(--text-color); }

    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Editar Pedido #<?php echo htmlspecialchars($pedido_id ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
            <a href="javascript:history.back()" class="btn-back">
                ← Volver
            </a>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?php echo htmlspecialchars($mensaje_tipo ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($mensaje ?? '', ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="editPedidoForm">
            <input type="hidden" name="action" value="update_pedido">
            <input type="hidden" name="pedido_id" value="<?php echo htmlspecialchars($pedido_id ?? '', ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="grid-2">
                <!-- Información del Pedido -->
                <div class="card">
                    <h2 class="card-title">Información del Pedido</h2>
                    
                    <div class="form-group">
                        <label for="estado">Estado del Pedido</label>
                        <select name="estado" id="estado">
                            <?php foreach ($estados_disponibles as $key => $value): ?>
                                <option value="<?php echo htmlspecialchars($key ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo $pedido['estado'] == $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="metodo_pago">Método de Pago</label>
                        <select name="metodo_pago" id="metodo_pago">
                            <?php foreach ($metodos_pago as $key => $value): ?>
                                <option value="<?php echo htmlspecialchars($key ?? '', ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($pedido['metodo_pago'] ?? 'efectivo') == $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observaciones">Observaciones</label>
                        <textarea name="observaciones" id="observaciones" placeholder="Notas adicionales sobre el pedido..."><?php echo htmlspecialchars($pedido['observaciones'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        💾 Actualizar Pedido
                    </button>
                </div>

                <!-- Información del Cliente -->
                <div class="card">
                    <h2 class="card-title">Información del Cliente</h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Nombre</div>
                            <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Correo Electrónico</div>
                            <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_email'] ?? 'No registrado'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Teléfono</div>
                            <div class="info-value"><?php echo htmlspecialchars($pedido['cliente_telefono'] ?? 'No registrado'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Fecha del Pedido</div>
                            <div class="info-value"><?php echo $pedido['fecha_formateada']; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Factura Asociada</div>
                            <div class="info-value">
                                <?php echo $pedido['tiene_factura'] > 0 ? '✓ Sí' : '✗ No'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Productos del Pedido -->
            <div class="card full-width" style="margin-top: 30px;">
                <h2 class="card-title">Productos del Pedido</h2>
                
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>SKU</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                                <th>Stock Actual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">No hay productos en este pedido</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($producto['nombre']); ?></strong>
                                            <?php if ($producto['producto_id']): ?>
                                                <br><small>ID: <?php echo $producto['producto_id']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($producto['sku'] ?? 'N/A'); ?></td>
                                        <td><?php echo $producto['cantidad']; ?></td>
                                        <td>$<?php echo number_format($producto['precio_unitario'], 2); ?></td>
                                        <td>$<?php echo number_format($producto['subtotal'], 2); ?></td>
                                        <td>
                                            <?php echo isset($producto['stock_actual']) ? $producto['stock_actual'] : 'N/A'; ?>
                                            <?php if (isset($producto['stock_actual']) && $producto['stock_actual'] < $producto['cantidad']): ?>
                                                <span style="color: #dc3545; font-size: 0.75rem;">⚠️ Stock insuficiente</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="totals">
                    <div class="total-row">
                        <span>Subtotal:</span>
                        <strong>$<?php echo number_format($pedido['subtotal'], 2); ?></strong>
                    </div>
                    <?php if ($pedido['iva'] > 0): ?>
                        <div class="total-row">
                            <span>IVA (21%):</span>
                            <strong>$<?php echo number_format($pedido['iva'], 2); ?></strong>
                        </div>
                    <?php endif; ?>
                    <div class="total-row grand-total">
                        <span>Total:</span>
                        <strong>$<?php echo number_format($pedido['total'], 2); ?></strong>
                    </div>
                </div>
            </div>
        </form>
    </div>

    
        <script>
        (function() {
            var saved = localStorage.getItem('darkMode');
            if (saved === 'enabled') {
                document.body.classList.add('dark-mode');
            }
        })();
        </script>
    <script>
        // Prevenir envío doble del formulario
        document.getElementById('editPedidoForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-submit');
            if (submitBtn.classList.contains('loading')) {
                e.preventDefault();
                return;
            }
            submitBtn.classList.add('loading');
            submitBtn.textContent = '⏳ Actualizando...';
            
            // Re-habilitar después de 3 segundos en caso de error
            setTimeout(() => {
                if (submitBtn.classList.contains('loading')) {
                    submitBtn.classList.remove('loading');
                    submitBtn.textContent = '💾 Actualizar Pedido';
                }
            }, 3000);
        });

        // Confirmar cambio de estado crítico
        const estadoSelect = document.getElementById('estado');
        if (estadoSelect) {
            estadoSelect.addEventListener('change', function() {
                if (this.value === 'cancelado') {
                    if (!confirm('⚠️ ¿Estás seguro de cancelar este pedido? Esta acción no se puede deshacer.')) {
                        this.value = '<?php echo $pedido['estado']; ?>';
                    }
                }
            });
        }
    </script>
</body>
</html>