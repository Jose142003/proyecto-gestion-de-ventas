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
<title>Cuentas por Cobrar - PIC</title>
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
.summary-cards { display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 15px; margin-bottom: 25px; }
.summary-card { background: var(--card-bg); border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border: 1px solid var(--border-color); }
.summary-card .value { font-size: 1.8rem; font-weight: 800; }
.summary-card .label { font-size: 0.85rem; color: #666; margin-top: 5px; }
.table-container { background: var(--card-bg); border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 25px; border: 1px solid var(--border-color); }
.table-header { padding: 15px 20px; background: linear-gradient(135deg,#1a1f3a,#2a3050); color: #fff; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.table-content { overflow-x: auto; }
.data-table { width: 100%; border-collapse: collapse; min-width: 700px; }
.data-table th { padding: 12px 15px; background: linear-gradient(135deg,var(--accent-color),var(--light-color)); color: #fff; font-weight: 600; font-size: 0.85rem; }
.data-table td { padding: 12px 15px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem; }
.data-table tr:hover td { background: rgba(60,145,237,0.05); }
.btn-action { border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: #fff; font-size: 0.75rem; }
.btn-pay { background: var(--success); }
.btn-view { background: var(--info); }
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
        <div class="menu-item active"><i class="fas fa-hand-holding-usd"></i> <span data-i18n="cuentas_cobrar">Cuentas Cobrar</span></div>
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
    <div class="header"><h2 data-i18n="cuentas_cobrar_title"><i class="fas fa-hand-holding-usd"></i> Cuentas por Cobrar</h2><div><span id="currentDate"></span></div></div>

    <div class="summary-cards" id="summaryCards">
        <div class="summary-card"><div class="value" id="totalPendiente">0</div><div class="label">Total Pendiente</div></div>
        <div class="summary-card"><div class="value" id="totalVencido">0</div><div class="label">Vencido</div></div>
        <div class="summary-card"><div class="value" id="totalPagadoMes">0</div><div class="label">Pagado este Mes</div></div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4"><select class="form-select" id="filtroEstado"><option value="" data-i18n="todos">Todos los estados</option><option value="pendiente" data-i18n="pendiente">Pendiente</option><option value="parcial" data-i18n="parcial">Parcial</option><option value="vencida" data-i18n="vencida">Vencida</option><option value="pagada" data-i18n="pagada">Pagada</option></select></div>
        <div class="col-md-4"><input type="text" class="form-control" id="filtroCliente" placeholder="Buscar cliente..." data-i18n-placeholder="buscar"></div>
        <div class="col-md-4"><button class="btn btn-primary" id="btnFiltrar" data-i18n="filtrar"><i class="fas fa-filter"></i> Filtrar</button></div>
    </div>

    <div class="table-container">
        <div class="table-header"><h3><i class="fas fa-list"></i> Cuentas</h3></div>
        <div class="table-content">
            <table class="data-table">
                <thead><tr><th>#</th><th data-i18n="cliente">Cliente</th><th>Documento</th><th>Factura</th><th>Monto Original</th><th>Saldo</th><th>Vencimiento</th><th>Días Venc.</th><th>0-30</th><th>31-60</th><th>61-90</th><th>90+</th><th data-i18n="estado">Estado</th><th data-i18n="acciones">Acciones</th></tr></thead>
                <tbody id="cuentasBody"><tr><td colspan="14" class="loading"><div class="spinner"></div><p data-i18n="cargando">Cargando...</p></td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="pagoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Registrar Pago</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="pagoForm">
                <div class="modal-body">
                    <input type="hidden" id="cuentaId">
                    <div class="mb-3"><label class="form-label" data-i18n="cliente">Cliente</label><p class="form-control-plaintext" id="pagoCliente"></p></div>
                    <div class="mb-3"><label class="form-label">Saldo Pendiente</label><p class="form-control-plaintext" id="pagoSaldo"></p></div>
                    <div class="mb-3"><label class="form-label">Monto *</label><input type="number" step="0.01" min="0.01" class="form-control" id="pagoMonto" required></div>
                    <div class="mb-3"><label class="form-label">Método de Pago *</label><select class="form-select" id="pagoMetodo" required><option value="" data-i18n="seleccionar">Seleccionar...</option><option value="efectivo" data-i18n="efectivo">Efectivo</option><option value="transferencia" data-i18n="transferencia">Transferencia</option><option value="pago_movil" data-i18n="pago_movil">Pago Móvil</option><option value="cheque">Cheque</option><option value="tarjeta">Tarjeta</option><option value="deposito">Depósito</option></select></div>
                    <div class="mb-3"><label class="form-label">Referencia</label><input type="text" class="form-control" id="pagoReferencia"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="cancelar">Cancelar</button><button type="submit" class="btn btn-success" id="btnRegistrarPago"><i class="fas fa-check"></i> <span data-i18n="guardar">Registrar Pago</span></button></div>
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

function fmt(n) { return 'Bs. ' + parseFloat(n||0).toFixed(2); }

function cargarResumen() {
    api(`${BASE}/cobros/obtener_resumen_cobros.php`).then(r => {
        if (!r.success) return;
        const d = r.data;
        $('#totalPendiente').text(fmt(d.total_pendiente));
        $('#totalVencido').text(fmt(d.total_vencido));
        $('#totalPagadoMes').text(fmt(d.total_pagado_mes));
    });
}

function cargarCuentas() {
    const estado = $('#filtroEstado').val();
    const cliente = $('#filtroCliente').val();
    let params = new URLSearchParams({ limit: 100 });
    if (estado) params.set('estado', estado);
    if (cliente) params.set('cliente_id', cliente);
    $('#cuentasBody').html('<tr><td colspan="14" class="loading"><div class="spinner"></div><p>' + __('cargando') + '</p></td></tr>');
    api(`${BASE}/cobros/obtener_cuentas_cobrar.php?${params}`).then(r => {
        if (!r.success) { $('#cuentasBody').html(`<tr><td colspan="14" class="text-center text-danger">${r.message}</td></tr>`); return; }
        if (!r.data.length) { $('#cuentasBody').html('<tr><td colspan="14" class="text-center">' + __('sin_resultados') + '</td></tr>'); return; }
        let h = '';
        r.data.forEach(c => {
            const est = c.estado;
            const badge = est === 'pagada' ? 'success' : est === 'vencida' ? 'danger' : est === 'parcial' ? 'warning' : 'secondary';
            h += `<tr>
                <td>${c.id}</td><td>${esc(c.cliente_nombre)}</td><td>${esc(c.cliente_documento||'')}</td>
                <td>${esc(c.numero_documento||'')}</td><td>${fmt(c.monto_original)}</td><td>${fmt(c.saldo_pendiente)}</td>
                <td>${c.fecha_vencimiento||''}</td><td>${c.dias_vencidos||0}</td>
                <td>${fmt(c.aging_0_30)}</td><td>${fmt(c.aging_31_60)}</td><td>${fmt(c.aging_61_90)}</td><td>${fmt(c.aging_90_plus)}</td>
                <td><span class="badge bg-${badge}">${est}</span></td>
                <td>${est !== 'pagada' && est !== 'anulada' ? `<button class="btn-action btn-pay" onclick="abrirPago(${c.id},'${esc(c.cliente_nombre)}',${c.saldo_pendiente})"><i class="fas fa-dollar-sign"></i> Pagar</button>` : ''}</td>
            </tr>`;
        });
        $('#cuentasBody').html(h);
    }).catch(() => $('#cuentasBody').html('<tr><td colspan="14" class="text-center text-danger">' + __('error_conexion') + '</td></tr>'));
}

function abrirPago(id, cliente, saldo) {
    $('#cuentaId').val(id);
    $('#pagoCliente').text(cliente);
    $('#pagoSaldo').text(fmt(saldo));
    $('#pagoMonto').val('');
    $('#pagoMetodo').val('');
    $('#pagoReferencia').val('');
    new bootstrap.Modal('#pagoModal').show();
}

$('#pagoForm').submit(function(e) {
    e.preventDefault();
    const data = {
        cuenta_cobrar_id: parseInt($('#cuentaId').val()),
        monto: parseFloat($('#pagoMonto').val()),
        metodo_pago: $('#pagoMetodo').val(),
        referencia: $('#pagoReferencia').val().trim()
    };
    if (!data.monto || data.monto <= 0) { notif(__('complete_campos'), 'danger'); return; }
    if (!data.metodo_pago) { notif(__('complete_campos'), 'danger'); return; }
    $('#btnRegistrarPago').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + __('guardar') + '...');
    api(`${BASE}/cobros/registrar_pago_cobro.php`, { method: 'POST', body: JSON.stringify(data) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); $('#btnRegistrarPago').prop('disabled', false).html('<i class="fas fa-check"></i> ' + __('guardar')); return; }
        notif(__('registro_guardado'));
        bootstrap.Modal.getInstance('#pagoModal').hide();
        cargarResumen();
        cargarCuentas();
        $('#btnRegistrarPago').prop('disabled', false).html('<i class="fas fa-check"></i> ' + __('guardar'));
    }).catch(() => { notif(__('error_conexion'), 'danger'); $('#btnRegistrarPago').prop('disabled', false).html('<i class="fas fa-check"></i> ' + __('guardar')); });
});

$('#btnFiltrar').click(() => cargarCuentas());

document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
cargarResumen();
cargarCuentas();
function toggleMobileMenu(){document.querySelector('.sidebar').classList.toggle('active')}
</script>
</body>
</html>