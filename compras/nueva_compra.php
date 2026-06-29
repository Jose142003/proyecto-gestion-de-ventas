<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../interfaz_usuario/login.html');
    exit;
}
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
setcookie('lang', $locale, time()+31536000, '/');
\I18n::load($locale);
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale, ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <title>Nueva Compra - PIC</title>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(generarTokenCSRF()); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, user-scalable=yes">
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
            --primary: #050C18;
            --secondary: #294E90;
            --accent: #3C91ED;
            --light: #7EBDE9;
            --bg-color: #f0f2f5;
            --text-color: #333;
            --text-secondary: #666;
            --card-bg: #ffffff;
            --border: #dee2e6;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #3C91ED;
        }
        body.dark-mode {
            --primary: #0a0e1a;
            --secondary: #1a1f2e;
            --accent: #3C91ED;
            --light: #5aa9e6;
            --bg-color: #0f1219;
            --text-color: #e4e6eb;
            --text-secondary: #aaa;
            --card-bg: #1e2436;
            --border: #2c3348;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-color);
            min-height: 100vh;
            padding: 16px;
            color: var(--text-color);
        }
        
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: var(--card-bg); 
            border-radius: 20px; 
            padding: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        
        h1 { 
            color: var(--primary); 
            margin-bottom: 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 3px solid var(--accent);
            padding-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .form-group { 
            margin-bottom: 16px; 
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            color: var(--text-color);
            font-size: 0.9rem;
        }
        
        input, select, textarea { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid var(--border); 
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            -webkit-appearance: none;
            appearance: none;
            background: var(--card-bg);
            color: var(--text-color);
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(60, 145, 237, 0.1);
        }
        
        button { 
            background: linear-gradient(135deg, #3C91ED, #294E90);
            color: white; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 12px; 
            cursor: pointer; 
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            justify-content: center;
        }
        
        button:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(60, 145, 237, 0.3);
        }
        
        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary { 
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
        }
        
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 20px 0;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            min-width: 500px;
        }
        
        .table th, .table td { 
            border: 1px solid var(--border); 
            padding: 12px; 
            text-align: left; 
            vertical-align: middle;
        }
        
        .table th { 
            background: linear-gradient(135deg, #3C91ED, #294E90);
            color: white;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .table td {
            font-size: 0.85rem;
        }
        
        .table tr:hover td {
            background-color: rgba(60, 145, 237, 0.05);
        }
        
        .total { 
            text-align: right; 
            font-size: 1rem; 
            font-weight: bold; 
            margin-top: 20px;
            padding: 15px;
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        
        .total strong {
            color: var(--accent);
            font-size: 1.1rem;
        }
        
        .error { 
            color: #dc3545; 
            background: #ffe6e6; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            display: none;
            font-size: 0.9rem;
        }
        
        .success { 
            color: #28a745; 
            background: #d4edda; 
            padding: 12px; 
            border-radius: 10px; 
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            display: none;
            font-size: 0.9rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 25px;
        }
        
        .btn-group button {
            flex: 1;
            min-width: 120px;
        }
        
        .stock-info {
            font-size: 0.75rem;
            margin-top: 5px;
            padding: 5px;
            border-radius: 5px;
        }
        
        .stock-normal {
            color: #28a745;
            background: #d4edda;
        }
        
        .stock-bajo {
            color: #ffc107;
            background: #fff3cd;
        }
        
        .stock-critico {
            color: #dc3545;
            background: #f8d7da;
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--border);
            border-top: 3px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Estilos para inputs en móvil */
        input[type="number"] {
            -moz-appearance: textfield;
        }
        
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }
            
            .container {
                padding: 16px;
            }
            
            h1 {
                font-size: 1.3rem;
                margin-bottom: 16px;
            }
            
            h1 i {
                font-size: 1.2rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn-group button {
                width: 100%;
                justify-content: center;
            }
            
            .table th, .table td {
                padding: 10px 8px;
                font-size: 0.75rem;
            }
            
            .total {
                font-size: 0.9rem;
                padding: 12px;
            }
            
            .total strong {
                font-size: 1rem;
            }
            
            input, select, textarea, button {
                font-size: 16px; /* Evita zoom en iOS */
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 8px;
            }
            
            .container {
                padding: 12px;
                border-radius: 16px;
            }
            
            .table {
                min-width: 450px;
            }
            
            .table th, .table td {
                padding: 8px 6px;
                font-size: 0.7rem;
            }
        }
        
        @media (hover: hover) {
            button {
                width: auto;
            }
            
            .btn-group button {
                width: auto;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-shopping-cart"></i> Nueva Orden de Compra<button id="themeToggle" style="margin-left:auto; background:rgba(255,255,255,0.2); border:2px solid rgba(255,255,255,0.3); color:white; width:36px; height:36px; border-radius:50%; cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center;"><i class="fas fa-moon"></i></button></h1>
    
    <div id="errorMsg" class="error"></div>
    <div id="successMsg" class="success"></div>
    
    <form id="compraForm">
        <div class="form-group">
            <label><i class="fas fa-building"></i> Proveedor *</label>
            <select id="proveedor_id" required>
                <option value="">Cargando proveedores...</option>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label><i class="fas fa-box"></i> Producto</label>
                <select id="producto_id">
                    <option value="">Seleccione un producto</option>
                </select>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-sort-amount-up"></i> Cantidad</label>
                <input type="number" id="cantidad" value="1" min="1">
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Precio Unitario (Bs)</label>
                <input type="number" id="precio_unitario" step="0.01" placeholder="Precio de compra">
            </div>
            
            <div class="form-group" style="display: flex; align-items: flex-end;">
                <button type="button" id="agregarProductoBtn">
                    <i class="fas fa-plus"></i> Agregar
                </button>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table" id="productosTable">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th style="width: 100px">Cantidad</th>
                        <th style="width: 120px">Precio Unitario</th>
                        <th style="width: 100px">Subtotal</th>
                        <th style="width: 80px">Acciones</th>
                    </tr>
                </thead>
                <tbody id="productosBody">
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-secondary);">
                            <i class="fas fa-info-circle"></i> No hay productos agregados
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="total">
            <div>Subtotal: <span id="subtotal">0.00</span> Bs</div>
            <div>IVA (16%): <span id="iva">0.00</span> Bs</div>
            <div><strong>Total: <span id="total">0.00</span> Bs</strong></div>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-sticky-note"></i> Observaciones</label>
            <textarea id="observaciones" rows="3" placeholder="Observaciones adicionales..."></textarea>
        </div>
        
        <div class="btn-group">
            <button type="button" class="btn-secondary" onclick="window.location.href='../panel_admin/panel_admin.php'">
                <i class="fas fa-arrow-left"></i> Cancelar
            </button>
            <button type="button" id="limpiarBtn" class="btn-warning">
                <i class="fas fa-trash-alt"></i> Limpiar Todo
            </button>
            <button type="submit" id="guardarBtn">
                <i class="fas fa-save"></i> Guardar Compra
            </button>
        </div>
    </form>
</div>

<script>
    let productos = [];
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const originalFetch = window.fetch;
    window.fetch = function(input, init = {}) {
        if (init.method && init.method.toUpperCase() !== 'GET') {
            if (csrfToken) {
                init.headers = init.headers || {};
                if (init.headers instanceof Headers) {
                    if (!init.headers.has('X-CSRF-Token')) init.headers.set('X-CSRF-Token', csrfToken);
                } else if (Array.isArray(init.headers)) {
                    if (!init.headers.some(h => h[0].toLowerCase() === 'x-csrf-token')) init.headers.push(['X-CSRF-Token', csrfToken]);
                } else {
                    if (!init.headers['X-CSRF-Token']) init.headers['X-CSRF-Token'] = csrfToken;
                }
            }
        }
        return originalFetch.call(this, input, init);
    };
    
    function showError(message) {
        const errorDiv = document.getElementById('errorMsg');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        document.getElementById('successMsg').style.display = 'none';
        setTimeout(() => {
            errorDiv.style.display = 'none';
        }, 5000);
    }
    
    function showSuccess(message) {
        const successDiv = document.getElementById('successMsg');
        successDiv.textContent = message;
        successDiv.style.display = 'block';
        setTimeout(() => {
            successDiv.style.display = 'none';
        }, 3000);
    }
    
    // Cargar proveedores
    function cargarProveedores() {
        fetch('/proyecto/proveedores/obtener_proveedores.php', { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                console.log('Proveedores response:', data);
                const select = document.getElementById('proveedor_id');
                select.innerHTML = '<option value="">Seleccione un proveedor</option>';
                
                let proveedores = [];
                if (data.data && Array.isArray(data.data)) {
                    proveedores = data.data;
                } else if (Array.isArray(data)) {
                    proveedores = data;
                } else if (data.proveedores && Array.isArray(data.proveedores)) {
                    proveedores = data.proveedores;
                }
                
                if (proveedores.length > 0) {
                    proveedores.forEach(p => {
                        const nombre = p.nombre_comercial || p.nombre || p.razon_social || 'Sin nombre';
                        select.innerHTML += `<option value="${p.id}">${escapeHtml(nombre)}</option>`;
                    });
                } else {
                    select.innerHTML = '<option value="">No hay proveedores disponibles</option>';
                    showError('No se encontraron proveedores');
                }
            })
            .catch(error => {
                console.error('Error cargando proveedores:', error);
                document.getElementById('proveedor_id').innerHTML = '<option value="">Error al cargar proveedores</option>';
                showError('Error al cargar proveedores: ' + error.message);
            });
    }
    
    // Cargar productos
    function cargarProductos() {
        fetch('/proyecto/producto/obtener_productos.php', { credentials: 'include' })
            .then(r => r.json())
            .then(data => {
                console.log('Productos response:', data);
                const select = document.getElementById('producto_id');
                select.innerHTML = '<option value="">Seleccione un producto</option>';
                
                let productosAPI = [];
                if (data.data && Array.isArray(data.data)) {
                    productosAPI = data.data;
                } else if (Array.isArray(data)) {
                    productosAPI = data;
                } else if (data.productos && Array.isArray(data.productos)) {
                    productosAPI = data.productos;
                } else if (data.products && Array.isArray(data.products)) {
                    productosAPI = data.products;
                }
                
                if (productosAPI.length > 0) {
                    productosAPI.forEach(p => {
                        const precio = parseFloat(p.price || p.precio || 0);
                        const nombre = p.name || p.nombre || 'Sin nombre';
                        const stock = p.stock || 0;
                        select.innerHTML += `<option value="${p.id}" data-precio="${precio}" data-stock="${stock}" data-nombre="${escapeHtml(nombre)}">
                            ${escapeHtml(nombre)} - ${precio.toFixed(2)} Bs 
                        </option>`;
                    });
                } else {
                    select.innerHTML = '<option value="">No hay productos disponibles</option>';
                    showError('No se encontraron productos');
                }
            })
            .catch(error => {
                console.error('Error cargando productos:', error);
                document.getElementById('producto_id').innerHTML = '<option value="">Error al cargar productos</option>';
                showError('Error al cargar productos: ' + error.message);
            });
    }
    
    // Auto-completar precio al seleccionar producto
    document.getElementById('producto_id').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const precio = selected.getAttribute('data-precio');
        
        if (precio && parseFloat(precio) > 0) {
            document.getElementById('precio_unitario').value = precio;
        } else {
            document.getElementById('precio_unitario').value = '';
        }
    });
    
    // Validar cantidad
    document.getElementById('cantidad').addEventListener('change', function() {
        let cantidad = parseInt(this.value);
        if (isNaN(cantidad) || cantidad < 1) {
            this.value = 1;
            showError('La cantidad debe ser al menos 1');
        }
    });
    
    // Validar precio
    document.getElementById('precio_unitario').addEventListener('change', function() {
        let precio = parseFloat(this.value);
        if (isNaN(precio) || precio < 0) {
            this.value = 0;
            showError('El precio no puede ser negativo');
        }
    });
    
    // Agregar producto a la tabla
    document.getElementById('agregarProductoBtn').addEventListener('click', function() {
        const productoSelect = document.getElementById('producto_id');
        const productoId = productoSelect.value;
        const selectedOption = productoSelect.options[productoSelect.selectedIndex];
        const productoNombre = selectedOption.getAttribute('data-nombre') || selectedOption.text.split(' - ')[0];
        let cantidad = parseInt(document.getElementById('cantidad').value);
        let precio = parseFloat(document.getElementById('precio_unitario').value);
        
        // Validaciones
        if (!productoId) {
            showError('Seleccione un producto');
            return;
        }
        
        if (isNaN(cantidad) || cantidad <= 0) {
            showError('Ingrese una cantidad válida mayor a 0');
            document.getElementById('cantidad').value = 1;
            return;
        }
        
        if (isNaN(precio) || precio <= 0) {
            showError('Ingrese un precio unitario válido');
            return;
        }
        
        // Validar si el producto ya existe en la lista
        const existe = productos.find(p => p.producto_id === productoId);
        if (existe) {
            showError('Este producto ya ha sido agregado. Si necesita más cantidad, modifique la cantidad existente o elimine el producto y vuelva a agregarlo.');
            return;
        }
        
        // Agregar producto
        productos.push({
            producto_id: parseInt(productoId),
            nombre: productoNombre,
            cantidad: cantidad,
            precio_unitario: precio,
            subtotal: cantidad * precio
        });
        
        actualizarTabla();
        calcularTotales();
        
        // Limpiar campos
        productoSelect.value = '';
        document.getElementById('cantidad').value = '1';
        document.getElementById('precio_unitario').value = '';
        
        showSuccess(`Producto "${productoNombre}" agregado correctamente`);
    });
    
    function actualizarTabla() {
        const tbody = document.getElementById('productosBody');
        
        if (productos.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; color: var(--text-secondary);">
                        <i class="fas fa-info-circle"></i> No hay productos agregados
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = '';
        productos.forEach((p, index) => {
            tbody.innerHTML += `
                <tr>
                    <td>${escapeHtml(p.nombre)}</td>
                    <td>
                        <input type="number" value="${p.cantidad}" min="1" 
                               onchange="actualizarCantidad(${index}, this.value)" 
                               style="width:70px; padding:6px; border-radius:8px; border:1px solid var(--border); text-align:center; font-size:0.85rem;">
                    </td>
                    <td>
                        <input type="number" step="0.01" value="${p.precio_unitario.toFixed(2)}" 
                               onchange="actualizarPrecio(${index}, this.value)" 
                               style="width:100px; padding:6px; border-radius:8px; border:1px solid var(--border); text-align:right; font-size:0.85rem;">
                    </td>
                    <td style="text-align:right; font-weight:bold;">${p.subtotal.toFixed(2)} Bs</td>
                    <td>
                        <button type="button" onclick="eliminarProducto(${index})" class="btn-danger" style="padding:6px 12px; border-radius:8px; width:auto;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    }
    
    function actualizarCantidad(index, nuevaCantidad) {
        const cantidad = parseInt(nuevaCantidad);
        if (!isNaN(cantidad) && cantidad > 0) {
            productos[index].cantidad = cantidad;
            productos[index].subtotal = productos[index].cantidad * productos[index].precio_unitario;
            actualizarTabla();
            calcularTotales();
        } else {
            showError('La cantidad debe ser mayor a 0');
            actualizarTabla();
        }
    }
    
    function actualizarPrecio(index, nuevoPrecio) {
        const precio = parseFloat(nuevoPrecio);
        if (!isNaN(precio) && precio >= 0) {
            productos[index].precio_unitario = precio;
            productos[index].subtotal = productos[index].cantidad * productos[index].precio_unitario;
            actualizarTabla();
            calcularTotales();
        } else {
            showError('El precio debe ser un número válido');
            actualizarTabla();
        }
    }
    
    function eliminarProducto(index) {
        const productoEliminado = productos[index];
        productos.splice(index, 1);
        actualizarTabla();
        calcularTotales();
        showSuccess(`Producto "${productoEliminado.nombre}" eliminado`);
    }
    
    function calcularTotales() {
        const subtotal = productos.reduce((sum, p) => sum + p.subtotal, 0);
        const iva = subtotal * 0.16;
        const total = subtotal + iva;
        
        document.getElementById('subtotal').innerHTML = subtotal.toFixed(2);
        document.getElementById('iva').innerHTML = iva.toFixed(2);
        document.getElementById('total').innerHTML = total.toFixed(2);
    }
    
    // Limpiar todo
    document.getElementById('limpiarBtn').addEventListener('click', function() {
        if (productos.length > 0 && confirm('¿Está seguro de que desea eliminar todos los productos?')) {
            productos = [];
            actualizarTabla();
            calcularTotales();
            showSuccess('Lista de productos limpiada');
        } else if (productos.length === 0) {
            showError('No hay productos para limpiar');
        }
    });
    
    function escapeHtml(str) {
        if (!str) return '';
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    
    // Guardar compra
    document.getElementById('compraForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (productos.length === 0) {
            showError('Agregue al menos un producto');
            return;
        }
        
        const proveedorId = document.getElementById('proveedor_id').value;
        if (!proveedorId) {
            showError('Seleccione un proveedor');
            return;
        }
        
        const submitBtn = document.getElementById('guardarBtn');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="loading"></span> Guardando...';
        
        const data = {
            proveedor_id: parseInt(proveedorId),
            productos: productos,
            observaciones: document.getElementById('observaciones').value,
            subtotal: parseFloat(document.getElementById('subtotal').innerHTML),
            iva: parseFloat(document.getElementById('iva').innerHTML),
            total: parseFloat(document.getElementById('total').innerHTML)
        };
        
        try {
            const response = await fetch('/proyecto/compras/guardar_compra.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                showSuccess('✓ Compra guardada correctamente. Stock actualizado.');
                setTimeout(() => {
                    window.location.href = '/proyecto/panel_admin/panel_admin.php';
                }, 1500);
            } else {
                showError('Error: ' + (result.message || 'Error desconocido'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Error:', error);
            showError('Error al guardar la compra: ' + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });
    
    // Inicializar
    cargarProveedores();
    cargarProductos();
</script>
<script>
(function() {
    const toggle = document.getElementById('themeToggle');
    function applyDark(isDark) {
        document.body.classList.toggle('dark-mode', isDark);
        if (toggle) {
            toggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        }
    }
    const saved = localStorage.getItem('darkMode');
    if (saved === 'enabled') applyDark(true);
    else if (saved === 'disabled') applyDark(false);
    if (toggle) {
        toggle.addEventListener('click', function() {
            const isDark = !document.body.classList.contains('dark-mode');
            localStorage.setItem('darkMode', isDark ? 'enabled' : 'disabled');
            applyDark(isDark);
        });
    }
})();
</script>
</body>
</html>