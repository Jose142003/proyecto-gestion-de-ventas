<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['pedido_id']) || empty($data['transportista']) || empty($data['numero_guia'])) {
        errorResponse('pedido_id, transportista y numero_guia son requeridos');
    }

    $stmt = $pdo->prepare("SELECT id, numero_pedido, estado FROM pedidos WHERE id = ?");
    $stmt->execute([$data['pedido_id']]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pedido) {
        errorResponse('El pedido no existe');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO envios (pedido_id, pedido_numero, transportista, numero_guia, url_rastreo, fecha_envio, fecha_estimada_entrega, estado, notas, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'preparando', ?, NOW(), NOW())");
    $stmt->execute([
        $data['pedido_id'],
        $pedido['numero_pedido'],
        $data['transportista'],
        $data['numero_guia'],
        $data['url_rastreo'] ?? '',
        $data['fecha_envio'] ?? date('Y-m-d H:i:s'),
        $data['fecha_estimada_entrega'] ?? null,
        $data['notas'] ?? ''
    ]);
    $envio_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("UPDATE pedidos SET transportista = ?, numero_guia = ?, costo_envio = ?, estado = 'enviado' WHERE id = ?");
    $stmt->execute([
        $data['transportista'],
        $data['numero_guia'],
        $data['costo_envio'] ?? 0,
        $data['pedido_id']
    ]);

    $stmt = $pdo->prepare("INSERT INTO envios_historial (envio_id, estado_anterior, estado_nuevo, ubicacion, descripcion, created_at) VALUES (?, NULL, 'preparando', ?, ?, NOW())");
    $stmt->execute([$envio_id, '', 'Envío creado - ' . ($data['notas'] ?? '')]);

    $pdo->commit();

    $stmt = $pdo->prepare("SELECT * FROM envios WHERE id = ?");
    $stmt->execute([$envio_id]);
    $envio = $stmt->fetch(PDO::FETCH_ASSOC);

    jsonResponse(['success' => true, 'message' => 'Envío creado correctamente', 'envio' => $envio]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en crear_envio: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
