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

    $usuario_id = (int)($_GET['usuario_id'] ?? 0);
    if ($usuario_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'usuario_id es requerido'], 400);
    }

    $stmt = $pdo->prepare("
        SELECT r.*, p.name AS producto_nombre, p.image_url AS producto_imagen
        FROM resenas r
        JOIN products p ON r.producto_id = p.id
        WHERE r.usuario_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$usuario_id]);
    $resenas = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'resenas' => $resenas
    ]);

} catch (PDOException $e) {
    error_log("Error en mis_resenas: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error interno del servidor'], 500);
}
