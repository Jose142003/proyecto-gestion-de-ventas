<?php
session_name('CLIENTSESSID');
session_start();
require_once __DIR__ . '/../config/i18n.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
\I18n::load($locale);
?><!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Método de Pago | Proyectos Industriales</title>
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
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --light: #ecf0f1;
            --dark: #1a1a1a;
            --success: #27ae60;
            --warning: #f39c12;
            --error: #e74c3c;
        }
        body.dark-mode {
            background-color: #121212;
        }
        body.dark-mode .payment-container {
            background: #1e1e2e;
        }
        body.dark-mode h2 { color: #7EBDE9; }
        body.dark-mode .cart-summary { background: #2a2a3a; }
        body.dark-mode .cart-summary h3 { color: #3C91ED; }
        body.dark-mode .cart-table th { background: #2a2a3a; color: #7EBDE9; }
        body.dark-mode .cart-table td { color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .client-type-section { background: #2a2a3a; }
        body.dark-mode .client-type-section h3 { color: #3C91ED; }
        body.dark-mode .client-option { background: #1e1e2e; border-color: #4a4a4a; color: #F3F3F3; }
        body.dark-mode .client-option label { color: #7EBDE9; }
        body.dark-mode .client-option small { color: #aaa; }
        body.dark-mode .payment-card { background: #1e1e2e; border-color: #4a4a4a; color: #F3F3F3; }
        body.dark-mode .payment-card i { color: #3C91ED; }
        body.dark-mode .mixed-payment-section { background: #2a2a3a; }
        body.dark-mode .mixed-payment-section h4 { color: #7EBDE9; }
        body.dark-mode .mixed-input input { background: #1e1e2e; color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .currency-selector select { background: #1e1e2e; color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .mixed-total { background: #1e1e2e; }
        body.dark-mode .reference-field input { background: #1e1e2e; color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .text-muted { color: #aaa !important; }
        body.dark-mode .reference-field small { color: #aaa; }
        body.dark-mode .error-message { background: #3a1a1a; }
        body.dark-mode .currency-rate-info { color: #aaa; }
        body.dark-mode .totals-table tr td { color: #F3F3F3; }
        body.dark-mode .cart-summary h3 { border-bottom-color: #3C91ED; }
        body.dark-mode .totals-table .final-total { color: #F3F3F3; }
        body.dark-mode .totals-table .final-total td { color: #F3F3F3; }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', 'Helvetica Neue', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 12px;
        }
        
        .payment-container {
            max-width: 650px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            padding: 20px 16px;
            text-align: center;
        }
        
        /* Para tablets y pantallas más grandes */
        @media (min-width: 768px) {
            .payment-container {
                max-width: 800px;
                padding: 30px;
            }
            body {
                padding: 20px;
            }
        }
        
        h2 {
            color: var(--primary);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 1.6rem;
        }
        
        @media (max-width: 480px) {
            h2 {
                font-size: 1.4rem;
                margin-bottom: 16px;
            }
        }
        
        .cart-summary {
            background: #fdfdfd;
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: left;
            overflow-x: auto;
        }
        
        .cart-summary h3 {
            font-size: 1rem;
            margin-bottom: 12px;
            color: var(--secondary);
            border-bottom: 2px solid var(--accent);
            display: inline-block;
            padding-bottom: 4px;
        }
        
        /* Tabla responsive - scroll horizontal en móvil */
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 0.85rem;
            min-width: 280px;
        }
        
        @media (max-width: 480px) {
            .cart-table {
                font-size: 0.75rem;
            }
            .cart-table th, 
            .cart-table td {
                padding: 8px 6px;
            }
        }
        
        .cart-table th {
            background-color: var(--light);
            padding: 10px;
            text-align: left;
            color: var(--primary);
            font-weight: 600;
        }
        
        .cart-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .cart-table tr:last-child td {
            border-bottom: none;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals-table {
            width: 100%;
            margin-top: 12px;
            border-top: 2px solid var(--primary);
            padding-top: 12px;
            font-size: 0.9rem;
        }
        
        .totals-table tr td {
            padding: 6px 0;
        }
        
        .totals-table tr td:last-child {
            text-align: right;
            font-weight: 500;
        }
        
        .totals-table .final-total {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--dark);
            border-top: 1px solid #cbd5e0;
            margin-top: 5px;
            padding-top: 10px;
        }
        
        /* Tipo de Cliente - mejor para móvil */
        .client-type-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .client-type-section h3 {
            font-size: 1rem;
            margin-bottom: 12px;
            color: var(--secondary);
            border-bottom: 2px solid var(--accent);
            display: inline-block;
        }
        
        .client-options {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        
        @media (min-width: 480px) {
            .client-options {
                flex-direction: row;
            }
        }
        
        .client-option {
            flex: 1;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .client-option:hover {
            border-color: var(--accent);
            background: #f0f9ff;
        }
        
        .client-option.selected {
            border-color: var(--success);
            background: #f0fff4;
        }
        
        .client-option input[type="radio"] {
            margin-right: 8px;
            transform: scale(1.1);
        }
        
        .client-option label {
            font-weight: 600;
            color: var(--primary);
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .client-option small {
            display: block;
            margin-top: 6px;
            color: #718096;
            font-size: 0.75rem;
        }
        
        /* Métodos de Pago - Grid adaptable */
        .payment-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 16px;
        }
        
        @media (min-width: 500px) {
            .payment-options {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }
        }
        
        .payment-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            padding: 14px 8px;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            color: var(--primary);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .payment-card:hover {
            border-color: var(--accent);
            background: #f0f9ff;
            transform: translateY(-2px);
        }
        
        .payment-card i {
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 8px;
        }
        
        @media (min-width: 480px) {
            .payment-card i {
                font-size: 2em;
            }
            .payment-card {
                padding: 20px 12px;
            }
        }
        
        .payment-card span {
            font-weight: 600;
            margin-bottom: 4px;
            font-size: 0.8rem;
        }
        
        @media (min-width: 480px) {
            .payment-card span {
                font-size: 0.9rem;
            }
        }
        
        .payment-card small {
            font-size: 0.7rem;
            color: #718096;
            display: block;
        }
        
        .badge-nuevo {
            background: var(--warning);
            color: white;
            font-size: 0.6rem;
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 5px;
            display: inline-block;
        }
        
        /* Pago Mixto - mejor para móvil */
        .mixed-payment-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
            text-align: left;
            display: none;
        }
        
        .mixed-payment-section h4 {
            color: var(--primary);
            margin-bottom: 12px;
            font-size: 0.95rem;
        }
        
        .mixed-input-group {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        
        @media (min-width: 480px) {
            .mixed-input-group {
                flex-direction: row;
            }
        }
        
        .mixed-input {
            flex: 1;
            min-width: 0;
        }
        
        .mixed-input label {
            display: block;
            font-size: 0.8rem;
            color: #4a5568;
            margin-bottom: 5px;
        }
        
        .mixed-input input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            -webkit-appearance: none;
            appearance: none;
        }
        
        .mixed-input input:focus {
            border-color: var(--accent);
            outline: none;
        }
        
        .currency-section {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e0;
        }
        
        .currency-selector {
            display: flex;
            gap: 10px;
            flex-direction: column;
            align-items: stretch;
        }
        
        @media (min-width: 480px) {
            .currency-selector {
                flex-direction: row;
                align-items: center;
            }
        }
        
        .currency-selector select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .currency-rate-info {
            font-size: 0.75rem;
            color: #666;
            margin-top: 8px;
        }
        
        .mixed-total {
            background: white;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 0.85rem;
        }
        
        .mixed-total-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
        }
        
        .mixed-total-row.total {
            border-top: 2px solid var(--primary);
            margin-top: 8px;
            padding-top: 8px;
            font-weight: bold;
            font-size: 0.95rem;
        }
        
        /* Botones */
        .btn-continuar {
            background: var(--success);
            color: white;
            border: none;
            padding: 14px 20px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
            font-size: 1rem;
            transition: background 0.3s ease;
            -webkit-tap-highlight-color: transparent;
        }
        
        .btn-continuar:active {
            transform: scale(0.98);
        }
        
        .btn-continuar:hover {
            background: #219a52;
        }
        
        .btn-continuar:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .btn-reintentar {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            margin-top: 10px;
            cursor: pointer;
            font-size: 0.9rem;
            width: auto;
        }
        
        /* Mensajes de error */
        .error-message {
            background: #fff5f5;
            color: var(--error);
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #feb2b2;
            margin-bottom: 20px;
            display: none;
            font-size: 0.85rem;
        }
        
        .reference-field {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        
        .reference-field label {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
            color: var(--primary);
            font-size: 0.85rem;
        }
        
        .reference-field input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .reference-field small {
            font-size: 0.7rem;
            color: #666;
        }
        
        .loading {
            color: #718096;
            padding: 20px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        /* Badges */
        .iva-badge, .retention-badge {
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
            margin-left: 5px;
        }
        
        .iva-badge {
            background: #e6f7ff;
            color: #0066cc;
        }
        
        .retention-badge {
            background: #fff3cd;
            color: #856404;
        }
        
        /* Ajustes para inputs en móvil */
        input, select, button {
            font-size: 16px !important; /* Previene zoom en iOS */
        }
        
        /* Mejora de touch targets */
        .payment-card, .client-option, .btn-continuar, .btn-reintentar {
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>

<div class="payment-container">
    <h2><?php echo \I18n::trans('checkout'); ?></h2>

    <div id="errorMessage" class="error-message"></div>

    <div class="cart-summary">
        <h3><i class="fas fa-shopping-basket"></i> <?php echo \I18n::trans('order_summary'); ?></h3>
        <div id="cartContent">
            <div class="loading"><i class="fas fa-spinner fa-spin"></i> <?php echo \I18n::trans('loading_products'); ?></div>
        </div>
    </div>

    <!-- Tipo de Cliente -->
    <div id="clientTypeSection" class="client-type-section" style="display:none;">
        <h3><i class="fas fa-user-tag"></i> <?php echo \I18n::trans('client_type'); ?></h3>
        <div class="client-options">
            <div class="client-option" onclick="selectClientType('regular')">
                <input type="radio" name="clientType" id="clientRegular" value="regular" checked>
                <label for="clientRegular"><?php echo \I18n::trans('regular_client'); ?></label>
                <small><?php echo \I18n::trans('includes_iva'); ?></small>
            </div>
            <div class="client-option" onclick="selectClientType('retencion')">
                <input type="radio" name="clientType" id="clientRetencion" value="retencion">
                <label for="clientRetencion"><?php echo \I18n::trans('retention_company'); ?></label>
                <small><?php echo \I18n::trans('iva_retention_75'); ?></small>
            </div>
        </div>
    </div>
                
    <!-- Métodos de Pago -->
    <div id="paymentMethods" style="display:none;">
        <p style="margin-bottom: 12px; color: #4a5568; font-size: 0.9rem;"><?php echo \I18n::trans('select_payment_method'); ?></p>
        <div class="payment-options">
            <a href="#" class="payment-card" onclick="selectPaymentMethod('transferencia'); return false;">
                <i class="fas fa-university"></i>
                <span><?php echo \I18n::trans('transfer'); ?></span>
                <small><?php echo \I18n::trans('national_banks'); ?></small>
            </a>
            
            <a href="#" class="payment-card" onclick="selectPaymentMethod('pago_movil'); return false;">
                <i class="fas fa-mobile-alt"></i>
                <span><?php echo \I18n::trans('mobile_payment'); ?></span>
                <small><?php echo \I18n::trans('from_your_bank'); ?></small>
            </a>
                
            <a href="#" class="payment-card" onclick="selectPaymentMethod('efectivo'); return false;">
                <i class="fas fa-money-bill-wave"></i>
                <span><?php echo \I18n::trans('cash_bolivars'); ?></span>
                <small><?php echo \I18n::trans('pay_at_store'); ?></small>
                <span class="badge-nuevo">NUEVO</span>
            </a>
            
            <a href="#" class="payment-card" onclick="selectPaymentMethod('mixto'); return false;">
                <i class="fas fa-sync-alt"></i>
                <span><?php echo \I18n::trans('mixed_payment'); ?></span>
                <small><?php echo \I18n::trans('transfer_cash'); ?></small>
                <span class="badge-nuevo">NUEVO</span>
            </a>
        </div>
    </div>
                    
    <!-- Sección para Pago Mixto -->
    <div id="mixedPaymentSection" class="mixed-payment-section">
        <h4><i class="fas fa-sync-alt"></i> <?php echo \I18n::trans('configure_mixed'); ?></h4>
        <p style="color: #666; margin-bottom: 12px; font-size: 0.8rem;">
            Indica cuánto pagarás por transferencia y cuánto en efectivo.
            El total debe coincidir con <strong id="mixedTotalReferencia">Bs. 0.00</strong>
        </p>
                    
        <div class="mixed-input-group">
            <div class="mixed-input">
                <label><?php echo \I18n::trans('transfer_amount'); ?></label>
                <input type="number" id="montoTransferencia" min="0" step="0.01" value="0" oninput="calcularPagoMixto()">
            </div>
            <div class="mixed-input">
                <label><?php echo \I18n::trans('cash_amount'); ?></label>
                <input type="number" id="montoEfectivo" min="0" step="0.01" value="0" oninput="calcularPagoMixto()">
            </div>
        </div>

        <div class="currency-section">
            <label style="font-weight: 500; display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-size: 0.85rem;">
                <i class="fas fa-dollar-sign"></i> <?php echo \I18n::trans('pay_in_foreign'); ?>
            </label>
            <div class="currency-selector">
                <select id="tipoDivisa" onchange="calcularDivisa()">
                    <option value="BS"><?php echo \I18n::trans('bolivars'); ?></option>
                    <option value="USD"><?php echo \I18n::trans('dollars'); ?></option>
                    <option value="EUR"><?php echo \I18n::trans('euros'); ?></option>
                </select>
                <div class="mixed-input" style="min-width: 0;">
                    <input type="number" id="montoDivisa" min="0" step="0.01" placeholder="Monto en divisa" oninput="calcularDivisa()">
                </div>
            </div>
            <div class="currency-rate-info" id="tasaInfo">
                💱 Tasa de cambio: 1 USD = 36.50 Bs. (aprox)
            </div>
        </div>
                    
        <div class="mixed-total">
            <div class="mixed-total-row">
                <span><?php echo \I18n::trans('total_to_pay'); ?></span>
                <span id="mixedTotal">Bs. 0.00</span>
            </div>
            <div class="mixed-total-row">
                <span>Transferencia:</span>
                <span id="mixedTransferencia">Bs. 0.00</span>
            </div>
            <div class="mixed-total-row">
                <span>Efectivo:</span>
                <span id="mixedEfectivo">Bs. 0.00</span>
            </div>
            <div class="mixed-total-row total">
                <span><?php echo \I18n::trans('verified_total'); ?></span>
                <span id="mixedVerificado">Bs. 0.00</span>
            </div>
        </div>
        
        <div class="reference-field" id="referenciaTransferenciaField" style="display: none;">
            <label><i class="fas fa-hashtag"></i> <?php echo \I18n::trans('ref_number_transfer'); ?></label>
            <input type="text" id="referenciaTransferencia" placeholder="Ej: 1234567890" maxlength="50">
            <small>Ingresa el número de referencia que te dio tu banco</small>
        </div>

        <div class="reference-field" id="referenciaPagoMovilField" style="display: none;">
            <label><i class="fas fa-hashtag"></i> <?php echo \I18n::trans('ref_number_mobile'); ?></label>
            <input type="text" id="referenciaPagoMovil" placeholder="Ej: 9876543210" maxlength="50">
            <small>Ingresa el número de referencia que te dio tu banco</small>
        </div>

        <div id="mixedError" style="color: var(--error); font-size: 0.8rem; margin: 10px 0; display: none;">
            <i class="fas fa-exclamation-circle"></i> <?php echo \I18n::trans('amounts_dont_match'); ?>
        </div>
        
        <button class="btn-continuar" onclick="continuarPagoMixto()" id="btnContinuarMixto" disabled>
            <i class="fas fa-arrow-right"></i> <?php echo \I18n::trans('continue_mixed'); ?>
        </button>
    </div>
</div>

<script>
// Constantes
const IVA_RATE = 0.16;
const RETENTION_RATE = 0.75;

// Tasas de cambio - obtenidas dinámicamente del servidor
let exchangeRates = {
    USD: 36.50,
    EUR: 39.80
};

// Cargar tasas desde el endpoint del servidor
(async function cargarTasas() {
    try {
        const resp = await fetch('/proyecto/tasas/bcv_scraper.php');
        const data = await resp.json();
        if (data.success && data.tasa_bcv) {
            exchangeRates.USD = data.tasa_bcv;
            exchangeRates.EUR = data.tasa_bcv * 1.04;
        }
    } catch (e) {
        console.warn('No se pudieron cargar tasas actualizadas, usando valores por defecto');
    }
})();

// Variables globales
let currentItems = [];
let currentTotal = 0;
let currentSubtotal = 0;
let currentIVA = 0;
let clientType = 'regular';
let selectedMethod = null;
let currentUserId = 1;

// ===== VERIFICACIÓN DE ADMINISTRADOR (AGREGADO) =====
async function verificarNoAdmin() {
    try {
        const response = await fetch('/proyecto/usuarios/verificar_sesion_cliente.php', {
            credentials: 'include',
            headers: { 'Cache-Control': 'no-cache' }
        });
        const data = await response.json();
        
        // Si es administrador, redirigir al panel
        if (data.is_admin === true || (data.user && data.user.rol === 'admin')) {
            alert('⚠️ Los administradores no pueden realizar compras. Redirigiendo al panel de administración.');
            window.location.href = '/proyecto/panel_admin/panel_admin.php';
            return false;
        }
        
        // Si no está autenticado como cliente, redirigir a login
        if (!data.success || !data.user) {
            alert('Debes iniciar sesión como cliente para realizar una compra.');
            window.location.href = '/proyecto/interfaz_usuario/login.html';
            return false;
        }
        
        return true;
    } catch (error) {
        console.error('Error verificando usuario:', error);
        return true; // Permitir continuar pero con advertencia
    }
}

document.addEventListener('DOMContentLoaded', async function() {
    // Verificar que no sea administrador ANTES de cargar cualquier cosa
    const puedeContinuar = await verificarNoAdmin();
    if (!puedeContinuar) return;
    
    obtenerUsuarioYCarrito();
    cargarTasasCambio();
});

async function cargarTasasCambio() {
    try {
        const tasaInfo = document.getElementById('tasaInfo');
        if (tasaInfo) {
            tasaInfo.innerHTML = `💱 Tasa: 1 USD = ${exchangeRates.USD.toFixed(2)} Bs. | 1 EUR = ${exchangeRates.EUR.toFixed(2)} Bs.`;
        }
    } catch (error) {
        console.log('Usando tasas por defecto');
    }
}

function calcularDivisa() {
    const tipoDivisa = document.getElementById('tipoDivisa').value;
    const montoDivisa = parseFloat(document.getElementById('montoDivisa').value) || 0;
    
    if (tipoDivisa !== 'BS' && montoDivisa > 0) {
        const tasa = exchangeRates[tipoDivisa];
        const montoBs = montoDivisa * tasa;
        const tasaInfo = document.getElementById('tasaInfo');
        tasaInfo.innerHTML = `💱 ${montoDivisa} ${tipoDivisa} = ${montoBs.toFixed(2)} Bs. (1 ${tipoDivisa} = ${tasa.toFixed(2)} Bs.)`;
    }
    
    calcularPagoMixto();
}

function selectPaymentMethod(method) {
    selectedMethod = method;
    
    document.getElementById('mixedPaymentSection').style.display = 'none';
    
    const totalPagar = calculateTotalWithClientType();
    
    const productosParaEnviar = currentItems.map(item => ({
        id: item.product_id || item.id,
        nombre: item.name,
        precio: parseFloat(item.price),
        cantidad: parseInt(item.quantity)
    }));
    
    const productosJson = encodeURIComponent(JSON.stringify(productosParaEnviar));
    
    if (method === 'mixto') {
        document.getElementById('mixedPaymentSection').style.display = 'block';
        document.getElementById('mixedTotalReferencia').textContent = `Bs. ${totalPagar.toFixed(2)}`;
        document.getElementById('mixedTotal').textContent = `Bs. ${totalPagar.toFixed(2)}`;
        
        document.getElementById('montoTransferencia').value = '0';
        document.getElementById('montoEfectivo').value = '0';
        document.getElementById('montoDivisa').value = '';
        document.getElementById('referenciaTransferencia').value = '';
        document.getElementById('referenciaPagoMovil').value = '';
        document.getElementById('mixedTransferencia').textContent = 'Bs. 0.00';
        document.getElementById('mixedEfectivo').textContent = 'Bs. 0.00';
        document.getElementById('mixedVerificado').textContent = 'Bs. 0.00';
        document.getElementById('btnContinuarMixto').disabled = true;
        document.getElementById('mixedError').style.display = 'none';
        document.getElementById('referenciaTransferenciaField').style.display = 'none';
        document.getElementById('referenciaPagoMovilField').style.display = 'none';
        
    } else if (method === 'efectivo') {
        window.location.href = `pedido_confirmado.php?total=${totalPagar.toFixed(2)}&clientType=${clientType}&metodo=efectivo&productos=${productosJson}&usuario_id=${currentUserId}`;
        
    } else if (method === 'transferencia') {
        localStorage.setItem('productos_temp', productosJson);
        localStorage.setItem('total_temp', totalPagar);
        localStorage.setItem('client_type_temp', clientType);
        window.location.href = `transferencia.php?total=${totalPagar.toFixed(2)}&clientType=${clientType}&metodo=transferencia`;
        
    } else if (method === 'pago_movil') {
        localStorage.setItem('productos_temp', productosJson);
        localStorage.setItem('total_temp', totalPagar);
        localStorage.setItem('client_type_temp', clientType);
        window.location.href = `pago_movil.php?total=${totalPagar.toFixed(2)}&clientType=${clientType}&metodo=pago_movil`;
    }
}

function calcularPagoMixto() {
    let montoTransferencia = parseFloat(document.getElementById('montoTransferencia').value) || 0;
    let montoEfectivo = parseFloat(document.getElementById('montoEfectivo').value) || 0;
    const totalPagar = calculateTotalWithClientType();
    
    const tipoDivisa = document.getElementById('tipoDivisa').value;
    const montoDivisa = parseFloat(document.getElementById('montoDivisa').value) || 0;
    
    if (tipoDivisa !== 'BS' && montoDivisa > 0) {
        const tasa = exchangeRates[tipoDivisa];
        const montoDivisaBs = montoDivisa * tasa;
        montoEfectivo += montoDivisaBs;
    }
    
    const suma = montoTransferencia + montoEfectivo;
    const diferencia = Math.abs(suma - totalPagar);
    
    document.getElementById('mixedTransferencia').textContent = `Bs. ${montoTransferencia.toFixed(2)}`;
    document.getElementById('mixedEfectivo').textContent = `Bs. ${montoEfectivo.toFixed(2)}`;
    document.getElementById('mixedVerificado').textContent = `Bs. ${suma.toFixed(2)}`;
    
    if (diferencia < 0.01 && montoTransferencia >= 0 && montoEfectivo >= 0) {
        document.getElementById('btnContinuarMixto').disabled = false;
        document.getElementById('mixedError').style.display = 'none';
    } else {
        document.getElementById('btnContinuarMixto').disabled = true;
        document.getElementById('mixedError').style.display = 'block';
        document.getElementById('mixedError').innerHTML = `<i class="fas fa-exclamation-circle"></i> Los montos no coinciden. Diferencia: ${Math.abs(suma - totalPagar).toFixed(2)} Bs.`;
    }
}

function continuarPagoMixto() {
    let montoTransferencia = parseFloat(document.getElementById('montoTransferencia').value) || 0;
    let montoEfectivo = parseFloat(document.getElementById('montoEfectivo').value) || 0;
    const totalPagar = calculateTotalWithClientType();
    const tipoDivisa = document.getElementById('tipoDivisa').value;
    const montoDivisa = parseFloat(document.getElementById('montoDivisa').value) || 0;
    
    const productosParaEnviar = currentItems.map(item => ({
        id: item.product_id || item.id,
        nombre: item.name,
        precio: parseFloat(item.price),
        cantidad: parseInt(item.quantity)
    }));
    
    const productosJson = encodeURIComponent(JSON.stringify(productosParaEnviar));
    
    if (tipoDivisa !== 'BS' && montoDivisa > 0) {
        const tasa = exchangeRates[tipoDivisa];
        montoEfectivo += montoDivisa * tasa;
    }
    
    window.location.href = `pedido_confirmado.php?total=${totalPagar.toFixed(2)}&transferencia=${montoTransferencia.toFixed(2)}&efectivo=${montoEfectivo.toFixed(2)}&clientType=${clientType}&tipoDivisa=${tipoDivisa}&montoDivisa=${montoDivisa}&metodo=mixto&productos=${productosJson}&usuario_id=${currentUserId}`;
}

function selectClientType(type) {
    clientType = type;
    
    document.getElementById('clientRegular').checked = (type === 'regular');
    document.getElementById('clientRetencion').checked = (type === 'retencion');
    
    const options = document.querySelectorAll('.client-option');
    options.forEach(opt => {
        opt.classList.remove('selected');
    });
    if (type === 'regular') {
        document.querySelector('[onclick="selectClientType(\'regular\')"]').classList.add('selected');
    } else {
        document.querySelector('[onclick="selectClientType(\'retencion\')"]').classList.add('selected');
    }
    
    if (currentItems.length > 0) {
        calculateTotals(currentItems);
        renderCart(currentItems);
    }
    
    if (selectedMethod === 'mixto') {
        const totalPagar = calculateTotalWithClientType();
        document.getElementById('mixedTotalReferencia').textContent = `Bs. ${totalPagar.toFixed(2)}`;
        document.getElementById('mixedTotal').textContent = `Bs. ${totalPagar.toFixed(2)}`;
        calcularPagoMixto();
    }
    
    localStorage.setItem('total_pagar', calculateTotalWithClientType());
    localStorage.setItem('client_type', clientType);
}

function calculateTotalWithClientType() {
    if (clientType === 'retencion') {
        const ivaReducido = currentIVA * (1 - RETENTION_RATE);
        return currentSubtotal + ivaReducido;
    } else {
        return currentTotal;
    }
}
    
function calculateTotals(items) {
    currentSubtotal = 0;
    currentIVA = 0;
    
    items.forEach(item => {
        const precio = parseFloat(item.price || item.precio || 0);
        const cantidad = parseInt(item.quantity || item.cantidad || 1);
        const subtotalItem = precio * cantidad;
        currentSubtotal += subtotalItem;
    });
    
    currentIVA = currentSubtotal * IVA_RATE;
    currentTotal = currentSubtotal + currentIVA;
}

async function obtenerUsuarioYCarrito() {
    try {
        const userResponse = await fetch('/proyecto/usuarios/obtener_usuario.php', {
            credentials: 'include'
        });
        
        const userData = await userResponse.json();
        console.log('Usuario actual:', userData);
        
        if (userData.success && userData.usuario) {
            currentUserId = userData.usuario.id;
            localStorage.setItem('user_id', currentUserId);
            await fetchData(currentUserId);
        } else {
            const storedId = localStorage.getItem('user_id');
            if (storedId) {
                currentUserId = storedId;
                await fetchData(currentUserId);
            } else {
                window.location.href = 'login.html';
            }
        }
        
    } catch (error) {
        console.error('Error al obtener usuario:', error);
        const storedId = localStorage.getItem('user_id');
        if (storedId) {
            currentUserId = storedId;
            await fetchData(currentUserId);
        } else {
            window.location.href = 'login.html';
        }
    }
}

async function fetchData(userId) {
    try {
        console.log(`Cargando carrito para usuario ${userId}...`);
        
        const response = await fetch(`/proyecto/carrito/tomar_carrito.php?user_id=${userId}`, {
            credentials: 'include'
        });
        
        if (!response.ok) throw new Error('Error al conectar con el carrito');
        
        const data = await response.json();
        console.log("Datos recibidos:", data);
        
        if (data.success && data.items && data.items.length > 0) {
            currentItems = data.items;
            calculateTotals(currentItems);
            renderCart(currentItems);
            document.getElementById('clientTypeSection').style.display = 'block';
            document.getElementById('paymentMethods').style.display = 'block';
        } else {
            renderEmpty();
        }
    } catch (err) {
        console.error('Error detallado:', err);
        
        const errorDiv = document.getElementById('errorMessage');
        errorDiv.style.display = 'block';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i> 
            Error: No se pudo cargar el carrito.
            <br>
            <button class="btn-reintentar" onclick="location.reload()">
                <i class="fas fa-redo"></i> <?php echo \I18n::trans('retry'); ?>
            </button>
        `;
        
        document.getElementById('cartContent').innerHTML = `
            <div style="text-align:center; padding: 20px; color: #a0aec0;">
                <i class="fas fa-exclamation-triangle" style="font-size: 2.5em; margin-bottom: 10px;"></i>
                <p>Error al cargar el carrito</p>
            </div>
        `;
    }
}

function renderCart(items) {
    const container = document.getElementById('cartContent');
    
    let tableHTML = `
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="text-center">Cant.</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    items.forEach(item => {
        const nombre = item.name || item.nombre || "Producto";
        const precio = parseFloat(item.price || item.precio || 0);
        const cantidad = parseInt(item.quantity || item.cantidad || 1);
        const subtotalItem = precio * cantidad;
        
        tableHTML += `
            <tr>
                <td>${escapeHtml(nombre)}</td>
                <td class="text-center">${cantidad}</td>
                <td class="text-right">Bs. ${precio.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="text-right">Bs. ${subtotalItem.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
    });
    
    const totalConIVA = currentSubtotal + currentIVA;
    let ivaMostrar = currentIVA;
    let totalMostrar = totalConIVA;
    let ivaLabel = 'IVA (16%)';
    
    if (clientType === 'retencion') {
        const ivaAPagar = currentIVA * (1 - RETENTION_RATE);
        ivaMostrar = ivaAPagar;
        totalMostrar = currentSubtotal + ivaAPagar;
        ivaLabel = 'IVA (16% con retención 75%)';
    }
    
    tableHTML += `
            </tbody>
        </table>
        
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">Bs. ${currentSubtotal.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
            <tr>
                <td>${ivaLabel}: 
                    ${clientType === 'retencion' ? 
                        '<span class="retention-badge"><i class="fas fa-percent"></i> Retención 75%</span>' : 
                        '<span class="iva-badge"><i class="fas fa-check"></i> IVA</span>'
                    }
                    </td>
                <td class="text-right">Bs. ${ivaMostrar.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
    `;
    
    if (clientType === 'retencion') {
        const ivaRetenido = currentIVA * RETENTION_RATE;
        tableHTML += `
            <tr style="color: #718096; font-size: 0.85rem;">
                <td>IVA retenido (75%):</td>
                <td class="text-right">- Bs. ${ivaRetenido.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
    }
    
    tableHTML += `
            <tr class="final-total">
                <td>Total a Pagar:</td>
                <td class="text-right"><strong>Bs. ${totalMostrar.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
            </tr>
        </table>
    `;
    
    container.innerHTML = tableHTML;
                
    localStorage.setItem('total_pagar', totalMostrar);
    localStorage.setItem('subtotal', currentSubtotal);
    localStorage.setItem('iva_total', clientType === 'retencion' ? ivaMostrar : currentIVA);
    localStorage.setItem('iva_retenido', clientType === 'retencion' ? (currentIVA * RETENTION_RATE) : 0);
    localStorage.setItem('client_type', clientType);
}

function renderEmpty() {
    document.getElementById('cartContent').innerHTML = `
        <div style="text-align:center; padding: 20px; color: #a0aec0;">
            <i class="fas fa-shopping-cart" style="font-size: 2.5em; margin-bottom: 10px;"></i>
            <p>Tu carrito está vacío.</p>
            <a href="pagina_modernizada.php" style="color: var(--accent); text-decoration: none; font-weight: bold; display: inline-block; margin-top: 8px;">
                <i class="fas fa-arrow-left"></i> <?php echo \I18n::trans('back_to_store'); ?>
            </a>
        </div>`;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<script>
// Dark mode from localStorage
(function() {
    const saved = localStorage.getItem('darkMode');
    if (saved === 'enabled') {
        document.body.classList.add('dark-mode');
    }
})();
</script>
</body>
</html>