<?php
// Desactivar cualquier output no deseado
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

function responder($exito, $mensaje, $datos = null) {
    $respuesta = ['success' => $exito, 'message' => $mensaje];
    if ($datos !== null) {
        $respuesta['data'] = $datos;
    }
    echo json_encode($respuesta);
    exit;
}

// Verificar sesión
if (!isset($_SESSION['user_id'])) {
    responder(false, 'Sesión no iniciada');
}

// Ruta correcta
$ruta_conexion = __DIR__ . '/../conexion/conexion.php';

if (!file_exists($ruta_conexion)) {
    responder(false, 'No se encuentra el archivo de conexión en: ' . $ruta_conexion);
}

require_once $ruta_conexion;

if (!function_exists('conectarDB')) {
    responder(false, 'La función conectarDB() no está definida en conexion.php');
}

try {
    $conn = conectarDB();
    
    if (!$conn) {
        responder(false, 'No se pudo establecer conexión a la base de datos');
    }
    
    // Configurar carpeta de backups
    $directorio_backups = __DIR__ . '/backups/';
    if (!file_exists($directorio_backups)) {
        mkdir($directorio_backups, 0777, true);
    }
    
    if (!is_writable($directorio_backups)) {
        responder(false, 'El directorio ' . $directorio_backups . ' no tiene permisos de escritura');
    }
    
    $nombre_archivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $ruta_completa = $directorio_backups . $nombre_archivo;
    
    // Obtener SOLO tablas, excluir vistas
    $tablas = [];
    $stmt = $conn->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if ($stmt) {
        while ($fila = $stmt->fetch(PDO::FETCH_NUM)) {
            $tablas[] = $fila[0];
        }
    }
    
    if (empty($tablas)) {
        responder(false, 'No se encontraron tablas en la base de datos');
    }
    
    $contenido = "-- Backup: " . date('Y-m-d H:i:s') . "\n";
    $contenido .= "-- Base de datos: carrito_db\n";
    $contenido .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $contenido .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
    $contenido .= "SET SQL_MODE='';\n\n";
    
    foreach ($tablas as $tabla) {
        try {
            // Estructura de la tabla
            $stmt = $conn->query("SHOW CREATE TABLE `$tabla`");
            if ($stmt) {
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                $contenido .= "\n\n-- --------------------------------------------------------\n";
                $contenido .= "-- Estructura de tabla: $tabla\n";
                $contenido .= "-- --------------------------------------------------------\n";
                $contenido .= $fila['Create Table'] . ";\n\n";
            }
            
            // Datos de la tabla
            $datos = $conn->query("SELECT * FROM `$tabla`");
            if ($datos) {
                $filas_datos = $datos->fetchAll(PDO::FETCH_ASSOC);
                if (count($filas_datos) > 0) {
                    $contenido .= "--\n-- Datos de tabla: $tabla\n--\n";
                    foreach ($filas_datos as $fila_datos) {
                        $columnas = array_keys($fila_datos);
                        $valores = array_map(function($v) {
                            if ($v === null) return 'NULL';
                            $v = str_replace("'", "''", $v);
                            return "'" . $v . "'";
                        }, array_values($fila_datos));
                        $contenido .= "INSERT INTO `$tabla` (`" . implode('`, `', $columnas) . "`) VALUES (" . implode(',', $valores) . ");\n";
            }
                    $contenido .= "\n";
        }
            }
        } catch (Exception $e) {
            // Continuar con la siguiente tabla si hay error
            error_log("Error procesando tabla $tabla: " . $e->getMessage());
            continue;
        }
    }
    $contenido .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Guardar archivo
    $bytes = file_put_contents($ruta_completa, $contenido);
        
    if ($bytes !== false && $bytes > 0) {
        $tamano = filesize($ruta_completa);
        $user_id = $_SESSION['user_id'];
        
        // Crear tabla de backups si no existe
        $conn->exec("CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre_archivo VARCHAR(255) NOT NULL,
            ruta_archivo VARCHAR(512) NOT NULL,
            tamanio_bytes BIGINT NOT NULL,
            tipo VARCHAR(50) DEFAULT 'completo',
            estado VARCHAR(50) DEFAULT 'completado',
            usuario_id INT NOT NULL,
            fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        $stmt = $conn->prepare("INSERT INTO backups (nombre_archivo, ruta_archivo, tamanio_bytes, tipo, estado, usuario_id) VALUES (?, ?, ?, 'completo', 'completado', ?)");
        $stmt->execute([$nombre_archivo, $ruta_completa, $tamano, $user_id]);
        
        auditoriaRegistrar('crear_backup', 'backups', "Backup creado: $nombre_archivo - " . round($tamano / 1024, 2) . " KB");
        responder(true, 'Backup generado correctamente', [
            'archivo' => $nombre_archivo,
            'tamaño' => round($tamano / 1024, 2) . ' KB',
            'tablas' => count($tablas)
        ]);
        } else {
        responder(false, 'Error al escribir el archivo');
    }
    
} catch (PDOException $e) {
    responder(false, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    responder(false, 'Error general: ' . $e->getMessage());
}
?>