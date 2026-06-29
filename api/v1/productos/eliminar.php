<?php
$id = $_REQUEST['api_id'] ?? null;

if (!$id) {
    apiError('ID de producto requerido');
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND active = 1 AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        apiError('Producto no encontrado', 404);
    }

    $stmt = $pdo->prepare("UPDATE products SET active = 0, deleted_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    apiResponse(['success' => true, 'message' => 'Producto eliminado exitosamente']);
} catch (Exception $e) {
    apiError('Error al eliminar producto: ' . $e->getMessage(), 500);
}
