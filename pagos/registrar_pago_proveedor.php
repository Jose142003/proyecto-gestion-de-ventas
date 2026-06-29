<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';

iniciarSesion();
requerirAdmin();
verificarCSRF();
Database::setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    errorResponse('Datos JSON inválidos', 400);
}

$cuentaPagarId = (int)($input['cuenta_pagar_id'] ?? 0);
$monto = (float)($input['monto'] ?? 0);
$metodoPago = $input['metodo_pago'] ?? '';
$referencia = trim($input['referencia'] ?? '');
$fechaPago = $input['fecha_pago'] ?? date('Y-m-d');
$notas = trim($input['notas'] ?? '');

if ($cuentaPagarId <= 0) {
    errorResponse('ID de cuenta requerido', 400);
}
if ($monto <= 0) {
    errorResponse('El monto debe ser mayor a cero', 400);
}

$metodosValidos = ['efectivo', 'transferencia', 'cheque', 'deposito'];
if (!in_array($metodoPago, $metodosValidos)) {
    errorResponse('Método de pago inválido', 400);
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaPago)) {
    errorResponse('Formato de fecha inválido (YYYY-MM-DD)', 400);
}

$pdo = Database::getPdoOrError();

$stmt = $pdo->prepare("SELECT id, proveedor_id, saldo_pendiente, estado FROM cuentas_pagar WHERE id = :id");
$stmt->execute([':id' => $cuentaPagarId]);
$cuenta = $stmt->fetch();

if (!$cuenta) {
    errorResponse('Cuenta por pagar no encontrada', 404);
}

if ($cuenta['estado'] === 'pagada' || $cuenta['estado'] === 'anulada') {
    errorResponse('La cuenta ya está ' . $cuenta['estado'], 400);
}

if ($monto > (float)$cuenta['saldo_pendiente']) {
    errorResponse('El monto excede el saldo pendiente (' . number_format($cuenta['saldo_pendiente'], 2) . ')', 400);
}

try {
    $pdo->beginTransaction();

    $nuevoSaldo = (float)$cuenta['saldo_pendiente'] - $monto;
    $nuevoEstado = $nuevoSaldo <= 0 ? 'pagada' : 'parcial';

    $stmtPago = $pdo->prepare("
        INSERT INTO pagos_proveedor (cuenta_pagar_id, monto, metodo_pago, referencia, fecha_pago, usuario_id, notas)
        VALUES (:cuenta_pagar_id, :monto, :metodo_pago, :referencia, :fecha_pago, :usuario_id, :notas)
    ");
    $stmtPago->execute([
        ':cuenta_pagar_id' => $cuentaPagarId,
        ':monto' => $monto,
        ':metodo_pago' => $metodoPago,
        ':referencia' => $referencia ?: null,
        ':fecha_pago' => $fechaPago,
        ':usuario_id' => $_SESSION['user_id'],
        ':notas' => $notas ?: null,
    ]);
    $pagoId = (int)$pdo->lastInsertId();

    $stmtUpdate = $pdo->prepare("UPDATE cuentas_pagar SET saldo_pendiente = :saldo, estado = :estado WHERE id = :id");
    $stmtUpdate->execute([
        ':saldo' => $nuevoSaldo,
        ':estado' => $nuevoEstado,
        ':id' => $cuentaPagarId,
    ]);

    $stmtUpdateProv = $pdo->prepare("UPDATE proveedores SET saldo_pendiente = GREATEST(0, saldo_pendiente - :monto) WHERE id = :id");
    $stmtUpdateProv->execute([
        ':monto' => $monto,
        ':id' => $cuenta['proveedor_id'],
    ]);

    $pdo->commit();

    auditoriaRegistrar(
        'registrar_pago_proveedor',
        'pagos',
        "Pago de $monto registrado en cuenta #$cuentaPagarId ($nuevoEstado)"
    );

    require_once __DIR__ . '/../telegram/notificar_cobro.php';
    telegramNotificarPagoProveedor($pdo, $cuentaPagarId, $monto, $metodoPago, $nuevoEstado);

    jsonResponse([
        'success' => true,
        'message' => 'Pago registrado correctamente',
        'data' => [
            'pago_id' => $pagoId,
            'cuenta_id' => $cuentaPagarId,
            'saldo_anterior' => (float)$cuenta['saldo_pendiente'],
            'monto' => $monto,
            'saldo_pendiente' => $nuevoSaldo,
            'estado' => $nuevoEstado,
        ],
    ], 201);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error registrar_pago_proveedor: " . $e->getMessage());
    errorResponse('Error al registrar el pago', 500);
}
