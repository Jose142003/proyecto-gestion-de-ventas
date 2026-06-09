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
    $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM cotizaciones WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Cotización eliminada correctamente'], JSON_UNESCAPED_UNICODE);

    auditoriaRegistrar('eliminar_cotizacion', 'crm', "Cotización #$id eliminada");

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
