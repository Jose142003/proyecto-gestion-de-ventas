<?php
session_start();

require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id']) || !esAdmin()) {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

if (ob_get_level()) {
    ob_end_clean();
}

$db = Database::getConnection();

$lastCheck = date('Y-m-d H:i:s');
$heartbeatCount = 0;

while (true) {
    if (connection_aborted()) {
        break;
    }

    try {
        $stmt = $db->prepare(
            "SELECT p.id, p.numero_pedido, u.nombre as cliente, p.total, p.fecha_pedido
             FROM pedidos p
             JOIN users u ON p.usuario_id = u.id
             WHERE p.estado = 'pendiente' AND p.fecha_pedido >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
             ORDER BY p.fecha_pedido DESC"
        );
        $stmt->execute();
        $nuevos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($nuevos)) {
            foreach ($nuevos as $pedido) {
                $data = json_encode([
                    'id' => $pedido['id'],
                    'numero_pedido' => $pedido['numero_pedido'],
                    'cliente' => $pedido['cliente'],
                    'total' => $pedido['total'],
                    'fecha' => $pedido['fecha_pedido'],
                ], JSON_UNESCAPED_UNICODE);
                echo "event: nuevo_pedido\n";
                echo "data: " . $data . "\n\n";
            }
        }
    } catch (Exception $e) {
        error_log("SSE Error: " . $e->getMessage());
    }

    $heartbeatCount++;
    if ($heartbeatCount >= 3) {
        echo "event: heartbeat\n";
        echo "data: " . json_encode(['time' => date('Y-m-d H:i:s')]) . "\n\n";
        $heartbeatCount = 0;
    }

    ob_flush();
    flush();

    sleep(10);
}
