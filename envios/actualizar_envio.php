<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

requerirAdmin();

verificarCSRF();

try {
    $pdo = conectarDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['id'])) {
        errorResponse('ID de envío requerido');
    }

    $stmt = $pdo->prepare("SELECT * FROM envios WHERE id = ?");
    $stmt->execute([$data['id']]);
    $envio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$envio) {
        errorResponse('Envío no encontrado');
    }

    $estado_anterior = $envio['estado'];
    $estado_nuevo = $data['estado'] ?? $envio['estado'];

    $campos = [];
    $params = [];

    if (isset($data['transportista'])) {
        $campos[] = "transportista = ?";
        $params[] = $data['transportista'];
    }
    if (isset($data['numero_guia'])) {
        $campos[] = "numero_guia = ?";
        $params[] = $data['numero_guia'];
    }
    if (isset($data['url_rastreo'])) {
        $campos[] = "url_rastreo = ?";
        $params[] = $data['url_rastreo'];
    }
    if (isset($data['estado'])) {
        $campos[] = "estado = ?";
        $params[] = $data['estado'];
    }
    if (isset($data['fecha_estimada_entrega'])) {
        $campos[] = "fecha_estimada_entrega = ?";
        $params[] = $data['fecha_estimada_entrega'];
    }
    if (isset($data['notas'])) {
        $campos[] = "notas = ?";
        $params[] = $data['notas'];
    }

    if ($estado_nuevo === 'entregado') {
        $campos[] = "fecha_entrega = NOW()";
    }

    $campos[] = "updated_at = NOW()";

    if (!empty($campos)) {
        $params[] = $data['id'];
        $sql = "UPDATE envios SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    if ($estado_nuevo === 'entregado') {
        $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id = ?");
        $stmt->execute([$envio['pedido_id']]);
    }

    $stmt = $pdo->prepare("INSERT INTO envios_historial (envio_id, estado_anterior, estado_nuevo, ubicacion, descripcion, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([
        $data['id'],
        $estado_anterior,
        $estado_nuevo,
        $data['ubicacion'] ?? '',
        $data['descripcion'] ?? 'Actualización de estado a: ' . $estado_nuevo
    ]);

    jsonResponse(['success' => true, 'message' => 'Envío actualizado correctamente']);

} catch (PDOException $e) {
    error_log("Error en actualizar_envio: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
