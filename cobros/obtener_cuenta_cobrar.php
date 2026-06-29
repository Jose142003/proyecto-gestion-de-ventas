<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';

iniciarSesion();
requerirAdmin();
Database::setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    errorResponse('ID de cuenta requerido', 400);
}

$pdo = Database::getPdoOrError();

$stmt = $pdo->prepare("
    SELECT
        cc.id,
        cc.cliente_id,
        cc.factura_id,
        cc.numero_documento,
        cc.monto_original,
        cc.saldo_pendiente,
        cc.fecha_emision,
        cc.fecha_vencimiento,
        cc.dias_vencidos,
        cc.estado,
        cc.prioridad,
        cc.notas,
        cc.created_at,
        cc.updated_at,
        cl.nombre AS cliente_nombre,
        cl.documento AS cliente_documento,
        cl.telefono AS cliente_telefono,
        cl.email AS cliente_email,
        cl.estado AS cliente_estado
    FROM cuentas_cobrar cc
    JOIN clientes cl ON cc.cliente_id = cl.id
    WHERE cc.id = :id
");
$stmt->execute([':id' => $id]);
$cuenta = $stmt->fetch();

if (!$cuenta) {
    errorResponse('Cuenta no encontrada', 404);
}

$pagosStmt = $pdo->prepare("
    SELECT
        id,
        monto,
        metodo_pago,
        referencia,
        fecha_pago,
        usuario_id,
        notas,
        created_at
    FROM pagos_cobro
    WHERE cuenta_cobrar_id = :cuenta_id
    ORDER BY fecha_pago DESC, created_at DESC
");
$pagosStmt->execute([':cuenta_id' => $id]);
$pagos = $pagosStmt->fetchAll();

$cuenta['pagos'] = $pagos;

jsonResponse([
    'success' => true,
    'data' => $cuenta,
]);
