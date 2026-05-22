<?php
require_once dirname(__DIR__) . '/conexion/conexion.php';
iniciarSesion();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);

// Verificar que se recibió el ID del usuario
$usuario_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$usuario_id) {
    // Si no se proporciona ID, intentar usar el de sesión
    if (isset($_SESSION['user_id'])) {
        $usuario_id = $_SESSION['user_id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado']);
        exit;
    }
}

$es_admin = $_SESSION['es_admin'] ?? false;
$tabla_origen = $_SESSION['tabla_origen'] ?? null;

try {
    require_once dirname(__DIR__) . '/conexion/conexion.php';
    $db = conectarDB();
    
    $foto = null;
    $tabla = null;
    
    // Primero buscar en admin_users si es admin o viene de ahí
    if ($es_admin || $tabla_origen === 'admin_users') {
        // Verificar si la columna existe
        $checkColumn = $db->query("SHOW COLUMNS FROM admin_users LIKE 'foto_perfil'");
        if ($checkColumn->rowCount() > 0) {
            $query = "SELECT foto_perfil FROM admin_users WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $usuario_id);
            $stmt->execute();
            $foto = $stmt->fetchColumn();
            $tabla = 'admin_users';
        }
    }
    
    // Si no se encontró en admin_users o no es admin, buscar en users
    if ($foto === null && $tabla === null) {
        $query = "SELECT foto_perfil FROM users WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $usuario_id);
        $stmt->execute();
        $foto = $stmt->fetchColumn();
        $tabla = 'users';
    }
    
    if ($foto !== null && $foto !== '' && $foto !== 'null') {
        // Verificar si el archivo existe físicamente
        $ruta_fisica = $_SERVER['DOCUMENT_ROOT'] . $foto;
        if (file_exists($ruta_fisica)) {
            echo json_encode([
                'success' => true,
                'photo_url' => $foto
            ]);
        } else {
            // Archivo no existe, actualizar BD a NULL
            $updateQuery = "UPDATE $tabla SET foto_perfil = NULL WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':id', $usuario_id);
            $updateStmt->execute();
            
            echo json_encode([
                'success' => true,
                'photo_url' => null,
                'message' => 'Foto no encontrada en el servidor'
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'photo_url' => null,
            'message' => 'El usuario no tiene foto de perfil'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error en obtener_foto_perfil: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}
?>