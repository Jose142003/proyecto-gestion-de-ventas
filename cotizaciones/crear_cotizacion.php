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
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $cliente_id = !empty($input['cliente_id']) ? (int)$input['cliente_id'] : null;
    $cliente_nombre = trim($input['cliente_nombre'] ?? '');
    $cliente_email = trim($input['cliente_email'] ?? '');
    $cliente_telefono = trim($input['cliente_telefono'] ?? '');
    $cliente_direccion = trim($input['cliente_direccion'] ?? '');
    $fecha_vencimiento = $input['fecha_vencimiento'] ?? null;
    $notas = trim($input['notas'] ?? '');
    $items = $input['items'] ?? [];
    $usuario_id = (int)($_SESSION['user_id'] ?? 0);

    if (!$cliente_nombre || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Nombre del cliente y productos requeridos']);
        exit;
    }

    // Generar número de cotización
    $anio = date('Y');
    $seq = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE YEAR(fecha_creacion) = $anio")->fetchColumn();
    $numero = 'COT-' . $anio . '-' . str_pad($seq + 1, 6, '0', STR_PAD_LEFT);

    // Calcular totales
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

    $stmt = $pdo->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_id, cliente_nombre, cliente_email, cliente_telefono, cliente_direccion, usuario_id, subtotal, iva, total, notas, fecha_vencimiento, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$numero, $cliente_id, $cliente_nombre, $cliente_email, $cliente_telefono, $cliente_direccion, $usuario_id, $subtotal, $iva, $total, $notas, $fecha_vencimiento]);
    $cotizacion_id = $pdo->lastInsertId();

    $detStmt = $pdo->prepare("INSERT INTO cotizacion_detalles (cotizacion_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        $precio = (float)($item['precio_unitario'] ?? 0);
        $subtotal_item = $cantidad * $precio;
        $detStmt->execute([
            $cotizacion_id,
            !empty($item['producto_id']) ? (int)$item['producto_id'] : null,
            $item['producto_nombre'] ?? 'Producto',
            $cantidad,
            $precio,
            $subtotal_item
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Cotización creada exitosamente',
        'data' => ['id' => $cotizacion_id, 'numero_cotizacion' => $numero]
    ], JSON_UNESCAPED_UNICODE);

    auditoriaRegistrar('crear_cotizacion', 'crm', "Cotización $numero creada para $cliente_nombre");

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al crear cotización: ' . $e->getMessage()]);
}
