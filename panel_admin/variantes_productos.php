<?php
require_once __DIR__ . '/../conexion/conexion.php';
session_start();
header('Cache-Control: no-cache, no-store, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true || ($_SESSION['tabla_origen'] ?? '') !== 'admin_users') {
    header('Location: ' . url('/interfaz_usuario/login.html'));
    exit;
}
if (function_exists('generarTokenCSRF')) { generarTokenCSRF(); }
$csrf = $_SESSION['_csrf_token'] ?? '';
?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Variantes de Productos - PIC</title>
<?php require_once __DIR__ . '/panel_config.php'; ?>
<link rel="icon" type="image/png" href='<?= url('/img/pic.png') ?>'>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
<style>
:root { --primary-color: #1a1f3a; --accent-color: #3C91ED; --light-color: #5aa9e6; --bg-color: #f0f2f5; --text-color: #1a1f3a; --card-bg: #fff; --border-color: #d1d8e6; --success: #2ed573; --danger: #ff4757; --warning: #ffa502; --info: #3498db; }
body { background: var(--bg-color); color: var(--text-color); font-family: 'Segoe UI',sans-serif; }
.sidebar { width: 280px; background: var(--primary-color); color: #fff; min-height: 100vh; padding: 20px 0; position: fixed; }
.sidebar .logo { padding: 20px; text-align: center; border-bottom: 2px solid #e8ecf4; }
.sidebar .logo h1 { color: var(--light-color); font-size: 2rem; }
.sidebar .logo p { color: rgba(255,255,255,0.7); }
.sidebar .menu { padding: 0 15px; }
.sidebar .menu-item { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
.sidebar .menu-item:hover, .sidebar .menu-item.active { background: rgba(41,78,144,0.3); color: #fff; border-left-color: var(--light-color); }
.sidebar .menu-item i { margin-right: 15px; width: 20px; }
.main-content { margin-left: 280px; padding: 25px; }
.header { background: linear-gradient(135deg,#1a1f3a,#2a3050); color: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
.card { background: var(--card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid var(--border-color); }
.table-container { background: var(--card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid var(--border-color); }
.table-header { padding: 15px 20px; background: linear-gradient(135deg,#1a1f3a,#2a3050); color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.table-content { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
.data-table th { padding: 12px 15px; background: linear-gradient(135deg,var(--accent-color),var(--light-color)); color: #fff; font-weight: 600; font-size: 0.85rem; }
.data-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
.data-table tr:hover td { background: rgba(60,145,237,0.05); }
.btn-action { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: #fff; font-size: 0.75rem; }
.btn-edit { background: var(--success); }
.btn-delete { background: var(--danger); }
.btn-add { background: var(--info); }
.loading { text-align: center; padding: 40px; }
.loading .spinner { width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid var(--accent-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 10px; }
@keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
@media(max-width:992px){.sidebar{width:260px;left:-280px;transition:left 0.3s ease;position:fixed;z-index:1000}.sidebar.active{left:0}.main-content{margin-left:0;padding:15px}.mobile-menu-toggle{display:block!important}}
.attr-row { display: flex; gap: 10px; margin-bottom: 8px; align-items: center; }
.attr-row input { flex: 1; }
</style>
</head>
<body>
<div class="sidebar" style="position:relative"><button class="mobile-menu-toggle" onclick="toggleMobileMenu()" style="position:absolute;top:15px;right:-45px;z-index:1001;background:var(--sidebar-bg,#1a1f3a);color:#fff;border:none;font-size:1.3rem;padding:8px 12px;border-radius:0 8px 8px 0;cursor:pointer;display:none"><i class="fas fa-bars"></i></button>
    <div class="logo"><h1>PIC</h1><p data-i18n="panel_admin">Panel Admin</p></div>
    <nav class="menu">
        <a href='<?= url('/panel_admin/panel_admin.php') ?>' class="menu-item"><i class="fas fa-tachometer-alt"></i> <span data-i18n="dashboard">Dashboard</span></a>
        <a href="almacenes.php" class="menu-item"><i class="fas fa-warehouse"></i> <span data-i18n="almacenes">Almacenes</span></a>
        <a href="cuentas_cobrar.php" class="menu-item"><i class="fas fa-hand-holding-usd"></i> <span data-i18n="cuentas_cobrar">Cuentas Cobrar</span></a>
        <a href="cuentas_pagar.php" class="menu-item"><i class="fas fa-file-invoice"></i> <span data-i18n="cuentas_pagar">Cuentas Pagar</span></a>
        <a href="notas_credito.php" class="menu-item"><i class="fas fa-undo-alt"></i> <span data-i18n="notas_credito">Notas Crédito</span></a>
        <div class="menu-item active"><i class="fas fa-puzzle-piece"></i> <span data-i18n="variantes">Variantes</span></div>
        <a href="api_tokens.php" class="menu-item"><i class="fas fa-key"></i> <span data-i18n="api_tokens">API Tokens</span></a>
    </nav>
    <div class="sidebar-controls">
        <div class="menu-item" id="themeToggle"><i class="fas fa-sun"></i> <span data-i18n="modo_oscuro">Modo Oscuro</span></div>
        <div class="menu-item" id="langToggle"><i class="fas fa-language"></i> <span>Idioma: Español</span></div>
    </div>
</div>
<div class="main-content">
    <div class="header"><h2 data-i18n="variantes_title"><i class="fas fa-puzzle-piece"></i> Variantes de Productos</h2><div><span id="currentDate"></span></div></div>

    <div class="card">
        <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-search"></i> Seleccionar Producto</h5></div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-8"><input type="text" class="form-control" id="searchProducto" placeholder="Buscar por nombre, SKU o ID..." data-i18n-placeholder="buscar"></div>
                <div class="col-md-4"><button class="btn btn-primary w-100" id="btnBuscarProducto" data-i18n="buscar"><i class="fas fa-search"></i> Buscar</button></div>
            </div>
            <div id="productoResultados" class="mt-2" style="max-height:200px;overflow-y:auto"></div>
            <div id="productoSeleccionado" class="mt-2 alert alert-success" style="display:none">
                <strong><span id="prodNombre"></span></strong> (SKU: <span id="prodSku"></span>) <button class="btn btn-sm btn-outline-danger float-end" id="btnCambiarProducto">Cambiar</button>
            </div>
        </div>
    </div>

    <div id="variantesContent" style="display:none">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-tags"></i> Atributos</h5></div>
                    <div class="card-body">
                        <div id="atributosList"></div>
                        <button class="btn btn-sm btn-info mt-2" id="btnAgregarAtributo"><i class="fas fa-plus"></i> Agregar Atributo</button>
                        <button class="btn btn-sm btn-success mt-2" id="btnGuardarAtributos"><i class="fas fa-save"></i> Guardar Atributos</button>
                        <button class="btn btn-sm btn-warning mt-2" id="btnGenerarVariantes"><i class="fas fa-cogs"></i> Generar Variantes</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-list"></i> Variantes Generadas</h5></div>
                    <div class="card-body">
                        <div id="variantesPreview" class="small text-muted mb-2">Guarde los atributos y genere variantes para ver resultados</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header"><h3><i class="fas fa-cubes"></i> Variantes</h3><div><button class="btn btn-info btn-sm" id="btnNuevaVariante"><i class="fas fa-plus"></i> Nueva Variante</button></div></div>
            <div class="table-content">
                <table class="data-table">
                    <thead><tr><th>SKU</th><th data-i18n="nombre">Nombre</th><th>Combinación</th><th>Precio Adic.</th><th>Stock</th><th>Activo</th><th data-i18n="acciones">Acciones</th></tr></thead>
                    <tbody id="variantesBody"><tr><td colspan="7" class="text-center">Seleccione un producto</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="varianteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title" id="varianteModalTitle">Nueva Variante</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="varianteForm">
                <div class="modal-body">
                    <input type="hidden" id="varianteId">
                    <div class="mb-3"><label class="form-label">SKU Variante *</label><input type="text" class="form-control" id="vSku" required></div>
                    <div class="mb-3"><label class="form-label">Nombre Variante *</label><input type="text" class="form-control" id="vNombre" required></div>
                    <div class="mb-3"><label class="form-label">Precio Adicional</label><input type="number" step="0.01" class="form-control" id="vPrecio" value="0"></div>
                    <div class="mb-3"><label class="form-label">Stock</label><input type="number" class="form-control" id="vStock" value="0"></div>
                    <div class="mb-3"><label class="form-label">Activo</label><select class="form-select" id="vActivo"><option value="1">Sí</option><option value="0">No</option></select></div>
                    <div class="mb-3"><label class="form-label">Combinación (JSON)</label><textarea class="form-control" id="vCombinacion" rows="3" placeholder='{"Color":"Rojo","Talla":"M"}'></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="cancelar">Cancelar</button><button type="submit" class="btn btn-primary" id="btnGuardarVariante" data-i18n="guardar"><i class="fas fa-save"></i> Guardar</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const BASE = '/proyecto';

function api(url, opts = {}) {
    opts.credentials = 'include';
    opts.headers = { ...opts.headers, 'X-CSRF-Token': csrf, 'Content-Type': 'application/json' };
    return fetch(url, opts).then(r => r.json());
}
function notif(msg, type = 'success') {
    msg = __(msg) || msg;
    const d = document.createElement('div');
    d.className = `alert alert-${type} position-fixed top-0 end-0 m-3`; d.style.zIndex = 9999;
    d.innerHTML = msg; document.body.appendChild(d); setTimeout(() => d.remove(), 3000);
}
function esc(s) { return $('<span>').text(s).html(); }

let selectedProductoId = null;

$('#btnBuscarProducto').click(function() {
    const q = $('#searchProducto').val().trim();
    if (!q) { notif('Ingrese término de búsqueda', 'danger'); return; }
    $('#productoResultados').html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> Buscando...</div>');
    api(`${BASE}/admin/obtener_inventario.php?search=${encodeURIComponent(q)}`).then(r => {
        if (!r.success) { $('#productoResultados').html(`<div class="text-danger">${r.message}</div>`); return; }
        const prods = r.inventario || r.data || [];
        if (!prods.length) { $('#productoResultados').html('<div class="text-muted">' + __('sin_resultados') + '</div>'); return; }
        let h = '<div class="list-group">';
        prods.forEach(p => {
            h += `<a href="#" class="list-group-item list-group-item-action" data-id="${p.id}" data-name="${esc(p.name)}" data-sku="${esc(p.sku||'')}">
                <strong>${esc(p.name)}</strong> <small class="text-muted">SKU: ${esc(p.sku||'N/A')} | ID: ${p.id}</small>
            </a>`;
        });
        h += '</div>';
        $('#productoResultados').html(h);
        $('#productoResultados a').click(function(e) {
            e.preventDefault();
            const id = parseInt($(this).data('id'));
            const name = $(this).data('name');
            const sku = $(this).data('sku');
            seleccionarProducto(id, name, sku);
        });
    });
});

function seleccionarProducto(id, name, sku) {
    selectedProductoId = id;
    $('#prodNombre').text(name);
    $('#prodSku').text(sku);
    $('#productoSeleccionado').show();
    $('#productoResultados').html('');
    $('#searchProducto').val('');
    $('#variantesContent').show();
    cargarAtributos();
    cargarVariantes();
}

$('#btnCambiarProducto').click(function() {
    selectedProductoId = null;
    $('#productoSeleccionado').hide();
    $('#variantesContent').hide();
});

function cargarAtributos() {
    if (!selectedProductoId) return;
    api(`${BASE}/variantes/obtener_atributos.php?producto_id=${selectedProductoId}`).then(r => {
        if (!r.success) { $('#atributosList').html(`<div class="text-danger">${r.message}</div>`); return; }
        const agrupados = r.agrupados || {};
        let h = '';
        let idx = 0;
        Object.keys(agrupados).forEach(nombre => {
            agrupados[nombre].forEach(a => {
                h += `<div class="attr-row">
                    <input type="text" class="form-control form-control-sm" placeholder="Nombre" value="${esc(a.nombre)}" data-idx="${idx}">
                    <input type="text" class="form-control form-control-sm" placeholder="Valor" value="${esc(a.valor)}" data-idx="${idx}">
                    <button class="btn btn-sm btn-outline-danger btn-remove-attr"><i class="fas fa-times"></i></button>
                </div>`;
                idx++;
            });
        });
        if (!h) h = '<div class="text-muted">Sin atributos. Agregue atributos como Color, Talla, Material, etc.</div>';
        $('#atributosList').html(h);
        $(document).off('click', '.btn-remove-attr').on('click', '.btn-remove-attr', function() {
            $(this).closest('.attr-row').remove();
        });
    });
}

$('#btnAgregarAtributo').click(function() {
    $('#atributosList').append(`<div class="attr-row">
        <input type="text" class="form-control form-control-sm" placeholder="Nombre (ej: Color)">
        <input type="text" class="form-control form-control-sm" placeholder="Valor (ej: Rojo)">
        <button class="btn btn-sm btn-outline-danger btn-remove-attr"><i class="fas fa-times"></i></button>
    </div>`);
});

$('#btnGuardarAtributos').click(function() {
    if (!selectedProductoId) { notif('Seleccione un producto', 'danger'); return; }
    const atributos = [];
    $('#atributosList .attr-row').each(function() {
        const nombre = $(this).find('input:first').val().trim();
        const valor = $(this).find('input:eq(1)').val().trim();
        if (nombre && valor) atributos.push({ nombre, valor });
    });
    if (!atributos.length) { notif('Agregue al menos un atributo', 'danger'); return; }
    api(`${BASE}/variantes/guardar_atributos.php`, { method: 'POST', body: JSON.stringify({ producto_id: selectedProductoId, atributos }) })
    .then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('operacion_exitosa'));
        cargarAtributos();
    }).catch(() => notif(__('error_conexion'), 'danger'));
});

$('#btnGenerarVariantes').click(function() {
    if (!selectedProductoId) { notif('Seleccione un producto', 'danger'); return; }
    api(`${BASE}/variantes/obtener_atributos.php?producto_id=${selectedProductoId}`).then(r => {
        if (!r.success || !r.atributos || !r.atributos.length) { notif('No hay atributos para generar variantes', 'warning'); return; }
        const agrupados = {};
        r.atributos.forEach(a => {
            if (!agrupados[a.nombre]) agrupados[a.nombre] = [];
            agrupados[a.nombre].push(a.valor);
        });
        const nombres = Object.keys(agrupados);
        // Generate combinations
        function combinar(arrays, idx, actual) {
            if (idx === arrays.length) return [actual];
            const res = [];
            arrays[idx].forEach(v => {
                res.push(...combinar(arrays, idx + 1, [...actual, v]));
            });
            return res;
        }
        const values = nombres.map(n => agrupados[n]);
        const combos = combinar(values, 0, []);
        let html = `<p class="mb-2">Se generarán <strong>${combos.length}</strong> variantes:</p><div class="list-group">`;
        const baseName = $('#prodNombre').text();
        const baseSku = $('#prodSku').text();
        combos.forEach((combo, i) => {
            const comboObj = {};
            nombres.forEach((n, j) => { comboObj[n] = combo[j]; });
            const namePart = combo.join(' ');
            const skuPart = combo.map(v => v.substring(0,3).toUpperCase()).join('-');
            html += `<div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col-4"><strong>${esc(baseName)} - ${esc(namePart)}</strong></div>
                    <div class="col-3"><small>SKU: ${esc(baseSku)}-${esc(skuPart)}</small></div>
                    <div class="col-2"><small>${esc(nombres.map((n,j)=>`${n}:${combo[j]}`).join(', '))}</small></div>
                    <div class="col-1"><input type="number" class="form-control form-control-sm stock-gen" placeholder="Stock" value="0" data-sku="${esc(baseSku)}-${esc(skuPart)}" data-name="${esc(baseName)} - ${esc(namePart)}" data-combo='${esc(JSON.stringify(comboObj))}'></div>
                    <div class="col-1"><input type="number" step="0.01" class="form-control form-control-sm precio-gen" placeholder="Precio" value="0" data-sku="${esc(baseSku)}-${esc(skuPart)}"></div>
                    <div class="col-1"><button class="btn btn-sm btn-success btn-create-variant" data-idx="${i}"><i class="fas fa-plus"></i></button></div>
                </div>
            </div>`;
        });
        html += '</div>';
        $('#variantesPreview').html(html);
    });
});

$(document).on('click', '.btn-create-variant', function() {
    if (!selectedProductoId) return;
    const item = $(this).closest('.list-group-item');
    const sku = item.find('.stock-gen').data('sku');
    const name = item.find('.stock-gen').data('name');
    const combo = item.find('.stock-gen').data('combo');
    const stock = parseInt(item.find('.stock-gen').val()) || 0;
    const precio = parseFloat(item.find('.precio-gen').val()) || 0;
    api(`${BASE}/variantes/crear_variante.php`, { method: 'POST', body: JSON.stringify({
        producto_id: selectedProductoId,
        sku_variante: sku,
        nombre_variante: name,
        combinacion: combo,
        precio_adicional: precio,
        stock
    })}).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('registro_guardado'));
        cargarVariantes();
    });
});

function cargarVariantes() {
    if (!selectedProductoId) return;
    $('#variantesBody').html('<tr><td colspan="7" class="loading"><div class="spinner"></div><p>' + __('cargando') + '</p></td></tr>');
    api(`${BASE}/variantes/obtener_variantes.php?producto_id=${selectedProductoId}`).then(r => {
        if (!r.success) { $('#variantesBody').html(`<tr><td colspan="7" class="text-center text-danger">${r.message}</td></tr>`); return; }
        const variantes = r.variantes || [];
        if (!variantes.length) { $('#variantesBody').html('<tr><td colspan="7" class="text-center">' + __('sin_datos') + '</td></tr>'); return; }
        let h = '';
        variantes.forEach(v => {
            const combo = typeof v.combinacion === 'object' ? v.combinacion : {};
            const comboStr = Object.keys(combo).map(k => `${k}: ${combo[k]}`).join(', ');
            h += `<tr>
                <td>${esc(v.sku_variante)}</td><td>${esc(v.nombre_variante)}</td>
                <td><small>${esc(comboStr)}</small></td>
                <td>Bs. ${parseFloat(v.precio_adicional).toFixed(2)}</td>
                <td>${v.stock}</td>
                <td>${v.activo ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                <td>
                    <button class="btn-action btn-edit" onclick="editarVariante(${v.id},'${esc(v.sku_variante)}','${esc(v.nombre_variante)}',${v.precio_adicional},${v.stock},${v.activo},'${esc(JSON.stringify(combo))}')"><i class="fas fa-edit"></i></button>
                    <button class="btn-action btn-delete" onclick="eliminarVariante(${v.id})"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
        $('#variantesBody').html(h);
    }).catch(() => $('#variantesBody').html('<tr><td colspan="7" class="text-center text-danger">' + __('error_conexion') + '</td></tr>'));
}

function editarVariante(id, sku, nombre, precio, stock, activo, combo) {
    $('#varianteId').val(id);
    $('#vSku').val(sku);
    $('#vNombre').val(nombre);
    $('#vPrecio').val(precio);
    $('#vStock').val(stock);
    $('#vActivo').val(activo);
    try { $('#vCombinacion').val(JSON.stringify(JSON.parse(combo), null, 2)); } catch(e) { $('#vCombinacion').val(combo); }
    $('#varianteModalTitle').text('Editar Variante');
    $('#btnGuardarVariante').text('Actualizar');
    new bootstrap.Modal('#varianteModal').show();
}

$('#btnNuevaVariante').click(function() {
    if (!selectedProductoId) { notif('Seleccione un producto primero', 'warning'); return; }
    $('#varianteForm')[0].reset();
    $('#varianteId').val('');
    $('#vActivo').val('1');
    $('#varianteModalTitle').text('Nueva Variante');
    $('#btnGuardarVariante').text('Guardar');
    new bootstrap.Modal('#varianteModal').show();
});

$('#varianteForm').submit(function(e) {
    e.preventDefault();
    if (!selectedProductoId) { notif('Seleccione un producto', 'danger'); return; }
    const id = $('#varianteId').val();
    const data = { producto_id: selectedProductoId };
    if (id) data.id = parseInt(id);
    data.sku_variante = $('#vSku').val().trim();
    data.nombre_variante = $('#vNombre').val().trim();
    data.precio_adicional = parseFloat($('#vPrecio').val()) || 0;
    data.stock = parseInt($('#vStock').val()) || 0;
    data.activo = parseInt($('#vActivo').val());
    try { data.combinacion = JSON.parse($('#vCombinacion').val()); } catch(e) { data.combinacion = $('#vCombinacion').val(); }
    if (!data.sku_variante || !data.nombre_variante) { notif(__('campo_requerido'), 'danger'); return; }
    const url = id ? `${BASE}/variantes/editar_variante.php` : `${BASE}/variantes/crear_variante.php`;
    $('#btnGuardarVariante').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
    api(url, { method: 'POST', body: JSON.stringify(data) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); $('#btnGuardarVariante').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar'); return; }
        notif(r.message);
        bootstrap.Modal.getInstance('#varianteModal').hide();
        cargarVariantes();
        $('#btnGuardarVariante').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');
    }).catch(() => { notif(__('error_conexion'), 'danger'); $('#btnGuardarVariante').prop('disabled', false).html('<i class="fas fa-save"></i> Guardar'); });
});

function eliminarVariante(id) {
    if (!confirm(__('confirmar_eliminar'))) return;
    api(`${BASE}/variantes/eliminar_variante.php`, { method: 'POST', body: JSON.stringify({ id }) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('operacion_exitosa'));
        cargarVariantes();
    }).catch(() => notif(__('error_conexion'), 'danger'));
}

document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
function toggleMobileMenu(){document.querySelector('.sidebar').classList.toggle('active')}
</script>
</body>
</html>