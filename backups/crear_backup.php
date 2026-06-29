<?php
error_reporting(0);
ini_set('display_errors', 0);
set_time_limit(0);

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

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

$ruta_conexion = __DIR__ . '/../conexion/conexion.php';

if (!file_exists($ruta_conexion)) {
    responder(false, 'No se encuentra el archivo de conexion');
}

require_once $ruta_conexion;

if (!function_exists('conectarDB')) {
    responder(false, 'La funcion conectarDB() no esta definida en conexion.php');
}

try {
    $conn = conectarDB();

    if (!$conn) {
        responder(false, 'No se pudo establecer conexion a la base de datos');
    }

    $directorio_backups = __DIR__ . '/../backups_db/';
    if (!is_dir($directorio_backups)) {
        if (!mkdir($directorio_backups, 0755, true)) {
            responder(false, 'Error al crear backup: no se pudo crear el directorio');
        }
    }
    if (!is_writable($directorio_backups)) {
        responder(false, 'Error al crear backup: permisos de escritura');
    }

    $nombre_archivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $ruta_completa = $directorio_backups . '/' . $nombre_archivo;

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

    $cabecera = "-- Backup: " . date('Y-m-d H:i:s') . "\n";
    $cabecera .= "-- Base de datos: carrito_db\n";
    $cabecera .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    file_put_contents($ruta_completa, $cabecera);

    foreach ($tablas as $tabla) {
        try {
            $bloque = "\n-- --------------------------------------------------------\n";
            $bloque .= "-- Estructura de tabla: $tabla\n";
            $bloque .= "-- --------------------------------------------------------\n";

            $stmt = $conn->query("SHOW CREATE TABLE `$tabla`");
            if ($stmt) {
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                $bloque .= $fila['Create Table'] . ";\n\n";
            }

            $datos = $conn->query("SELECT * FROM `$tabla`");
            if ($datos) {
                $filas_datos = $datos->fetchAll(PDO::FETCH_ASSOC);
                if (count($filas_datos) > 0) {
                    $bloque .= "--\n-- Datos de tabla: $tabla\n--\n";
                    foreach ($filas_datos as $fila_datos) {
                        $columnas = array_keys($fila_datos);
                        $valores = array_map(function($v) use ($conn) {
                            if ($v === null) return 'NULL';
                            return $conn->quote($v);
                        }, array_values($fila_datos));
                        $bloque .= "INSERT INTO `$tabla` (`" . implode('`, `', $columnas) . "`) VALUES (" . implode(',', $valores) . ");\n";
                    }
                }
            }

            file_put_contents($ruta_completa, $bloque, FILE_APPEND);

        } catch (Exception $e) {
            error_log("Error procesando tabla $tabla: " . $e->getMessage());
            continue;
        }
    }

    file_put_contents($ruta_completa, "\nSET FOREIGN_KEY_CHECKS=1;\n", FILE_APPEND);

    $bytes = filesize($ruta_completa);

    if ($bytes > 0) {
        $tamano = $bytes;
        $user_id = $_SESSION['user_id'] ?? 0;

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

        if (function_exists('auditoriaRegistrar')) {
            auditoriaRegistrar('crear_backup', 'backups', "Backup creado: $nombre_archivo - " . round($tamano / 1024, 2) . " KB");
        }

        responder(true, 'Backup generado correctamente', [
            'archivo' => $nombre_archivo,
            'tamano' => round($tamano / 1024, 2) . ' KB',
            'tablas' => count($tablas)
        ]);
    } else {
        responder(false, 'Error al escribir el archivo');
    }

} catch (PDOException $e) {
    error_log("crear_backup.php PDO Error: " . $e->getMessage());
    responder(false, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("crear_backup.php Error: " . $e->getMessage());
    responder(false, 'Error general: ' . $e->getMessage());
}
