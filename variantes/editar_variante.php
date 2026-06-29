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

    $check = $pdo->prepare("SELECT id, stock FROM producto_variantes WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        errorResponse('Variante no encontrada', 404);
    }

    $updates = [];
    $params = [];

    if (isset($input['sku_variante'])) {
        $sku = trim($input['sku_variante']);
        if ($sku === '') errorResponse('sku_variante no puede estar vacío', 400);
        $dup = $pdo->prepare("SELECT id FROM producto_variantes WHERE sku_variante = ? AND id != ?");
        $dup->execute([$sku, $id]);
        if ($dup->fetch()) errorResponse('El SKU de variante ya existe', 409);
        $updates[] = 'sku_variante = ?';
        $params[] = $sku;
    }

    if (isset($input['nombre_variante'])) {
        $updates[] = 'nombre_variante = ?';
        $params[] = trim($input['nombre_variante']);
    }

    if (isset($input['precio_adicional'])) {
        $updates[] = 'precio_adicional = ?';
        $params[] = floatval($input['precio_adicional']);
    }

    if (isset($input['stock'])) {
        $updates[] = 'stock = ?';
        $params[] = intval($input['stock']);
    }

    if (isset($input['activo'])) {
        $updates[] = 'activo = ?';
        $params[] = intval($input['activo']);
    }

    if (isset($input['imagen_url'])) {
        $updates[] = 'imagen_url = ?';
        $params[] = trim($input['imagen_url']);
    }

    if (isset($input['combinacion'])) {
        $combo = $input['combinacion'];
        if (is_array($combo)) $combo = json_encode($combo, JSON_UNESCAPED_UNICODE);
        $updates[] = 'combinacion = ?';
        $params[] = $combo;
    }

    if (empty($updates)) {
        errorResponse('No hay campos para actualizar', 400);
    }

    $params[] = $id;
    $sql = "UPDATE producto_variantes SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    require_once __DIR__ . '/../telegram/notificar_otros.php';
    telegramNotificarVarianteModificada($pdo, $id, $input['sku_variante'] ?? '');

    jsonResponse([
        'success' => true,
        'message' => 'Variante actualizada correctamente'
    ]);
} catch (Exception $e) {
    error_log("Error en editar_variante: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
