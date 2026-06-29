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
$sku_variante = trim($input['sku_variante'] ?? '');
$nombre_variante = trim($input['nombre_variante'] ?? '');
$combinacion = $input['combinacion'] ?? '{}';
$precio_adicional = isset($input['precio_adicional']) ? floatval($input['precio_adicional']) : 0.00;
$stock = isset($input['stock']) ? intval($input['stock']) : 0;
$imagen_url = trim($input['imagen_url'] ?? '');

if ($producto_id <= 0) {
    errorResponse('producto_id es requerido', 400);
}
if ($sku_variante === '') {
    errorResponse('sku_variante es requerido', 400);
}
if ($nombre_variante === '') {
    errorResponse('nombre_variante es requerido', 400);
}

if (is_array($combinacion)) {
    $combinacion = json_encode($combinacion, JSON_UNESCAPED_UNICODE);
}

try {
    $pdo = Database::getConnection();

    $check = $pdo->prepare("SELECT id FROM producto_variantes WHERE sku_variante = ?");
    $check->execute([$sku_variante]);
    if ($check->fetch()) {
        errorResponse('El SKU de variante ya existe', 409);
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO producto_variantes (producto_id, sku_variante, nombre_variante, combinacion, precio_adicional, stock, imagen_url, activo)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$producto_id, $sku_variante, $nombre_variante, $combinacion, $precio_adicional, $stock, $imagen_url]);
    $variante_id = $pdo->lastInsertId();

    if ($stock > 0) {
        $almacen = $pdo->prepare("SELECT id FROM almacenes WHERE es_principal = 1 AND activo = 1 LIMIT 1");
        $almacen->execute();
        $alm = $almacen->fetch();
        if ($alm) {
            $paStmt = $pdo->prepare("
                INSERT INTO producto_almacen (producto_id, almacen_id, stock, stock_minimo)
                VALUES (?, ?, ?, 0)
                ON DUPLICATE KEY UPDATE stock = stock + VALUES(stock)
            ");
            $paStmt->execute([$producto_id, $alm['id'], $stock]);
        }
    }

    $pdo->commit();

    require_once __DIR__ . '/../telegram/notificar_otros.php';
    telegramNotificarVarianteCreada($pdo, $producto_id, $sku_variante, $nombre_variante);

    jsonResponse([
        'success' => true,
        'message' => 'Variante creada correctamente',
        'variante_id' => $variante_id
    ], 201);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en crear_variante: " . $e->getMessage());
    errorResponse('Error interno del servidor', 500);
}
