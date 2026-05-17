<?php
// /proyecto/usuario/pedido_confirmado.php
// VERSIÓN CORREGIDA - MANTIENE SESIÓN DE CLIENTE

session_start();

// ============================================================================
// IMPORTANTE: NO modificar la sesión existente del cliente
// Solo verificar que exista un usuario logueado
// ============================================================================

$es_cliente = false;
$usuario_id = null;
$usuario_nombre = null;
$usuario_correo = null;
$usuario_telefono = null;
$usuario_cedula = null;

// Verificar si hay sesión de cliente (desde tabla 'users')
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'cliente') {
    $es_cliente = true;
    $usuario_id = $_SESSION['user_id'];
    $usuario_nombre = $_SESSION['user_nombre'] ?? null;
    $usuario_correo = $_SESSION['user_email'] ?? null;
}
// También verificar si hay sesión de admin (pero NO permitir redirigir a admin-panel automáticamente)
else if (isset($_SESSION['user_id']) && isset($_SESSION['tabla_origen']) && $_SESSION['tabla_origen'] === 'admin_users') {
    // Es administrador, pero para el pedido lo tratamos como cliente normal con datos
    $usuario_id = $_SESSION['user_id'];
    $usuario_nombre = $_SESSION['user_nombre'] ?? 'Administrador';
    $usuario_correo = $_SESSION['user_email'] ?? null;
    $es_cliente = true; // Para efectos del pedido, permitir compra
}
// Si no hay sesión, intentar obtener de la URL (fallback)
else {
    $usuario_id_url = $_GET['usuario_id'] ?? 0;
    if ($usuario_id_url > 0) {
        $usuario_id = $usuario_id_url;
    }
}

header('Content-Type: text/html; charset=utf-8');

// ============================================================================
// 1. FUNCIONES AUXILIARES
// ============================================================================
function escapeHtml($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

function getMetodoPagoBadge($metodo) {
    if (!$metodo) return '<span class="metodo-badge metodo-desconocido"><i class="fas fa-question-circle"></i> No especificado</span>';
    $m = strtolower(trim($metodo));
    if ($m === 'efectivo' || strpos($m, 'efectivo') !== false) return '<span class="metodo-badge metodo-efectivo"><i class="fas fa-money-bill-wave"></i> Efectivo</span>';
    if ($m === 'mixto' || strpos($m, 'mixto') !== false) return '<span class="metodo-badge metodo-mixto"><i class="fas fa-sync-alt"></i> Pago Mixto</span>';
    if ($m === 'transferencia' || strpos($m, 'transferencia') !== false) return '<span class="metodo-badge metodo-transferencia"><i class="fas fa-university"></i> Transferencia</span>';
    if ($m === 'pago_movil' || $m === 'pago movil') return '<span class="metodo-badge metodo-pago-movil"><i class="fas fa-mobile-alt"></i> Pago Móvil</span>';
    return '<span class="metodo-badge metodo-desconocido"><i class="fas fa-question-circle"></i> ' . htmlspecialchars($metodo) . '</span>';
}

function getEstadoBadge($estado) {
    $estados = [
        'pendiente' => '<span class="estado-badge estado-pendiente"><i class="fas fa-clock"></i> Pendiente</span>',
        'pagada' => '<span class="estado-badge estado-pagada"><i class="fas fa-check-circle"></i> Pagada</span>'
    ];
    return $estados[$estado] ?? "<span class=\"estado-badge\">" . htmlspecialchars($estado) . "</span>";
}

// ============================================================================
// 2. OBTENER DATOS DE LA URL
// ============================================================================
$numero_pedido = $_GET['numero'] ?? $_GET['numero_pedido'] ?? '';
$total = floatval($_GET['total'] ?? 0);
$metodo = $_GET['metodo'] ?? '';
$referencia = $_GET['referencia'] ?? '';
$clientType = $_GET['clientType'] ?? 'regular';
$productos_json = $_GET['productos'] ?? '';

$monto_transferencia = floatval($_GET['transferencia'] ?? 0);
$monto_efectivo = floatval($_GET['efectivo'] ?? 0);
$tipo_divisa = $_GET['tipoDivisa'] ?? 'BS';
$monto_divisa = floatval($_GET['montoDivisa'] ?? 0);

// ============================================================================
// 3. OBTENER ID DE USUARIO (priorizar sesión, luego URL)
// ============================================================================
if (!$usuario_id && $usuario_id_url > 0) {
    $usuario_id = $usuario_id_url;
}

// ============================================================================
// 4. CONEXIÓN A LA BASE DE DATOS
// ============================================================================
require_once '../conexion/conexion.php';

$pdo = conectarDB();

// ============================================================================
// 5. OBTENER DATOS DEL USUARIO DESDE LA BD
// ============================================================================
if ($usuario_id) {
    // Primero buscar en tabla users (clientes)
    $stmt_user = $pdo->prepare("SELECT id, nombre, correo, telefono, cedula FROM users WHERE id = ?");
    $stmt_user->execute([$usuario_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $usuario_nombre = $user_data['nombre'];
        $usuario_correo = $user_data['correo'];
        $usuario_telefono = $user_data['telefono'];
        $usuario_cedula = $user_data['cedula'];
    } else {
        // Si no está en users, buscar en admin_users (caso admin comprando)
        $stmt_admin = $pdo->prepare("SELECT id, nombre, correo FROM admin_users WHERE id = ?");
        $stmt_admin->execute([$usuario_id]);
        $admin_data = $stmt_admin->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_data) {
            $usuario_nombre = $admin_data['nombre'];
            $usuario_correo = $admin_data['correo'];
            $usuario_telefono = null;
            $usuario_cedula = null;
        }
    }
}

// ============================================================================
// 6. NORMALIZAR MÉTODO DE PAGO
// ============================================================================
$metodo_normalizado = '';
$es_pago_mixto = false;
$metodo_original = strtolower(trim($metodo));

if ($metodo_original === 'mixto' || $metodo_original === 'mixed' || $metodo_original === 'pago mixto') {
    $es_pago_mixto = true;
    $metodo_normalizado = 'mixto';
} elseif ($metodo_original === 'efectivo' || $metodo_original === 'cash' || $metodo_original === 'efectivo (bolívares)') {
    $metodo_normalizado = 'efectivo';
} elseif ($metodo_original === 'transferencia' || $metodo_original === 'transferencia bancaria') {
    $metodo_normalizado = 'transferencia';
} elseif ($metodo_original === 'pago_movil' || $metodo_original === 'pago movil') {
    $metodo_normalizado = 'pago_movil';
} else {
    $metodo_normalizado = 'efectivo';
}

// ============================================================================
// 7. OBTENER O CREAR CLIENTE EN TABLA clientes
// ============================================================================
$cliente_id = null;

if (!empty($usuario_cedula)) {
    $stmt_cliente = $pdo->prepare("SELECT id FROM clientes WHERE documento = ?");
    $stmt_cliente->execute([$usuario_cedula]);
    $cliente_existente = $stmt_cliente->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente_existente) {
        $cliente_id = $cliente_existente['id'];
    } else {
        $nombre_cliente = !empty($usuario_nombre) ? $usuario_nombre : '';
        $email_cliente = !empty($usuario_correo) ? $usuario_correo : '';
        $telefono_cliente = !empty($usuario_telefono) ? $usuario_telefono : '';
        $stmt_insert = $pdo->prepare("INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, estado) VALUES ('cedula', ?, ?, ?, ?, 'activo')");
        $stmt_insert->execute([$usuario_cedula, $nombre_cliente, $email_cliente, $telefono_cliente]);
        $cliente_id = $pdo->lastInsertId();
    }
}

if (!$cliente_id) {
    $doc_generico = 'CLI-' . ($usuario_id ?? 'invitado');
    $nombre_cliente = !empty($usuario_nombre) ? $usuario_nombre : 'Cliente ' . ($usuario_id ?? 'invitado');
    $email_cliente = !empty($usuario_correo) ? $usuario_correo : 'cliente' . ($usuario_id ?? '0') . '@email.com';
    $telefono_cliente = !empty($usuario_telefono) ? $usuario_telefono : '';
    
    $stmt_insert = $pdo->prepare("INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, estado) VALUES ('cedula', ?, ?, ?, ?, 'activo')");
    $stmt_insert->execute([$doc_generico, $nombre_cliente, $email_cliente, $telefono_cliente]);
    $cliente_id = $pdo->lastInsertId();
}

// ============================================================================
// 8. VERIFICAR SI EL PEDIDO EXISTE
// ============================================================================
$pedido_id = null;

if ($numero_pedido) {
    $stmt = $pdo->prepare("SELECT id FROM pedidos WHERE numero_pedido = ?");
    $stmt->execute([$numero_pedido]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($pedido) {
        $pedido_id = $pedido['id'];
    }
}

// ============================================================================
// 9. PROCESAR PRODUCTOS DESDE URL
// ============================================================================
$productosDesdeURL = [];
if (!empty($productos_json)) {
    $productosDesdeURL = json_decode(urldecode($productos_json), true);
}

// ============================================================================
// 10. SI EL PEDIDO NO EXISTE Y HAY PRODUCTOS, CREARLO
// ============================================================================
if (!$pedido_id && !empty($productosDesdeURL)) {
    if (empty($numero_pedido)) {
        $anio = date('Y');
        $prefijo = "PED-{$anio}-";
        $stmt_seq = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(numero_pedido, LOCATE('-', numero_pedido, 5) + 1) AS UNSIGNED)) as max_num FROM pedidos WHERE numero_pedido LIKE ?");
        $stmt_seq->execute([$prefijo . '%']);
        $seq_row = $stmt_seq->fetch(PDO::FETCH_ASSOC);
        $siguiente = ($seq_row['max_num'] ?? 0) + 1;
        $numero_pedido = $prefijo . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
    }
    
    $subtotal_calc = $total / 1.16;
    $iva_calc = $total - $subtotal_calc;
    $observaciones = "Pedido por {$metodo_normalizado}" . ($referencia ? " - Ref: {$referencia}" : "");
    $referencia_null = $referencia ?: null;
    
    $stmt_insert = $pdo->prepare("INSERT INTO pedidos (usuario_id, cliente_id, numero_pedido, subtotal, iva, total, estado, metodo_pago, referencia_pago, observaciones, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, NOW())");
    $stmt_insert->execute([
        $usuario_id,
        $cliente_id,
        $numero_pedido,
        $subtotal_calc,
        $iva_calc,
        $total,
        $metodo_normalizado,
        $referencia_null,
        $observaciones
    ]);
    $pedido_id = $pdo->lastInsertId();
    
    if ($pedido_id) {
        $stmt_detalle = $pdo->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal, producto_nombre) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($productosDesdeURL as $item) {
            $producto_id = $item['id'] ?? 0;
            $cantidad = $item['cantidad'] ?? 1;
            $precio = $item['precio'] ?? 0;
            $subtotal_item = $precio * $cantidad;
            $nombre = $item['nombre'] ?? $item['name'] ?? 'Producto';
            
            $stmt_detalle->execute([$pedido_id, $producto_id, $cantidad, $precio, $subtotal_item, $nombre]);
            
            if ($producto_id > 0) {
                $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt_stock->execute([$cantidad, $producto_id]);
            }
        }
        
        $stmt_clear = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt_clear->execute([$usuario_id]);
    }
}

// ============================================================================
// 11. OBTENER DATOS DEL PEDIDO PARA MOSTRAR
// ============================================================================
$pedido_data = null;
$productos = [];

if ($pedido_id) {
    $stmt = $pdo->prepare("SELECT p.*, c.nombre as cliente_nombre, c.email as cliente_email, c.telefono as cliente_telefono FROM pedidos p LEFT JOIN clientes c ON p.cliente_id = c.id WHERE p.id = ?");
    $stmt->execute([$pedido_id]);
    $pedido_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $detalles_stmt = $pdo->prepare("SELECT pd.cantidad, pd.precio_unitario, pd.subtotal, pd.producto_nombre, p.name as product_name FROM pedido_detalles pd LEFT JOIN products p ON pd.producto_id = p.id WHERE pd.pedido_id = ?");
    $detalles_stmt->execute([$pedido_id]);
    $productos = $detalles_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================================
// 12. CALCULAR TOTAL A MOSTRAR
// ============================================================================
$totalMostrar = $total;
if ($totalMostrar == 0 && $pedido_data) {
    $totalMostrar = $pedido_data['total'];
}

$metodo_final = $metodo_normalizado;
if (empty($metodo_final) && $pedido_data && $pedido_data['metodo_pago']) {
    $metodo_final = $pedido_data['metodo_pago'];
}

// ============================================================================
// 13. DETERMINAR PÁGINA DE RETORNO SEGÚN TIPO DE USUARIO
// ============================================================================
// IMPORTANTE: Evaluar si es administrador o cliente
$es_administrador = false;
if (isset($_SESSION['tabla_origen']) && $_SESSION['tabla_origen'] === 'admin_users') {
    $es_administrador = true;
}

// URL base para redirección (mantener en la tienda, NO ir a panel_admin)
$return_url = '/proyecto/usuario/pagina_modernizada.html';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido Confirmado - PIC</title>
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
        :root { --primary-color: #050C18; --secondary-color: #294E90; --accent-color: #3C91ED; --success-color: #2ed573; --danger-color: #ff4757; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { max-width: 1000px; width: 100%; background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .header { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 30px; text-align: center; }
        .header i { font-size: 4rem; color: var(--success-color); }
        .content { padding: 30px; }
        .success-message { text-align: center; margin-bottom: 30px; }
        .success-message h2 { color: var(--success-color); }
        .info-card { background: #f8f9fa; padding: 20px; border-radius: 15px; margin-bottom: 20px; }
        .info-card h3 { margin-bottom: 15px; color: var(--primary-color); border-left: 4px solid var(--accent-color); padding-left: 12px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #dee2e6; }
        .info-row:last-child { border-bottom: none; }
        .productos-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .productos-table th { background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)); color: white; padding: 12px; text-align: left; }
        .productos-table td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        .total-row { text-align: right; padding: 15px; background: #f0f9ff; border-radius: 10px; margin-top: 15px; font-size: 1.2rem; }
        .btn-actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap; }
        .btn-primary { background: linear-gradient(135deg, var(--accent-color), var(--secondary-color)); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .metodo-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .metodo-efectivo { background: #2ed573; color: white; }
        .metodo-transferencia { background: #3498db; color: white; }
        .metodo-pago-movil { background: #9b59b6; color: white; }
        .metodo-mixto { background: #f39c12; color: white; }
        .metodo-desconocido { background: #95a5a6; color: white; }
        .estado-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
        .estado-pendiente { background: #ffa502; color: white; }
        .estado-pagada { background: #2ed573; color: white; }
        @media print { .btn-actions { display: none; } }
        @media (max-width: 768px) {
            .content { padding: 20px; }
            .info-row { flex-direction: column; gap: 5px; }
            .productos-table { font-size: 0.8rem; }
            .btn-primary { padding: 10px 16px; font-size: 0.8rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <i class="fas fa-check-circle"></i>
        <h1>¡Pedido Confirmado!</h1>
        <p>Tu pedido ha sido registrado exitosamente</p>
    </div>
    
    <div class="content">
        <div class="success-message">
            <h2>Gracias por tu compra</h2>
            <p>Hemos recibido tu pedido y lo estamos procesando.</p>
        </div>
        
        <div class="info-card">
            <h3><i class="fas fa-receipt"></i> Detalles del Pedido</h3>
            <div class="info-row"><span class="info-label">Número de Pedido:</span><span class="info-value"><strong><?php echo escapeHtml($numero_pedido); ?></strong></span></div>
            <div class="info-row"><span class="info-label">Fecha:</span><span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span></div>
            <div class="info-row"><span class="info-label">Método de Pago:</span><span class="info-value"><?php echo getMetodoPagoBadge($metodo_final); ?></span></div>
            <?php if ($referencia): ?>
            <div class="info-row"><span class="info-label">Referencia:</span><span class="info-value"><?php echo escapeHtml($referencia); ?></span></div>
            <?php endif; ?>
            <?php if ($es_pago_mixto && $monto_transferencia > 0): ?>
            <div class="info-row"><span class="info-label">Monto Transferencia:</span><span class="info-value">Bs. <?php echo number_format($monto_transferencia, 2); ?></span></div>
            <?php endif; ?>
            <?php if ($es_pago_mixto && $monto_efectivo > 0): ?>
            <div class="info-row"><span class="info-label">Monto Efectivo:</span><span class="info-value">Bs. <?php echo number_format($monto_efectivo, 2); ?></span></div>
            <?php endif; ?>
            <div class="info-row"><span class="info-label">Estado:</span><span class="info-value"><?php echo getEstadoBadge($pedido_data['estado'] ?? 'pendiente'); ?></span></div>
        </div>
        
        <div class="info-card">
            <h3><i class="fas fa-user"></i> Información del Cliente</h3>
            <div class="info-row"><span class="info-label">Nombre:</span><span class="info-value"><?php echo escapeHtml($pedido_data['cliente_nombre'] ?? $usuario_nombre ?? 'Cliente'); ?></span></div>
            <div class="info-row"><span class="info-label">Email:</span><span class="info-value"><?php echo escapeHtml($pedido_data['cliente_email'] ?? $usuario_correo ?? 'cliente@email.com'); ?></span></div>
            <div class="info-row"><span class="info-label">Teléfono:</span><span class="info-value"><?php echo escapeHtml($pedido_data['cliente_telefono'] ?? $usuario_telefono ?? 'No registrado'); ?></span></div>
        </div>
        
        <h3><i class="fas fa-boxes"></i> Productos</h3>
        <table class="productos-table">
            <thead>
                <tr><th>Producto</th><th style="text-align:center">Cantidad</th><th style="text-align:right">Precio Unitario</th><th style="text-align:right">Subtotal</th></tr>
            </thead>
            <tbody>
                <?php if (!empty($productos)): ?>
                    <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo escapeHtml($producto['producto_nombre'] ?? ($producto['product_name'] ?? 'Producto')); ?></td>
                        <td style="text-align:center"><?php echo $producto['cantidad']; ?></td>
                        <td style="text-align:right">Bs. <?php echo number_format($producto['precio_unitario'], 2); ?></td>
                        <td style="text-align:right">Bs. <?php echo number_format($producto['subtotal'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#999;">No hay productos en este pedido</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="total-row">
            <strong>Total a Pagar: Bs. <?php echo number_format($totalMostrar, 2); ?></strong>
        </div>
        
        <div class="btn-actions no-print">
            <button class="btn-primary" onclick="window.print()"><i class="fas fa-print"></i> Imprimir</button>
            <a href="<?php echo $return_url; ?>" class="btn-primary" id="btnSeguirComprando">
                <i class="fas fa-shopping-cart"></i> Seguir Comprando
            </a>
            <?php if ($es_administrador): ?>
            <a href="/proyecto/admin/panel_admin.php" class="btn-primary" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
                <i class="fas fa-tachometer-alt"></i> Ir al admin-panel
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Asegurar que al hacer clic en "Seguir Comprando" se preserve la sesión del cliente
    document.getElementById('btnSeguirComprando')?.addEventListener('click', function(e) {
        // No prevenir el comportamiento por defecto, solo asegurar que la URL es correcta
        console.log('Redirigiendo a la tienda...');
    });
    
    // Si el usuario es administrador, mostrar aviso informativo
    <?php if ($es_administrador): ?>
    console.log('Usuario administrador - compra registrada correctamente');
    setTimeout(function() {
        console.log('Puedes volver al admin-panelistrativo desde el botón correspondiente');
    }, 1000);
    <?php endif; ?>
</script>
</body>
</html>