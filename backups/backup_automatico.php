<?php
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

if (!isset($_SESSION['user_id'])) {
    responder(false, 'Sesión no iniciada');
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $db = Database::getConnection();

    // Leer configuración
    $stmt = $db->prepare("SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('backup_auto_frecuencia', 'backup_auto_enabled', 'backup_max_files')");
    $stmt->execute();
    $configRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $config = [];
    foreach ($configRows as $row) {
        $config[$row['clave']] = $row['valor'];
    }

    $enabled = $config['backup_auto_enabled'] ?? '0';
    $frecuencia = $config['backup_auto_frecuencia'] ?? 'daily';
    $maxFiles = intval($config['backup_max_files'] ?? 10);

    if ($enabled !== '1') {
        responder(false, 'Backup automático deshabilitado');
    }

    // Determinar período actual
    switch ($frecuencia) {
        case 'daily':
            $periodoInicio = date('Y-m-d 00:00:00');
            $periodoLabel = 'diario';
            break;
        case 'weekly':
            $periodoInicio = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $periodoLabel = 'semanal';
            break;
        case 'monthly':
            $periodoInicio = date('Y-m-01 00:00:00');
            $periodoLabel = 'mensual';
            break;
        default:
            responder(false, 'Frecuencia no válida: ' . $frecuencia);
    }

    // Verificar si ya se hizo backup en el período actual
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM backups WHERE fecha_creacion >= ? AND tipo = 'automatico'");
    $stmt->execute([$periodoInicio]);
    $yaExiste = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($yaExiste['total'] > 0) {
        responder(true, 'Ya existe un backup ' . $periodoLabel . ' para el período actual');
    }

    // Configurar directorio
    $directorio_backups = __DIR__ . '/backups/';
    if (!file_exists($directorio_backups)) {
        mkdir($directorio_backups, 0777, true);
    }

    $nombre_archivo = 'backup_auto_' . date('Y-m-d_H-i-s') . '.sql';
    $ruta_completa = $directorio_backups . $nombre_archivo;

    // Obtener tablas
    $tablas = [];
    $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    if ($stmt) {
        while ($fila = $stmt->fetch(PDO::FETCH_NUM)) {
            $tablas[] = $fila[0];
        }
    }

    if (empty($tablas)) {
        responder(false, 'No se encontraron tablas');
    }

    $contenido = "-- Backup Automatico: " . date('Y-m-d H:i:s') . "\n";
    $contenido .= "-- Frecuencia: " . $periodoLabel . "\n";
    $contenido .= "-- Base de datos: " . DB_NAME . "\n";
    $contenido .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $contenido .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n";
    $contenido .= "SET SQL_MODE='';\n\n";

    foreach ($tablas as $tabla) {
        try {
            $stmt = $db->query("SHOW CREATE TABLE `$tabla`");
            if ($stmt) {
                $fila = $stmt->fetch(PDO::FETCH_ASSOC);
                $contenido .= "\n\n-- --------------------------------------------------------\n";
                $contenido .= "-- Estructura de tabla: $tabla\n";
                $contenido .= "-- --------------------------------------------------------\n";
                $contenido .= $fila['Create Table'] . ";\n\n";
            }

            $datos = $db->query("SELECT * FROM `$tabla`");
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
            error_log("Error procesando tabla $tabla: " . $e->getMessage());
            continue;
        }
    }

    $contenido .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $bytes = file_put_contents($ruta_completa, $contenido);

    if ($bytes === false || $bytes <= 0) {
        responder(false, 'Error al escribir el archivo');
    }

    $tamano = filesize($ruta_completa);
    $user_id = $_SESSION['user_id'];

    // Crear tabla si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(512) NOT NULL,
        tamanio_bytes BIGINT NOT NULL,
        tipo VARCHAR(50) DEFAULT 'completo',
        estado VARCHAR(50) DEFAULT 'completado',
        usuario_id INT NOT NULL,
        fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Registrar en la tabla backups con tipo 'automatico'
    $stmt = $db->prepare("INSERT INTO backups (nombre_archivo, ruta_archivo, tamanio_bytes, tipo, estado, usuario_id) VALUES (?, ?, ?, 'automatico', 'completado', ?)");
    $stmt->execute([$nombre_archivo, $ruta_completa, $tamano, $user_id]);

    // Limpiar backups antiguos (mantener solo los últimos backup_max_files)
    $stmt = $db->prepare("SELECT id FROM backups WHERE tipo = 'automatico' ORDER BY fecha_creacion DESC");
    $stmt->execute();
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($todos) > $maxFiles) {
        $eliminar = array_slice($todos, $maxFiles);
        foreach ($eliminar as $b) {
            $stmt = $db->prepare("SELECT ruta_archivo FROM backups WHERE id = ?");
            $stmt->execute([$b['id']]);
            $archivo = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($archivo && file_exists($archivo['ruta_archivo'])) {
                @unlink($archivo['ruta_archivo']);
            }
            $stmt = $db->prepare("DELETE FROM backups WHERE id = ?");
            $stmt->execute([$b['id']]);
        }
    }

    logSistema("Backup automático generado: $nombre_archivo (" . round($tamano / 1024, 2) . " KB, $periodoLabel)", 'INFO');

    responder(true, 'Backup automático generado correctamente', [
        'archivo' => $nombre_archivo,
        'tamaño' => round($tamano / 1024, 2) . ' KB',
        'tablas' => count($tablas),
        'frecuencia' => $periodoLabel,
    ]);

} catch (PDOException $e) {
    responder(false, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    responder(false, 'Error general: ' . $e->getMessage());
}
