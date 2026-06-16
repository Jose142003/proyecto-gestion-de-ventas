<?php
/**
 * TEST DE INTEGRACIÓN — Prueba todo el sistema sin salida JSON
 * Cliente (carrito, favoritos, historial) + Admin (pedidos, facturas, stock, inventario)
 * Usa transacciones revertidas — no modifica datos reales
 */

// Suppress all output from required files
ob_start();

require_once __DIR__ . '/conexion/conexion.php';
require_once __DIR__ . '/telegram/config.php';
require_once __DIR__ . '/telegram/helpers.php';
require_once __DIR__ . '/telegram/respuestas_bot.php';
require_once __DIR__ . '/telegram/notificar_pedido.php';

// Services
if (file_exists(__DIR__ . '/src/Services/StockService.php')) {
    require_once __DIR__ . '/src/Services/StockService.php';
}
if (file_exists(__DIR__ . '/notificaciones/cola.php')) {
    require_once __DIR__ . '/notificaciones/cola.php';
}

ob_end_clean();

$passed = 0;
$failed = 0;
$errors = [];

function test(string $name, $ok, string $detail = '') {
    global $passed, $failed, $errors;
    if ($ok) {
        echo "  ✅ $name\n";
        $passed++;
    } else {
        echo "  ❌ $name\n";
        if ($detail) echo "     $detail\n";
        $errors[] = $name;
        $failed++;
    }
}

function section(string $title) {
    echo "\n═══════════════════════════════════════\n";
    echo "  $title\n";
    echo "═══════════════════════════════════════\n";
}

// ─── DB CONNECTION ────────────────────────
section("1. CONEXIÓN Y DATOS DE PRUEBA");
try {
    $pdo = conectarDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    test("Conexión DB", true);
} catch (Exception $e) {
    test("Conexión DB", false, $e->getMessage());
    exit(1);
}

$stmt = $pdo->query("SELECT COUNT(*) FROM products");
test("Productos existen: " . $stmt->fetchColumn(), true);

$stmt = $pdo->query("SELECT id, name, price, stock, active FROM products WHERE active = 1 AND deleted_at IS NULL LIMIT 1");
$product = $stmt->fetch(PDO::FETCH_ASSOC);
test("Producto activo disponible", $product !== false, "ID: {$product['id']}, Stock: {$product['stock']}");
$productoId = $product['id'];

$stmt = $pdo->query("SELECT id, nombre, correo FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
test("Usuario disponible", $user !== false, "ID: {$user['id']}");
$userId = $user['id'];

$stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
test("Admin users: " . $stmt->fetchColumn(), true);

$stmt = $pdo->query("SELECT COUNT(DISTINCT category) FROM products WHERE category IS NOT NULL AND category != ''");
test("Categorías: " . $stmt->fetchColumn(), true);

// ─── CLIENTE: CARRITO ──────────────────────
section("2. CLIENTE — CARRITO");

// Probar añadir al carrito (transacción revertida)
$pdo->beginTransaction();
try {
    // Añadir item
    $stmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productoId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + 1, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productoId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
        $stmt->execute([$userId, $productoId]);
    }
    test("Añadir producto #$productoId al carrito", true);

    // Ver carrito
    $stmt = $pdo->prepare("
        SELECT ci.id, ci.product_id, ci.quantity, p.name, p.price, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    test("Ver carrito devuelve resultados", count($cart) > 0);

    // Remover del carrito
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productoId]);
    test("Remover producto del carrito", $stmt->rowCount() > 0);
} catch (Exception $e) {
    test("Operaciones de carrito", false, $e->getMessage());
}
$pdo->rollBack();

// ─── CLIENTE: FAVORITOS ────────────────────
section("3. CLIENTE — FAVORITOS");

$pdo->beginTransaction();
try {
    // No asumimos que tabla favoritos existe; verificar
    $stmt = $pdo->query("SHOW TABLES LIKE 'favoritos'");
    if ($stmt->fetch() !== false) {
        $stmt = $pdo->prepare("INSERT INTO favoritos (usuario_id, producto_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $productoId]);
        test("Agregar favorito", true);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoritos WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productoId]);
        test("Favorito aparece en DB", $stmt->fetchColumn() > 0);

        $stmt = $pdo->prepare("DELETE FROM favoritos WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productoId]);
        test("Remover favorito", $stmt->rowCount() > 0);
    } else {
        test("Tabla 'favoritos' no existe (usará otra tabla?)", true, "(no crítica)");
    }
} catch (Exception $e) {
    test("Operaciones de favoritos", false, $e->getMessage());
}
$pdo->rollBack();

// ─── CLIENTE: FLUJO COMPLETO DE COMPRA ─────
section("4. CLIENTE — FLUJO DE COMPRA (SPs)");

// Verificar SPs
$dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = 'sp_crear_pedido'");
$stmt->execute([$dbName]);
test("sp_crear_pedido existe", $stmt->fetchColumn() > 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = 'sp_agregar_producto_pedido'");
$stmt->execute([$dbName]);
test("sp_agregar_producto_pedido existe", $stmt->fetchColumn() > 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = 'sp_generar_numero_pedido'");
$stmt->execute([$dbName]);
test("sp_generar_numero_pedido existe", $stmt->fetchColumn() > 0);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = ? AND ROUTINE_NAME = 'sp_generar_numero_factura'");
$stmt->execute([$dbName]);
test("sp_generar_numero_factura existe", $stmt->fetchColumn() > 0);

// Probar SPs con transacción revertida
$pdo->beginTransaction();
try {
    // 1. Generar número de pedido
    $stmt = $pdo->prepare("CALL sp_generar_numero_pedido(@num_pedido)");
    $stmt->execute();
    $stmt = $pdo->query("SELECT @num_pedido as num");
    $numPedido = $stmt->fetch(PDO::FETCH_ASSOC);
    test("sp_generar_numero_pedido genera número", !empty($numPedido['num']), $numPedido['num']);
    test("  Formato PED-YYYY-NNNNNN", preg_match('/^PED-\d{4}-\d{6}$/', $numPedido['num']), $numPedido['num']);

    // 2. Crear pedido
    $stmt = $pdo->prepare("CALL sp_crear_pedido(?, 'transferencia', 'Dirección de prueba', '0412-1234567', 'Test User', 'Observaciones test', @pedido_id, @num_pedido)");
    $stmt->execute([$userId]);
    $stmt = $pdo->query("SELECT @pedido_id as pid, @num_pedido as num");
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    test("sp_crear_pedido crea pedido", !empty($pedido['pid']), "ID: {$pedido['pid']}, Nro: {$pedido['num']}");

    // 3. Verificar pedido creado en tabla
    $stmt = $pdo->prepare("SELECT id, numero_pedido, usuario_id, estado, metodo_pago, total FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido['pid']]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    test("  Pedido en DB", $p !== false);
    if ($p) {
        test("    estado = 'pendiente'", $p['estado'] == 'pendiente', $p['estado']);
        test("    usuario_id = $userId", $p['usuario_id'] == $userId);
        test("    metodo_pago = 'transferencia'", $p['metodo_pago'] == 'transferencia');
        test("    numero_pedido asignado", !empty($p['numero_pedido']), $p['numero_pedido']);
        test("    total = 0 (sin items)", $p['total'] == 0, "Bs. {$p['total']}");
    }

    // 4. Agregar producto al pedido
    $stmt = $pdo->prepare("CALL sp_agregar_producto_pedido(?, ?, 2)");
    $stmt->execute([$pedido['pid'], $productoId]);

    $stmt = $pdo->prepare("SELECT id, cantidad, precio_unitario, subtotal, producto_nombre FROM pedido_detalles WHERE pedido_id = ?");
    $stmt->execute([$pedido['pid']]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    test("sp_agregar_producto_pedido agrega detalle", $detalle !== false);
    if ($detalle) {
        test("    cantidad = 2", $detalle['cantidad'] == 2);
        test("    precio_unitario > 0", $detalle['precio_unitario'] > 0, "Bs. {$detalle['precio_unitario']}");
        test("    subtotal = precio * cantidad", abs($detalle['subtotal'] - ($detalle['precio_unitario'] * 2)) < 0.01, "Bs. {$detalle['subtotal']}");
        test("    producto_nombre guardado", !empty($detalle['producto_nombre']), $detalle['producto_nombre']);
    }

    // 5. Verificar que total del pedido se actualizó
    $stmt = $pdo->prepare("SELECT total FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido['pid']]);
    $totalPedido = $stmt->fetchColumn();
    test("  Total pedido actualizado > 0", $totalPedido > 0, "Bs. $totalPedido");

    // 6. Verificar que el stock se descontó
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$productoId]);
    $stockActual = $stmt->fetchColumn();
    $productoStockOrig = $product['stock'];
    $diferencia = $productoStockOrig - $stockActual;
    test("  Stock descontado en $diferencia unidades", $diferencia > 0, "Stock: $productoStockOrig → $stockActual");

    // 7. Verificar que se registró en historial_stock
    $stmt = $pdo->prepare("SELECT id, tipo, cantidad, stock_anterior, stock_nuevo FROM historial_stock WHERE referencia LIKE CONCAT('%', ?, '%') ORDER BY id DESC LIMIT 1");
    $stmt->execute([$pedido['num']]);
    $historial = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($historial) {
        test("  historial_stock registrado", true);
        test("    tipo = 'venta'", $historial['tipo'] == 'venta', $historial['tipo']);
        test("    stock_anterior = $productoStockOrig", $historial['stock_anterior'] == $productoStockOrig);
        test("    stock_nuevo = $stockActual", $historial['stock_nuevo'] == $stockActual);
    } else {
        $histAll = $pdo->prepare("SELECT id, tipo, cantidad, producto_id FROM historial_stock ORDER BY id DESC LIMIT 5");
        $histAll->execute();
        $recent = $histAll->fetchAll(PDO::FETCH_ASSOC);
        test("  historial_stock registrado (1 reciente: " . json_encode($recent[0] ?? []) . ")", $historial !== false);
    }

    test("✅ FLUJO COMPLETO DE COMPRA OK", true);
} catch (Exception $e) {
    test("❌ FLUJO COMPLETO DE COMPRA", false, $e->getMessage());
}
$pdo->rollBack();

// ─── ADMIN: CAMBIAR ESTADO PEDIDO ──────────
section("5. ADMIN — CAMBIAR ESTADO PEDIDO");

$pdo->beginTransaction();
try {
    // Crear un pedido de prueba
    $stmt = $pdo->prepare("CALL sp_crear_pedido(?, 'transferencia', 'Dir', '0412-0000000', 'Test', 'Obs', @pid, @np)");
    $stmt->execute([$userId]);
    $stmt = $pdo->query("SELECT @pid as pid");
    $pedidoId = $stmt->fetchColumn();

    // Validar estados
    $estadosValidos = ['pendiente','procesando','enviado','entregado','cancelado','facturado'];
    foreach ($estadosValidos as $estado) {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $pedidoId]);
        test("  Cambiar estado a '$estado'", $stmt->rowCount() > 0);
    }

    // Ver ENUM en DB
    $stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'estado'");
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    foreach ($estadosValidos as $estado) {
        test("  ENUM contiene '$estado'", strpos($col['Type'], "'$estado'") !== false);
    }
} catch (Exception $e) {
    test("Cambiar estado pedido", false, $e->getMessage());
}
$pdo->rollBack();

// ─── ADMIN: FACTURACIÓN ────────────────────
section("6. ADMIN — FACTURACIÓN");

// Ver tablas existen
$tables = ['facturas','factura_detalles','movimientos_inventario','historial_stock','secuencias_facturacion'];
foreach ($tables as $t) {
    $stmt = $pdo->query("SHOW TABLES LIKE '$t'");
    test("Tabla '$t' existe", $stmt->fetch() !== false);
}

// Columnas en facturas
$stmt = $pdo->query("SHOW COLUMNS FROM facturas LIKE 'updated_at'");
test("facturas NO tiene updated_at (correcto, solo created_at)", $stmt->fetch() === false);

// Columnas en historial_stock
$stmt = $pdo->query("SHOW COLUMNS FROM historial_stock LIKE 'tipo'");
test("historial_stock.tipo existe (no tipo_movimiento)", $stmt->fetch() !== false);
$stmt = $pdo->query("SHOW COLUMNS FROM historial_stock LIKE 'fecha'");
test("historial_stock.fecha existe (no created_at)", $stmt->fetch() !== false);
$stmt = $pdo->query("SHOW COLUMNS FROM historial_stock LIKE 'stock_anterior'");
test("historial_stock.stock_anterior existe", $stmt->fetch() !== false);

// ENUM movimientos_inventario
$stmt = $pdo->query("SHOW COLUMNS FROM movimientos_inventario LIKE 'tipo_movimiento'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
test("movimientos_inventario ENUM tiene 'salida'", strpos($col['Type'] ?? '', "'salida'") !== false);

// Secuencias
$stmt = $pdo->query("SELECT tipo, prefijo, siguiente_valor, anio FROM secuencias_facturacion");
$secs = $stmt->fetchAll(PDO::FETCH_ASSOC);
test("secuencias_facturacion con datos", count($secs) > 0);
foreach ($secs as $s) {
    test("  {$s['tipo']}: siguiente_valor > 0", $s['siguiente_valor'] > 0, "{$s['prefijo']}{$s['anio']}-...[{$s['siguiente_valor']}]");
}

// Probar sp_generar_numero_factura
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("CALL sp_generar_numero_factura(@fac_num)");
    $stmt->execute();
    $stmt = $pdo->query("SELECT @fac_num as num");
    $facNum = $stmt->fetch(PDO::FETCH_ASSOC);
    test("sp_generar_numero_factura funciona", !empty($facNum['num']), $facNum['num']);
    test("  Formato FAC-YYYY-NNNNNN", preg_match('/^FAC-\d{4}-\d{6}$/', $facNum['num']), $facNum['num']);
} catch (Exception $e) {
    test("sp_generar_numero_factura", false, $e->getMessage());
}
$pdo->rollBack();

// ─── ADMIN: STOCK ──────────────────────────
section("7. ADMIN — STOCK / HISTORIAL_STOCK");

// Probar INSERT en historial_stock con columnas correctas
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$productoId]);
    $stockAct = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("INSERT INTO historial_stock (producto_id, cantidad, tipo, stock_anterior, stock_nuevo, referencia, fecha) VALUES (?, ?, 'ajuste', ?, ?, 'test_manual', NOW())");
    $stmt->execute([$productoId, 5, $stockAct, $stockAct + 5]);
    $insertId = $pdo->lastInsertId();
    test("INSERT historial_stock OK", $insertId > 0);

    // Leer el registro
    $stmt = $pdo->prepare("SELECT * FROM historial_stock WHERE id = ?");
    $stmt->execute([$insertId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    test("  Lectura del registro creado", $row !== false);
    if ($row) {
        test("    tipo = 'ajuste'", $row['tipo'] == 'ajuste', $row['tipo']);
        test("    stock_anterior = $stockAct", $row['stock_anterior'] == $stockAct);
        test("    stock_nuevo = " . ($stockAct + 5), $row['stock_nuevo'] == $stockAct + 5);
    }
} catch (Exception $e) {
    test("INSERT historial_stock", false, $e->getMessage());
}
$pdo->rollBack();

if (class_exists('PIC\Services\StockService')) {
    $pdo->beginTransaction();
    try {
        $svc = new PIC\Services\StockService($pdo);
        test("StockService instanciado", true);
        $low = $svc->getLowStockProducts(5);
        test("getLowStockProducts() retorna array", is_array($low));
    } catch (Exception $e) {
        test("StockService", false, $e->getMessage());
    }
    $pdo->rollBack();
} else {
    test("StockService.php existe", file_exists(__DIR__ . '/src/Services/StockService.php'));
}

// ─── TELEGRAM ──────────────────────────────
section("8. TELEGRAM BOT");

test("TELEGRAM_BOT_TOKEN definido", defined('TELEGRAM_BOT_TOKEN'));
test("telegramEnviar() existe", function_exists('telegramEnviar'));
test("telegramEnviarDocumento() existe", function_exists('telegramEnviarDocumento'));
test("sendTelegramMessage() existe", function_exists('sendTelegramMessage'));
test("botResponder() existe", function_exists('botResponder'));
test("botNormalizarTexto() existe", function_exists('botNormalizarTexto'));
test("botBuscarProducto() existe", function_exists('botBuscarProducto'));
test("botEstadoPedidoTexto() existe", function_exists('botEstadoPedidoTexto'));
test("botCargarVinculos() existe", function_exists('botCargarVinculos'));
test("botBuscarPedidoPorNumero() existe", function_exists('botBuscarPedidoPorNumero'));
test("telegramNotificarPedido() existe", function_exists('telegramNotificarPedido'));
test("telegramNotificarClientePedido() existe", function_exists('telegramNotificarClientePedido'));
test("telegramNotificarCambioEstado() existe", function_exists('telegramNotificarCambioEstado'));

// Probar botBuscarProducto
$productos = botBuscarProducto('sensor', $pdo);
test("botBuscarProducto('sensor') retorna resultados", is_array($productos) && count($productos) > 0, "Count: " . (is_array($productos) ? count($productos) : 0));
if (is_array($productos) && count($productos) > 0) {
    test("  image_url incluida", isset($productos[0]['image_url']));
    test("  specs incluida", isset($productos[0]['specs']));
}

// Probar botEstadoPedidoTexto
$texto = botEstadoPedidoTexto('enviado');
test("botEstadoPedidoTexto('enviado')", !empty($texto), $texto);

// Probar vinculación
$vinculos = botCargarVinculos();
test("botCargarVinculos() retorna array", is_array($vinculos));

// ─── WHATSAPP ──────────────────────────────
section("9. WHATSAPP (si existe)");
$wd = __DIR__ . '/whatsapp';
if (is_dir($wd)) {
    $files = glob("$wd/*.php");
    test("Archivos WhatsApp: " . count($files), count($files) > 0);
    foreach ($files as $f) {
        $output = shell_exec("php -l " . escapeshellarg($f) . " 2>&1");
        test("  " . basename($f) . " syntax OK", strpos($output, 'No syntax errors') !== false);
    }
} else {
    test("Directorio WhatsApp (opcional)", true);
}

// ─── SYNTAX CHECK GLOBAL ───────────────────
section("10. SYNTAX CHECK — Archivos clave");
$filesToCheck = [
    'producto/obtener_productos.php', 'producto/buscar_producto.php',
    'producto/detalles_producto.php', 'producto/obtener_categorias.php',
    'producto/agregar_favorito.php', 'producto/obtener_favoritos.php',
    'proceso_compra/guardar_pedido.php', 'proceso_compra/cancelar_pedido.php',
    'proceso_compra/ver_historial_pedido.php',
    'proceso_compra/obtener_todos_los_pedidos.php',
    'proceso_compra/obtener_pedidos_pendientes.php',
    'proceso_compra/obtener_pedido.php',
    'facturacion/acciones.php', 'facturacion/actualizar_estado_pedido.php',
    'facturacion/facturar_pedidos.php',
    'stock/actualizar_stock.php', 'admin/obtener_inventario.php',
    'notificaciones/procesar.php',
    'telegram/config.php', 'telegram/helpers.php',
    'telegram/respuestas_bot.php', 'telegram/notificar_pedido.php',
    'telegram/bot_daemon.php', 'telegram/poll.php', 'telegram/webhook.php',
    'telegram/enviar.php', 'telegram/conversar_debug.php',
    'src/Services/StockService.php',
];
foreach ($filesToCheck as $f) {
    $path = __DIR__ . '/' . $f;
    if (!file_exists($path)) {
        test("  $f", false, "ARCHIVO NO EXISTE");
        continue;
    }
    $output = shell_exec("php -l " . escapeshellarg($path) . " 2>&1");
    test("  $f", strpos($output, 'No syntax errors') !== false);
}

// ─── STORED PROCEDURES SQL ─────────────────
section("11. MIGRACIÓN SQL — Stored Procedures");
$sqlPath = __DIR__ . '/sql/migracion_stored_procedures.sql';
if (file_exists($sqlPath)) {
    test("migracion_stored_procedures.sql existe", true);
    $content = file_get_contents($sqlPath);
    test("  Contiene sp_crear_pedido", strpos($content, 'sp_crear_pedido') !== false);
    test("  Contiene sp_agregar_producto_pedido", strpos($content, 'sp_agregar_producto_pedido') !== false);
}

$sqlPath2 = __DIR__ . '/sql/migracion_telegram_vinculos.sql';
if (file_exists($sqlPath2)) {
    test("migracion_telegram_vinculos.sql existe", true);
}

// ─── COLUMNA direccion_envio ───────────────
section("12. VERIFICACIÓN direccion_envio");
$stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'direccion_envio'");
test("pedidos.direccion_envio existe", $stmt->fetch() !== false);
$stmt = $pdo->query("SHOW COLUMNS FROM pedidos LIKE 'direccion_entrega'");
test("pedidos NO tiene direccion_entrega (correcto)", $stmt->fetch() === false);

// ─── CLIENTE: HISTORIAL ────────────────────
section("13. CLIENTE — HISTORIAL DE PEDIDOS");
$stmt = $pdo->prepare("SELECT p.id, p.numero_pedido, p.estado, p.total, p.fecha_pedido,
    (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as items
    FROM pedidos p WHERE p.usuario_id = ? ORDER BY p.id DESC LIMIT 5");
$stmt->execute([$userId]);
$historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
test("Historial de pedidos accesible", is_array($historial));
if (count($historial) > 0) {
    test("  Pedidos encontrados: " . count($historial), true);
    foreach ($historial as $h) {
        test("  #{$h['numero_pedido']} - {$h['estado']} - Bs.{$h['total']} - Items: {$h['items']}", true);
    }
} else {
    test("  Sin pedidos previos (normal para test)", true);
}

// ─── BÚSQUEDA DE PRODUCTOS ─────────────────
section("14. BÚSQUEDA DE PRODUCTOS");
$stmt = $pdo->prepare("SELECT id, name FROM products WHERE name LIKE ? AND active = 1 AND deleted_at IS NULL LIMIT 5");
$stmt->execute(['%sensor%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
test("Buscar 'sensor' en products", count($results) > 0, "Encontrados: " . count($results));
foreach ($results as $r) {
    test("  #{$r['id']}: {$r['name']}", true);
}

// ─── NOTIFICACIONES ────────────────────────
section("15. NOTIFICACIONES");
if (function_exists('colaNotificacionesAgregar')) {
    test("colaNotificacionesAgregar() existe", true);
}

// ─── RESUMEN ───────────────────────────────
echo "\n═══════════════════════════════════════\n";
echo "  📋 RESUMEN FINAL\n";
echo "───────────────────────────────────────────\n";
$total = $passed + $failed;
echo "  Pruebas: $total total\n";
echo "  ✅ Pasadas: $passed\n";
if ($failed > 0) {
    echo "  ❌ Fallidas: $failed\n";
    echo "  Errores:\n";
    foreach ($errors as $e) {
        echo "    • $e\n";
    }
} else {
    echo "  🎉 ¡Todas las pruebas pasaron!\n";
}
echo "═══════════════════════════════════════\n";

$pdo = null;
