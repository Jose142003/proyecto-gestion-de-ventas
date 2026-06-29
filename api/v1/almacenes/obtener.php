<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de producto requerido');
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT p.*, COALESCE((SELECT SUM(cantidad) FROM pedido_detalles pd JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pd.producto_id = p.id), 0) AS total_vendido
                           FROM products p WHERE p.id = ? AND p.active = 1 AND p.deleted_at IS NULL");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        apiError('Producto no encontrado en inventario', 404);
    }

    $stmtMov = $pdo->prepare("SELECT id, tipo_movimiento, cantidad, descripcion, referencia, fecha_movimiento FROM movimientos_inventario WHERE producto_id = ? ORDER BY fecha_movimiento DESC LIMIT 50");
    $stmtMov->execute([$id]);
    $producto['movimientos'] = $stmtMov->fetchAll();

    $stmtHist = $pdo->prepare("SELECT id, cantidad, stock_anterior, stock_nuevo, tipo, referencia, fecha FROM historial_stock WHERE producto_id = ? ORDER BY fecha DESC LIMIT 50");
    $stmtHist->execute([$id]);
    $producto['historial_stock'] = $stmtHist->fetchAll();

    apiResponse(['success' => true, 'data' => $producto]);
} catch (Exception $e) {
    apiError('Error al obtener producto del almacén: ' . $e->getMessage(), 500);
}
