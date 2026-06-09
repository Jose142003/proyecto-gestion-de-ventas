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

    $cliente_id = isset($input['cliente_id']) ? (int)$input['cliente_id'] : 0;
    $tipo = isset($input['tipo']) ? trim($input['tipo']) : '';
    $titulo = isset($input['titulo']) ? trim($input['titulo']) : '';
    $descripcion = isset($input['descripcion']) ? trim($input['descripcion']) : '';
    $fecha_interaccion = isset($input['fecha_interaccion']) ? trim($input['fecha_interaccion']) : date('Y-m-d H:i:s');
    $usuario_id = (int)$_SESSION['user_id'];

    if ($cliente_id <= 0 || empty($tipo) || empty($titulo)) {
        echo json_encode(['success' => false, 'message' => 'Complete los campos requeridos (Cliente, Tipo, Título)']);
        exit;
    }

    $tipos_validos = ['llamada', 'correo', 'reunion', 'nota', 'seguimiento', 'recordatorio'];
    if (!in_array($tipo, $tipos_validos)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de interacción inválido']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO cliente_interacciones (cliente_id, usuario_id, tipo, titulo, descripcion, fecha_interaccion)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$cliente_id, $usuario_id, $tipo, $titulo, $descripcion, $fecha_interaccion]);

    echo json_encode([
        'success' => true,
        'message' => 'Interacción registrada correctamente',
        'id' => (int)$pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar la interacción']);
}
