<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $id = (int)$input['id'];
    $cliente_id = !empty($input['cliente_id']) ? (int)$input['cliente_id'] : null;
    $cliente_nombre = trim($input['cliente_nombre'] ?? '');
    $cliente_email = trim($input['cliente_email'] ?? '');
    $cliente_telefono = trim($input['cliente_telefono'] ?? '');
    $cliente_direccion = trim($input['cliente_direccion'] ?? '');
    $fecha_vencimiento = $input['fecha_vencimiento'] ?? null;
    $notas = trim($input['notas'] ?? '');
    $items = $input['items'] ?? [];

    if (!$cliente_nombre || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Nombre del cliente y productos requeridos']);
        exit;
    }

    $subtotal = 0;
    foreach ($items as $item) {
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        $precio = (float)($item['precio_unitario'] ?? 0);
        $subtotal += $cantidad * $precio;
    }
    $ivaPorcentaje = $pdo->query("SELECT valor FROM configuracion_sistema WHERE clave = 'iva_porcentaje'")->fetchColumn() ?: 16;
    $iva = $subtotal * ($ivaPorcentaje / 100);
    $total = $subtotal + $iva;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE cotizaciones SET cliente_id=?, cliente_nombre=?, cliente_email=?, cliente_telefono=?, cliente_direccion=?, subtotal=?, iva=?, total=?, notas=?, fecha_vencimiento=? WHERE id=?");
    $stmt->execute([$cliente_id, $cliente_nombre, $cliente_email, $cliente_telefono, $cliente_direccion, $subtotal, $iva, $total, $notas, $fecha_vencimiento, $id]);

    $pdo->prepare("DELETE FROM cotizacion_detalles WHERE cotizacion_id = ?")->execute([$id]);

    $detStmt = $pdo->prepare("INSERT INTO cotizacion_detalles (cotizacion_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        $precio = (float)($item['precio_unitario'] ?? 0);
        $subtotal_item = $cantidad * $precio;
        $detStmt->execute([
            $id,
            !empty($item['producto_id']) ? (int)$item['producto_id'] : null,
            $item['producto_nombre'] ?? 'Producto',
            $cantidad,
            $precio,
            $subtotal_item
        ]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Cotización actualizada correctamente'], JSON_UNESCAPED_UNICODE);

    auditoriaRegistrar('editar_cotizacion', 'crm', "Cotización #$id actualizada");

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
