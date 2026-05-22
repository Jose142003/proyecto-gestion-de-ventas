<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$es_admin = $_SESSION['es_admin'] ?? false;
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

// Leer datos JSON del cuerpo de la petición
$datos = json_decode(file_get_contents('php://input'), true);

// Los administradores no pueden eliminar foto de perfil (no tienen)
if ($es_admin || $tabla_origen === 'admin_users') {
    echo json_encode(['success' => false, 'message' => 'Las cuentas de administrador no tienen foto de perfil para eliminar']);
    exit;
}

require_once dirname(__DIR__) . '/conexion/conexion.php';

verificarCSRF();

try {
    $db = Database::getConnection();
    
    // Obtener la ruta de la foto actual
    $query = "SELECT foto_perfil FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    $stmt->execute();
    $foto_actual = $stmt->fetchColumn();
    
    // Actualizar la base de datos para eliminar la referencia a la foto
    $query = "UPDATE users SET foto_perfil = NULL WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $usuario_id);
    
    if ($stmt->execute()) {
        // Eliminar el archivo físico si existe
        if ($foto_actual && $foto_actual !== '' && $foto_actual !== 'null') {
            $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . $foto_actual;
            if (file_exists($ruta_fisica)) {
                unlink($ruta_fisica);
            }
        }
        
        // Eliminar de sesión
        unset($_SESSION['foto_perfil']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Foto de perfil eliminada correctamente'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar la foto de la base de datos']);
    }
} catch (PDOException $e) {
    error_log("Error en eliminar_foto_perfil: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>