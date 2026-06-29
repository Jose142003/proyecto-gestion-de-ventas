<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de producto requerido');
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, sku, name, price, image_url, description, category, rating, specs, stock, is_featured, weight, dimensions, currency, active, created_at, updated_at FROM products WHERE id = ? AND active = 1 AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        apiError('Producto no encontrado', 404);
    }

    apiResponse(['success' => true, 'data' => $producto]);
} catch (Exception $e) {
    apiError('Error al obtener producto: ' . $e->getMessage(), 500);
}
