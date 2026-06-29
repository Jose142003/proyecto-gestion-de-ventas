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

    $motivos_validos = ['interes_mora', 'diferencia_precio', 'gastos_adicionales', 'error_cargo', 'otro'];
    if (!in_array($motivo, $motivos_validos)) {
        errorResponse('Motivo inválido');
    }

    $stmt = $pdo->prepare("SELECT f.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento FROM facturas f JOIN clientes c ON f.cliente_id = c.id WHERE f.id = ?");
    $stmt->execute([$factura_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        errorResponse('La factura no existe', 404);
    }

    $cliente_id = (int) $factura['cliente_id'];
    $detalles_nota = [];
    $subtotal = 0;

    foreach ($items as $item) {
        if (empty($item['concepto']) || empty($item['monto'])) {
            errorResponse('Cada item debe tener concepto y monto');
        }
        $monto = (float) $item['monto'];
        if ($monto <= 0) {
            errorResponse('El monto de cada item debe ser mayor a 0');
        }
        $detalles_nota[] = [
            'producto_id' => !empty($item['producto_id']) ? (int) $item['producto_id'] : null,
            'concepto' => $item['concepto'],
            'monto' => $monto
        ];
        $subtotal += $monto;
    }

    $iva = round($subtotal * obtenerIvaPorcentaje($pdo) / 100, 2);
    $total = round($subtotal + $iva, 2);

    $anio = date('Y');
    $stmt = $pdo->prepare("SELECT numero_nota FROM notas_debito WHERE numero_nota LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute(["ND-{$anio}-%"]);
    $ultima = $stmt->fetchColumn();
    if ($ultima) {
        $partes = explode('-', $ultima);
        $ultimo_num = (int) end($partes);
        $siguiente = $ultimo_num + 1;
    } else {
        $siguiente = 1;
    }
    $numero_nota = "ND-{$anio}-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO notas_debito (numero_nota, factura_id, cliente_id, motivo, descripcion, subtotal, iva, total, usuario_id, estado, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'emitida', NOW())");
    $stmt->execute([$numero_nota, $factura_id, $cliente_id, $motivo, $descripcion, $subtotal, $iva, $total, $_SESSION['user_id']]);
    $nota_id = (int) $pdo->lastInsertId();

    $stmt_d = $pdo->prepare("INSERT INTO notas_debito_detalles (nota_debito_id, producto_id, concepto, monto) VALUES (?, ?, ?, ?)");
    foreach ($detalles_nota as $d) {
        $stmt_d->execute([$nota_id, $d['producto_id'], $d['concepto'], $d['monto']]);
    }

    $pdo->commit();

    auditoriaRegistrar('crear_nota_debito', 'notas_debito', "Nota de Débito $numero_nota creada para factura #{$factura['numero_factura']} por Bs. $total");

    require_once __DIR__ . '/../telegram/notificar_notas.php';
    telegramNotificarNotaDebitoEmitida($pdo, $nota_id, $numero_nota, $factura['cliente_nombre'], $total, $motivo);

    $stmt = $pdo->prepare("SELECT nd.*, c.nombre AS cliente_nombre FROM notas_debito nd JOIN clientes c ON nd.cliente_id = c.id WHERE nd.id = ?");
    $stmt->execute([$nota_id]);
    $nota = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM notas_debito_detalles WHERE nota_debito_id = ?");
    $stmt->execute([$nota_id]);
    $nota['detalles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'message' => 'Nota de débito creada exitosamente', 'nota' => $nota], 201);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en crear_nota_debito: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
