<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de factura requerido');
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT f.*, c.nombre AS cliente_nombre, c.documento AS cliente_documento, c.telefono AS cliente_telefono, c.direccion AS cliente_direccion
                           FROM facturas f
                           LEFT JOIN clientes c ON f.cliente_id = c.id
                           WHERE f.id = ?");
    $stmt->execute([$id]);
    $factura = $stmt->fetch();

    if (!$factura) {
        apiError('Factura no encontrada', 404);
    }

    $stmtDet = $pdo->prepare("SELECT fd.*, p.name AS producto_nombre, p.sku AS producto_sku
                              FROM factura_detalles fd
                              LEFT JOIN products p ON fd.producto_id = p.id
                              WHERE fd.factura_id = ?");
    $stmtDet->execute([$id]);
    $factura['detalles'] = $stmtDet->fetchAll();

    if ($factura['pedido_id']) {
        $stmtPed = $pdo->prepare("SELECT numero_pedido, fecha_pedido, estado FROM pedidos WHERE id = ?");
        $stmtPed->execute([$factura['pedido_id']]);
        $factura['pedido'] = $stmtPed->fetch();
    }

    apiResponse(['success' => true, 'data' => $factura]);
} catch (Exception $e) {
    apiError('Error al obtener factura: ' . $e->getMessage(), 500);
}
