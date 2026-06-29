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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    errorResponse('Método no permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    errorResponse('ID de almacén requerido');
}

$id = (int)$input['id'];

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("SELECT * FROM almacenes WHERE id = ?");
    $stmt->execute([$id]);
    $almacen = $stmt->fetch();

    if (!$almacen) {
        errorResponse('Almacén no encontrado', 404);
    }

    $codigo = trim($input['codigo'] ?? $almacen['codigo']);
    $nombre = trim($input['nombre'] ?? $almacen['nombre']);
    $direccion = trim($input['direccion'] ?? $almacen['direccion'] ?? '');
    $ciudad = trim($input['ciudad'] ?? $almacen['ciudad'] ?? '');
    $telefono = trim($input['telefono'] ?? $almacen['telefono'] ?? '');
    $encargado = trim($input['encargado'] ?? $almacen['encargado'] ?? '');

    $es_principal = isset($input['es_principal'])
        ? !empty($input['es_principal'])
        : (bool)$almacen['es_principal'];

    $stmt = $pdo->prepare("SELECT id FROM almacenes WHERE codigo = ? AND id != ?");
    $stmt->execute([$codigo, $id]);
    if ($stmt->fetch()) {
        errorResponse('El código de almacén ya está en uso', 409);
    }

    $pdo->beginTransaction();

    if ($es_principal && !$almacen['es_principal']) {
        $pdo->exec("UPDATE almacenes SET es_principal = 0 WHERE es_principal = 1");
    }

    $stmt = $pdo->prepare(
        "UPDATE almacenes SET codigo = ?, nombre = ?, direccion = ?, ciudad = ?, telefono = ?, encargado = ?, es_principal = ?, updated_at = NOW()
         WHERE id = ?"
    );
    $stmt->execute([$codigo, $nombre, $direccion, $ciudad, $telefono, $encargado, $es_principal ? 1 : 0, $id]);

    $pdo->commit();

    auditoriaRegistrar('editar_almacen', 'almacenes', "Almacén editado: $nombre (ID: $id)");

    jsonResponse([
        'success' => true,
        'message' => 'Almacén actualizado correctamente',
        'data' => ['id' => $id, 'codigo' => $codigo, 'nombre' => $nombre]
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error editar almacén: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
