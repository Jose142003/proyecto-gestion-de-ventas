<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

session_start();
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

$producto_id = (int)($_GET['producto_id'] ?? 0);
if ($producto_id <= 0) {
    errorResponse('ID de producto requerido');
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT id, name, sku, stock FROM products WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        errorResponse('Producto no encontrado', 404);
    }

    $stmt = $pdo->prepare(
        "SELECT pa.*, a.nombre AS almacen_nombre, a.codigo AS almacen_codigo
         FROM producto_almacen pa
         JOIN almacenes a ON a.id = pa.almacen_id
         WHERE pa.producto_id = ? AND a.activo = 1
         ORDER BY a.es_principal DESC, a.nombre ASC"
    );
    $stmt->execute([$producto_id]);
    $stock_almacenes = $stmt->fetchAll();

    $total_stock = 0;
    foreach ($stock_almacenes as $s) {
        $total_stock += (int)$s['stock'];
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'producto' => [
                'id' => (int)$producto['id'],
                'nombre' => $producto['name'],
                'sku' => $producto['sku']
            ],
            'stock_global' => $total_stock,
            'stock_por_almacen' => $stock_almacenes
        ]
    ]);

} catch (Throwable $e) {
    error_log("Error obtener stock por almacén: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
