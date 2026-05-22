<?php
session_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$db = Database::getConnection();

$query = "DELETE FROM proveedores WHERE id = ?";
$stmt = $db->prepare($query);

if ($stmt->execute([$data['id']])) {
    auditoriaRegistrar('eliminar_proveedor', 'proveedores', "Proveedor ID {$data['id']} eliminado");
    echo json_encode(['success' => true, 'message' => 'Proveedor eliminado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al eliminar proveedor']);
}
?>