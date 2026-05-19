<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Desactivar display de errores para mantener JSON limpio
error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

function logError($message) {
    error_log("[subir_foto_perfil] " . $message);
}

try {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
        exit;
    }

    $usuario_id = $_SESSION['user_id'];
    $es_admin = $_SESSION['es_admin'] ?? false;
    $tabla_origen = $_SESSION['tabla_origen'] ?? null;
    $user_rol = $_SESSION['user_rol'] ?? $_SESSION['rol'] ?? 'usuario';

    logError("Usuario ID: $usuario_id, es_admin: " . ($es_admin ? 'true' : 'false'));

    // Determinar qué tabla usar
    $tabla = 'users';
    $campo_foto = 'foto_perfil';
    
    // Si es admin o viene de admin_users, usar admin_users
    if ($es_admin || $tabla_origen === 'admin_users') {
        $tabla = 'admin_users';
        logError("Usando tabla admin_users para usuario $usuario_id");
    }

    // Verificar que se haya subido un archivo
    if (!isset($_FILES) || empty($_FILES)) {
        echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
        exit;
    }

    // Determinar el nombre del campo (soporta tanto 'foto' como 'profile_photo')
    $fieldName = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fieldName = 'foto';
    } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fieldName = 'profile_photo';
    } else {
        echo json_encode(['success' => false, 'message' => 'No se seleccionó ningún archivo']);
        exit;
    }

    $archivo = $_FILES[$fieldName];

    // Verificar error de subida
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'Error al subir la imagen';
        switch ($archivo['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'La imagen es demasiado grande (máx 5MB)';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'El archivo se subió parcialmente';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = 'Falta la carpeta temporal';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = 'Error al escribir el archivo';
                break;
        }
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    // Validar tipo de archivo
    $tipos_permitidos = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $tipos_permitidos)) {
        echo json_encode(['success' => false, 'message' => 'Formato no permitido. Use JPG, PNG, GIF o WEBP']);
        exit;
    }

    // Validar tamaño (max 5MB)
    if ($archivo['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'La imagen no puede superar los 5MB']);
        exit;
    }

    // Crear directorio si no existe
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/perfiles/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'No se pudo crear el directorio de uploads']);
            exit;
        }
    }

    // Generar nombre único
    $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    $nombre_archivo = $tabla . '_' . $usuario_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $ruta_relativa = '/uploads/perfiles/' . $nombre_archivo;
    $ruta_completa = $upload_dir . $nombre_archivo;

    logError("Guardando archivo en: " . $ruta_completa);

    // Mover el archivo
    if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
        logError("No se pudo mover el archivo subido a: " . $ruta_completa);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
        exit;
    }

    // Conectar a la base de datos
    require_once dirname(__DIR__) . '/conexion/conexion.php';

    verificarCSRF();

    try {
        $db = conectarDB();
        
        // Verificar si la columna foto_perfil existe en la tabla
        $checkColumn = $db->query("SHOW COLUMNS FROM $tabla LIKE '$campo_foto'");
        if ($checkColumn->rowCount() == 0) {
            // Agregar columna si no existe
            $db->exec("ALTER TABLE $tabla ADD COLUMN $campo_foto VARCHAR(255) NULL");
            logError("Columna $campo_foto agregada a tabla $tabla");
        }
        
        // Verificar si el usuario existe
        $checkQuery = "SELECT id FROM $tabla WHERE id = :id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $usuario_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            logError("Usuario $usuario_id no encontrado en tabla $tabla");
            unlink($ruta_completa);
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
            exit;
        }
        
        // Obtener foto anterior para eliminarla
        $query = "SELECT $campo_foto FROM $tabla WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $usuario_id);
        $stmt->execute();
        $foto_anterior = $stmt->fetchColumn();
        
        // Actualizar base de datos
        $query = "UPDATE $tabla SET $campo_foto = :foto WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':foto', $ruta_relativa);
        $stmt->bindParam(':id', $usuario_id);
        
        if ($stmt->execute()) {
            // Eliminar foto anterior si existe y es diferente a la nueva
            if ($foto_anterior && $foto_anterior !== $ruta_relativa && $foto_anterior !== 'null') {
                $ruta_anterior = $_SERVER['DOCUMENT_ROOT'] . $foto_anterior;
                if (file_exists($ruta_anterior)) {
                    unlink($ruta_anterior);
                    logError("Foto anterior eliminada: " . $ruta_anterior);
                }
            }
            
            // Actualizar sesión con la nueva foto
            $_SESSION['foto_perfil'] = $ruta_relativa;
            
            logError("Foto guardada exitosamente para usuario $usuario_id en tabla $tabla");
            
            echo json_encode([
                'success' => true,
                'message' => 'Foto de perfil actualizada correctamente',
                'photo_url' => $ruta_relativa
            ]);
        } else {
            logError("Error al actualizar la base de datos");
            unlink($ruta_completa);
            echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
        }
        
    } catch (PDOException $e) {
        logError("Error de base de datos: " . $e->getMessage());
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
        }
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
    
} catch (Exception $e) {
    logError("Error general: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>