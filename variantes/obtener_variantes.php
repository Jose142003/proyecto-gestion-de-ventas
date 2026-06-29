<?php
require_once __DIR__ . '/../conexion/conexion.php';
iniciarSesion();
Database::setHeaders();
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$producto_id = isset($_GET['producto_id']) ? intval($_GET['producto_id']) : 0;
if ($producto_id <= 0) {
    errorResponse('producto_id es requerido', 400);
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        SELECT id, producto_id, sku_variante, nombre_variante, combinacion,
               precio_adicional, stock, imagen_url, activo, created_at, updated_at
        FROM producto_variantes
        WHERE producto_id = ?
        ORDER BY id
    ");
    $stmt->execute([$producto_id]);
    $variantes = $stmt->fetchAll();

    foreach ($variantes as &$v) {
        $v['combinacion'] = json_decode($v['combinacion'], true);
        $v['precio_adicional'] = (float)$v['precio_adicional'];
        $v['stock'] = (int)$v['stock'];
        $v['activo'] = (int)$v['activo'];
    }

    jsonResponse([
        'success' => true,
        'producto_id' => $producto_id,
        'variantes' => $variantes
    ]);
} catch (Exception $e) {
    error_log("Error en obtener_variantes: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
