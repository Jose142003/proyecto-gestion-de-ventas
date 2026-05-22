<?php
session_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getConnection();

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;

    $countStmt = $db->query("SELECT COUNT(*) FROM proveedores");
    $total = (int)$countStmt->fetchColumn();

    $query = "SELECT * FROM proveedores ORDER BY id DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$limit, $offset]);

    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $proveedores,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>