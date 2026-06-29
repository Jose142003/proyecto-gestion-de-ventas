<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    error_log("Error de conexión en procesar-pedido: " . $e->getMessage());
    die("Error interno del servidor");
}

if (!isset($_SESSION['user_id'])) {
    die("Error: Debes iniciar sesión para procesar un pedido.");
}

$user_id = $_SESSION['user_id'];
$cart_items = $_SESSION['cart_items'] ?? [];
$metodo_pago = $_POST['metodo_pago'] ?? 'transferencia';

if (empty($cart_items)) {
    die("Error: El carrito está vacío.");
}

try {
    $pdo->beginTransaction();

    $subtotal = 0;
    foreach ($cart_items as $item) {
        $subtotal += floatval($item['price']) * intval($item['quantity']);
    }

    $ivaPorcentaje = 16;
    $stmtIva = $pdo->query("SELECT valor FROM configuracion_sistema WHERE clave = 'iva_porcentaje'");
    if ($stmtIva) {
        $ivaPorcentaje = (int)($stmtIva->fetchColumn() ?: 16);
    }

    $iva = $subtotal * ($ivaPorcentaje / 100);
    $total = $subtotal + $iva;

    $numero_pedido = spCrearPedido($pdo, $user_id, $subtotal, $iva, $total, $metodo_pago);

    if (!$numero_pedido) {
        $anio = date('Y');
        $seq = $pdo->query("SELECT COALESCE(MAX(id), 0) FROM pedidos WHERE YEAR(created_at) = $anio")->fetchColumn();
        $numero_pedido = 'PED-' . $anio . '-' . str_pad($seq + 1, 6, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO pedidos (usuario_id, numero_pedido, subtotal, iva, total, metodo_pago, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())");
        $stmt->execute([$user_id, $numero_pedido, $subtotal, $iva, $total, $metodo_pago]);
        $pedido_id = $pdo->lastInsertId();

        $stmtDet = $pdo->prepare("INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $precio = floatval($item['price']);
            $cantidad = intval($item['quantity']);
            $stmtDet->execute([$pedido_id, intval($item['product_id']), $cantidad, $precio, $precio * $cantidad]);
        }
    }

    $pdo->commit();

    $_SESSION['cart_items'] = [];
    $_SESSION['ultimo_pedido'] = $pedido_id ?? null;

    header("Location: factura.php?id=" . ($pedido_id ?? $pdo->lastInsertId()));
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error en procesar-pedido: " . $e->getMessage());
    echo "Error interno del servidor";
}

function spCrearPedido(PDO $pdo, int $userId, float $subtotal, float $iva, float $total, string $metodoPago): ?string {
    try {
        $stmt = $pdo->prepare("CALL sp_crear_pedido(?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $subtotal, $iva, $total, $metodoPago]);
        return $stmt->fetchColumn() ?: null;
    } catch (Exception $e) {
        return null;
    }
}
?>