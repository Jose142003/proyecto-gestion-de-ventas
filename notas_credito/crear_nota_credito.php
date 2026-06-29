<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

requerirAdmin();
verificarCSRF();

try {
    $pdo = Database::getConnection();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['factura_id']) || empty($data['motivo']) || empty($data['descripcion']) || empty($data['items'])) {
        errorResponse('factura_id, motivo, descripcion e items son requeridos');
    }

    $factura_id = (int) $data['factura_id'];
    $motivo = $data['motivo'];
    $descripcion = $data['descripcion'];
    $items = $data['items'];

    $motivos_validos = ['devolucion', 'descuento', 'error_facturacion', 'anulacion', 'otro'];
    if (!in_array($motivo, $motivos_validos)) {
        errorResponse('Motivo inválido');
    }

    $stmt = $pdo->prepare("SELECT f.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.id = ?");
    $stmt->execute([$factura_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        errorResponse('La factura no existe', 404);
    }

    $stmt = $pdo->prepare("SELECT id FROM notas_credito WHERE factura_id = ? AND estado != 'anulada'");
    $stmt->execute([$factura_id]);
    if ($stmt->fetch()) {
        errorResponse('La factura ya tiene una nota de crédito activa');
    }

    $cliente_id = (int) $factura['cliente_id'];

    $stmt = $pdo->prepare("SELECT producto_id, cantidad, precio_unitario, subtotal FROM factura_detalles WHERE factura_id = ?");
    $stmt->execute([$factura_id]);
    $factura_detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $detalles_indexados = [];
    foreach ($factura_detalles as $d) {
        $detalles_indexados[(int) $d['producto_id']] = $d;
    }

    $detalles_nota = [];
    $subtotal = 0;

    foreach ($items as $item) {
        if (empty($item['producto_id']) || empty($item['cantidad'])) {
            errorResponse('Cada item debe tener producto_id y cantidad');
        }
        $producto_id = (int) $item['producto_id'];
        $cantidad = (int) $item['cantidad'];

        if (!isset($detalles_indexados[$producto_id])) {
            errorResponse("El producto ID $producto_id no pertenece a la factura");
        }

        $precio_unitario = (float) $detalles_indexados[$producto_id]['precio_unitario'];
        $subtotal_item = $precio_unitario * $cantidad;

        $detalles_nota[] = [
            'producto_id' => $producto_id,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'subtotal' => $subtotal_item
        ];
        $subtotal += $subtotal_item;
    }

    $iva = round($subtotal * obtenerIvaPorcentaje($pdo) / 100, 2);
    $total = round($subtotal + $iva, 2);

    $anio = date('Y');
    $stmt = $pdo->prepare("SELECT numero_nota FROM notas_credito WHERE numero_nota LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["NC-{$anio}-%"]);
    $ultima = $stmt->fetchColumn();
    if ($ultima) {
        $partes = explode('-', $ultima);
        $ultimo_num = (int) end($partes);
        $siguiente = $ultimo_num + 1;
    } else {
        $siguiente = 1;
    }
    $numero_nota = "NC-{$anio}-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO notas_credito (numero_nota, factura_id, cliente_id, motivo, descripcion, subtotal, iva, total, usuario_id, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitida', NOW())");
    $stmt->execute([$numero_nota, $factura_id, $cliente_id, $motivo, $descripcion, $subtotal, $iva, $total, $_SESSION['user_id']]);
    $nota_id = (int) $pdo->lastInsertId();

    $stmt_d = $pdo->prepare("INSERT INTO notas_credito_detalles (nota_credito_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($detalles_nota as $d) {
        $stmt_d->execute([$nota_id, $d['producto_id'], $d['cantidad'], $d['precio_unitario'], $d['subtotal']]);
    }

    $pdo->commit();

    auditoriaRegistrar('crear_nota_credito', 'notas_credito', "Nota de Crédito $numero_nota creada para factura #{$factura['numero_factura']} por Bs. $total");

    require_once __DIR__ . '/../telegram/notificar_notas.php';
    telegramNotificarNotaCreditoEmitida($pdo, $nota_id, $numero_nota, $factura['cliente_nombre'], $total, $motivo);

    $stmt = $pdo->prepare("SELECT nc.*, c.nombre AS cliente_nombre FROM notas_credito nc JOIN clientes c ON nc.cliente_id = c.id WHERE nc.id = ?");
    $stmt->execute([$nota_id]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM notas_credito_detalles WHERE nota_credito_id = ?");
    $stmt->execute([$nota_id]);
    $nota['detalles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'message' => 'Nota de crédito creada exitosamente', 'nota' => $nota], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en crear_nota_credito: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
