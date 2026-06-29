<?php
$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$price = $input['price'] ?? null;
$category = trim($input['category'] ?? 'General');
$description = trim($input['description'] ?? '');
$stock = (int)($input['stock'] ?? 0);
$sku = trim($input['sku'] ?? '');
$image_url = trim($input['image_url'] ?? '');
$weight = (float)($input['weight'] ?? 0);
$dimensions = trim($input['dimensions'] ?? '');
$currency = trim($input['currency'] ?? 'Bs');

if (!$name) {
    apiError('El nombre del producto es requerido');
}
if (!$price || $price <= 0) {
    apiError('El precio debe ser mayor a 0');
}

try {
    $pdo = Database::getConnection();

    if (empty($sku)) {
        $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next FROM products");
        $nextId = $stmt->fetch()['next'];
        $sku = 'PROD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    } else {
        $check = $pdo->prepare("SELECT id FROM products WHERE sku = ?");
        $check->execute([$sku]);
        if ($check->fetch()) {
            apiError("El SKU '$sku' ya existe");
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (sku, name, price, image_url, description, category, stock, weight, dimensions, currency) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$sku, $name, $price, $image_url, $description, $category, $stock, $weight, $dimensions, $currency]);

    $id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id, sku, name, price, image_url, description, category, stock, created_at FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    apiResponse(['success' => true, 'message' => 'Producto creado exitosamente', 'data' => $producto], 201);
} catch (Exception $e) {
    apiError('Error al crear producto: ' . $e->getMessage(), 500);
}
