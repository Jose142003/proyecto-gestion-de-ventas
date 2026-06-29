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

$totalPendiente = 0;
$totalVencido = 0;
$totalPagadoMes = 0;
$agingDist = ['0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
$topMorosos = [];

try {
    $stmtPendiente = $pdo->query("
        SELECT COALESCE(SUM(saldo_pendiente), 0)
        FROM cuentas_cobrar
        WHERE estado IN ('pendiente', 'parcial', 'vencida')
    ");
    $totalPendiente = (float)$stmtPendiente->fetchColumn();

    $stmtVencido = $pdo->query("
        SELECT COALESCE(SUM(saldo_pendiente), 0)
        FROM cuentas_cobrar
        WHERE estado IN ('pendiente', 'parcial', 'vencida')
          AND fecha_vencimiento < CURDATE()
    ");
    $totalVencido = (float)$stmtVencido->fetchColumn();

    $stmtPagadoMes = $pdo->prepare("
        SELECT COALESCE(SUM(pc.monto), 0)
        FROM pagos_cobro pc
        WHERE pc.fecha_pago BETWEEN :inicio AND :fin
    ");
    $stmtPagadoMes->execute([
        ':inicio' => date('Y-m-01'),
        ':fin' => date('Y-m-t'),
    ]);
    $totalPagadoMes = (float)$stmtPagadoMes->fetchColumn();

    $stmtAging = $pdo->query("
        SELECT
            COALESCE(SUM(CASE WHEN dias_vencidos BETWEEN 1 AND 30 THEN saldo_pendiente ELSE 0 END), 0) AS aging_0_30,
            COALESCE(SUM(CASE WHEN dias_vencidos BETWEEN 31 AND 60 THEN saldo_pendiente ELSE 0 END), 0) AS aging_31_60,
            COALESCE(SUM(CASE WHEN dias_vencidos BETWEEN 61 AND 90 THEN saldo_pendiente ELSE 0 END), 0) AS aging_61_90,
            COALESCE(SUM(CASE WHEN dias_vencidos > 90 THEN saldo_pendiente ELSE 0 END), 0) AS aging_90_plus
        FROM cuentas_cobrar
        WHERE estado IN ('pendiente', 'parcial', 'vencida')
          AND fecha_vencimiento < CURDATE()
    ");
    $agingRow = $stmtAging->fetch();
    $agingDist = [
        '0_30' => (float)$agingRow['aging_0_30'],
        '31_60' => (float)$agingRow['aging_31_60'],
        '61_90' => (float)$agingRow['aging_61_90'],
        '90_plus' => (float)$agingRow['aging_90_plus'],
    ];

    $stmtMorosos = $pdo->query("
        SELECT
            cl.id,
            cl.nombre,
            cl.documento,
            cl.telefono,
            cl.email,
            COUNT(cc.id) AS cuentas_vencidas,
            SUM(cc.saldo_pendiente) AS total_adeudado,
            MAX(cc.dias_vencidos) AS max_dias_vencido
        FROM cuentas_cobrar cc
        JOIN clientes cl ON cc.cliente_id = cl.id
        WHERE cc.estado IN ('pendiente', 'parcial', 'vencida')
          AND cc.fecha_vencimiento < CURDATE()
        GROUP BY cl.id, cl.nombre, cl.documento, cl.telefono, cl.email
        ORDER BY total_adeudado DESC
        LIMIT 5
    ");
    $topMorosos = $stmtMorosos->fetchAll();
} catch (Exception $e) {
    error_log("Error obtener_resumen_cobros: " . $e->getMessage());
    errorResponse('Error al obtener resumen', 500);
}

jsonResponse([
    'success' => true,
    'data' => [
        'total_pendiente' => $totalPendiente,
        'total_vencido' => $totalVencido,
        'total_pagado_mes' => $totalPagadoMes,
        'aging_distribution' => $agingDist,
        'top_morosos' => $topMorosos,
    ],
]);
