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

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query(
        "SELECT a.*,
                (SELECT COUNT(*) FROM producto_almacen pa WHERE pa.almacen_id = a.id) AS total_productos,
                (SELECT COUNT(*) FROM producto_almacen pa WHERE pa.almacen_id = a.id AND pa.stock > 0) AS productos_con_stock
         FROM almacenes a
         ORDER BY a.es_principal DESC, a.nombre ASC"
    );

    $almacenes = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => $almacenes
    ]);

} catch (Throwable $e) {
    error_log("Error obtener almacenes: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
