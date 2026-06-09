<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();
$usuarioNombre = $_SESSION['user_nombre'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Asistente Técnico - PIC Industrial</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
<style>
:root { --primary: #050C18; --secondary: #294E90; --accent: #3C91ED; --bg: #f0f2f5; }
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
body { background: var(--bg); min-height: 100vh; display: flex; }
.sidebar { width: 250px; background: var(--primary); color: white; padding: 20px 0; height: 100vh; position: fixed; overflow-y: auto; }
.sidebar h2 { text-align: center; padding: 0 15px 20px; font-size: 16px; border-bottom: 1px solid rgba(255,255,255,.1); margin-bottom: 10px; }
.sidebar h2 span { font-size: 11px; opacity: .7; display: block; }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 12px 20px; color: rgba(255,255,255,.7); text-decoration: none; transition: .3s; font-size: 14px; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,.1); color: white; }
.sidebar a i { width: 20px; text-align: center; }
.main { margin-left: 250px; flex: 1; padding: 20px; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.header h1 { font-size: 22px; color: var(--primary); }
.header .badge { background: var(--accent); color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
.calculator-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.08); margin-bottom: 20px; }
.card h3 { font-size: 16px; color: var(--primary); margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
.card h3 i { color: var(--accent); }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 13px; color: #555; margin-bottom: 4px; font-weight: 600; }
.form-group input, .form-group select { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; }
.form-group input:focus, .form-group select:focus { border-color: var(--accent); }
.btn { padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; transition: .3s; }
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: var(--secondary); }
.btn-secondary { background: #6c757d; color: white; }
.btn-success { background: #28a745; color: white; }
.btn-sm { padding: 5px 12px; font-size: 12px; }
.result-box { background: #f8f9ff; border: 1px solid #e0e5ff; border-radius: 8px; padding: 15px; margin-top: 15px; display: none; }
.result-box.show { display: block; }
.result-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
.result-item:last-child { border: none; }
.result-item .label { color: #666; font-size: 13px; }
.result-item .value { font-weight: 700; color: var(--primary); font-size: 14px; }
.result-item .value.highlight { color: var(--accent); font-size: 16px; }
.product-suggest { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 10px; }
.product-item { background: #f8f9fa; border-radius: 6px; padding: 10px; text-align: center; }
.product-item .name { font-size: 12px; color: var(--primary); margin-bottom: 4px; }
.product-item .price { font-size: 14px; font-weight: 700; color: var(--accent); }
.product-item .stock { font-size: 11px; color: #999; }
.tabs { display: flex; gap: 2px; margin-bottom: 20px; background: white; border-radius: 10px; padding: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.tab { padding: 10px 20px; cursor: pointer; border-radius: 8px; font-size: 13px; font-weight: 600; color: #666; transition: .3s; border: none; background: none; }
.tab:hover { background: #f0f2f5; }
.tab.active { background: var(--accent); color: white; }
.tab-content { display: none; }
.tab-content.active { display: block; }
.compat-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.compat-table th { background: var(--primary); color: white; padding: 8px 12px; text-align: left; }
.compat-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
.compat-table tr:hover { background: #f8f9ff; }
.badge-comp { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
.badge-directo { background: #d4edda; color: #155724; }
.badge-adaptador { background: #fff3cd; color: #856404; }
.badge-funcional { background: #cce5ff; color: #004085; }
.component-list { list-style: none; }
.component-list li { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8f9fa; margin-bottom: 4px; border-radius: 4px; font-size: 13px; }
.component-list li .remove { color: #dc3545; cursor: pointer; }
.config-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 10px; }
.config-card { background: #f8f9fa; border-radius: 8px; padding: 15px; border-left: 3px solid var(--accent); }
.config-card h4 { font-size: 14px; color: var(--primary); margin-bottom: 5px; }
.config-card .meta { font-size: 11px; color: #999; }
.config-card .total { font-size: 16px; font-weight: 700; color: var(--accent); margin-top: 5px; }
.search-box { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
.search-box input, .search-box select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
.search-box input { flex: 1; min-width: 200px; }
@media (max-width: 768px) { .sidebar { display: none; } .main { margin-left: 0; } .calculator-container { grid-template-columns: 1fr; } .tabs { overflow-x:auto;white-space:nowrap } }
@media print { .sidebar, .tabs, .header .badge, .btn, .logout { display:none!important } .main { margin:0!important;padding:10px!important } .card { break-inside:avoid;box-shadow:none!important;border:1px solid #ddd } }
.loading { text-align: center; padding: 20px; color: #999; }
.loading i { font-size: 24px; margin-bottom: 10px; }
@keyframes slideIn { from { transform:translateX(100px);opacity:0 } to { transform:translateX(0);opacity:1 } }
.maintenance-alert { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 12px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
.maintenance-alert.vencida { background: #f8d7da; border-color: #dc3545; }
.maintenance-alert .info { font-size: 13px; }
.maintenance-alert .info strong { display: block; }
.maintenance-alert .date { font-size: 12px; color: #666; }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Asistente Técnico <span>PIC Industrial</span></h2>
    <a href="/proyecto/panel_admin/panel_admin.php"><i class="fas fa-arrow-left"></i> Volver al Panel</a>
    <a href="#" class="active" onclick="showTab('calculadora')"><i class="fas fa-calculator"></i> Calculadora</a>
    <a href="#" onclick="showTab('compatibilidad')"><i class="fas fa-random"></i> Compatibilidad</a>
    <a href="#" onclick="showTab('configurador')"><i class="fas fa-microchip"></i> Configurador</a>
    <a href="#" onclick="showTab('mantenimiento')"><i class="fas fa-tools"></i> Mantenimiento</a>
    <a href="https://www.instagram.com/piccavzla" target="_blank" style="margin-top:auto;border-top:1px solid rgba(255,255,255,.1);">
        <i class="fab fa-instagram"></i> @piccavzla
    </a>
</div>

<div class="main">
    <div class="header">
        <div>
            <h1><i class="fas fa-robot"></i> Asistente Técnico Inteligente</h1>
            <small style="color:#999">Calculadora eléctrica · Compatibilidad · Configurador · Mantenimiento</small>
        </div>
    </div>

    <div class="tabs">
        <button class="tab active" onclick="showTab('resumen')"><i class="fas fa-th-large"></i> Resumen</button>
        <button class="tab" onclick="showTab('calculadora')"><i class="fas fa-calculator"></i> Calculadora</button>
        <button class="tab" onclick="showTab('compatibilidad')"><i class="fas fa-random"></i> Compatibilidad</button>
        <button class="tab" onclick="showTab('configurador')"><i class="fas fa-microchip"></i> Configurador</button>
        <button class="tab" onclick="showTab('mantenimiento')"><i class="fas fa-tools"></i> Mantenimiento</button>
    </div>

    <div id="tab-resumen" class="tab-content">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px">
            <div class="card" style="text-align:center;padding:25px">
                <div style="font-size:32px;color:var(--accent);margin-bottom:8px"><i class="fas fa-calculator"></i></div>
                <div style="font-size:28px;font-weight:700;color:var(--primary)" id="resumenCalculos">0</div>
                <div style="font-size:13px;color:#999">Tipos de cálculo</div>
            </div>
            <div class="card" style="text-align:center;padding:25px">
                <div style="font-size:32px;color:#28a745;margin-bottom:8px"><i class="fas fa-random"></i></div>
                <div style="font-size:28px;font-weight:700;color:var(--primary)" id="resumenCompat">0</div>
                <div style="font-size:13px;color:#999">Compatibilidades</div>
            </div>
            <div class="card" style="text-align:center;padding:25px">
                <div style="font-size:32px;color:#fd7e14;margin-bottom:8px"><i class="fas fa-microchip"></i></div>
                <div style="font-size:28px;font-weight:700;color:var(--primary)" id="resumenConfigs">0</div>
                <div style="font-size:13px;color:#999">Configuraciones</div>
            </div>
            <div class="card" style="text-align:center;padding:25px">
                <div style="font-size:32px;color:#dc3545;margin-bottom:8px"><i class="fas fa-tools"></i></div>
                <div style="font-size:28px;font-weight:700;color:var(--primary)" id="resumenMant">0</div>
                <div style="font-size:13px;color:#999">Alertas activas</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="card">
                <h3><i class="fas fa-cubes"></i> Tipos de Cálculo Disponibles</h3>
                <div id="listaTiposCalculo" style="font-size:13px;color:#666;line-height:2">
                    <div><i class="fas fa-bolt" style="color:var(--accent);width:20px"></i> Motor Trifásico</div>
                    <div><i class="fas fa-bolt" style="color:#28a745;width:20px"></i> Motor Monofásico</div>
                    <div><i class="fas fa-lightbulb" style="color:#fd7e14;width:20px"></i> Carga Resistiva</div>
                    <div><i class="fas fa-chart-line" style="color:#dc3545;width:20px"></i> Variador de Frecuencia (VFD)</div>
                </div>
            </div>
            <div class="card">
                <h3><i class="fas fa-info-circle"></i> ¿Cómo usar el Asistente?</h3>
                <ol style="font-size:13px;color:#666;line-height:2;padding-left:20px">
                    <li><strong>Calculadora</strong> — Ingrese HP, voltaje y distancia para obtener protecciones y cable recomendado</li>
                    <li><strong>Compatibilidad</strong> — Busque alternativas de otras marcas para un producto</li>
                    <li><strong>Configurador</strong> — Arme un tablero completo con productos del catálogo</li>
                    <li><strong>Mantenimiento</strong> — Programe alertas para mantenimiento preventivo</li>
                </ol>
            </div>
        </div>
    </div>

    <div id="tab-calculadora" class="tab-content active">
        <div class="calculator-container">
            <div class="card">
                <h3><i class="fas fa-sliders-h"></i> Parámetros de Entrada</h3>
                <div class="form-group">
                    <label>Tipo de equipo</label>
                    <select id="tipoEquipo" onchange="actualizarFormulario()">
                        <option value="motor_trifasico">Motor Trifásico</option>
                        <option value="motor_monofasico">Motor Monofásico</option>
                        <option value="carga_resistiva">Carga Resistiva (Alumbrado/Calefacción)</option>
                        <option value="variador_vfd">Variador de Frecuencia (VFD)</option>
                    </select>
                </div>
                <div id="parametrosEntrada"></div>
                <button class="btn btn-primary" onclick="calcular()" style="margin-top:10px;width:100%">
                    <i class="fas fa-calculator"></i> Calcular
                </button>
            </div>

            <div class="card">
                <h3><i class="fas fa-chart-bar"></i> Resultados</h3>
                <div id="resultadoCalculo" class="result-box"></div>
                <div id="loadingCalculo" class="loading" style="display:none">
                    <i class="fas fa-spinner fa-spin"></i><br>Calculando...
                </div>
                <div id="resultadoDefault" style="text-align:center;padding:40px;color:#ccc">
                    <i class="fas fa-arrow-left" style="font-size:40px;display:block;margin-bottom:10px"></i>
                    Ingrese los parámetros y calcule
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-lightbulb"></i> Recomendaciones Técnicas</h3>
            <div id="recomendacionesTecnicas">
                <ul style="padding-left:20px;font-size:13px;color:#666;line-height:1.8">
                    <li>Use breakers curva D para motores (soportan picos de arranque)</li>
                    <li>Breakers curva C para cargas resistivas y alumbrado</li>
                    <li>Sobredimensione el contactor al 150% de la corriente nominal para categoría AC3</li>
                    <li>El cable debe calcularse considerando caída de tensión máxima del 3%</li>
                    <li>Para distancias >50m entre VFD y motor, use cable apantallado</li>
                </ul>
            </div>
        </div>
    </div>

    <div id="tab-compatibilidad" class="tab-content">
        <div class="card">
            <h3><i class="fas fa-search"></i> Buscar Compatibilidad entre Marcas</h3>
            <div class="search-box">
                <select id="compatCategoria" onchange="cargarMarcas()">
                    <option value="">Todas las categorías</option>
                </select>
                <select id="compatMarca">
                    <option value="">Todas las marcas</option>
                </select>
                <input type="text" id="compatModelo" placeholder="Buscar modelo...">
                <button class="btn btn-primary" onclick="buscarCompatibilidad()"><i class="fas fa-search"></i> Buscar</button>
                <button class="btn btn-secondary" onclick="limpiarCompatibilidad()"><i class="fas fa-eraser"></i></button>
            </div>
            <div id="compatResultados" style="overflow-x:auto"></div>
        </div>

        <div class="card">
            <h3><i class="fas fa-exchange-alt"></i> Compatibilidad por Producto</h3>
            <div class="search-box">
                <input type="text" id="compatProductoSearch" placeholder="Buscar por nombre o ID del producto..." style="flex:1">
                <button class="btn btn-primary" onclick="buscarCompatPorProducto()"><i class="fas fa-search"></i> Buscar</button>
                <button class="btn btn-secondary" onclick="limpiarCompatProducto()"><i class="fas fa-eraser"></i></button>
            </div>
            <div id="compatProductoResultados"></div>
        </div>
    </div>

    <div id="tab-configurador" class="tab-content">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="card">
                <h3><i class="fas fa-plus-circle"></i> Nueva Configuración</h3>
                <div class="form-group">
                    <label>Nombre del proyecto</label>
                    <input type="text" id="confNombre" placeholder="Ej: Bomba de agua 5HP">
                </div>
                <div class="form-group">
                    <label>Aplicación</label>
                    <select id="confAplicacion">
                        <option value="Bomba de agua">Bomba de agua</option>
                        <option value="Compresor">Compresor</option>
                        <option value="Cinta transportadora">Cinta transportadora</option>
                        <option value="Ventilador industrial">Ventilador industrial</option>
                        <option value="Elevador">Elevador / Montacargas</option>
                        <option value="Mezclador">Mezclador / Agitador</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>HP del motor</label>
                    <input type="number" id="confHp" step="0.5" placeholder="Ej: 5">
                </div>
                <div class="form-group">
                    <label>Voltaje (V)</label>
                    <input type="number" id="confVoltaje" value="220" placeholder="220">
                </div>
                <div class="form-group">
                    <label>Descripción del proyecto</label>
                    <input type="text" id="confDescripcion" placeholder="Ej: Bomba centrífuga para tanque de 50m³" style="font-size:13px">
                </div>
                <h4 style="font-size:14px;color:var(--primary);margin:10px 0">Componentes del tablero</h4>
                <div style="margin-bottom:8px">
                    <input type="text" id="confBuscarProducto" placeholder="🔍 Buscar producto por nombre..." style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-size:13px">
                </div>
                <div id="componentesTablero">
                </div>
                <button class="btn btn-sm btn-secondary" onclick="agregarComponente()" style="margin:10px 0">
                    <i class="fas fa-plus"></i> Agregar componente
                </button>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #ddd">
                    <strong>Total estimado: Bs <span id="confTotal">0.00</span></strong>
                    <button class="btn btn-success" onclick="guardarConfiguracion()"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-folder-open"></i> Configuraciones Guardadas</h3>
                <div id="listaConfiguraciones">
                    <div class="loading"><i class="fas fa-spinner fa-spin"></i><br>Cargando...</div>
                </div>
            </div>
        </div>
    </div>

    <div id="tab-mantenimiento" class="tab-content">
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:15px">
            <a href="/proyecto/admin/generar_reporte_mantenimiento.php" target="_blank" class="btn btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;background:#dc3545"><i class="fas fa-file-pdf"></i> Reporte PDF</a>
            <a href="/proyecto/admin/generar_reporte_mantenimiento.php?excel=1" target="_blank" class="btn btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;background:#28a745"><i class="fas fa-file-excel"></i> Exportar Excel</a>
            <button class="btn btn-primary" onclick="window.print()" style="display:inline-flex;align-items:center;gap:6px;background:#6c757d"><i class="fas fa-print"></i> Imprimir</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
            <div class="card">
                <h3><i class="fas fa-bell"></i> Alertas de Mantenimiento</h3>
                <div id="alertasMantenimiento">
                    <div class="loading"><i class="fas fa-spinner fa-spin"></i><br>Cargando alertas...</div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-calendar-plus"></i> Programar Alerta</h3>
                <div class="form-group">
                    <label>Producto (ID)</label>
                    <input type="number" id="mantProductoId" placeholder="Ej: 26">
                </div>
                <div class="form-group">
                    <label>Intervalo de mantenimiento</label>
                    <select id="mantIntervalo">
                        <option value="30">Cada mes (30 días)</option>
                        <option value="90" selected>Cada 3 meses (90 días)</option>
                        <option value="180">Cada 6 meses (180 días)</option>
                        <option value="365">Cada año (365 días)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de compra/instalación</label>
                    <input type="date" id="mantFechaCompra">
                </div>
                <button class="btn btn-primary" onclick="programarMantenimiento()" style="width:100%">
                    <i class="fas fa-calendar-check"></i> Programar
                </button>
                <hr>
                <h4 style="font-size:14px;color:var(--primary);margin-bottom:10px">Recomendaciones por tipo</h4>
                <div id="recomendacionesMantenimiento" style="font-size:13px;color:#666"></div>
            </div>
        </div>
    </div>
</div>

<script>
let productosCache = [];

function toast(msg, tipo) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:8px;color:white;font-size:14px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.2);animation:slideIn .3s;background:${tipo === 'ok' ? '#28a745' : tipo === 'err' ? '#dc3545' : '#ffc107'}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; t.style.transition = '.3s'; setTimeout(() => t.remove(), 300); }, 3000);
}

async function fetchApi(url, data = null) {
    const options = { headers: { 'Content-Type': 'application/json' } };
    if (data) { options.method = 'POST'; options.body = JSON.stringify(data); }
    try {
        const res = await fetch(url, options);
        return res.json();
    } catch (e) {
        return { success: false, error: 'Error de conexión' };
    }
}

function showTab(tab) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelector(`.tab[onclick*="'${tab}'"]`).classList.add('active');
    if (tab === 'resumen') cargarResumen();
    if (tab === 'configurador') { cargarConfiguraciones(); document.getElementById('componentesTablero').innerHTML = ''; agregarComponente(); }
    if (tab === 'mantenimiento') cargarAlertas();
    if (tab === 'compatibilidad') { cargarCategoriasCompat(); cargarRecomendacionesMant(); }
}

async function cargarResumen() {
    const [comp, configs, mant] = await Promise.all([
        fetchApi('/proyecto/asistente/compatibilidad.php'),
        fetchApi('/proyecto/asistente/configurador.php'),
        fetchApi('/proyecto/asistente/mantenimiento.php')
    ]);
    document.getElementById('resumenCalculos').textContent = 4;
    document.getElementById('resumenCompat').textContent = comp.success && comp.resumen ? comp.resumen.reduce((a, c) => a + parseInt(c.total), 0) : '...';
    document.getElementById('resumenConfigs').textContent = configs.success ? configs.total_configuraciones : '...';
    document.getElementById('resumenMant').textContent = mant.success ? mant.vencidas_ahora : '...';
}

function actualizarFormulario() {
    const tipo = document.getElementById('tipoEquipo').value;
    const html = {
        motor_trifasico: `
            <div class="form-group"><label>HP del motor</label><input type="number" id="calcHp" step="0.5" placeholder="Ej: 5"></div>
            <div class="form-group"><label>Voltaje (V)</label><input type="number" id="calcVoltaje" value="220"></div>
            <div class="form-group"><label>Distancia al motor (m)</label><input type="number" id="calcDistancia" value="50"></div>
            <div class="form-group"><label>Factor de potencia (0.80 - 0.95)</label><input type="number" id="calcFp" step="0.01" value="0.85"></div>
            <div class="form-group"><label>Eficiencia (0.85 - 0.95)</label><input type="number" id="calcEficiencia" step="0.01" value="0.90"></div>`,
        motor_monofasico: `
            <div class="form-group"><label>HP del motor</label><input type="number" id="calcHp" step="0.5" placeholder="Ej: 1.5"></div>
            <div class="form-group"><label>Voltaje (V)</label><input type="number" id="calcVoltaje" value="115"></div>
            <div class="form-group"><label>Distancia (m)</label><input type="number" id="calcDistancia" value="30"></div>
            <div class="form-group"><label>Factor de potencia</label><input type="number" id="calcFp" step="0.01" value="0.80"></div>
            <div class="form-group"><label>Eficiencia</label><input type="number" id="calcEficiencia" step="0.01" value="0.80"></div>`,
        carga_resistiva: `
            <div class="form-group"><label>Potencia (Watts)</label><input type="number" id="calcPotencia" placeholder="Ej: 3000"></div>
            <div class="form-group"><label>Voltaje (V)</label><input type="number" id="calcVoltaje" value="220"></div>
            <div class="form-group"><label>Fases</label><select id="calcFases"><option value="1">Monofásico</option><option value="3">Trifásico</option></select></div>
            <div class="form-group"><label>Distancia (m)</label><input type="number" id="calcDistancia" value="30"></div>`,
        variador_vfd: `
            <div class="form-group"><label>HP del motor</label><input type="number" id="calcHp" step="0.5" placeholder="Ej: 5"></div>
            <div class="form-group"><label>Voltaje (V)</label><input type="number" id="calcVoltaje" value="220"></div>
            <div class="form-group"><label>Fases de entrada</label><select id="calcFases"><option value="3">Trifásico</option><option value="1">Monofásico</option></select></div>`,
    };
    document.getElementById('parametrosEntrada').innerHTML = html[tipo] || '';
}

function getParametros() {
    const tipo = document.getElementById('tipoEquipo').value;
    const params = { tipo };
    if (tipo.includes('motor')) {
        params.hp = +document.getElementById('calcHp').value || 0;
        params.voltaje = +document.getElementById('calcVoltaje').value || 0;
        params.distancia = +document.getElementById('calcDistancia').value || 0;
        params.factor_potencia = +document.getElementById('calcFp').value || 0.85;
        params.eficiencia = +document.getElementById('calcEficiencia').value || 0.90;
    } else if (tipo === 'carga_resistiva') {
        params.potencia_w = +document.getElementById('calcPotencia').value || 0;
        params.voltaje = +document.getElementById('calcVoltaje').value || 0;
        params.fases = +document.getElementById('calcFases').value || 1;
        params.distancia = +document.getElementById('calcDistancia').value || 0;
    } else if (tipo === 'variador_vfd') {
        params.hp = +document.getElementById('calcHp').value || 0;
        params.voltaje = +document.getElementById('calcVoltaje').value || 0;
        params.fases = +document.getElementById('calcFases').value || 3;
    }
    return params;
}

function renderResultados(r) {
    if (!r.success) return `<div style="padding:15px;background:#f8d7da;border-radius:6px;color:#721c24">${r.error || 'Error'}</div>`;
    let html = '';
    for (const [key, val] of Object.entries(r.resultados)) {
        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        html += `<div class="result-item"><span class="label">${label}</span><span class="value highlight">${val}</span></div>`;
    }
    if (r.productos_sugeridos) {
        html += '<div class="result-item" style="border-top:2px solid var(--accent);margin-top:8px;padding-top:8px"><span style="font-weight:700;color:var(--accent);font-size:13px">Productos sugeridos del catálogo</span></div>';
        for (const [cat, items] of Object.entries(r.productos_sugeridos)) {
            if (typeof items === 'string') {
                html += `<div class="result-item"><span class="label">${cat.replace(/_/g, ' ')}</span><span class="value">${items}</span></div>`;
            } else if (Array.isArray(items) && items.length) {
                html += '<div class="product-suggest">';
                items.forEach(p => {
                    const st = p.stock < 5 ? '#dc3545' : p.stock < 15 ? '#fd7e14' : '#28a745';
                    html += `<div class="product-item"><div class="name">${p.name}</div><div class="price">Bs ${(+p.price).toFixed(2)}</div><div class="stock" style="color:${st}">Stock: ${p.stock}</div></div>`;
                });
                html += '</div>';
            }
        }
        html += '<button class="btn btn-sm btn-secondary" onclick="window.print()" style="margin-top:10px"><i class="fas fa-print"></i> Imprimir</button>';
    }
    if (r.resultados && r.resultados.recomendaciones) {
        html += '<div style="margin-top:10px;padding:10px;background:#fff3cd;border-radius:6px;font-size:12px;line-height:1.6">';
        r.resultados.recomendaciones.forEach(rec => { html += `<div>• ${rec}</div>`; });
        html += '</div>';
    }
    return html;
}

async function calcular() {
    document.getElementById('resultadoDefault').style.display = 'none';
    document.getElementById('loadingCalculo').style.display = 'block';
    document.getElementById('resultadoCalculo').classList.remove('show');
    const params = getParametros();
    const res = await fetchApi('/proyecto/asistente/calcular.php', params);
    document.getElementById('loadingCalculo').style.display = 'none';
    document.getElementById('resultadoCalculo').innerHTML = renderResultados(res);
    document.getElementById('resultadoCalculo').classList.add('show');
}

async function cargarCategoriasCompat() {
    const res = await fetchApi('/proyecto/asistente/compatibilidad.php?accion=categorias');
    if (res.success) {
        document.getElementById('compatCategoria').innerHTML = '<option value="">Todas las categorías</option>' + res.categorias.map(c => `<option value="${c}">${c}</option>`).join('');
    }
}

async function cargarMarcas() {
    const cat = document.getElementById('compatCategoria').value;
    const res = await fetchApi('/proyecto/asistente/compatibilidad.php?accion=marcas&categoria=' + cat);
    if (res.success) {
        document.getElementById('compatMarca').innerHTML = '<option value="">Todas las marcas</option>' + res.marcas.map(m => `<option value="${m}">${m}</option>`).join('');
    }
}

async function buscarCompatibilidad() {
    const p = new URLSearchParams({ accion: 'buscar' });
    const cat = document.getElementById('compatCategoria').value;
    const marca = document.getElementById('compatMarca').value;
    const modelo = document.getElementById('compatModelo').value;
    if (cat) p.set('categoria', cat);
    if (marca) p.set('marca', marca);
    if (modelo) p.set('modelo', modelo);
    const res = await fetchApi('/proyecto/asistente/compatibilidad.php?' + p);
    if (res.success && res.resultados.length) {
        let html = '<table class="compat-table"><thead><tr><th>Categoría</th><th>Marca A</th><th>Modelo A</th><th></th><th>Marca B</th><th>Modelo B</th><th>Tipo</th><th>Notas</th></tr></thead><tbody>';
        res.resultados.forEach(r => {
            html += `<tr><td>${r.categoria}</td><td>${r.marca_a}</td><td>${r.modelo_a}</td><td><i class="fas fa-arrow-right"></i></td><td>${r.marca_b}</td><td>${r.modelo_b}</td><td><span class="badge-comp badge-${r.tipo_compatibilidad}">${r.tipo_compatibilidad}</span></td><td style="font-size:11px;color:#666">${r.notas || ''}</td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('compatResultados').innerHTML = html;
    } else {
        document.getElementById('compatResultados').innerHTML = '<div style="text-align:center;padding:30px;color:#999">Sin resultados. Intente otra búsqueda.</div>';
    }
}

async function buscarCompatPorProducto() {
    const compatProductoId = document.getElementById('compatProductoId');
    const id = (compatProductoId ? compatProductoId.value : '') || document.getElementById('compatProductoSearch').value;
    if (!id) return;
    const isNum = /^\d+$/.test(id);
    const url = isNum ? `accion=producto&producto_id=${id}` : `accion=buscar&modelo=${id}`;
    const res = await fetchApi('/proyecto/asistente/compatibilidad.php?' + url);
    if (res.success && res.producto) {
        let html = `<div class="card"><h4>${res.producto.name}</h4><p style="font-size:13px;color:#666">Categoría: ${res.producto.category} | Marca: ${res.marca_detectada}</p>`;
        if (res.compatibilidades && res.compatibilidades.length) {
            html += '<h5 style="margin:10px 0;font-size:13px">Compatibilidades:</h5><table class="compat-table"><thead><tr><th>Marca</th><th>Modelo</th><th>Tipo</th><th>Notas</th></tr></thead><tbody>';
            res.compatibilidades.forEach(c => {
                html += `<tr><td>${c.marca_b}</td><td>${c.modelo_b}</td><td><span class="badge-comp badge-${c.tipo_compatibilidad}">${c.tipo_compatibilidad}</span></td><td style="font-size:11px;color:#666">${c.notas || ''}</td></tr>`;
            });
            html += '</tbody></table>';
        }
        if (res.alternativas && res.alternativas.length) {
            html += '<h5 style="margin:10px 0;font-size:13px">Alternativas:</h5><div class="product-suggest">';
            res.alternativas.forEach(p => {
                html += `<div class="product-item"><div class="name">${p.name}</div><div class="price">Bs ${(+p.price).toFixed(2)}</div><div class="stock">Stock: ${p.stock}</div></div>`;
            });
            html += '</div>';
        }
        html += '</div>';
        document.getElementById('compatProductoResultados').innerHTML = html;
    } else if (res.resultados && res.resultados.length) {
        let html = '<table class="compat-table"><thead><tr><th>Marca A</th><th>Modelo A</th><th>→</th><th>Marca B</th><th>Modelo B</th><th>Tipo</th></tr></thead><tbody>';
        res.resultados.forEach(r => {
            html += `<tr><td>${r.marca_a}</td><td>${r.modelo_a}</td><td><i class="fas fa-arrow-right"></i></td><td>${r.marca_b}</td><td>${r.modelo_b}</td><td><span class="badge-comp badge-${r.tipo_compatibilidad}">${r.tipo_compatibilidad}</span></td></tr>`;
        });
        html += '</tbody></table>';
        document.getElementById('compatProductoResultados').innerHTML = html;
    } else {
        document.getElementById('compatProductoResultados').innerHTML = '<div style="color:#999;padding:20px;text-align:center">No se encontraron resultados</div>';
    }
}

function limpiarCompatibilidad() {
    document.getElementById('compatCategoria').value = '';
    document.getElementById('compatMarca').innerHTML = '<option value="">Todas las marcas</option>';
    document.getElementById('compatModelo').value = '';
    document.getElementById('compatResultados').innerHTML = '';
}

function limpiarCompatProducto() {
    document.getElementById('compatProductoSearch').value = '';
    document.getElementById('compatProductoResultados').innerHTML = '';
}

async function cargarProductos() {
    if (productosCache.length) return;
    try {
        const res = await fetch('/proyecto/producto/obtener_productos.php');
        const data = await res.json();
        productosCache = Array.isArray(data) ? data : (data.productos || data.products || data.data || []);
    } catch (e) {
        productosCache = [];
    }
}

function filtrarProductos(texto) {
    if (!texto) return productosCache;
    const t = texto.toLowerCase();
    return productosCache.filter(p => (p.name || '').toLowerCase().includes(t) || (p.category || '').toLowerCase().includes(t));
}

async function agregarComponente(productoId) {
    await cargarProductos();
    const container = document.getElementById('componentesTablero');
    const div = document.createElement('div');
    div.className = 'component-row';
    div.style.cssText = 'display:flex;gap:8px;margin-bottom:6px';
    let opts = '<option value="">Seleccionar...</option>';
    productosCache.forEach(p => {
        const sel = productoId && p.id == productoId ? 'selected' : '';
        opts += `<option value="${p.id}" data-precio="${p.price}" ${sel}>${p.name}</option>`;
    });
    div.innerHTML = `
        <select class="comp-select" style="flex:1;padding:6px;border:1px solid #ddd;border-radius:6px;font-size:13px" onchange="actualizarTotal()">${opts}</select>
        <input type="number" class="comp-cant" value="1" min="1" style="width:70px;padding:6px;border:1px solid #ddd;border-radius:6px;text-align:center;font-size:14px" onchange="actualizarTotal()" step="1">
        <button class="btn btn-sm btn-secondary" onclick="this.parentElement.remove();actualizarTotal()"><i class="fas fa-times"></i></button>`;
    container.appendChild(div);
    actualizarTotal();
}

function actualizarTotal() {
    let total = 0;
    document.querySelectorAll('.component-row').forEach(row => {
        const sel = row.querySelector('.comp-select');
        const cant = +row.querySelector('.comp-cant').value || 1;
        if (sel && sel.selectedIndex > 0) {
            total += (+(sel.options[sel.selectedIndex].dataset.precio || 0)) * cant;
        }
    });
    document.getElementById('confTotal').textContent = total.toFixed(2);
}

document.getElementById('confBuscarProducto')?.addEventListener('input', function() {
    const t = this.value.toLowerCase();
    document.querySelectorAll('.component-row .comp-select').forEach(sel => {
        sel.querySelectorAll('option').forEach(o => {
            o.style.display = (!t || o.text.toLowerCase().includes(t) || !o.value) ? '' : 'none';
        });
    });
});

async function guardarConfiguracion() {
    const componentes = [];
    document.querySelectorAll('.component-row').forEach(row => {
        const sel = row.querySelector('.comp-select');
        if (sel && sel.selectedIndex > 0) {
            componentes.push({ id: sel.value, name: sel.options[sel.selectedIndex].text, cantidad: +row.querySelector('.comp-cant').value || 1, precio: +(sel.options[sel.selectedIndex].dataset.precio || 0) });
        }
    });
    if (!componentes.length) { toast('Agregue al menos un componente', 'err'); return; }
    const data = {
        accion: 'guardar',
        nombre: document.getElementById('confNombre').value || 'Configuración',
        aplicacion: document.getElementById('confAplicacion').value,
        descripcion: document.getElementById('confDescripcion')?.value || '',
        hp: +document.getElementById('confHp').value || 0,
        voltaje: +document.getElementById('confVoltaje').value || 220,
        componentes, total_estimado: +document.getElementById('confTotal').textContent
    };
    const res = await fetchApi('/proyecto/asistente/configurador.php', data);
    if (res.success) { toast('✓ Configuración guardada', 'ok'); cargarConfiguraciones(); }
    else { toast('Error: ' + (res.error || 'Desconocido'), 'err'); }
}

async function cargarConfiguraciones() {
    const res = await fetchApi('/proyecto/asistente/configurador.php?accion=listar');
    if (res.success && res.configuraciones.length) {
        let html = '<div class="config-list">';
        res.configuraciones.forEach(c => {
            html += `<div class="config-card">
                <h4>${c.nombre}</h4>
                <div class="meta">${c.aplicacion} · ${new Date(c.created_at).toLocaleDateString()}</div>
                <div class="total">Bs ${(+c.total_estimado).toFixed(2)}</div>
                <div style="margin-top:8px">
                    <button class="btn btn-sm btn-primary" onclick="cargarConfig(${c.id})"><i class="fas fa-folder-open"></i></button>
                    <button class="btn btn-sm btn-secondary" onclick="eliminarConfig(${c.id})"><i class="fas fa-trash"></i></button>
                </div>
            </div>`;
        });
        html += '</div>';
        document.getElementById('listaConfiguraciones').innerHTML = html;
    } else {
        document.getElementById('listaConfiguraciones').innerHTML = '<div style="text-align:center;padding:30px;color:#999">No hay configuraciones guardadas</div>';
    }
}

async function cargarConfig(id) {
    const res = await fetchApi('/proyecto/asistente/configurador.php?accion=cargar&id=' + id);
    if (res.success) {
        const c = res.configuracion;
        const params = typeof c.parametros === 'string' ? JSON.parse(c.parametros) : (c.parametros || {});
        const comps = typeof c.componentes === 'string' ? JSON.parse(c.componentes) : (c.componentes || []);
        document.getElementById('confNombre').value = c.nombre;
        document.getElementById('confAplicacion').value = c.aplicacion;
        document.getElementById('confHp').value = params.hp || '';
        document.getElementById('confVoltaje').value = params.voltaje || 220;
        document.getElementById('componentesTablero').innerHTML = '';
        for (const comp of comps) {
            await agregarComponente(comp.id);
        }
        toast('Configuración cargada: ' + c.nombre, 'ok');
        showTab('configurador');
    }
}

async function eliminarConfig(id) {
    if (!confirm('¿Eliminar esta configuración?')) return;
    const res = await fetchApi('/proyecto/asistente/configurador.php', { accion: 'eliminar', id });
    if (res.success) { toast('Configuración eliminada', 'ok'); cargarConfiguraciones(); }
}

async function cargarAlertas() {
    const res = await fetchApi('/proyecto/asistente/mantenimiento.php?accion=pendientes');
    if (res.success) {
        let html = '';
        if (res.vencidas.length) {
            html += '<h4 style="font-size:14px;color:#dc3545;margin:0 0 10px"><i class="fas fa-exclamation-triangle"></i> Vencidas</h4>';
            res.vencidas.forEach(a => {
                html += `<div class="maintenance-alert vencida"><div class="info"><strong>${a.producto_nombre}</strong>Vencida: ${a.proximo_mantenimiento}</div><button class="btn btn-sm btn-success" onclick="completarAlerta(${a.id})">Completar</button></div>`;
            });
        }
        if (res.proximas.length) {
            html += '<h4 style="font-size:14px;color:#856404;margin:15px 0 10px"><i class="fas fa-clock"></i> Próximas</h4>';
            res.proximas.forEach(a => {
                html += `<div class="maintenance-alert"><div class="info"><strong>${a.producto_nombre}</strong>Próximo: ${a.proximo_mantenimiento}</div></div>`;
            });
        }
        if (!html) html = '<div style="text-align:center;padding:30px;color:#999">No hay alertas de mantenimiento</div>';
        document.getElementById('alertasMantenimiento').innerHTML = html;
    }
}

async function completarAlerta(id) {
    const res = await fetchApi('/proyecto/asistente/mantenimiento.php', { accion: 'completar', id });
    if (res.success) { toast('Mantenimiento completado', 'ok'); cargarAlertas(); }
}

async function programarMantenimiento() {
    const data = {
        accion: 'generar',
        producto_id: +document.getElementById('mantProductoId').value,
        intervalo_dias: +document.getElementById('mantIntervalo').value,
        fecha_compra: document.getElementById('mantFechaCompra').value || new Date().toISOString().split('T')[0]
    };
    if (!data.producto_id) { toast('Ingrese un ID de producto', 'err'); return; }
    const res = await fetchApi('/proyecto/asistente/mantenimiento.php', data);
    if (res.success) {
        let msg = '✓ Alerta: ' + res.proximo_mantenimiento;
        if (res.telegram_notificado) msg += ' | Telegram OK';
        else if (res.telegram_error) msg += ' | Telegram: ' + res.telegram_error;
        toast(msg, 'ok');
        cargarAlertas();
    } else { toast('Error: ' + (res.error || ''), 'err'); }
}

async function cargarRecomendacionesMant() {
    const res = await fetchApi('/proyecto/asistente/mantenimiento.php?accion=intervalos');
    if (res.success && res.recomendaciones) {
        document.getElementById('recomendacionesMantenimiento').innerHTML = '<ul style="padding-left:20px;line-height:1.8">' +
            Object.entries(res.recomendaciones).map(([k, v]) => `<li>${k}: cada ${v} días</li>`).join('') + '</ul>';
    }
}

actualizarFormulario();
document.getElementById('mantFechaCompra').valueAsDate = new Date();
cargarProductos();
</script>
</body>
</html>
