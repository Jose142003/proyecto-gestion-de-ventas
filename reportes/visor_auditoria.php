<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../config/i18n_helpers.php';
requerirAdmin();

$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
setcookie('lang', $locale, time()+31536000, '/');
\I18n::load($locale);

$pdo = conectarDB();

$porPagina = 50;
$pagina = max(1, intval($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $porPagina;

$filtroModulo = $_GET['modulo'] ?? '';
$filtroAccion = $_GET['accion'] ?? '';
$filtroUsuario = $_GET['usuario'] ?? '';
$fechaDesde = $_GET['desde'] ?? '';
$fechaHasta = $_GET['hasta'] ?? '';

$where = [];
$params = [];

if ($filtroModulo) {
    $where[] = "a.modulo = ?";
    $params[] = $filtroModulo;
}
if ($filtroAccion) {
    $where[] = "a.accion = ?";
    $params[] = $filtroAccion;
}
if ($filtroUsuario) {
    $where[] = "a.usuario_nombre LIKE ?";
    $params[] = "%$filtroUsuario%";
}
if ($fechaDesde) {
    $where[] = "a.fecha_creacion >= ?";
    $params[] = $fechaDesde . ' 00:00:00';
}
if ($fechaHasta) {
    $where[] = "a.fecha_creacion <= ?";
    $params[] = $fechaHasta . ' 23:59:59';
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM auditoria_logs a $whereClause");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

$totalPaginas = max(1, ceil($total / $porPagina));

$stmt = $pdo->prepare("
    SELECT a.* FROM auditoria_logs a
    $whereClause
    ORDER BY a.fecha_creacion DESC
    LIMIT ? OFFSET ?
");
$params[] = $porPagina;
$params[] = $offset;
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modulos = $pdo->query("SELECT DISTINCT modulo FROM auditoria_logs ORDER BY modulo")->fetchAll(PDO::FETCH_COLUMN);
$acciones = $pdo->query("SELECT DISTINCT accion FROM auditoria_logs ORDER BY accion")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Auditoría - PIC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #f8f9fa;
            --text-color: #212529;
            --card-bg: #ffffff;
            --border: #dee2e6;
            --pre-bg: #f8f9fa;
        }
        body.dark-mode {
            --bg-color: #0f1219;
            --text-color: #e4e6eb;
            --card-bg: #1e2436;
            --border: #2c3348;
            --pre-bg: #1a1f2e;
        }
        body { background: var(--bg-color); padding: 20px; color: var(--text-color); }
        .table-audit { font-size: 0.85rem; }
        .table-audit td { vertical-align: middle; }
        .badge-modulo { font-size: 0.75rem; }
        .data-preview { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; }
        .data-preview:hover { white-space: normal; background: var(--pre-bg); }
        .card { background: var(--card-bg); border-color: var(--border); }
        .modal-content { background: var(--card-bg); color: var(--text-color); }
        body.dark-mode .table { --bs-table-bg: var(--card-bg); --bs-table-striped-bg: #1a1f2e; --bs-table-hover-bg: #2c3348; color: var(--text-color); }
        body.dark-mode .table-striped > tbody > tr:nth-of-type(odd) > * { --bs-table-color-type: var(--text-color); }
        body.dark-mode .text-muted { color: #aaa !important; }
        body.dark-mode .bg-light { background-color: var(--pre-bg) !important; }
        body.dark-mode .btn-outline-secondary { color: #aaa; border-color: #2c3348; }
        body.dark-mode .btn-outline-secondary:hover { background: #2c3348; color: white; }
        body.dark-mode .page-link { background: var(--card-bg); color: var(--text-color); border-color: var(--border); }
        body.dark-mode .page-item.active .page-link { background: var(--accent, #3C91ED); border-color: var(--accent, #3C91ED); }
        body.dark-mode .modal-header { border-bottom-color: var(--border); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-history"></i> Registro de Auditoría</h2>
            <button id="themeToggle" class="btn btn-outline-secondary" style="width:38px;"><i class="fas fa-moon"></i></button>
            <a href="/proyecto/panel_admin/panel_admin.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label small">Módulo</label>
                        <select name="modulo" class="form-select form-select-sm">
                            <option value="">Todos</option>
                            <?php foreach ($modulos as $m): ?>
                                <option value="<?= htmlspecialchars($m) ?>" <?= $filtroModulo === $m ? 'selected' : '' ?>><?= htmlspecialchars($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Acción</label>
                        <select name="accion" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach ($acciones as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $filtroAccion === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Usuario</label>
                        <input type="text" name="usuario" class="form-control form-control-sm" value="<?= htmlspecialchars($filtroUsuario) ?>" placeholder="Nombre de usuario">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Desde</label>
                        <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaDesde) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Hasta</label>
                        <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaHasta) ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><strong><?= number_format($total) ?></strong> registros encontrados</span>
                <span class="small text-muted">Página <?= $pagina ?> de <?= $totalPaginas ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-audit mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Módulo</th>
                                <th>Acción</th>
                                <th>Descripción</th>
                                <th>IP</th>
                                <th>Detalle</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No se encontraron registros de auditoría</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-nowrap small"><?= htmlspecialchars($log['fecha_creacion'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($log['usuario_nombre'] ?: 'Sistema') ?></td>
                                        <td><span class="badge bg-info badge-modulo"><?= htmlspecialchars($log['modulo']) ?></span></td>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['accion']) ?></span></td>
                                        <td class="data-preview" title="<?= htmlspecialchars($log['descripcion'] ?? '') ?>">
                                            <?= htmlspecialchars(mb_substr($log['descripcion'] ?? '', 0, 100)) ?>
                                            <?= mb_strlen($log['descripcion'] ?? '') > 100 ? '...' : '' ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($log['ip_address'] ?? '') ?></td>
                                        <td>
                                            <?php if ($log['datos_anteriores'] || $log['datos_nuevos']): ?>
                                                <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#detalleModal"
                                                        data-antes='<?= htmlspecialchars(json_encode($log['datos_anteriores'] ? json_decode($log['datos_anteriores'], true) : null, JSON_PRETTY_PRINT)) ?>'
                                                        data-despues='<?= htmlspecialchars(json_encode($log['datos_nuevos'] ? json_decode($log['datos_nuevos'], true) : null, JSON_PRETTY_PRINT)) ?>'>
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if ($totalPaginas > 1): ?>
                <div class="card-footer">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">Anterior</a>
                            </li>
                            <?php for ($i = max(1, $pagina - 2); $i <= min($totalPaginas, $pagina + 2); $i++): ?>
                                <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $pagina >= $totalPaginas ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">Siguiente</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="detalleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del Cambio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-danger"><i class="fas fa-arrow-left"></i> Datos Anteriores</h6>
                            <pre id="datosAnteriores" class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-size: 0.8rem;"></pre>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success"><i class="fas fa-arrow-right"></i> Datos Nuevos</h6>
                            <pre id="datosNuevos" class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-size: 0.8rem;"></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('detalleModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('datosAnteriores').textContent = button.getAttribute('data-antes') || 'No disponible';
            document.getElementById('datosNuevos').textContent = button.getAttribute('data-despues') || 'No disponible';
        });
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
