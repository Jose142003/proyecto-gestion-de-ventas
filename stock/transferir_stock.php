<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

session_start();
requerirAdmin();
verificarCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['producto_id']) || empty($input['almacen_origen_id']) || empty($input['almacen_destino_id']) || empty($input['cantidad'])) {
    errorResponse('Datos incompletos. Se requiere: producto_id, almacen_origen_id, almacen_destino_id, cantidad');
}

$producto_id = (int)$input['producto_id'];
$almacen_origen_id = (int)$input['almacen_origen_id'];
$almacen_destino_id = (int)$input['almacen_destino_id'];
$cantidad = (int)$input['cantidad'];

if ($cantidad <= 0) {
    errorResponse('La cantidad debe ser mayor a 0');
}

if ($almacen_origen_id === $almacen_destino_id) {
    errorResponse('El almacén de origen y destino no pueden ser el mismo');
}

$observaciones = trim($input['observaciones'] ?? '');

try {
    $pdo = Database::getConnection();

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, name, sku FROM products WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();
    if (!$producto) {
        throw new Exception('Producto no encontrado');
    }

    $stmt = $pdo->prepare("SELECT id, nombre FROM almacenes WHERE id = ? AND activo = 1");
    $stmt->execute([$almacen_origen_id]);
    $origen = $stmt->fetch();
    if (!$origen) {
        throw new Exception('Almacén de origen no encontrado o inactivo');
    }

    $stmt = $pdo->prepare("SELECT id, nombre FROM almacenes WHERE id = ? AND activo = 1");
    $stmt->execute([$almacen_destino_id]);
    $destino = $stmt->fetch();
    if (!$destino) {
        throw new Exception('Almacén de destino no encontrado o inactivo');
    }

    $stmt = $pdo->prepare("SELECT id, stock FROM producto_almacen WHERE producto_id = ? AND almacen_id = ? FOR UPDATE");
    $stmt->execute([$producto_id, $almacen_origen_id]);
    $stock_origen = $stmt->fetch();

    if (!$stock_origen) {
        throw new Exception('El producto no existe en el almacén de origen');
    }

    if ((int)$stock_origen['stock'] < $cantidad) {
        throw new Exception("Stock insuficiente en {$origen['nombre']}. Disponible: {$stock_origen['stock']}, solicitado: $cantidad");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO producto_almacen (producto_id, almacen_id, stock, stock_minimo, created_at)
         VALUES (?, ?, ?, 5, NOW())
         ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock), updated_at = NOW()"
    );
    $stmt->execute([$producto_id, $almacen_destino_id, $cantidad]);

    $stmt = $pdo->prepare("UPDATE producto_almacen SET stock = stock - ?, updated_at = NOW() WHERE producto_id = ? AND almacen_id = ?");
    $stmt->execute([$cantidad, $producto_id, $almacen_origen_id]);

    $numero_transferencia = 'TRF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

    $usuario_id = (int)($_SESSION['user_id'] ?? 0);

    $stmt = $pdo->prepare(
        "INSERT INTO transferencias_almacen (numero_transferencia, producto_id, cantidad, almacen_origen_id, almacen_destino_id, usuario_id, estado, observaciones, fecha_completada, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 'completada', ?, NOW(), NOW())"
    );
    $stmt->execute([$numero_transferencia, $producto_id, $cantidad, $almacen_origen_id, $almacen_destino_id, $usuario_id, $observaciones]);

    $pdo->commit();

    auditoriaRegistrar(
        'transferir_stock',
        'stock',
        "Transferencia de stock: {$producto['name']} (ID: $producto_id) - $cantidad unidades de {$origen['nombre']} a {$destino['nombre']} - Nro: $numero_transferencia"
    );

    require_once __DIR__ . '/../telegram/notificar_almacen.php';
    telegramNotificarTransferencia($pdo, $producto_id, $almacen_origen_id, $almacen_destino_id, $cantidad, $producto['name']);

    jsonResponse([
        'success' => true,
        'message' => 'Transferencia completada correctamente',
        'data' => [
            'numero_transferencia' => $numero_transferencia,
            'producto_id' => $producto_id,
            'producto_nombre' => $producto['name'],
            'cantidad' => $cantidad,
            'almacen_origen' => $origen['nombre'],
            'almacen_destino' => $destino['nombre'],
            'estado' => 'completada'
        ]
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error transferencia stock: " . $e->getMessage());

    if ($e instanceof Exception) {
        errorResponse($e->getMessage());
    }
    errorResponse('Error interno del servidor', 500);
}
