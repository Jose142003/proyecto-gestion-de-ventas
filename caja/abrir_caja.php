<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['monto_inicial'])) {
    echo json_encode(['success' => false, 'message' => 'Monto inicial requerido']);
    exit;
}

try {
    $db = conectarDB();

    // Verificar si ya hay una caja abierta para hoy
    $stmt = $db->prepare("SELECT id FROM caja_arqueos WHERE estado = 'abierta' AND DATE(fecha_apertura) = CURDATE()");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ya hay una caja abierta para hoy']);
        exit;
    }

    // Generar número de arqueo
    $numeroArqueo = 'CAJA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

    $query = "INSERT INTO caja_arqueos (numero_arqueo, fecha_apertura, usuario_apertura_id, monto_inicial, estado, observaciones) 
              VALUES (?, NOW(), ?, ?, 'abierta', ?)";
    $stmt = $db->prepare($query);

    if ($stmt->execute([$numeroArqueo, $_SESSION['user_id'], $data['monto_inicial'], $data['observaciones'] ?? ''])) {
        echo json_encode(['success' => true, 'message' => 'Caja abierta correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al abrir caja']);
    }
} catch (Exception $e) {
    error_log("Abrir caja error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al abrir caja']);
}
?>