<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/conexion.php';

session_start();
$usuarioId = $_SESSION['user_id'] ?? ($_SESSION['usuario_id'] ?? 0);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_GET;
$accion = $input['accion'] ?? '';

try {
    $db = obtenerDb();

    if ($accion === 'guardar') {
        if (!$usuarioId) responder(['error' => 'Debe iniciar sesión'], 401);

        $nombre = $input['nombre'] ?? 'Configuración sin nombre';
        $aplicacion = $input['aplicacion'] ?? '';
        $descripcion = $input['descripcion'] ?? '';
        $hp = floatval($input['hp'] ?? 0);
        $voltaje = floatval($input['voltaje'] ?? 220);
        $componentes = $input['componentes'] ?? [];
        $total = floatval($input['total_estimado'] ?? 0);

        if (empty($componentes)) responder(['error' => 'Debe agregar al menos un componente'], 400);

        $stmt = $db->prepare("
            INSERT INTO configuraciones_tablero (usuario_id, nombre, descripcion, aplicacion, parametros, componentes, total_estimado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $usuarioId, $nombre, $descripcion, $aplicacion,
            json_encode(['hp' => $hp, 'voltaje' => $voltaje]),
            json_encode($componentes), $total
        ]);

        $id = $db->lastInsertId();
        responder(['success' => true, 'id' => $id, 'message' => 'Configuración guardada']);
    }

    elseif ($accion === 'listar') {
        if (!$usuarioId) responder(['error' => 'Debe iniciar sesión'], 401);

        $stmt = $db->prepare("
            SELECT id, nombre, aplicacion, total_estimado, created_at, updated_at 
            FROM configuraciones_tablero 
            WHERE usuario_id = ? 
            ORDER BY updated_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$usuarioId]);
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        responder(['success' => true, 'configuraciones' => $configs]);
    }

    elseif ($accion === 'cargar') {
        $id = intval($input['id'] ?? 0);

        $stmt = $db->prepare("SELECT * FROM configuraciones_tablero WHERE id = ?");
        $stmt->execute([$id]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) responder(['error' => 'Configuración no encontrada'], 404);

        if (is_string($config['parametros'])) $config['parametros'] = json_decode($config['parametros'], true);
        if (is_string($config['componentes'])) $config['componentes'] = json_decode($config['componentes'], true);

        responder(['success' => true, 'configuracion' => $config]);
    }

    elseif ($accion === 'eliminar') {
        $id = intval($input['id'] ?? 0);

        if ($usuarioId) {
            $stmt = $db->prepare("DELETE FROM configuraciones_tablero WHERE id = ? AND (usuario_id = ? OR ? IN (SELECT id FROM admin_users WHERE id = ?))");
            $stmt->execute([$id, $usuarioId, $usuarioId, $usuarioId]);
        } else {
            $stmt = $db->prepare("DELETE FROM configuraciones_tablero WHERE id = ?");
            $stmt->execute([$id]);
        }

        responder(['success' => true, 'message' => 'Configuración eliminada']);
    }

    else {
        $stmt = $db->query("SELECT COUNT(*) as total FROM configuraciones_tablero");
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmtCat = $db->query("SELECT aplicacion, COUNT(*) as total FROM configuraciones_tablero GROUP BY aplicacion ORDER BY total DESC LIMIT 10");
        $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        responder(['success' => true, 'total_configuraciones' => $total, 'aplicaciones' => $categorias]);
    }

} catch (Exception $e) {
    responder(['error' => 'Error interno: ' . $e->getMessage()], 500);
}
