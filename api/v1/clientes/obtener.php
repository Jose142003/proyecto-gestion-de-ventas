<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de cliente requerido');
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, tipo_documento, documento, nombre, email, telefono, direccion, ciudad, estado, fecha_registro FROM clientes WHERE id = ?");
    $stmt->execute([$id]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        apiError('Cliente no encontrado', 404);
    }

    $stmtPed = $pdo->prepare("SELECT id, numero_pedido, fecha_pedido, total, estado FROM pedidos WHERE cliente_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmtPed->execute([$id]);
    $cliente['pedidos_recientes'] = $stmtPed->fetchAll();

    apiResponse(['success' => true, 'data' => $cliente]);
} catch (Exception $e) {
    apiError('Error al obtener cliente: ' . $e->getMessage(), 500);
}
