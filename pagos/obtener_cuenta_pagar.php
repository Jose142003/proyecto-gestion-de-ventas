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
        cp.id,
        cp.proveedor_id,
        cp.compra_id,
        cp.numero_documento,
        cp.monto_original,
        cp.saldo_pendiente,
        cp.fecha_emision,
        cp.fecha_vencimiento,
        cp.dias_vencidos,
        cp.estado,
        cp.prioridad,
        cp.notas,
        cp.created_at,
        cp.updated_at,
        pv.nombre_comercial AS proveedor_nombre,
        pv.ruc AS proveedor_ruc,
        pv.telefono_principal AS proveedor_telefono,
        pv.email_principal AS proveedor_email,
        pv.estado AS proveedor_estado
    FROM cuentas_pagar cp
    JOIN proveedores pv ON cp.proveedor_id = pv.id
    WHERE cp.id = :id
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
    FROM pagos_proveedor
    WHERE cuenta_pagar_id = :cuenta_id
    ORDER BY fecha_pago DESC, created_at DESC
");
$pagosStmt->execute([':cuenta_id' => $id]);
$pagos = $pagosStmt->fetchAll();

$cuenta['pagos'] = $pagos;

jsonResponse([
    'success' => true,
    'data' => $cuenta,
]);
