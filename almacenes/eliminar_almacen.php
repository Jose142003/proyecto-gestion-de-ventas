<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

session_start();
requerirAdmin();
verificarCSRF();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    errorResponse('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$id = (int)($_GET['id'] ?? $input['id'] ?? 0);
if ($id <= 0) {
    errorResponse('ID de almacén requerido');
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT * FROM almacenes WHERE id = ? AND activo = 1");
    $stmt->execute([$id]);
    $almacen = $stmt->fetch();

    if (!$almacen) {
        errorResponse('Almacén no encontrado', 404);
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM producto_almacen WHERE almacen_id = ? AND stock > 0");
    $stmt->execute([$id]);
    $productos_asignados = (int)$stmt->fetchColumn();

    if ($productos_asignados > 0) {
        errorResponse("No se puede eliminar el almacén: tiene $productos_asignados producto(s) con stock asignado");
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM producto_almacen WHERE almacen_id = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("UPDATE almacenes SET activo = 0, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();

    auditoriaRegistrar('eliminar_almacen', 'almacenes', "Almacén eliminado: {$almacen['nombre']} (ID: $id)");

    jsonResponse(['success' => true, 'message' => 'Almacén eliminado correctamente']);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error eliminar almacén: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
