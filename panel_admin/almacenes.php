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
<title>Gestión de Almacenes - PIC</title>
<?php require_once __DIR__ . '/panel_config.php'; ?>
<link rel="icon" type="image/png" href='<?= url('/img/pic.png') ?>'>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
<style>
:root { --primary-color: #1a1f3a; --secondary-color: #e8ecf4; --accent-color: #3C91ED; --light-color: #5aa9e6; --bg-color: #f0f2f5; --text-color: #1a1f3a; --card-bg: #ffffff; --sidebar-bg: #1a1f3a; --success: #2ed573; --warning: #ffa502; --danger: #ff4757; --info: #3498db; --border-color: #d1d8e6; --table-hover: rgba(60,145,237,0.05); }
body { background: var(--bg-color); color: var(--text-color); font-family: 'Segoe UI',sans-serif; }
.sidebar { width: 280px; background: var(--primary-color); color: #fff; min-height: 100vh; padding: 20px 0; position: fixed; }
.sidebar .logo { padding: 20px; text-align: center; border-bottom: 2px solid var(--secondary-color); }
.sidebar .logo h1 { color: var(--light-color); font-size: 2rem; }
.sidebar .logo p { color: rgba(255,255,255,0.7); font-size: 0.9rem; }
.sidebar .menu { padding: 0 15px; }
.sidebar .menu-item { padding: 12px 20px; margin: 5px 0; border-radius: 8px; cursor: pointer; display: flex; align-items: center; color: rgba(255,255,255,0.8); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
.sidebar .menu-item:hover, .sidebar .menu-item.active { background: rgba(41,78,144,0.3); color: #fff; border-left-color: var(--light-color); }
.sidebar .menu-item i { margin-right: 15px; width: 20px; }
.main-content { margin-left: 280px; padding: 25px; }
.header { background: linear-gradient(135deg,#1a1f3a,#2a3050); color: #fff; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
.table-container { background: var(--card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid var(--border-color); }
.table-header { padding: 15px 20px; background: linear-gradient(135deg,#1a1f3a,#2a3050); color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.table-content { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
.data-table th { padding: 12px 15px; background: linear-gradient(135deg,var(--accent-color),var(--light-color)); color: #fff; font-weight: 600; font-size: 0.85rem; }
.data-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
.data-table tr:hover td { background: var(--table-hover); }
.btn-action { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: #fff; font-size: 0.75rem; transition: all 0.2s; }
.btn-edit { background: var(--success); }
.btn-delete { background: var(--danger); }
.btn-view { background: var(--info); }
.badge-success { background: var(--success); color: #fff; }
.badge-secondary { background: #95a5a6; color: #fff; }
.loading { text-align: center; padding: 40px; }
.loading .spinner { width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid var(--accent-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 10px; }
@keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
@media(max-width:992px){.sidebar{width:260px;left:-280px;transition:left 0.3s ease;position:fixed;z-index:1000}.sidebar.active{left:0}.main-content{margin-left:0;padding:15px}.mobile-menu-toggle{display:block!important}}
</style>
</head>
<body>
<div class="sidebar" style="position:relative"><button class="mobile-menu-toggle" onclick="toggleMobileMenu()" style="position:absolute;top:15px;right:-45px;z-index:1001;background:var(--sidebar-bg,#1a1f3a);color:#fff;border:none;font-size:1.3rem;padding:8px 12px;border-radius:0 8px 8px 0;cursor:pointer;display:none"><i class="fas fa-bars"></i></button>
    <div class="logo"><h1>PIC</h1><p data-i18n="panel_admin">Panel Admin</p></div>
    <nav class="menu">
        <a href='<?= url('/panel_admin/panel_admin.php') ?>' class="menu-item"><i class="fas fa-tachometer-alt"></i> <span data-i18n="dashboard">Dashboard</span></a>
        <div class="menu-item active"><i class="fas fa-warehouse"></i> <span data-i18n="almacenes">Almacenes</span></div>
        <a href="cuentas_cobrar.php" class="menu-item"><i class="fas fa-hand-holding-usd"></i> <span data-i18n="cuentas_cobrar">Cuentas Cobrar</span></a>
        <a href="cuentas_pagar.php" class="menu-item"><i class="fas fa-file-invoice"></i> <span data-i18n="cuentas_pagar">Cuentas Pagar</span></a>
        <a href="notas_credito.php" class="menu-item"><i class="fas fa-undo-alt"></i> <span data-i18n="notas_credito">Notas Crédito</span></a>
        <a href="variantes_productos.php" class="menu-item"><i class="fas fa-puzzle-piece"></i> <span data-i18n="variantes">Variantes</span></a>
        <a href="api_tokens.php" class="menu-item"><i class="fas fa-key"></i> <span data-i18n="api_tokens">API Tokens</span></a>
    </nav>
    <div class="sidebar-controls">
        <div class="menu-item" id="themeToggle"><i class="fas fa-sun"></i> <span data-i18n="modo_oscuro">Modo Oscuro</span></div>
        <div class="menu-item" id="langToggle"><i class="fas fa-language"></i> <span>Idioma: Español</span></div>
    </div>
</div>
<div class="main-content">
    <div class="header"><h2 data-i18n="almacenes_title"><i class="fas fa-warehouse"></i> Gestión de Almacenes</h2><div><span id="currentDate"></span></div></div>

    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> <span data-i18n="almacenes">Almacenes</span></h3>
            <div><button class="btn btn-primary btn-sm" id="btnNuevoAlmacen"><i class="fas fa-plus"></i> <span data-i18n="nuevo">Nuevo Almacén</span></button></div>
        </div>
        <div class="table-content">
            <table class="data-table">
                <thead><tr><th data-i18n="codigo">Código</th><th data-i18n="nombre">Nombre</th><th>Dirección</th><th>Ciudad</th><th>Teléfono</th><th>Encargado</th><th>Principal</th><th>Productos</th><th data-i18n="acciones">Acciones</th></tr></thead>
                <tbody id="almacenesBody"><tr><td colspan="9" class="loading"><div class="spinner"></div><p data-i18n="cargando">Cargando...</p></td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="table-container" id="stockSection" style="display:none">
        <div class="table-header"><h3><i class="fas fa-cubes"></i> Stock del Almacén: <span id="stockAlmacenNombre"></span></h3><div><button class="btn btn-info btn-sm" id="btnCerrarStock"><i class="fas fa-times"></i> <span data-i18n="cerrar">Cerrar</span></button></div></div>
        <div class="table-content">
            <table class="data-table">
                <thead><tr><th data-i18n="producto">Producto</th><th>SKU</th><th>Stock</th><th>Stock Mínimo</th></tr></thead>
                <tbody id="stockBody"><tr><td colspan="4" class="loading"><div class="spinner"></div><p data-i18n="cargando">Cargando...</p></td></tr></tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Transferencia entre Almacenes</h5></div>
        <div class="card-body">
            <form id="transferForm" class="row g-3">
                <div class="col-md-3"><label class="form-label">Producto</label><select class="form-select" id="transferProducto" required><option value="" data-i18n="seleccionar">Seleccionar...</option></select></div>
                <div class="col-md-3"><label class="form-label">Origen</label><select class="form-select" id="transferOrigen" required><option value="" data-i18n="seleccionar">Seleccionar...</option></select></div>
                <div class="col-md-3"><label class="form-label">Destino</label><select class="form-select" id="transferDestino" required><option value="" data-i18n="seleccionar">Seleccionar...</option></select></div>
                <div class="col-md-2"><label class="form-label">Cantidad</label><input type="number" class="form-control" id="transferCantidad" min="1" required></div>
                <div class="col-md-1 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100"><i class="fas fa-arrow-right"></i></button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="almacenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title" id="almacenModalTitle">Nuevo Almacén</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="almacenForm">
                <div class="modal-body">
                    <input type="hidden" id="almacenId">
                    <div class="mb-3"><label class="form-label"><span data-i18n="codigo">Código</span> *</label><input type="text" class="form-control" id="codigo" required></div>
                    <div class="mb-3"><label class="form-label"><span data-i18n="nombre">Nombre</span> *</label><input type="text" class="form-control" id="nombre" required></div>
                    <div class="mb-3"><label class="form-label">Dirección</label><input type="text" class="form-control" id="direccion"></div>
                    <div class="mb-3"><label class="form-label">Ciudad</label><input type="text" class="form-control" id="ciudad"></div>
                    <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" class="form-control" id="telefono"></div>
                    <div class="mb-3"><label class="form-label">Encargado</label><input type="text" class="form-control" id="encargado"></div>
                    <div class="form-check"><input type="checkbox" class="form-check-input" id="es_principal"><label class="form-check-label" for="es_principal">Almacén principal</label></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="cancelar">Cancelar</button><button type="submit" class="btn btn-primary" id="btnGuardarAlmacen"><i class="fas fa-save"></i> <span data-i18n="guardar">Guardar</span></button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const BASE = '/proyecto';
const api = (url, opts = {}) => {
    opts.credentials = 'include';
    opts.headers = { ...opts.headers, 'X-CSRF-Token': csrf, 'Content-Type': 'application/json' };
    return fetch(url, opts).then(r => r.json());
};

function notif(msg, type = 'success') {
    msg = __(msg) || msg;
    const d = document.createElement('div');
    d.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    d.style.zIndex = 9999;
    d.innerHTML = msg;
    document.body.appendChild(d);
    setTimeout(() => d.remove(), 3000);
}

function cargarAlmacenes() {
    $('#almacenesBody').html('<tr><td colspan="9" class="loading"><div class="spinner"></div><p>' + __('cargando') + '</p></td></tr>');
    api(`${BASE}/almacenes/obtener_almacenes.php`).then(r => {
        if (!r.success) { $('#almacenesBody').html(`<tr><td colspan="9" class="text-center text-danger">${r.message}</td></tr>`); return; }
        if (!r.data.length) { $('#almacenesBody').html('<tr><td colspan="9" class="text-center">' + __('sin_resultados') + '</td></tr>'); return; }
        let h = '';
        r.data.forEach(a => {
            h += `<tr>
                <td>${esc(a.codigo)}</td><td>${esc(a.nombre)}</td><td>${esc(a.direccion||'')}</td>
                <td>${esc(a.ciudad||'')}</td><td>${esc(a.telefono||'')}</td><td>${esc(a.encargado||'')}</td>
                <td>${a.es_principal ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>'}</td>
                <td>${a.total_productos||0}</td>
                <td class="action-buttons">
                    <button class="btn-action btn-view" onclick="verStock(${a.id},'${esc(a.nombre)}')" title="Ver Stock"><i class="fas fa-cubes"></i></button>
                    <button class="btn-action btn-edit" onclick="editarAlmacen(${a.id},'${esc(a.codigo)}','${esc(a.nombre)}','${esc(a.direccion||'')}','${esc(a.ciudad||'')}','${esc(a.telefono||'')}','${esc(a.encargado||'')}',${a.es_principal})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn-action btn-delete" onclick="eliminarAlmacen(${a.id},'${esc(a.nombre)}')" title="Eliminar"><i class="fas fa-trash"></i></button>
                </td></tr>`;
        });
        $('#almacenesBody').html(h);
    }).catch(e => $('#almacenesBody').html(`<tr><td colspan="9" class="text-center text-danger">${__('error_conexion')}</td></tr>`));
}

function esc(s) { return $('<span>').text(s).html(); }

function verStock(id, nombre) {
    $('#stockAlmacenNombre').text(nombre);
    $('#stockSection').show();
    $('#stockBody').html('<tr><td colspan="4" class="loading"><div class="spinner"></div><p>' + __('cargando') + '</p></td></tr>');
    api(`${BASE}/stock/obtener_stock_almacen.php?almacen_id=${id}`).then(r => {
        if (!r.success) { $('#stockBody').html(`<tr><td colspan="4" class="text-center text-danger">${r.message}</td></tr>`); return; }
        const items = r.data?.stock_por_almacen || [];
        if (!items.length) { $('#stockBody').html('<tr><td colspan="4" class="text-center">' + __('sin_datos') + '</td></tr>'); return; }
        let h = '';
        items.forEach(s => { h += `<tr><td>${esc(s.producto_nombre||s.producto_name||'')}</td><td>${esc(s.sku||'')}</td><td>${s.stock}</td><td>${s.stock_minimo||0}</td></tr>`; });
        $('#stockBody').html(h);
    }).catch(() => $('#stockBody').html('<tr><td colspan="4" class="text-center text-danger">' + __('error_conexion') + '</td></tr>'));
}

$('#btnCerrarStock').click(() => $('#stockSection').hide());

function editarAlmacen(id, codigo, nombre, direccion, ciudad, telefono, encargado, es_principal) {
    $('#almacenId').val(id);
    $('#codigo').val(codigo);
    $('#nombre').val(nombre);
    $('#direccion').val(direccion);
    $('#ciudad').val(ciudad);
    $('#telefono').val(telefono);
    $('#encargado').val(encargado);
    $('#es_principal').prop('checked', !!es_principal);
    $('#almacenModalTitle').text(__('editar'));
    $('#btnGuardarAlmacen').text(__('guardar'));
    new bootstrap.Modal('#almacenModal').show();
}

$('#btnNuevoAlmacen').click(() => {
    $('#almacenForm')[0].reset();
    $('#almacenId').val('');
    $('#es_principal').prop('checked', false);
    $('#almacenModalTitle').text(__('nuevo'));
    $('#btnGuardarAlmacen').text(__('guardar'));
    new bootstrap.Modal('#almacenModal').show();
});

$('#almacenForm').submit(function(e) {
    e.preventDefault();
    const id = $('#almacenId').val();
    const data = {
        codigo: $('#codigo').val().trim(),
        nombre: $('#nombre').val().trim(),
        direccion: $('#direccion').val().trim(),
        ciudad: $('#ciudad').val().trim(),
        telefono: $('#telefono').val().trim(),
        encargado: $('#encargado').val().trim(),
        es_principal: $('#es_principal').is(':checked')
    };
    if (!data.codigo || !data.nombre) { notif(__('complete_campos'), 'danger'); return; }
    $('#btnGuardarAlmacen').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + __('guardar') + '...');
    const url = id ? `${BASE}/almacenes/editar_almacen.php` : `${BASE}/almacenes/crear_almacen.php`;
    if (id) data.id = parseInt(id);
    api(url, { method: 'POST', body: JSON.stringify(data) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); $('#btnGuardarAlmacen').prop('disabled', false).text(__('guardar')); return; }
        notif(r.message);
        bootstrap.Modal.getInstance('#almacenModal').hide();
        cargarAlmacenes();
        $('#btnGuardarAlmacen').prop('disabled', false).text(__('guardar'));
    }).catch(() => { notif(__('error_conexion'), 'danger'); $('#btnGuardarAlmacen').prop('disabled', false).text(__('guardar')); });
});

function eliminarAlmacen(id, nombre) {
    if (!confirm(__('confirmar_eliminar'))) return;
    api(`${BASE}/almacenes/eliminar_almacen.php`, { method: 'POST', body: JSON.stringify({ id }) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(r.message);
        cargarAlmacenes();
    }).catch(() => notif(__('error_conexion'), 'danger'));
}

function cargarProductos() {
    api(`${BASE}/admin/obtener_inventario.php`).then(r => {
        if (!r.success) return;
        const sel = $('#transferProducto');
        sel.html('<option value="">' + __('seleccionar') + '</option>');
        (r.data||[]).forEach(p => { sel.append(`<option value="${p.id}">${esc(p.name)} (${esc(p.sku||'')})</option>`); });
    });
}

function cargarSelectsAlmacenes() {
    api(`${BASE}/almacenes/obtener_almacenes.php`).then(r => {
        if (!r.success) return;
        ['#transferOrigen','#transferDestino'].forEach(s => {
            const sel = $(s);
            sel.html('<option value="">' + __('seleccionar') + '</option>');
            r.data.forEach(a => { sel.append(`<option value="${a.id}">${esc(a.nombre)}</option>`); });
        });
    });
}

$('#transferForm').submit(function(e) {
    e.preventDefault();
    const data = {
        producto_id: parseInt($('#transferProducto').val()),
        almacen_origen_id: parseInt($('#transferOrigen').val()),
        almacen_destino_id: parseInt($('#transferDestino').val()),
        cantidad: parseInt($('#transferCantidad').val())
    };
    if (!data.producto_id || !data.almacen_origen_id || !data.almacen_destino_id || !data.cantidad) { notif(__('complete_campos'), 'danger'); return; }
    api(`${BASE}/stock/transferir_stock.php`, { method: 'POST', body: JSON.stringify(data) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('operacion_exitosa'));
        $('#transferForm')[0].reset();
        cargarAlmacenes();
    }).catch(() => notif(__('error_conexion'), 'danger'));
});

document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
cargarAlmacenes();
cargarProductos();
cargarSelectsAlmacenes();
function toggleMobileMenu(){document.querySelector('.sidebar').classList.toggle('active')}
</script>
</body>
</html>