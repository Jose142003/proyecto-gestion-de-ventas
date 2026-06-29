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
$proveedorId = $_GET['proveedor_id'] ?? '';
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';

$where = [];
$params = [];

if ($estado !== '') {
    $where[] = 'cp.estado = :estado';
    $params[':estado'] = $estado;
}
if ($proveedorId !== '') {
    $where[] = 'cp.proveedor_id = :proveedor_id';
    $params[':proveedor_id'] = (int)$proveedorId;
}
if ($fechaInicio !== '') {
    $where[] = 'cp.fecha_vencimiento >= :fecha_inicio';
    $params[':fecha_inicio'] = $fechaInicio;
}
if ($fechaFin !== '') {
    $where[] = 'cp.fecha_vencimiento <= :fecha_fin';
    $params[':fecha_fin'] = $fechaFin;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM cuentas_pagar cp $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql = "
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
        pv.estado AS proveedor_estado,
        CASE
            WHEN cp.dias_vencidos <= 0 THEN 0
            WHEN cp.dias_vencidos BETWEEN 1 AND 30 THEN cp.saldo_pendiente
            ELSE 0
        END AS aging_0_30,
        CASE
            WHEN cp.dias_vencidos BETWEEN 31 AND 60 THEN cp.saldo_pendiente
            ELSE 0
        END AS aging_31_60,
        CASE
            WHEN cp.dias_vencidos BETWEEN 61 AND 90 THEN cp.saldo_pendiente
            ELSE 0
        END AS aging_61_90,
        CASE
            WHEN cp.dias_vencidos > 90 THEN cp.saldo_pendiente
            ELSE 0
        END AS aging_90_plus
    FROM cuentas_pagar cp
    JOIN proveedores pv ON cp.proveedor_id = pv.id
    $whereClause
    ORDER BY cp.fecha_vencimiento ASC
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
