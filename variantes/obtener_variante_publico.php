<?php
require_once __DIR__ . '/../conexion/conexion.php';
iniciarSesion();
Database::setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$sku = trim($_GET['sku'] ?? '');
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($sku === '' && $id <= 0) {
    errorResponse('sku o id es requerido', 400);
}

try {
    $pdo = Database::getConnection();

    if ($sku !== '') {
        $stmt = $pdo->prepare("
            SELECT v.id, v.producto_id, v.sku_variante, v.nombre_variante, v.combinacion,
                   v.precio_adicional, v.stock, v.imagen_url, v.activo,
                   p.price as product_price, p.name as product_name, p.image_url as product_image
            FROM producto_variantes v
            JOIN products p ON v.producto_id = p.id
            WHERE v.sku_variante = ? AND v.activo = 1
            LIMIT 1
        ");
        $stmt->execute([$sku]);
    } else {
        $stmt = $pdo->prepare("
            SELECT v.id, v.producto_id, v.sku_variante, v.nombre_variante, v.combinacion,
                   v.precio_adicional, v.stock, v.imagen_url, v.activo,
                   p.price as product_price, p.name as product_name, p.image_url as product_image
            FROM producto_variantes v
            JOIN products p ON v.producto_id = p.id
            WHERE v.id = ? AND v.activo = 1
            LIMIT 1
        ");
        $stmt->execute([$id]);
    }

    $variante = $stmt->fetch();
    if (!$variante) {
        errorResponse('Variante no encontrada', 404);
    }

    $variante['combinacion'] = json_decode($variante['combinacion'], true);
    $variante['precio_adicional'] = (float)$variante['precio_adicional'];
    $variante['stock'] = (int)$variante['stock'];
    $variante['activo'] = (int)$variante['activo'];
    $variante['product_price'] = (float)$variante['product_price'];
    $variante['precio_total'] = $variante['product_price'] + $variante['precio_adicional'];

    jsonResponse([
        'success' => true,
        'variante' => $variante
    ]);
} catch (Exception $e) {
    error_log("Error en obtener_variante_publico: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
