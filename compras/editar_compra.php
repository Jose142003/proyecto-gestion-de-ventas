<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../interfaz usuario/login.html');
    exit;
}

require_once dirname(__DIR__) . '/conexion/conexion.php';

$database = new Database();
$db = $database->getConnection();

$compraId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($compraId == 0) {
    header('Location: ver_compra.php');
    exit;
}

// Obtener datos de la compra
$query = "SELECT c.*, p.nombre_comercial as proveedor_nombre 
          FROM compras c 
          LEFT JOIN proveedores p ON c.proveedor_id = p.id 
          WHERE c.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $compraId, PDO::PARAM_INT);
$stmt->execute();
$compra = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$compra) {
    header('Location: ver_compra.php');
    exit;
}

// Obtener productos de la compra
$queryProductos = "SELECT cd.*, pr.name as producto_nombre, pr.sku 
                   FROM compra_detalles cd
                   LEFT JOIN products pr ON cd.producto_id = pr.id
                   WHERE cd.compra_id = :compra_id
                   ORDER BY cd.id ASC";
$stmtProductos = $db->prepare($queryProductos);
$stmtProductos->bindParam(':compra_id', $compraId, PDO::PARAM_INT);
$stmtProductos->execute();
$productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de proveedores para el select
$queryProveedores = "SELECT id, nombre_comercial, ruc FROM proveedores WHERE estado = 'activo' ORDER BY nombre_comercial";
$stmtProveedores = $db->prepare($queryProveedores);
$stmtProveedores->execute();
$proveedores = $stmtProveedores->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de productos para el buscador
$queryProductosLista = "SELECT id, name, sku, price, stock FROM products ORDER BY name";
$stmtProductosLista = $db->prepare($queryProductosLista);
$stmtProductosLista->execute();
$productosLista = $stmtProductosLista->fetchAll(PDO::FETCH_ASSOC);

// Estados posibles
$estados = [
    'cotizacion' => 'Cotización',
    'aprobada' => 'Aprobada',
    'enviada' => 'Enviada',
    'recibida_parcial' => 'Recibida Parcial',
    'recibida_total' => 'Recibida Total',
    'anulada' => 'Anulada'
];

// Métodos de pago
$metodosPago = [
    'transferencia' => 'Transferencia',
    'efectivo' => 'Efectivo',
    'cheque' => 'Cheque',
    'credito' => 'Crédito'
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar Orden de Compra - PIC</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #050C18 0%, #0a1a2e 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: #ffc107;
        }

        .btn-volver {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-volver:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .btn-guardar {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 10px;
        }

        .btn-guardar:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        /* Formulario */
        .form-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group label i {
            margin-right: 8px;
            color: #3C91ED;
        }

        .form-group input, .form-group select, .form-group textarea {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3C91ED;
            box-shadow: 0 0 0 3px rgba(60,145,237,0.1);
        }

        .form-group input[readonly] {
            background: #f8f9fa;
            cursor: not-allowed;
        }

        /* Tabla de productos */
        .productos-section {
            margin-top: 25px;
        }

        .productos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .productos-header h3 {
            color: #333;
            font-size: 18px;
        }

        .btn-agregar {
            background: #3C91ED;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-agregar:hover {
            background: #294E90;
        }

        .btn-eliminar {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
        }

        .btn-eliminar:hover {
            background: #c82333;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
            color: #555;
        }

        .producto-select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .cantidad-input {
            width: 80px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .precio-input {
            width: 100px;
            padding: 8px;
            text-align: right;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        /* Totales */
        .totales {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            text-align: right;
        }

        .totales p {
            margin: 5px 0;
            font-size: 16px;
        }

        .totales .total-final {
            font-size: 24px;
            font-weight: bold;
            color: #050C18;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #dee2e6;
        }

        /* Alertas */
        .alert {
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        /* Modal para buscar producto */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #050C18 0%, #0a1a2e 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
        }

        .buscar-producto {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .lista-productos {
            max-height: 400px;
            overflow-y: auto;
        }

        .producto-item {
            padding: 10px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .producto-item:hover {
            background: #f8f9fa;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            font-weight: 600;
            color: #333;
        }

        .producto-detalle {
            font-size: 12px;
            color: #666;
        }

        .producto-precio {
            font-weight: bold;
            color: #28a745;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .productos-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            th, td {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>
            <i class="fas fa-edit"></i>
            Editar Orden de Compra
        </h1>
        <div>
            <a href="ver_compra.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <div id="alertMessage" class="alert"></div>

    <form id="formEditarCompra">
        <input type="hidden" name="compra_id" value="<?php echo $compraId; ?>">
        
        <div class="form-card">
            <div class="form-grid">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Número de Orden</label>
                    <input type="text" value="<?php echo htmlspecialchars($compra['numero_orden']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-building"></i> Proveedor *</label>
                    <select name="proveedor_id" id="proveedor_id" required>
                        <option value="">Seleccione un proveedor</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?php echo $proveedor['id']; ?>" 
                                <?php echo ($compra['proveedor_id'] == $proveedor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proveedor['nombre_comercial']); ?> 
                                (<?php echo htmlspecialchars($proveedor['ruc']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar"></i> Fecha de Orden *</label>
                    <input type="date" name="fecha_orden" id="fecha_orden" 
                           value="<?php echo $compra['fecha_orden']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-calendar-check"></i> Fecha Requerida</label>
                    <input type="date" name="fecha_requerida" id="fecha_requerida" 
                           value="<?php echo $compra['fecha_requerida']; ?>">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-tag"></i> Estado *</label>
                    <select name="estado" id="estado" required>
                        <?php foreach ($estados as $key => $label): ?>
                            <option value="<?php echo $key; ?>" 
                                <?php echo ($compra['estado'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-credit-card"></i> Método de Pago *</label>
                    <select name="metodo_pago" id="metodo_pago" required>
                        <?php foreach ($metodosPago as $key => $label): ?>
                            <option value="<?php echo $key; ?>" 
                                <?php echo ($compra['metodo_pago'] == $key) ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-file-invoice"></i> Condiciones de Pago</label>
                    <input type="text" name="condiciones_pago" id="condiciones_pago" 
                           value="<?php echo htmlspecialchars($compra['condiciones_pago'] ?? ''); ?>"
                           placeholder="Ej: 30 días, Contado, etc.">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-comment"></i> Observaciones</label>
                    <textarea name="observaciones" id="observaciones" rows="2" 
                              placeholder="Observaciones adicionales..."><?php echo htmlspecialchars($compra['observaciones'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="productos-section">
            <div class="productos-header">
                <h3><i class="fas fa-boxes"></i> Productos</h3>
                <button type="button" class="btn-agregar" onclick="abrirModalProductos()">
                    <i class="fas fa-plus"></i> Agregar Producto
                </button>
            </div>
            
            <div class="table-container">
                <table id="tablaProductos">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyProductos">
                        <?php foreach ($productos as $producto): ?>
                        <tr data-id="<?php echo $producto['id']; ?>" 
                            data-producto-id="<?php echo $producto['producto_id']; ?>">
                            <td>
                                <input type="hidden" name="producto_id[]" value="<?php echo $producto['producto_id']; ?>">
                                <span class="producto-nombre-txt"><?php echo htmlspecialchars($producto['producto_nombre']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($producto['sku'] ?? '-'); ?></td>
                            <td>
                                <input type="number" name="cantidad[]" class="cantidad-input" 
                                       value="<?php echo $producto['cantidad']; ?>" step="1" min="1" 
                                       onchange="calcularFila(this)">
                            </td>
                            <td>
                                <input type="number" name="precio_unitario[]" class="precio-input" 
                                       value="<?php echo $producto['precio_unitario']; ?>" step="0.01" min="0" 
                                       onchange="calcularFila(this)">
                            </td>
                            <td class="subtotal-td"><?php echo number_format($producto['subtotal'], 2); ?></td>
                            <td>
                                <button type="button" class="btn-eliminar" onclick="eliminarFila(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="totales">
                <p>Subtotal: <strong id="subtotal"><?php echo number_format($compra['subtotal'], 2); ?></strong> Bs</p>
                <p>IVA (16%): <strong id="iva"><?php echo number_format($compra['iva'], 2); ?></strong> Bs</p>
                <p class="total-final">Total: <strong id="total"><?php echo number_format($compra['total'], 2); ?></strong> Bs</p>
            </div>
        </div>
        
        <div style="text-align: right; margin-top: 20px;">
            <button type="button" class="btn-guardar" onclick="guardarCompra()">
                <i class="fas fa-save"></i> Guardar Cambios
            </button>
            <a href="ver_compra.php" class="btn-volver">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>

<!-- Modal para seleccionar producto -->
<div id="modalProductos" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-search"></i> Buscar Producto</h2>
            <button class="modal-close" onclick="cerrarModalProductos()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="text" id="buscarProductoInput" class="buscar-producto" 
                   placeholder="Buscar por nombre o SKU..." onkeyup="filtrarProductos()">
            <div class="lista-productos" id="listaProductos">
                <?php foreach ($productosLista as $producto): ?>
                    <div class="producto-item" onclick="seleccionarProducto(<?php echo htmlspecialchars(json_encode($producto)); ?>)">
                        <div class="producto-info">
                            <div class="producto-nombre"><?php echo htmlspecialchars($producto['name']); ?></div>
                            <div class="producto-detalle">SKU: <?php echo htmlspecialchars($producto['sku']); ?> | Stock: <?php echo $producto['stock']; ?></div>
                        </div>
                        <div class="producto-precio">Bs <?php echo number_format($producto['price'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    let productosSeleccionados = [];
    
    // Calcular totales al cargar
    document.addEventListener('DOMContentLoaded', function() {
        calcularTotales();
    });
    
    function calcularFila(input) {
        const row = input.closest('tr');
        const cantidad = parseFloat(row.querySelector('.cantidad-input').value) || 0;
        const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
        const subtotal = cantidad * precio;
        row.querySelector('.subtotal-td').textContent = subtotal.toFixed(2);
        calcularTotales();
    }
    
    function calcularTotales() {
        let subtotal = 0;
        document.querySelectorAll('#tbodyProductos tr').forEach(row => {
            const subtotalTd = row.querySelector('.subtotal-td');
            if (subtotalTd) {
                subtotal += parseFloat(subtotalTd.textContent) || 0;
            }
        });
        
        const iva = subtotal * 0.16;
        const total = subtotal + iva;
        
        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('iva').textContent = iva.toFixed(2);
        document.getElementById('total').textContent = total.toFixed(2);
    }
    
    function eliminarFila(btn) {
        if (confirm('¿Está seguro de eliminar este producto?')) {
            const row = btn.closest('tr');
            row.remove();
            calcularTotales();
        }
    }
    
    function abrirModalProductos() {
        document.getElementById('modalProductos').style.display = 'flex';
        document.getElementById('buscarProductoInput').value = '';
        filtrarProductos();
    }
    
    function cerrarModalProductos() {
        document.getElementById('modalProductos').style.display = 'none';
    }
    
    function filtrarProductos() {
        const searchTerm = document.getElementById('buscarProductoInput').value.toLowerCase();
        const items = document.querySelectorAll('.producto-item');
        
        items.forEach(item => {
            const nombre = item.querySelector('.producto-nombre').textContent.toLowerCase();
            const sku = item.querySelector('.producto-detalle').textContent.toLowerCase();
            if (nombre.includes(searchTerm) || sku.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    function seleccionarProducto(producto) {
        // Verificar si el producto ya está agregado
        const productosExistentes = document.querySelectorAll('#tbodyProductos tr');
        let existe = false;
        productosExistentes.forEach(row => {
            const productoId = row.getAttribute('data-producto-id');
            if (productoId == producto.id) {
                existe = true;
            }
        });
        
        if (existe) {
            mostrarAlerta('Este producto ya está agregado a la orden', 'warning');
            cerrarModalProductos();
            return;
        }
        
        const nuevaFila = `
            <tr data-producto-id="${producto.id}">
                <td>
                    <input type="hidden" name="producto_id[]" value="${producto.id}">
                    <span class="producto-nombre-txt">${escapeHtml(producto.name)}</span>
                </td>
                <td>${escapeHtml(producto.sku || '-')}</td>
                <td>
                    <input type="number" name="cantidad[]" class="cantidad-input" 
                           value="1" step="1" min="1" onchange="calcularFila(this)">
                </td>
                <td>
                    <input type="number" name="precio_unitario[]" class="precio-input" 
                           value="${producto.price}" step="0.01" min="0" onchange="calcularFila(this)">
                </td>
                <td class="subtotal-td">${producto.price}</td>
                <td>
                    <button type="button" class="btn-eliminar" onclick="eliminarFila(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        document.getElementById('tbodyProductos').insertAdjacentHTML('beforeend', nuevaFila);
        calcularTotales();
        cerrarModalProductos();
    }
    
    function mostrarAlerta(mensaje, tipo) {
        const alertDiv = document.getElementById('alertMessage');
        alertDiv.className = `alert alert-${tipo}`;
        alertDiv.innerHTML = `<i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'times-circle'}"></i> ${mensaje}`;
        alertDiv.style.display = 'block';
        
        setTimeout(() => {
            alertDiv.style.display = 'none';
        }, 3000);
    }
    
    async function guardarCompra() {
        const proveedorId = document.getElementById('proveedor_id').value;
        if (!proveedorId) {
            mostrarAlerta('Por favor seleccione un proveedor', 'danger');
            return;
        }
        
        const productos = document.querySelectorAll('#tbodyProductos tr');
        if (productos.length === 0) {
            mostrarAlerta('Debe agregar al menos un producto', 'danger');
            return;
        }
        
        const formData = new FormData();
        formData.append('compra_id', document.querySelector('input[name="compra_id"]').value);
        formData.append('proveedor_id', proveedorId);
        formData.append('fecha_orden', document.getElementById('fecha_orden').value);
        formData.append('fecha_requerida', document.getElementById('fecha_requerida').value);
        formData.append('estado', document.getElementById('estado').value);
        formData.append('metodo_pago', document.getElementById('metodo_pago').value);
        formData.append('condiciones_pago', document.getElementById('condiciones_pago').value);
        formData.append('observaciones', document.getElementById('observaciones').value);
        formData.append('subtotal', document.getElementById('subtotal').textContent);
        formData.append('iva', document.getElementById('iva').textContent);
        formData.append('total', document.getElementById('total').textContent);
        
        // Agregar productos
        const productoIds = document.querySelectorAll('input[name="producto_id[]"]');
        const cantidades = document.querySelectorAll('input[name="cantidad[]"]');
        const precios = document.querySelectorAll('input[name="precio_unitario[]"]');
        
        for (let i = 0; i < productoIds.length; i++) {
            formData.append(`productos[${i}][producto_id]`, productoIds[i].value);
            formData.append(`productos[${i}][cantidad]`, cantidades[i].value);
            formData.append(`productos[${i}][precio_unitario]`, precios[i].value);
        }
        
        try {
            const response = await fetch('actualizar_compra.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                mostrarAlerta(data.message, 'success');
                setTimeout(() => {
                    window.location.href = 'ver_compra.php';
                }, 1500);
            } else {
                mostrarAlerta(data.message, 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarAlerta('Error de conexión: ' + error.message, 'danger');
        }
    }
    
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    
    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modal = document.getElementById('modalProductos');
        if (event.target === modal) {
            cerrarModalProductos();
        }
    };
</script>
</body>
</html>