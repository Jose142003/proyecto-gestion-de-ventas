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
<title>API Tokens - PIC</title>
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
.btn-revoke { background: var(--danger); }
.btn-activate { background: var(--success); }
.loading { text-align: center; padding: 40px; }
.loading .spinner { width: 40px; height: 40px; border: 3px solid #f3f3f3; border-top: 3px solid var(--accent-color); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 10px; }
@keyframes spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
.token-display { background: #1a1f3a; color: #2ed573; padding: 12px 16px; border-radius: 8px; font-family: monospace; font-size: 0.9rem; word-break: break-all; }
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
        <a href="notas_credito.php" class="menu-item"><i class="fas fa-undo-alt"></i> <span data-i18n="notas_credito">Notas Crédito</span></a>
        <a href="variantes_productos.php" class="menu-item"><i class="fas fa-puzzle-piece"></i> <span data-i18n="variantes">Variantes</span></a>
        <div class="menu-item active"><i class="fas fa-key"></i> <span data-i18n="api_tokens">API Tokens</span></div>
    </nav>
    <div class="sidebar-controls">
        <div class="menu-item" id="themeToggle"><i class="fas fa-sun"></i> <span data-i18n="modo_oscuro">Modo Oscuro</span></div>
        <div class="menu-item" id="langToggle"><i class="fas fa-language"></i> <span>Idioma: Español</span></div>
    </div>
</div>
<div class="main-content">
    <div class="header"><h2 data-i18n="api_tokens_title"><i class="fas fa-key"></i> API Tokens</h2><div><span id="currentDate"></span></div></div>

    <div class="table-container">
        <div class="table-header">
            <h3><i class="fas fa-list"></i> Tokens Registrados</h3>
            <div><button class="btn btn-primary btn-sm" id="btnNuevoToken" data-i18n="nuevo"><i class="fas fa-plus"></i> Nuevo Token</button></div>
        </div>
        <div class="table-content">
            <table class="data-table">
                <thead><tr><th data-i18n="nombre">Nombre</th><th>Usuario</th><th>Permisos</th><th>Último Uso</th><th>Expira</th><th data-i18n="estado">Estado</th><th>Creado</th><th data-i18n="acciones">Acciones</th></tr></thead>
                <tbody id="tokensBody"><tr><td colspan="8" class="loading"><div class="spinner"></div><p data-i18n="cargando">Cargando...</p></td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="tokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Nuevo Token API</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <form id="tokenForm">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Nombre del Token *</label><input type="text" class="form-control" id="tokenNombre" required placeholder="Ej: Integración Shopify"></div>
                    <div class="mb-3"><label class="form-label">Usuario ID *</label><input type="number" class="form-control" id="tokenUsuarioId" required placeholder="ID del admin (ej: 1)"></div>
                    <div class="mb-3"><label class="form-label">Permisos</label>
                        <div class="border rounded p-2">
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="*" id="permTodo" checked><label class="form-check-label" for="permTodo">Todos los permisos (*)</label></div>
                            <hr class="my-1">
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="productos:leer"><label class="form-check-label">productos:leer</label></div>
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="productos:escribir"><label class="form-check-label">productos:escribir</label></div>
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="pedidos:leer"><label class="form-check-label">pedidos:leer</label></div>
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="pedidos:escribir"><label class="form-check-label">pedidos:escribir</label></div>
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="clientes:leer"><label class="form-check-label">clientes:leer</label></div>
                            <div class="form-check"><input class="form-check-input permiso-item" type="checkbox" value="clientes:escribir"><label class="form-check-label">clientes:escribir</label></div>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Fecha de Expiración</label><input type="date" class="form-control" id="tokenExpira"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="cancelar">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnCrearToken" data-i18n="guardar"><i class="fas fa-save"></i> Crear Token</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="tokenCreadoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title"><i class="fas fa-check-circle"></i> Token Creado</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Copie este token ahora. No podrá verlo nuevamente.</div>
                <div class="token-display" id="tokenDisplay"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="copiarToken()"><i class="fas fa-copy"></i> Copiar Token</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n="cerrar">Cerrar</button>
            </div>
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

let ultimoToken = '';

function cargarTokens() {
    $('#tokensBody').html('<tr><td colspan="8" class="loading"><div class="spinner"></div><p>' + __('cargando') + '</p></td></tr>');
    api(`${BASE}/admin/gestionar_tokens.php`).then(r => {
        if (!r.success) { $('#tokensBody').html(`<tr><td colspan="8" class="text-center text-danger">${r.message}</td></tr>`); return; }
        const tokens = r.data || [];
        if (!tokens.length) { $('#tokensBody').html('<tr><td colspan="8" class="text-center">' + __('sin_datos') + '</td></tr>'); return; }
        let h = '';
        tokens.forEach(t => {
            const activo = t.activo == 1 || t.activo === true;
            const permisos = t.permisos ? (typeof t.permisos === 'string' ? t.permisos : JSON.stringify(t.permisos)) : '[]';
            const expires = t.expires_at ? new Date(t.expires_at + 'Z') : null;
            const expired = expires && expires < new Date();
            h += `<tr>
                <td><strong>${esc(t.nombre)}</strong></td>
                <td>${esc(t.usuario_nombre||'')} <small class="text-muted">(${esc(t.usuario_login||'')})</small></td>
                <td><small>${esc(permisos)}</small></td>
                <td>${t.ultimo_uso ? new Date(t.ultimo_uso).toLocaleDateString() : 'Nunca'}</td>
                <td>${t.expires_at ? `${new Date(t.expires_at+'Z').toLocaleDateString()}${expired ? ' <span class="badge bg-danger">Expirado</span>' : ''}` : '<span class="text-muted">Sin expiración</span>'}</td>
                <td>${activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-danger">Revocado</span>'}</td>
                <td>${t.created_at ? new Date(t.created_at).toLocaleDateString() : ''}</td>
                <td>
                    ${activo ? `<button class="btn-action btn-revoke" onclick="revocarToken(${t.id})"><i class="fas fa-ban"></i> Revocar</button>` : `<button class="btn-action btn-activate" onclick="activarToken(${t.id})"><i class="fas fa-check"></i> Activar</button>`}
                </td>
            </tr>`;
        });
        $('#tokensBody').html(h);
    }).catch(() => $('#tokensBody').html('<tr><td colspan="8" class="text-center text-danger">' + __('error_conexion') + '</td></tr>'));
}

$('#btnNuevoToken').click(function() {
    $('#tokenForm')[0].reset();
    $('#permTodo').prop('checked', true);
    $('#tokenUsuarioId').val('');
    new bootstrap.Modal('#tokenModal').show();
});

$('#permTodo').change(function() {
    const checked = $(this).is(':checked');
    $('.permiso-item').not(this).each(function() { $(this).prop('checked', checked); });
});

$('#tokenForm').submit(function(e) {
    e.preventDefault();
    const nombre = $('#tokenNombre').val().trim();
    const usuarioId = parseInt($('#tokenUsuarioId').val());
    if (!nombre) { notif(__('campo_requerido'), 'danger'); return; }
    if (!usuarioId) { notif(__('campo_requerido'), 'danger'); return; }
    let permisos = [];
    if ($('#permTodo').is(':checked')) {
        permisos = ['*'];
    } else {
        $('.permiso-item:checked:not(#permTodo)').each(function() { permisos.push($(this).val()); });
    }
    const data = {
        accion: 'crear',
        nombre,
        usuario_id: usuarioId,
        permisos,
        expires_at: $('#tokenExpira').val() || null
    };
    $('#btnCrearToken').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
    api(`${BASE}/admin/gestionar_tokens.php`, { method: 'POST', body: JSON.stringify(data) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); $('#btnCrearToken').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Token'); return; }
        bootstrap.Modal.getInstance('#tokenModal').hide();
        ultimoToken = r.data?.token || '';
        if (ultimoToken) {
            $('#tokenDisplay').text(ultimoToken);
            new bootstrap.Modal('#tokenCreadoModal').show();
        } else {
            notif(__('registro_guardado'));
        }
        cargarTokens();
        $('#btnCrearToken').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Token');
    }).catch(() => { notif(__('error_conexion'), 'danger'); $('#btnCrearToken').prop('disabled', false).html('<i class="fas fa-save"></i> Crear Token'); });
});

function revocarToken(id) {
    if (!confirm(__('confirmar_eliminar'))) return;
    api(`${BASE}/admin/gestionar_tokens.php`, { method: 'POST', body: JSON.stringify({ accion: 'revocar', token_id: id }) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('operacion_exitosa'));
        cargarTokens();
    }).catch(() => notif(__('error_conexion'), 'danger'));
}

function activarToken(id) {
    api(`${BASE}/admin/gestionar_tokens.php`, { method: 'POST', body: JSON.stringify({ accion: 'activar', token_id: id }) }).then(r => {
        if (!r.success) { notif(r.message, 'danger'); return; }
        notif(__('operacion_exitosa'));
        cargarTokens();
    }).catch(() => notif(__('error_conexion'), 'danger'));
}

function copiarToken() {
    navigator.clipboard.writeText(ultimoToken).then(() => notif('Token copiado al portapapeles')).catch(() => notif('No se pudo copiar', 'danger'));
}

document.getElementById('currentDate').textContent = new Date().toLocaleDateString('es-ES', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
cargarTokens();
function toggleMobileMenu(){document.querySelector('.sidebar').classList.toggle('active')}
</script>
</body>
</html>