<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();

    $producto_id = (int)($_GET['producto_id'] ?? 0);
    if ($producto_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'producto_id es requerido'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT r.*, u.nombre AS usuario_nombre
        FROM resenas r
        JOIN users u ON r.usuario_id = u.id
        WHERE r.producto_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$producto_id]);
    $resenas = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM v_producto_rating WHERE producto_id = ?");
    $stmt->execute([$producto_id]);
    $rating = $stmt->fetch();

    $distribucion = [
        5 => (int)($rating['cinco'] ?? 0),
        4 => (int)($rating['cuatro'] ?? 0),
        3 => (int)($rating['tres'] ?? 0),
        2 => (int)($rating['dos'] ?? 0),
        1 => (int)($rating['uno'] ?? 0),
    ];

    jsonResponse([
        'success' => true,
        'resenas' => $resenas,
        'total' => (int)($rating['total_resenas'] ?? count($resenas)),
        'rating_promedio' => (float)($rating['rating_promedio'] ?? 0),
        'distribucion' => $distribucion
    ]);

} catch (PDOException $e) {
    error_log("Error en obtener_resenas: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error interno del servidor'], 500);
}
