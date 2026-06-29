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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    errorResponse('ID de almacén requerido');
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT * FROM almacenes WHERE id = ?");
    $stmt->execute([$id]);
    $almacen = $stmt->fetch();

    if (!$almacen) {
        errorResponse('Almacén no encontrado', 404);
    }

    $stmt = $pdo->prepare(
        "SELECT pa.*, p.name AS producto_nombre, p.sku AS producto_sku, p.price AS producto_precio
         FROM producto_almacen pa
         JOIN products p ON p.id = pa.producto_id
         WHERE pa.almacen_id = ?
         ORDER BY p.name ASC"
    );
    $stmt->execute([$id]);
    $productos = $stmt->fetchAll();

    $almacen['productos'] = $productos;

    jsonResponse([
        'success' => true,
        'data' => $almacen
    ]);

} catch (Throwable $e) {
    error_log("Error obtener almacén: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
