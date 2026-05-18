<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = intval($data['id']);

try {
    // Obtener la ruta del archivo antes de eliminar el registro
    $stmt = $pdo->prepare("SELECT ruta_archivo FROM backups WHERE id = ?");
    $stmt->execute([$id]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$backup) {
        echo json_encode(['success' => false, 'message' => 'Backup no encontrado']);
        exit;
    }
    
    // Eliminar archivo físico
    if (file_exists($backup['ruta_archivo'])) {
        unlink($backup['ruta_archivo']);
    }
    
    // Eliminar registro de la base de datos
    $stmt = $pdo->prepare("DELETE FROM backups WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'Backup eliminado correctamente']);
    
} catch (Exception $e) {
    error_log("Error en eliminar_backup.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar backup']);
}
?>