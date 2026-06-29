<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de producto requerido');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    apiError('Datos requeridos');
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND active = 1 AND deleted_at IS NULL");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        apiError('Producto no encontrado', 404);
    }

    $fields = [];
    $params = [];

    $allowed = ['name', 'price', 'description', 'category', 'stock', 'sku', 'image_url', 'weight', 'dimensions', 'currency', 'is_featured', 'rating'];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $fields[] = "$field = ?";
            $params[] = $input[$field];
        }
    }

    if ($input['sku'] ?? null) {
        $check = $pdo->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
        $check->execute([$input['sku'], $id]);
        if ($check->fetch()) {
            apiError("El SKU '{$input['sku']}' ya está en uso");
        }
    }

    if (empty($fields)) {
        apiError('No hay campos para actualizar');
    }

    $params[] = $id;
    $sql = "UPDATE products SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $stmt = $pdo->prepare("SELECT id, sku, name, price, image_url, description, category, rating, stock, is_featured, weight, dimensions, currency, active, created_at, updated_at FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    apiResponse(['success' => true, 'message' => 'Producto actualizado exitosamente', 'data' => $producto]);
} catch (Exception $e) {
    apiError('Error al actualizar producto: ' . $e->getMessage(), 500);
}
