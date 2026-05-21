<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
});

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$alerta_id = (int)($input['alerta_id'] ?? $_POST['alerta_id'] ?? 0);

if (!$alerta_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de alerta requerido']);
    exit;
}

try {
    $pdo = conectarDB();

    $check = $pdo->query("SHOW TABLES LIKE 'alertas_stock'");
    if ($check->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Tabla alertas_stock no existe. Ejecute la migración SQL.']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE alertas_stock SET resuelta = TRUE, fecha_resolucion = NOW() WHERE id = ? AND resuelta = FALSE");
    $stmt->execute([$alerta_id]);

    if ($stmt->rowCount() > 0) {
        try {
            $stmtLog = $pdo->prepare("INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address) VALUES (?, ?, 'resolver_alerta_stock', 'inventario', ?, ?)");
            $stmtLog->execute([$_SESSION['user_id'], $_SESSION['user_nombre'] ?? '', "Alerta de stock resuelta: #$alerta_id", $_SERVER['REMOTE_ADDR'] ?? '']);
        } catch (Throwable $e) {}
        echo json_encode(['success' => true, 'message' => 'Alerta resuelta correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Alerta no encontrada o ya resuelta']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al resolver alerta']);
}
