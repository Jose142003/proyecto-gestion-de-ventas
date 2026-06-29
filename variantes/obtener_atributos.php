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
    $stmt = $pdo->prepare("SELECT id, nombre, valor FROM producto_atributos WHERE producto_id = ? ORDER BY id");
    $stmt->execute([$producto_id]);
    $atributos = $stmt->fetchAll();

    $agrupados = [];
    foreach ($atributos as $a) {
        $agrupados[$a['nombre']][] = $a;
    }

    jsonResponse([
        'success' => true,
        'producto_id' => $producto_id,
        'atributos' => $atributos,
        'agrupados' => $agrupados
    ]);
} catch (Exception $e) {
    error_log("Error en obtener_atributos: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
