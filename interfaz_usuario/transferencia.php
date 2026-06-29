<?php
session_name('CLIENTSESSID');
session_start();
require_once __DIR__ . '/../config/i18n.php';
require_once __DIR__ . '/../conexion/conexion.php';
$locale = $_GET['lang'] ?? $_COOKIE['lang'] ?? 'es';
\I18n::load($locale);
$tokenCSRF = generarTokenCSRF();
?><!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencia Bancaria | Proyectos Industriales</title>
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
            --primary-blue: #294E90;
            --secondary-blue: #3C91ED;
            --success-green: #28a745;
            --warning: #f39c12;
            --retention-bg: #fff3cd;
            --retention-text: #856404;
        }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            min-height: 100vh;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            margin: 0; 
            padding: 20px;
        }
        .container { 
            max-width: 700px; 
            width: 100%;
            background: white; 
            border-radius: 20px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header i {
            font-size: 3rem;
        }
        .content { padding: 30px; }
        .cart-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .cart-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .detail-row.total {
            border-top: 2px solid var(--primary-blue);
            border-bottom: none;
            margin-top: 10px;
            padding-top: 15px;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .detail-row.retention {
            background: var(--retention-bg);
            color: var(--retention-text);
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .iva-badge {
            background: #e6f7ff;
            color: #0066cc;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .retention-badge {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8em;
        }
        .bank-info { 
            background: #e8f4f8; 
            padding: 20px; 
            border-radius: 15px; 
            margin: 20px 0; 
        }
        .btn-confirm { 
            padding: 15px 30px; 
            border-radius: 50px; 
            background-color: var(--success-green); 
            color: white; 
            border: none; 
            cursor: pointer; 
            font-weight: bold;
            width: 100%;
        }
        .btn-confirm:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }
        .processing {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-blue);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            background: #fee;
            color: #c00;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            display: none;
        }
        .reference-field {
            margin: 20px 0;
        }
        .reference-field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        .reference-field input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .reference-field input:focus {
            border-color: var(--secondary-blue);
            outline: none;
        }
        .reference-field small {
            color: #666;
            font-size: 0.8rem;
        }
        .client-type-selector {
            margin: 15px 0;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 10px;
        }
        .client-options {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .client-option {
            flex: 1;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
        }
        .client-option.selected {
            border-color: var(--success-green);
            background: #f0fff4;
        }
        .total-grande {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary-blue);
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: var(--primary-blue);
            text-decoration: none;
        }
        body.dark-mode { background: #121212; }
        body.dark-mode .container { background: #1e1e2e; }
        body.dark-mode .header { background: linear-gradient(135deg, #0a0a1a, #1a1a2e); }
        body.dark-mode .content { color: #F3F3F3; }
        body.dark-mode .cart-summary { background: #2a2a3a; }
        body.dark-mode .cart-item { color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .detail-row { color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .bank-info { background: #2a2a3a; color: #F3F3F3; }
        body.dark-mode .bank-info h4 { color: #3C91ED; }
        body.dark-mode .reference-field label { color: #7EBDE9; }
        body.dark-mode .reference-field input { background: #1e1e2e; color: #F3F3F3; border-color: #4a4a4a; }
        body.dark-mode .reference-field small { color: #aaa; }
        body.dark-mode .client-type-selector { background: #2a2a3a; }
        body.dark-mode .client-option { background: #1e1e2e; border-color: #4a4a4a; color: #F3F3F3; }
        body.dark-mode .error-message { background: #3a1a1a; }
        body.dark-mode .back-link a { color: #3C91ED; }
        body.dark-mode .total-grande { color: #3C91ED; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <i class="fas fa-university"></i>
        <h2><?php echo \I18n::trans('transfer'); ?></h2>
        <p><?php echo \I18n::trans('confirm_transfer'); ?></p>
    </div>
    
    <div class="content">
        <div id="errorMessage" class="error-message"></div>
        
        <div class="cart-summary">
            <h3><i class="fas fa-shopping-basket"></i> <?php echo \I18n::trans('order_summary'); ?></h3>
            <div id="cartItemsList">
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> <?php echo \I18n::trans('loading'); ?>
                </div>
            </div>
            
            <!-- Detalle de totales con IVA/Retención -->
            <div id="totalesDetalle" style="margin-top: 20px;"></div>
        </div>
        
        <!-- Selector de tipo de cliente -->
        <div class="client-type-selector">
            <label><strong><i class="fas fa-user-tag"></i> <?php echo \I18n::trans('client_type'); ?>:</strong></label>
            <div class="client-options">
                <div class="client-option" onclick="selectClientType('regular')" id="optRegular">
                    <i class="fas fa-user"></i> <?php echo \I18n::trans('regular_client'); ?>
                    <small style="display: block;"><?php echo \I18n::trans('includes_iva'); ?></small>
                </div>
                <div class="client-option" onclick="selectClientType('empresa')" id="optEmpresa">
                    <i class="fas fa-building"></i> <?php echo \I18n::trans('retention_company'); ?>
                    <small style="display: block;"><?php echo \I18n::trans('iva_retention_75'); ?></small>
                </div>
            </div>
        </div>
        
        <div class="bank-info">
            <h4><i class="fas fa-building"></i> <?php echo \I18n::trans('bank_details'); ?></h4>
            <div class="bank-selector" style="margin-bottom:12px">
                <label><strong><?php echo \I18n::trans('bank'); ?></strong></label>
                <select id="bancoSelect" onchange="actualizarBanco()" style="width:100%;padding:8px;border-radius:6px;border:1px solid var(--border-color,#ddd);background:var(--card-bg,#fff);color:var(--text-color,#333);font-size:14px">
                    <option value="mercantil">Mercantil</option>
                    <option value="provincial">Provincial</option>
                </select>
            </div>
            <div id="bancoMercantil">
                <p><strong><?php echo \I18n::trans('bank'); ?></strong> Mercantil</p>
                <p><strong><?php echo \I18n::trans('account'); ?></strong> 0105-0094-54-1094383937</p>
                <p><strong><?php echo \I18n::trans('holder'); ?></strong> Proyectos Industriales C.A</p>
                <p><strong><?php echo \I18n::trans('rif'); ?></strong> J-29384799-0</p>
            </div>
            <div id="bancoProvincial" style="display:none">
                <p><strong><?php echo \I18n::trans('bank'); ?></strong> Provincial</p>
                <p><strong><?php echo \I18n::trans('account'); ?></strong> 0108-0042-15-3028475612</p>
                <p><strong><?php echo \I18n::trans('holder'); ?></strong> Proyectos Industriales C.A</p>
                <p><strong><?php echo \I18n::trans('rif'); ?></strong> J-29384799-0</p>
            </div>
        </div>
        
        <div class="reference-field">
            <label>
                <i class="fas fa-hashtag"></i> <?php echo \I18n::trans('ref_number_transfer'); ?>
            </label>
            <input type="text" id="referenciaPago" 
                   placeholder="Ej: 1234567890" 
                   maxlength="50">
            <small><?php echo \I18n::trans('enter_ref_number'); ?></small>
        </div>
        
        <div id="processing" class="processing">
            <div class="spinner"></div>
            <p><?php echo \I18n::trans('processing_payment'); ?></p>
        </div>

        <button class="btn-confirm" id="btnConfirm" onclick="procesarPago()" disabled>
            <i class="fas fa-check-circle"></i> <?php echo \I18n::trans('confirm_transfer'); ?>
        </button>
        
        <div class="back-link">
            <a href="pasarela_de_pago.php">
                <i class="fas fa-arrow-left"></i> <?php echo \I18n::trans('back'); ?>
            </a>
        </div>
    </div>
</div>

<script>
// Constantes
const IVA_RATE = 0.16;
const RETENTION_RATE = 0.75;

let cartItems = [];
let subtotal = 0;
let ivaTotal = 0;
let totalConIva = 0;
let clientType = 'regular'; // 'regular' o 'empresa'
let csrfToken = '<?php echo $tokenCSRF; ?>';

function actualizarBanco() {
    var banco = document.getElementById('bancoSelect').value;
    document.getElementById('bancoMercantil').style.display = banco === 'mercantil' ? '' : 'none';
    document.getElementById('bancoProvincial').style.display = banco === 'provincial' ? '' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    cargarCarrito();
});

async function cargarCarrito() {
    try {
        const user_id = localStorage.getItem('user_id') || 1;
        const response = await fetch(`/proyecto/carrito/tomar_carrito.php?user_id=${user_id}`);
        const data = await response.json();
        
        if (data.success && data.items && data.items.length > 0) {
            cartItems = data.items;
            calcularTotales();
            mostrarResumen();
            mostrarTotalesDetalle();
            document.getElementById('btnConfirm').disabled = false;
        } else {
            mostrarVacio();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al cargar el carrito');
    }
}

function calcularTotales() {
    subtotal = 0;
    cartItems.forEach(item => {
        const precio = parseFloat(item.price || 0);
        const cantidad = parseInt(item.quantity || 1);
        subtotal += precio * cantidad;
    });
    ivaTotal = subtotal * IVA_RATE;
    totalConIva = subtotal + ivaTotal;
}

function getTotalConClientType() {
    if (clientType === 'empresa') {
        const ivaReducido = ivaTotal * (1 - RETENTION_RATE);
        return subtotal + ivaReducido;
    }
    return totalConIva;
}

function getIvaMostrar() {
    if (clientType === 'empresa') {
        return ivaTotal * (1 - RETENTION_RATE);
    }
    return ivaTotal;
}

function getIvaRetenido() {
    if (clientType === 'empresa') {
        return ivaTotal * RETENTION_RATE;
    }
    return 0;
}

function selectClientType(type) {
    clientType = type;
    
    // Actualizar UI de selección
    document.getElementById('optRegular').classList.remove('selected');
    document.getElementById('optEmpresa').classList.remove('selected');
    if (type === 'regular') {
        document.getElementById('optRegular').classList.add('selected');
    } else {
        document.getElementById('optEmpresa').classList.add('selected');
    }
    
    // Recalcular y mostrar
    mostrarTotalesDetalle();
}

function mostrarResumen() {
    const container = document.getElementById('cartItemsList');
    let html = '';
    
    cartItems.forEach(item => {
        const precio = parseFloat(item.price || 0);
        const cantidad = parseInt(item.quantity || 1);
        const subtotalItem = precio * cantidad;
        html += `
            <div class="cart-item">
                <span>${escapeHtml(item.name)} (x${cantidad})</span>
                <span>Bs. ${subtotalItem.toFixed(2)}</span>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function mostrarTotalesDetalle() {
    const container = document.getElementById('totalesDetalle');
    const totalPagar = getTotalConClientType();
    const ivaMostrar = getIvaMostrar();
    const ivaRetenido = getIvaRetenido();
    
    let html = `
        <div style="margin-top: 15px; padding-top: 15px; border-top: 2px solid #dee2e6;">
            <div class="detail-row">
                <span>Subtotal:</span>
                <span>Bs. ${subtotal.toFixed(2)}</span>
            </div>
            <div class="detail-row">
                <span>IVA (16%): 
                    ${clientType === 'empresa' ? 
                        '<span class="retention-badge"><i class="fas fa-percent"></i> Con retención 75%</span>' : 
                        '<span class="iva-badge"><i class="fas fa-check"></i> IVA</span>'
                    }
                </span>
                <span>Bs. ${ivaMostrar.toFixed(2)}</span>
            </div>
    `;
    
    if (clientType === 'empresa') {
        html += `
            <div class="detail-row retention">
                <span><i class="fas fa-building"></i> IVA retenido (75%):</span>
                <span>- Bs. ${ivaRetenido.toFixed(2)}</span>
            </div>
        `;
    }
    
    html += `
            <div class="detail-row total">
                <span><strong>Total a Pagar:</strong></span>
                <span class="total-grande">Bs. ${totalPagar.toFixed(2)}</span>
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

function mostrarVacio() {
    document.getElementById('cartItemsList').innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="fas fa-shopping-cart" style="font-size: 3em; color: #ccc;"></i>
            <p><?php echo \I18n::trans('cart_empty'); ?></p>
            <a href="pagina_modernizada.php" style="color: var(--primary-blue);"><?php echo \I18n::trans('go_to_store'); ?></a>
        </div>
    `;
    document.getElementById('totalesDetalle').innerHTML = '';
}

function mostrarError(mensaje) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${mensaje}`;
    errorDiv.style.display = 'block';
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function procesarPago() {
    const btn = document.getElementById('btnConfirm');
    const processing = document.getElementById('processing');
    const referencia = document.getElementById('referenciaPago').value.trim();
    const totalPagar = getTotalConClientType();
    
    if (cartItems.length === 0) {
        mostrarError('El carrito está vacío');
        return;
    }
    
    if (!referencia) {
        mostrarError('Por favor, ingresa el número de referencia de la transferencia');
        return;
    }
    
    btn.disabled = true;
    processing.style.display = 'block';
    
    try {
        const user_id = localStorage.getItem('user_id') || 1;
        
        // Preparar productos con el formato correcto (usar product_id, no el ID del carrito)
        const productosEnvio = cartItems.map(item => ({
            id: item.product_id,
            name: item.name,
            price: parseFloat(item.price),
            quantity: parseInt(item.quantity)
        }));
        
        const response = await fetch('/proyecto/proceso_compra/procesar-pago.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ 
                user_id: parseInt(user_id),
                payment_method: 'transferencia',
                referencia: referencia,
                client_type: clientType,
                items: productosEnvio,
                subtotal: subtotal,
                iva: ivaTotal,
                total: totalPagar
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            sessionStorage.setItem('pedido_procesado', JSON.stringify(result));
            // Preparar productos para respaldo en URL
            const productosJson = encodeURIComponent(JSON.stringify(cartItems.map(item => ({
                id: item.product_id || item.id,
                nombre: item.name,
                precio: parseFloat(item.price),
                cantidad: parseInt(item.quantity)
            }))));
            // Incluir clientType y productos en la URL
            window.location.href = `pedido_confirmado.php?numero=${result.numero_pedido}&total=${totalPagar}&metodo=transferencia&referencia=${encodeURIComponent(referencia)}&usuario_id=${user_id}&productos=${productosJson}&clientType=${clientType}`;
        } else {
            throw new Error(result.message || 'Error al procesar el pago');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError(error.message);
        btn.disabled = false;
        processing.style.display = 'none';
    }
}
</script>
<script>
(function() {
    const saved = localStorage.getItem('darkMode');
    if (saved === 'enabled') {
        document.body.classList.add('dark-mode');
    }
})();
</script>
</body>
</html>