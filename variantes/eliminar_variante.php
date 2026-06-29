<?php
require_once __DIR__ . '/../conexion/conexion.php';
iniciarSesion();
Database::setHeaders();
requerirAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

verificarCSRF();

$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    $input = $_POST;
}

$id = isset($input['id']) ? intval($input['id']) : 0;
if ($id <= 0) {
    errorResponse('id de variante es requerido', 400);
}

try {
    $pdo = Database::getConnection();

    $check = $pdo->prepare("SELECT id FROM producto_variantes WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        errorResponse('Variante no encontrada', 404);
    }

    $stmt = $pdo->prepare("UPDATE producto_variantes SET activo = 0 WHERE id = ?");
    $stmt->execute([$id]);

    require_once __DIR__ . '/../telegram/notificar_otros.php';
    telegramNotificarVarianteEliminada($pdo, $id, '');

    jsonResponse([
        'success' => true,
        'message' => 'Variante desactivada correctamente'
    ]);
} catch (Exception $e) {
    error_log("Error en eliminar_variante: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
