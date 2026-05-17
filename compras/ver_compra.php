<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../usuario/login.html');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ver Compras - PIC</title>
    <meta charset="UTF-8">
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f0f2f5;
            padding: 16px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, #050C18 0%, #0a1a2e 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header h1 i {
            color: #3C91ED;
            font-size: 1.3rem;
        }
        
        .header-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-volver, .btn-nueva {
            background: #3C91ED;
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-nueva {
            background: #28a745;
        }
        
        .btn-volver:hover, .btn-nueva:hover {
            transform: translateY(-2px);
        }
        
        /* Filtros */
        .filtros-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .filtro-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filtro-group label {
            font-weight: 600;
            color: #333;
            font-size: 0.8rem;
        }
        
        .filtro-group input, .filtro-group select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        
        .filtro-group input:focus, .filtro-group select:focus {
            outline: none;
            border-color: #3C91ED;
            box-shadow: 0 0 0 3px rgba(60,145,237,0.1);
        }
        
        .filtro-buttons {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .btn-buscar, .btn-limpiar {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        
        .btn-buscar {
            background: #3C91ED;
            color: white;
        }
        
        .btn-limpiar {
            background: #6c757d;
            color: white;
        }
        
        .btn-buscar:hover, .btn-limpiar:hover {
            transform: translateY(-2px);
        }
        
        /* Tabla */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            -webkit-overflow-scrolling: touch;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
            cursor: pointer;
            user-select: none;
            font-size: 0.8rem;
        }
        
        th:hover {
            background: #e9ecef;
        }
        
        th i {
            margin-left: 5px;
            font-size: 10px;
            color: #6c757d;
        }
        
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
            color: #555;
            font-size: 0.8rem;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        /* Estados */
        .estado-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-align: center;
            white-space: nowrap;
        }
        
        .estado-cotizacion { background: #e7e7e7; color: #666; }
        .estado-aprobada { background: #d4edda; color: #155724; }
        .estado-enviada { background: #fff3cd; color: #856404; }
        .estado-recibida_parcial { background: #d1ecf1; color: #0c5460; }
        .estado-recibida_total { background: #d4edda; color: #155724; }
        .estado-anulada { background: #f8d7da; color: #721c24; }
        
        /* Botones de acción */
        .acciones-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-accion {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 5px 8px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .btn-ver {
            color: #3C91ED;
        }
        
        .btn-ver:hover {
            background: #e3f2fd;
        }
        
        .btn-editar {
            color: #ffc107;
        }
        
        .btn-editar:hover {
            background: #fff3cd;
        }
        
        /* Modal */
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
            padding: 16px;
        }
        
        .modal-content {
            background: white;
            border-radius: 16px;
            width: 100%;
            max-width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #050C18 0%, #0a1a2e 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 16px 16px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-header h2 {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0 10px;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .detalle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detalle-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detalle-item label {
            font-weight: 600;
            color: #666;
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        
        .detalle-item span {
            font-size: 0.9rem;
            color: #333;
            word-break: break-word;
        }
        
        .detalle-item .valor-grande {
            font-size: 1rem;
            font-weight: bold;
            color: #3C91ED;
        }
        
        .tabla-detalle {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .tabla-detalle th {
            background: #f8f9fa;
            padding: 8px;
            font-size: 0.75rem;
        }
        
        .tabla-detalle td {
            padding: 8px;
            font-size: 0.75rem;
        }
        
        .total-detalle {
            text-align: right;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #e9ecef;
        }
        
        .total-detalle p {
            margin: 5px 0;
            font-size: 0.85rem;
        }
        
        .total-detalle strong {
            font-size: 1rem;
            color: #050C18;
        }
        
        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            padding: 15px;
            flex-wrap: wrap;
        }
        
        .pagination button {
            padding: 6px 12px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.8rem;
        }
        
        .pagination button:hover {
            background: #3C91ED;
            color: white;
            border-color: #3C91ED;
        }
        
        .pagination button.active {
            background: #3C91ED;
            color: white;
            border-color: #3C91ED;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .loading i {
            font-size: 30px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .sin-datos {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .sin-datos i {
            font-size: 40px;
            margin-bottom: 10px;
            display: block;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }
            
            .header {
                padding: 12px 16px;
            }
            
            .header h1 {
                font-size: 1.1rem;
            }
            
            .detalle-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .filtro-buttons {
                flex-direction: column;
            }
            
            .btn-buscar, .btn-limpiar {
                width: 100%;
                justify-content: center;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 0.7rem;
            }
            
            .acciones-buttons {
                flex-direction: column;
                gap: 3px;
            }
            
            .btn-accion {
                padding: 3px 6px;
                font-size: 0.7rem;
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .modal-header h2 {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 8px;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: stretch;
            }
            
            .btn-volver, .btn-nueva {
                flex: 1;
                justify-content: center;
                font-size: 0.75rem;
                padding: 8px 12px;
            }
            
            table {
                min-width: 500px;
            }
        }
        
        @media (hover: hover) {
            .pagination button {
                padding: 8px 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>
            <i class="fas fa-shopping-cart"></i>
            Órdenes de Compra
        </h1>
        <div class="header-buttons">
            <a href="nueva_compra.php" class="btn-nueva">
                <i class="fas fa-plus"></i> Nueva Compra
            </a>
            <a href="/proyecto/admin-panel/panel_admin.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver al Panel
            </a>
        </div>
    </div>
    
    <div class="filtros-card">
        <div class="filtros-grid">
            <div class="filtro-group">
                <label><i class="fas fa-hashtag"></i> N° Orden</label>
                <input type="text" id="filtro_orden" placeholder="Buscar por número...">
            </div>
            <div class="filtro-group">
                <label><i class="fas fa-building"></i> Proveedor</label>
                <input type="text" id="filtro_proveedor" placeholder="Nombre del proveedor...">
            </div>
            <div class="filtro-group">
                <label><i class="fas fa-tag"></i> Estado</label>
                <select id="filtro_estado">
                    <option value="">Todos</option>
                    <option value="cotizacion">Cotización</option>
                    <option value="aprobada">Aprobada</option>
                    <option value="enviada">Enviada</option>
                    <option value="recibida_parcial">Recibida Parcial</option>
                    <option value="recibida_total">Recibida Total</option>
                    <option value="anulada">Anulada</option>
                </select>
            </div>
            <div class="filtro-group">
                <label><i class="fas fa-calendar"></i> Desde</label>
                <input type="date" id="filtro_desde">
            </div>
            <div class="filtro-group">
                <label><i class="fas fa-calendar"></i> Hasta</label>
                <input type="date" id="filtro_hasta">
            </div>
            <div class="filtro-buttons">
                <button class="btn-buscar" id="btnBuscar">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button class="btn-limpiar" id="btnLimpiar">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
            </div>
        </div>
    </div>
    
    <div class="table-container">
        <div id="loading" class="loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Cargando datos...</p>
        </div>
        <div id="tablaContent"></div>
    </div>
    
    <div id="pagination" class="pagination"></div>
</div>

<!-- Modal Detalle -->
<div id="modalDetalle" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-receipt"></i> Detalle de Orden de Compra</h2>
            <button class="modal-close" onclick="cerrarModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <div class="loading">Cargando...</div>
        </div>
    </div>
</div>

<script>
    let currentPage = 1;
    let totalPages = 1;
    let currentFilters = {};
    
    // Cargar compras al iniciar
    document.addEventListener('DOMContentLoaded', function() {
        cargarCompras();
        
        // Event listeners
        document.getElementById('btnBuscar').addEventListener('click', function() {
            currentPage = 1;
            cargarCompras();
        });
        
        document.getElementById('btnLimpiar').addEventListener('click', function() {
            document.getElementById('filtro_orden').value = '';
            document.getElementById('filtro_proveedor').value = '';
            document.getElementById('filtro_estado').value = '';
            document.getElementById('filtro_desde').value = '';
            document.getElementById('filtro_hasta').value = '';
            currentPage = 1;
            cargarCompras();
        });
        
        // Enter key en filtros
        const inputs = ['filtro_orden', 'filtro_proveedor', 'filtro_estado', 'filtro_desde', 'filtro_hasta'];
        inputs.forEach(id => {
            document.getElementById(id).addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    currentPage = 1;
                    cargarCompras();
                }
            });
        });
    });
    
    async function cargarCompras() {
        const loadingDiv = document.getElementById('loading');
        const tablaContent = document.getElementById('tablaContent');
        
        loadingDiv.style.display = 'block';
        tablaContent.innerHTML = '';
        
        // Construir filtros
        const filters = {
            page: currentPage,
            orden: document.getElementById('filtro_orden').value,
            proveedor: document.getElementById('filtro_proveedor').value,
            estado: document.getElementById('filtro_estado').value,
            desde: document.getElementById('filtro_desde').value,
            hasta: document.getElementById('filtro_hasta').value
        };
        
        currentFilters = filters;
        
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await fetch(`listar_compras.php?${queryParams}`, {
                credentials: 'include'
            });
            const data = await response.json();
            
            loadingDiv.style.display = 'none';
            
            if (data.success) {
                totalPages = data.total_pages || 1;
                renderTabla(data.data);
                renderPagination();
            } else {
                tablaContent.innerHTML = `
                    <div class="sin-datos">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>${data.message || 'Error al cargar los datos'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error:', error);
            loadingDiv.style.display = 'none';
            tablaContent.innerHTML = `
                <div class="sin-datos">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error de conexión: ${error.message}</p>
                </div>
            `;
        }
    }
    
    function renderTabla(compras) {
        const tablaContent = document.getElementById('tablaContent');
        
        if (!compras || compras.length === 0) {
            tablaContent.innerHTML = `
                <div class="sin-datos">
                    <i class="fas fa-box-open"></i>
                    <p>No hay órdenes de compra registradas</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <table>
                <thead>
                    <tr>
                        <th onclick="ordenarPor('numero_orden')">N° Orden <i class="fas fa-sort"></i></th>
                        <th onclick="ordenarPor('proveedor')">Proveedor <i class="fas fa-sort"></i></th>
                        <th onclick="ordenarPor('fecha_orden')">Fecha <i class="fas fa-sort"></i></th>
                        <th>Productos</th>
                        <th onclick="ordenarPor('subtotal')">Subtotal <i class="fas fa-sort"></i></th>
                        <th onclick="ordenarPor('total')">Total <i class="fas fa-sort"></i></th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        compras.forEach(compra => {
            const estadoClass = getEstadoClass(compra.estado);
            const estadoText = getEstadoText(compra.estado);
            
            html += `
                <tr>
                    <td><strong>${escapeHtml(compra.numero_orden)}</strong></td>
                    <td>${escapeHtml(compra.proveedor_nombre || compra.nombre_comercial || '-')}</td>
                    <td>${formatDate(compra.fecha_orden)}</td>
                    <td>${compra.total_productos || 0} productos</td>
                    <td>${formatCurrency(compra.subtotal)}</td>
                    <td><strong>${formatCurrency(compra.total)}</strong></td>
                    <td><span class="estado-badge ${estadoClass}">${estadoText}</span></td>
                    <td class="acciones-buttons">
                        <button class="btn-accion btn-ver" onclick="verDetalle(${compra.id})" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-accion btn-editar" onclick="editarCompra(${compra.id})" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        tablaContent.innerHTML = html;
    }
    
    function renderPagination() {
        const paginationDiv = document.getElementById('pagination');
        
        if (totalPages <= 1) {
            paginationDiv.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Botón anterior
        html += `<button onclick="cambiarPagina(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>`;
        
        // Números de página
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += `<button onclick="cambiarPagina(1)">1</button>`;
            if (startPage > 2) html += `<button disabled>...</button>`;
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button onclick="cambiarPagina(${i})" class="${i === currentPage ? 'active' : ''}">${i}</button>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) html += `<button disabled>...</button>`;
            html += `<button onclick="cambiarPagina(${totalPages})">${totalPages}</button>`;
        }
        
        // Botón siguiente
        html += `<button onclick="cambiarPagina(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>`;
        
        paginationDiv.innerHTML = html;
    }
    
    function cambiarPagina(page) {
        if (page < 1 || page > totalPages) return;
        currentPage = page;
        cargarCompras();
    }
    
    let sortField = '';
    let sortOrder = 'asc';
    
    function ordenarPor(field) {
        if (sortField === field) {
            sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            sortField = field;
            sortOrder = 'asc';
        }
        cargarCompras();
    }
    
    async function verDetalle(compraId) {
        const modal = document.getElementById('modalDetalle');
        const modalBody = document.getElementById('modalBody');
        
        modal.style.display = 'flex';
        modalBody.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><p>Cargando detalles...</p></div>';
        
        try {
            const response = await fetch(`obtener_compras.php?id=${compraId}`, {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.success) {
                renderDetalleCompra(data.data);
            } else {
                modalBody.innerHTML = `
                    <div class="sin-datos">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p>${data.message || 'Error al cargar el detalle'}</p>
                    </div>
                `;
            }
        } catch (error) {
            modalBody.innerHTML = `
                <div class="sin-datos">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>Error de conexión: ${error.message}</p>
                </div>
            `;
        }
    }
    
    function renderDetalleCompra(compra) {
        const modalBody = document.getElementById('modalBody');
        
        let productosHtml = '';
        if (compra.productos && compra.productos.length > 0) {
            productosHtml = `
                <div style="overflow-x: auto;">
                    <table class="tabla-detalle">
                        <thead>
                            <tr><th>Producto</th><th>Cantidad</th><th>Precio Unitario</th><th>Subtotal</th>
                        </thead>
                        <tbody>
            `;
            
            compra.productos.forEach(producto => {
                productosHtml += `
                    <tr>
                        <td>${escapeHtml(producto.producto_nombre || producto.nombre)}</td>
                        <td>${producto.cantidad}</td>
                        <td>${formatCurrency(producto.precio_unitario)}</td>
                        <td>${formatCurrency(producto.subtotal)}</td>
                    </tr>
                `;
            });
            
            productosHtml += `
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            productosHtml = '<p class="sin-datos">No hay productos registrados</p>';
        }
        
        modalBody.innerHTML = `
            <div class="detalle-grid">
                <div class="detalle-item">
                    <label><i class="fas fa-hashtag"></i> Número de Orden</label>
                    <span class="valor-grande">${escapeHtml(compra.numero_orden)}</span>
                </div>
                <div class="detalle-item">
                    <label><i class="fas fa-building"></i> Proveedor</label>
                    <span>${escapeHtml(compra.proveedor_nombre || compra.nombre_comercial)}</span>
                </div>
                <div class="detalle-item">
                    <label><i class="fas fa-calendar"></i> Fecha de Orden</label>
                    <span>${formatDate(compra.fecha_orden)}</span>
                </div>
                <div class="detalle-item">
                    <label><i class="fas fa-tag"></i> Estado</label>
                    <span class="estado-badge ${getEstadoClass(compra.estado)}">${getEstadoText(compra.estado)}</span>
                </div>
                ${compra.fecha_recibido ? `
                <div class="detalle-item">
                    <label><i class="fas fa-check-circle"></i> Fecha de Recepción</label>
                    <span>${formatDate(compra.fecha_recibido)}</span>
                </div>
                ` : ''}
                ${compra.observaciones ? `
                <div class="detalle-item">
                    <label><i class="fas fa-comment"></i> Observaciones</label>
                    <span>${escapeHtml(compra.observaciones)}</span>
                </div>
                ` : ''}
            </div>
            
            <h3 style="margin: 20px 0 15px 0; font-size:1rem;"><i class="fas fa-boxes"></i> Productos</h3>
            ${productosHtml}
            
            <div class="total-detalle">
                <p>Subtotal: <strong>${formatCurrency(compra.subtotal)}</strong></p>
                <p>IVA (16%): <strong>${formatCurrency(compra.iva)}</strong></p>
                <hr>
                <p><strong>TOTAL: ${formatCurrency(compra.total)}</strong></p>
            </div>
        `;
    }
    
    function editarCompra(compraId) {
        window.location.href = `editar_compra.php?id=${compraId}`;
    }
    
    function cerrarModal() {
        document.getElementById('modalDetalle').style.display = 'none';
    }
    
    // Cerrar modal al hacer clic fuera
    window.onclick = function(event) {
        const modal = document.getElementById('modalDetalle');
        if (event.target === modal) {
            cerrarModal();
        }
    };
    
    function getEstadoClass(estado) {
        const clases = {
            'cotizacion': 'estado-cotizacion',
            'aprobada': 'estado-aprobada',
            'enviada': 'estado-enviada',
            'recibida_parcial': 'estado-recibida_parcial',
            'recibida_total': 'estado-recibida_total',
            'anulada': 'estado-anulada'
        };
        return clases[estado] || 'estado-cotizacion';
    }
    
    function getEstadoText(estado) {
        const textos = {
            'cotizacion': 'Cotización',
            'aprobada': 'Aprobada',
            'enviada': 'Enviada',
            'recibida_parcial': 'Recibida Parcial',
            'recibida_total': 'Recibida Total',
            'anulada': 'Anulada'
        };
        return textos[estado] || estado;
    }
    
    function formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    }
    
    function formatCurrency(amount) {
        if (amount === undefined || amount === null) return '0.00 Bs';
        return parseFloat(amount).toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }) + ' Bs';
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
</script>
</body>
</html>