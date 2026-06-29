<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';

iniciarSesion();
requerirAdmin();
Database::setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$pdo = Database::getPdoOrError();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

$estado = $_GET['estado'] ?? '';
$clienteId = $_GET['cliente_id'] ?? '';
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';

$where = [];
$params = [];

if ($estado !== '') {
    $where[] = 'cc.estado = :estado';
    $params[':estado'] = $estado;
}
if ($clienteId !== '') {
    $where[] = 'cc.cliente_id = :cliente_id';
    $params[':cliente_id'] = (int)$clienteId;
}
if ($fechaInicio !== '') {
    $where[] = 'cc.fecha_vencimiento >= :fecha_inicio';
    $params[':fecha_inicio'] = $fechaInicio;
}
if ($fechaFin !== '') {
    $where[] = 'cc.fecha_vencimiento <= :fecha_fin';
    $params[':fecha_fin'] = $fechaFin;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM cuentas_cobrar cc $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
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
        cl.estado AS cliente_estado,
        CASE
            WHEN cc.dias_vencidos <= 0 THEN 0
            WHEN cc.dias_vencidos BETWEEN 1 AND 30 THEN cc.saldo_pendiente
            ELSE 0
        END AS aging_0_30,
        CASE
            WHEN cc.dias_vencidos BETWEEN 31 AND 60 THEN cc.saldo_pendiente
            ELSE 0
        END AS aging_31_60,
        CASE
            WHEN cc.dias_vencidos BETWEEN 61 AND 90 THEN cc.saldo_pendiente
            ELSE 0
        END AS aging_61_90,
        CASE
            WHEN cc.dias_vencidos > 90 THEN cc.saldo_pendiente
            ELSE 0
        END AS aging_90_plus
    FROM cuentas_cobrar cc
    JOIN clientes cl ON cc.cliente_id = cl.id
    $whereClause
    ORDER BY cc.fecha_vencimiento ASC
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cuentas = $stmt->fetchAll();

jsonResponse([
    'success' => true,
    'data' => $cuentas,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'pages' => (int)ceil($total / $limit),
    ],
]);
