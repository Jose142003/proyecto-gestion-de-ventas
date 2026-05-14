<?php
// descargar_backup.php - Colocar en: C:\laragon\www\proyecto\backups\
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.0 403 Forbidden');
    die('Acceso no autorizado');
}

// Incluir conexión para obtener la ruta del backup por ID
$ruta_conexion = __DIR__ . '/../conexion/conexion.php';
if (!file_exists($ruta_conexion)) {
    die('Error: No se encuentra el archivo de conexión');
}

require_once $ruta_conexion;

try {
    $conn = conectarDB();
    
    // Verificar si se recibió ID o archivo directamente
    $archivo = null;
    $ruta_archivo = null;
    
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        // Buscar por ID en la base de datos
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT nombre_archivo, ruta_archivo FROM backups WHERE id = ?");
        $stmt->execute([$id]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($backup) {
            $archivo = $backup['nombre_archivo'];
            $ruta_archivo = $backup['ruta_archivo'];
        } else {
            header('HTTP/1.0 404 Not Found');
            die('Backup no encontrado en la base de datos');
        }
    } 
    elseif (isset($_GET['archivo']) && !empty($_GET['archivo'])) {
        // Método alternativo: por nombre de archivo
        $archivo = basename($_GET['archivo']);
        $ruta_archivo = __DIR__ . '/' . $archivo;
    }
    else {
        header('HTTP/1.0 400 Bad Request');
        die('Nombre de archivo o ID no especificado');
}

    // Verificar que el archivo existe
    if (!file_exists($ruta_archivo)) {
        // Intentar buscar en la carpeta de backups
        $ruta_alternativa = __DIR__ . '/' . $archivo;
        if (file_exists($ruta_alternativa)) {
            $ruta_archivo = $ruta_alternativa;
        } else {
            header('HTTP/1.0 404 Not Found');
            die('El archivo no existe en: ' . $ruta_archivo);
        }
    }
    
    // Verificar que es un archivo .sql (backup)
    if (pathinfo($ruta_archivo, PATHINFO_EXTENSION) !== 'sql') {
        header('HTTP/1.0 403 Forbidden');
        die('Tipo de archivo no permitido');
    }
    
    // Configurar cabeceras para descarga
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $archivo . '"');
    header('Content-Length: ' . filesize($ruta_archivo));
    header('Cache-Control: private');
    header('Pragma: public');
    header('Expires: 0');
    
    // Limpiar buffer de salida
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Leer y enviar el archivo
    readfile($ruta_archivo);
    exit;
    
} catch (Exception $e) {
    header('HTTP/1.0 500 Internal Server Error');
    die('Error: ' . $e->getMessage());
}
?>