<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de pedido requerido');
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT p.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento, c.telefono AS cliente_telefono, c.direccion AS cliente_direccion
                           FROM pedidos p
                           LEFT JOIN clientes c ON p.cliente_id = c.id
                           WHERE p.id = ?");
    $stmt->execute([$id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        apiError('Pedido no encontrado', 404);
    }

    $stmtDet = $pdo->prepare("SELECT pd.*, pr.name AS producto_nombre, pr.sku AS producto_sku, pr.image_url AS producto_imagen
                              FROM pedido_detalles pd
                              LEFT JOIN products pr ON pd.producto_id = pr.id
                              WHERE pd.pedido_id = ?");
    $stmtDet->execute([$id]);
    $pedido['detalles'] = $stmtDet->fetchAll();

    apiResponse(['success' => true, 'data' => $pedido]);
} catch (Exception $e) {
    apiError('Error al obtener pedido: ' . $e->getMessage(), 500);
}
