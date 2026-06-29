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
<title>Notas de Crédito - PIC</title>
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
.table-container { background: var(--card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid var(--border-color); }
.table-header { padding: 15px 20px; background: linear-gradient(135deg,#1a1f3a,#2a3050); color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.table-content { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
.data-table th { padding: 12px 15px; background: linear-gradient(135deg,var(--accent-color),var(--light-color)); color: #fff; font-weight: 600; font-size: 0.85rem; }
.data-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
.data-table tr:hover td { background: rgba(60,145,237,0.05); }
.btn-action { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: #fff; font-size: 0.75rem; }
.btn-view { background: var(--info); }
.btn-danger { background: var(--danger); }
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
        <a href="almacenes.php" class="menu-item"><i class="fas fa-warehouse"></i> <span data-i18n="almacenes">Almacenes</span></a>
        <a href="cuentas_cobrar.php" class="menu-item"><i class="fas fa-hand-holding-usd"></i> <span data-i18n="cuentas_cobrar">Cuentas Cobrar</span></a>
        <a href="cuentas_pagar.php" class="menu-item"><i class="fas fa-file-invoice"></i> <span data-i18n="cuentas_pagar">Cuentas Pagar</span></a>
        <div class="menu-item active"><i class="fas fa-undo-alt"></i> <span data-i18n="notas_credito">Notas Crédito</span></div>
        <a href="variantes_productos.php" class="menu-item"><i class="fas fa-puzzle-piece"></i> <span data-i18n="variantes">Variantes</span></a>
        <a href="api_tokens.php" class="menu-item"><i class="fas fa-key"></i> <span data-i18n="api_tokens">API Tokens</span></a>
    </nav>
    <div class="sidebar-controls">
        <div class="menu-item" id="themeToggle"><i class="fas fa-sun"></i> <span data-i18n="modo_oscuro">Modo Oscuro</span></div>
        <div class="menu-item" id="langToggle"><i class="fas fa-language"></i> <span>Idioma: Español</span></div>
    </div>
</div>
<div class="main-content">
    <div class="header"><h2 data-i18n="notas_credito_title"><i class="fas fa-undo-alt"></i> Notas de Crédito</h2><div><span id="currentDate"></span></div></div>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-plus"></i> Crear Nota de Crédito</h5></div>
                <div class="card-body">
                    <form id="crearNotaForm">
                        <div class="mb-3"><label class="form-label">Factura ID *</label>
                            <div class="input-group"><input type="number" class="form-control" id="facturaId" required><button class="btn btn-info" type="button" id="btnCargarFactura"><i class="fas fa-search"></i> Cargar</button></div>
                        </div>
                        <div id="facturaInfo" style="display:none" class="mb-3 p-3 bg-light rounded"></div>
                        <div class="mb-3"><label class="form-label">Motivo *</label><select class="form-select" id="motivo" required><option value="" data-i18n="seleccionar">Seleccionar...</option><option value="devolucion">Devolución</option><option value="descuento">Descuento</option><option value="error_facturacion">Error de Facturación</option><option value="anulacion">Anulación</option><option value="otro">Otro</option></select></div>
                        <div class="mb-3"><label class="form-label">Descripción *</label><textarea class="form-control" id="descripcion" rows="2" required></textarea></div>
                        <div class="mb-3"><label class="form-label">Seleccionar productos para devolver</label>
                            <div id="productosLista" class="border rounded p-2" style="max-height:200px;overflow-y:auto"></div>
                        </div>
                        <button type="submit" class="btn btn-success" id="btnCrearNota"><i class="fas fa-save"></i> Crear Nota de Crédito</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white"><h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Factura ID</label><input type="number" class="form-control" id="filtroFacturaId"></div>
                        <div class="col-6"><label class="form-label">Cliente ID</label><input type="number" class="form-control" id="filtroClienteId"></div>
                        <div class="col-6"><label class="form-label">Desde</label><input type="date" class="form-control" id="filtroFechaDesde"></div>
                        <div class="col-6"><label class="form-label">Hasta</label><input type="date" class="form-control" id="filtroFechaHasta"></div>
                        <div class="col-12"><button class="btn btn-primary w-100" id="btnFiltrar" data-i18n="filtrar"><i class="fas fa-filter"></i> Filtrar</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-header"><h3><i class="fas fa-list"></i> Notas de Crédito</h3></div>
        <div class="table-content">
            <table class="data-table">
                <thead><tr><th>#</th><th>N° Nota</th><th>Factura</th><th data-i18n="cliente">Cliente</th><th>Motivo</th><th data-i18n="total">Total</th><th data-i18n="fecha">Fecha</th><th data-i18n="estado">Estado</th><th data-i18n="acciones">Acciones</th></tr></thead>
                <tbody id="notasBody"><tr><td colspan="9" class="loading"><div class="spinner"></div><p data-i18n="cargando">Cargando...</p></td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="detalleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Detalle de Nota de Crédito</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="detalleBody" data-i18n="cargando">Cargando...</div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="cerrar">Cerrar</button></div>
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

function cargarNotas() {
    const params = new URLSearchParams({ limit: 50 });
    const fid = $('#filtroFacturaId').val(); if (fid) params.set('factura_id', fid);
    const cid = $('#filtroClienteId').val(); if (cid) params.set('cliente_id', cid);
    const fd = $('#filtroFechaDesde').val(); if (fd) params.set('fecha_desde', fd);
    const fh = $('#filtroFechaHasta').val(); if (fh) params.set('fecha_hasta', fh);
    $('#notasBody').html('<tr><td colspan="9" class="loading"><div class="spinner"></div><p>' + __('cargando') + '</p></td></tr>');
    api(`${BASE}/notas_credito/listar_notas_credito.php?${params}`).then(r => {
        if (!r.success) { $('#notasBody').html(`<tr><td colspan="9" class="text-center text-danger">${r.message}</td></tr>`); return; }
        const notas = r.notas || [];
        if (!notas.length) { $('#notasBody').html('<tr><td colspan="9" class="text-center">' + __('sin_datos') + '</td></tr>'); return; }
        let h = '';
        notas.forEach(n => {
            const badge = n.estado === 'emitida' ? 'success' : n.estado === 'anulada' ? 'danger' : 'secondary';
            h += `<tr>
                <td>${n.id}</td><td>${esc(n.numero_nota)}</td><td>${esc(n.numero_factura||'')}</td>
                <td>${esc(n.cliente_nombre)}</td><td>${esc(n.motivo)}</td>
                <td>Bs. ${parseFloat(n.total||0).toFixed(2)}</td>
                <td>${n.created_at||''}</td>
                <td><span class="badge bg-${badge}">${n.estado}</span></td>
                <td>
                    <button class="btn-action btn-view" onclick="verDetalle(${n.id})"><i class="fas fa-eye"></i></button>
                    ${n.estado === 'emitida' ? `<button class="btn-action btn-danger" onclick="anularNota(${n.id})"><i class="fas fa-ban"></i></button>` : ''}
                </td>
            </tr>`;
        });
        $('#notasBody').html(h);
    }).catch(() => $('#notasBody').html('<tr><td colspan="9" class="text-center text-danger">' + __('error_conexion') + '</td></tr>'));
}

function verDetalle(id) {
    $('#detalleBody').html('<div class="text-center"><div class="spinner-border"></div><p>' + __('cargando') + '</p></div>');
    new bootstrap.Modal('#detalleModal').show();
    api(`${BASE}/notas_credito/obtener_nota_credito.php?id=${id}`).then(r => {
        if (!r.success || !r.nota) { $('#detalleBody').html(`<p class="text-danger">${r.message||'Error'}</p>`); return; }
        const n = r.nota;
        let html = `<div class="row mb-3"><div class="col-md-6"><strong>Número:</strong> ${esc(n.numero_nota)}</div>
            <div class="col-md-6"><strong>Factura:</strong> ${esc(n.numero_factura)}</div>
            <div class="col-md-6"><strong>Cliente:</strong> ${esc(n.cliente_nombre)}</div>
            <div class="col-md-6"><strong>Documento:</strong> ${esc(n.cliente_documento)}</div>
            <div class="col-md-6"><strong>Motivo:</strong> ${esc(n.motivo)}</div>
            <div class="col-md-6"><strong>Estado:</strong> <span class="badge bg-${n.estado==='emitida'?'success':'danger'}">${n.estado}</span></div></div>
            <hr><p><strong>Descripción:</strong> ${esc(n.descripcion)}</p>
            <h6>Detalles</h6><table class="table table-sm"><thead><tr><th>${__('producto')}</th><th>SKU</th><th>${__('cantidad')}</th><th>P/U</th><th>Subtotal</th></tr></thead><tbody>`;
        (n.detalles||[]).forEach(d => {
            html += `<tr><td>${esc(d.producto_nombre||'')}</td><td>${esc(d.sku||'')}</td><td>${d.cantidad}</td><td>Bs. ${parseFloat(d.precio_unitario).toFixed(2)}</td><td>Bs. ${parseFloat(d.subtotal).toFixed(2)}</td></tr>`;
        });
        html += `</tbody></table>
            <div class="text-end"><p><strong>Subtotal:</strong> Bs. ${parseFloat(n.subtotal).toFixed(2)}</p>
            <p><strong>IVA:</strong> Bs. ${parseFloat(n.iva).toFixed(2)}</p>
            <h5><strong>Total:</strong> Bs. ${parseFloat(n.total).toFixed(2)}</h5></div>`;
        $('#detalleBody').html(html);
    }).catch(() => $('#detalleBody').html('<p class="text-danger">' + __('error_conexion') + '</p>'));
}

function anularNota(id) {
    if (!confirm(__('confirmar_eliminar'))) return;
    api(`${BASE}/notas_credito/anular_nota_credito.php`, { method: 'POST', body: JSON.stringify({ id }) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('registro_eliminado'));
        cargarNotas();
    }).catch(() => notif(__('error_conexion'), 'danger'));
}

$('#btnCargarFactura').click(function() {
    const fid = parseInt($('#facturaId').val());
    if (!fid) { notif('Ingrese ID de factura', 'danger'); return; }
    $('#facturaInfo').hide().html('<div class="text-center"><div class="spinner-border spinner-border-sm"></div> ' + __('cargando') + '</div>').show();
    // Load factura info via facturacion API
    fetch(`${BASE}/facturacion/obtener_factura.php?id=${fid}`, { credentials: 'include' })
    .then(r => r.json()).then(r => {
        if (!r.success) { $('#facturaInfo').html(`<div class="text-danger">${r.message}</div>`); return; }
        const f = r.factura || r.data;
        let html = `<div><strong>Factura #${esc(f.numero_factura||f.id)}</strong></div>
            <div>Cliente: ${esc(f.cliente_nombre||'')}</div>
            <div>Total: Bs. ${parseFloat(f.total||0).toFixed(2)}</div>`;
        if (f.detalles && f.detalles.length) {
            html += '<hr><div class="form-label">Productos:</div>';
            f.detalles.forEach(d => {
                const pid = d.producto_id || d.id;
                html += `<div class="form-check">
                    <input class="form-check-input producto-item" type="checkbox" data-producto-id="${pid}" data-precio="${d.precio_unitario||0}" value="1" checked>
                    <label class="form-check-label">${esc(d.producto_nombre||d.name||'Producto #'+pid)} - Bs. ${parseFloat(d.precio_unitario||0).toFixed(2)}</label>
                    <input type="number" class="form-control form-control-sm d-inline-block" style="width:80px" value="${d.cantidad||1}" min="1" data-producto-id="${pid}">
                </div>`;
            });
        }
        $('#facturaInfo').html(html);
    }).catch(() => $('#facturaInfo').html('<div class="text-danger">Error al cargar factura</div>'));
});

$('#crearNotaForm').submit(function(e) {
    e.preventDefault();
    const fid = parseInt($('#facturaId').val());
    const motivo = $('#motivo').val();
    const descripcion = $('#descripcion').val().trim();
    if (!fid || !motivo || !descripcion) { notif(__('complete_campos'), 'danger'); return; }
    const items = [];
    $('.producto-item:checked').each(function() {
        const pid = parseInt($(this).data('producto-id'));
        const cantidad = parseInt($(this).closest('.form-check').find('input[type="number"]').val()) || 1;
        items.push({ producto_id: pid, cantidad });
    });
    if (!items.length) { notif('Seleccione al menos un producto', 'danger'); return; }
    $('#btnCrearNota').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
    api(`${BASE}/notas_credito/crear_nota_credito.php`, { method: 'POST', body: JSON.stringify({ factura_id: fid, motivo, descripcion, items }) })
    .then(r => {
        if (!r.success) { notif(r.message, 'danger'); $('#btnCrearNota').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Nota de Crédito'); return; }
        notif(__('registro_guardado'));
        $('#crearNotaForm')[0].reset();
        $('#facturaInfo').hide();
        cargarNotas();
        $('#btnCrearNota').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Nota de Crédito');
    }).catch(() => { notif(__('error_conexion'), 'danger'); $('#btnCrearNota').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Nota de Crédito'); });
});

$('#btnFiltrar').click(() => cargarNotas());

document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
cargarNotas();
function toggleMobileMenu(){document.querySelector('.sidebar').classList.toggle('active')}
</script>
</body>
</html>