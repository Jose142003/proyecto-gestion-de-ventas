<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'No autorizado'], 401);
}

try {
    $pdo = conectarDB();
    $pedido_id = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
    $envio_id = isset($_GET['envio_id']) ? intval($_GET['envio_id']) : 0;

    if ($pedido_id <= 0 && $envio_id <= 0) {
        errorResponse('Debe proporcionar pedido_id o envio_id');
    }

    if ($envio_id > 0) {
        $stmt = $pdo->prepare("SELECT e.*, p.numero_pedido, p.estado as pedido_estado FROM envios e LEFT JOIN pedidos p ON e.pedido_id = p.id WHERE e.id = ?");
        $stmt->execute([$envio_id]);
    } else {
        $stmt = $pdo->prepare("SELECT e.*, p.numero_pedido, p.estado as pedido_estado FROM envios e LEFT JOIN pedidos p ON e.pedido_id = p.id WHERE e.pedido_id = ? ORDER BY e.id DESC LIMIT 1");
        $stmt->execute([$pedido_id]);
    }

    $envio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$envio) {
        jsonResponse(['success' => false, 'message' => 'Envío no encontrado', 'envio' => null, 'historial' => []]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM envios_historial WHERE envio_id = ? ORDER BY created_at ASC");
    $stmt->execute([$envio['id']]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'envio' => $envio, 'historial' => $historial]);

} catch (PDOException $e) {
    error_log("Error en obtener_envio: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
