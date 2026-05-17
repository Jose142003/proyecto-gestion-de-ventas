<?php
session_start();
header('Content-Type: application/json');

function responder($exito, $mensaje, $datos = null) {
    $respuesta = ['success' => $exito, 'message' => $mensaje];
    if ($datos !== null) $respuesta['data'] = $datos;
    echo json_encode($respuesta);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    responder(false, 'Sesión no iniciada');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['id'])) {
    responder(false, 'ID de backup no proporcionado');
}

$id = intval($data['id']);

require_once dirname(__DIR__) . '/conexion/conexion.php';

try {
    $pdo = conectarDB();

    $stmt = $pdo->prepare("SELECT ruta_archivo, nombre_archivo FROM backups WHERE id = ?");
    $stmt->execute([$id]);
    $backup = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$backup) {
        responder(false, 'Backup no encontrado en la base de datos');
    }

    $ruta_archivo = $backup['ruta_archivo'];
    if (!file_exists($ruta_archivo)) {
        $ruta_alt = __DIR__ . '/' . $backup['nombre_archivo'];
        $ruta_alt2 = __DIR__ . '/backups/' . $backup['nombre_archivo'];
        if (file_exists($ruta_alt)) $ruta_archivo = $ruta_alt;
        elseif (file_exists($ruta_alt2)) $ruta_archivo = $ruta_alt2;
        else responder(false, 'Archivo físico no encontrado en ninguna ubicación');
    }

    $sql_content = file_get_contents($ruta_archivo);
    if ($sql_content === false) {
        responder(false, 'Error al leer el archivo de backup');
    }

    $sql_content = preg_replace('/--.*$/m', '', $sql_content);
    $sql_content = preg_replace('/#.*$/m', '', $sql_content);
    $sql_content = str_replace("\r\n", "\n", $sql_content);

    $statements = explode(";\n", $sql_content);
    $statements = array_filter($statements, function($s) {
        $s = trim($s);
        return !empty($s) && !preg_match('/^(SET|USE)/i', $s);
    });

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");

    $errores = [];
    $ejecutados = 0;

    foreach ($statements as $stmt_str) {
        $stmt_str = trim($stmt_str);
        if (empty($stmt_str)) continue;
        try {
            $pdo->exec($stmt_str);
            $ejecutados++;
        } catch (PDOException $e) {
            $errores[] = "Error en: " . substr($stmt_str, 0, 80) . "... -> " . $e->getMessage();
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    responder(true, "Restauración completada: $ejecutados sentencias ejecutadas", [
        'ejecutados' => $ejecutados,
        'errores' => $errores,
        'archivo' => $backup['nombre_archivo']
    ]);

} catch (PDOException $e) {
    responder(false, 'Error de base de datos: ' . $e->getMessage());
} catch (Exception $e) {
    responder(false, 'Error general: ' . $e->getMessage());
}
?>
