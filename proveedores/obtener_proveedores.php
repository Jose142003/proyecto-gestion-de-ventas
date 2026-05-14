<?php
session_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM proveedores ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();

$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $proveedores
]);
?>