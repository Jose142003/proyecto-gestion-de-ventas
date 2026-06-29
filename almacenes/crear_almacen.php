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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['codigo']) || empty($input['nombre'])) {
    errorResponse('Datos incompletos. Se requiere codigo y nombre');
}

$codigo = trim($input['codigo']);
$nombre = trim($input['nombre']);
$direccion = trim($input['direccion'] ?? '');
$ciudad = trim($input['ciudad'] ?? '');
$telefono = trim($input['telefono'] ?? '');
$encargado = trim($input['encargado'] ?? '');
$es_principal = !empty($input['es_principal']);

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT id FROM almacenes WHERE codigo = ?");
    $stmt->execute([$codigo]);
    if ($stmt->fetch()) {
        errorResponse('El código de almacén ya existe', 409);
    }

    $pdo->beginTransaction();

    if ($es_principal) {
        $pdo->exec("UPDATE almacenes SET es_principal = 0 WHERE es_principal = 1");
    }

    $stmt = $pdo->prepare(
        "INSERT INTO almacenes (codigo, nombre, direccion, ciudad, telefono, encargado, es_principal, activo, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())"
    );
    $stmt->execute([$codigo, $nombre, $direccion, $ciudad, $telefono, $encargado, $es_principal ? 1 : 0]);

    $id = (int)$pdo->lastInsertId();

    $pdo->commit();

    auditoriaRegistrar('crear_almacen', 'almacenes', "Almacén creado: $nombre (Código: $codigo)");

    require_once __DIR__ . '/../telegram/notificar_almacen.php';
    telegramNotificarNuevoAlmacen($pdo, $id, $codigo, $nombre);

    jsonResponse([
        'success' => true,
        'message' => 'Almacén creado correctamente',
        'data' => ['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre]
    ], 201);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error crear almacén: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
