<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();
verificarCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    $pdo = conectarDB();
    $input = json_decode(file_get_contents('php://input'), true);

    $id = isset($input['id']) ? (int)$input['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de interacción inválido']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM cliente_interacciones WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Interacción eliminada correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'La interacción no existe']);
    }

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la interacción']);
}
