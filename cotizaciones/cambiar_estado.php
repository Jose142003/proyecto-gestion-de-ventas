<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();
verificarCSRF();

try {
    $pdo = conectarDB();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id']) || empty($input['estado'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $id = (int)$input['id'];
    $estado = $input['estado'];
    $seguimiento = trim($input['seguimiento'] ?? '');

    $validos = ['pendiente', 'aprobada', 'rechazada', 'vencida', 'convertida'];
    if (!in_array($estado, $validos)) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido']);
        exit;
    }

    if ($seguimiento) {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET estado = ?, seguimiento = CONCAT(COALESCE(seguimiento, ''), ?, '\n') WHERE id = ?");
        $nota = '[' . date('Y-m-d H:i') . '] ' . $seguimiento;
        $stmt->execute([$estado, $nota, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE cotizaciones SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
    }

    echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente'], JSON_UNESCAPED_UNICODE);

    auditoriaRegistrar('cambiar_estado_cotizacion', 'crm', "Cotización #$id cambiada a $estado");

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
