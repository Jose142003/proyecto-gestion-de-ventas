<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

session_start();
requerirSesion();

$conn = Database::getConnection();

$usuario_id = $_SESSION['user_id'];

if ($usuario_id <= 0) {
    echo json_encode(['success' => false, 'favoritos' => [], 'ids' => []]);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT f.id as favorito_id, f.producto_id, f.created_at,
               p.id, p.name, p.price, p.image_url as image, p.category, p.rating, p.stock, p.active
        FROM favoritos f
        JOIN products p ON f.producto_id = p.id
        WHERE f.usuario_id = ?
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$usuario_id]);
    $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'favoritos' => $favoritos,
        'ids' => array_map(function($f) { return intval($f['producto_id']); }, $favoritos)
    ]);
} catch (Exception $e) {
    error_log("Error en obtener_favoritos: " . $e->getMessage());
    echo json_encode(['success' => false, 'favoritos' => [], 'ids' => []]);
}
