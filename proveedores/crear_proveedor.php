<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);

require_once dirname(__DIR__) . '/conexion/conexion.php';

verificarCSRF();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['nombre']) || empty($data['telefono']) || empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Generar código de proveedor
    $codigo = 'PROV-' . strtoupper(substr(uniqid(), -6));

    $query = "INSERT INTO proveedores (codigo, nombre_comercial, ruc, telefono_principal, email_principal, contacto_nombre, direccion, estado) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')";
    $stmt = $db->prepare($query);

    if ($stmt->execute([
        $codigo,
        $data['nombre'],
        $data['ruc'] ?? '',
        $data['telefono'],
        $data['email'],
        $data['contacto'] ?? '',
        $data['direccion'] ?? ''
    ])) {
        echo json_encode(['success' => true, 'message' => 'Proveedor creado correctamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear proveedor']);
    }
} catch (Exception $e) {
    error_log("Crear proveedor error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al crear proveedor']);
}
?>