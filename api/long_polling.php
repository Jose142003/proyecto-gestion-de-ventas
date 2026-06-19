<?php

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id']) || !esAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$maxWait = 30;
$interval = 3;
$elapsed = 0;

$db = Database::getConnection();

while ($elapsed < $maxWait) {
    if (connection_aborted()) {
        exit;
    }

    try {
        $stmt = $db->prepare(
            "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.total, p.fecha_pedido
             FROM pedidos p
             JOIN users u ON p.usuario_id = u.id
             WHERE p.id > :last_id
             ORDER BY p.id ASC
             LIMIT 50"
        );
        $stmt->execute([':last_id' => $lastId]);
        $nuevos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($nuevos)) {
            echo json_encode([
                'success' => true,
                'timeout' => false,
                'data' => $nuevos,
                'last_id' => max(array_column($nuevos, 'id')),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } catch (Exception $e) {
        error_log("LongPolling Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno']);
        exit;
    }

    $elapsed += $interval;
    if ($elapsed < $maxWait) {
        sleep($interval);
    }
}

echo json_encode([
    'success' => true,
    'timeout' => true,
    'data' => [],
    'last_id' => $lastId,
]);
