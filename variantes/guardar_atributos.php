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

$producto_id = isset($input['producto_id']) ? intval($input['producto_id']) : 0;
$atributos = $input['atributos'] ?? [];

if ($producto_id <= 0) {
    errorResponse('producto_id es requerido', 400);
}
if (!is_array($atributos) || empty($atributos)) {
    errorResponse('atributos debe ser un array no vacío', 400);
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    $del = $pdo->prepare("DELETE FROM producto_atributos WHERE producto_id = ?");
    $del->execute([$producto_id]);

    $insert = $pdo->prepare("INSERT INTO producto_atributos (producto_id, nombre, valor) VALUES (?, ?, ?)");
    foreach ($atributos as $a) {
        $nombre = trim($a['nombre'] ?? '');
        $valor = trim($a['valor'] ?? '');
        if ($nombre === '' || $valor === '') continue;
        $insert->execute([$producto_id, $nombre, $valor]);
    }

    $pdo->commit();

    require_once __DIR__ . '/../telegram/notificar_otros.php';
    telegramNotificarAtributosGuardados($pdo, $producto_id, $input['atributos'] ? count($input['atributos']) : 0);

    jsonResponse([
        'success' => true,
        'message' => 'Atributos guardados correctamente',
        'total' => $insert->rowCount()
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en guardar_atributos: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
