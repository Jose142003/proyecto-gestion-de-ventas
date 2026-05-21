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

    if ($accion === 'generar') {
        if (!$usuarioId) responder(['error' => 'Debe iniciar sesión'], 401);

        $productoId = intval($input['producto_id'] ?? 0);
        $intervaloDias = intval($input['intervalo_dias'] ?? 90);
        $fechaCompra = $input['fecha_compra'] ?? date('Y-m-d');

        if (!$productoId) responder(['error' => 'Producto requerido'], 400);

        $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productoId]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$producto) responder(['error' => 'Producto no encontrado'], 404);

        $proximo = date('Y-m-d', strtotime("+$intervaloDias days"));

        $stmt = $db->prepare("
            INSERT INTO alertas_mantenimiento (producto_id, producto_nombre, usuario_id, fecha_compra, intervalo_dias, proximo_mantenimiento)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$productoId, $producto['name'], $usuarioId, $fechaCompra, $intervaloDias, $proximo]);

        $id = $db->lastInsertId();
        responder(['success' => true, 'id' => $id, 'proximo_mantenimiento' => $proximo]);
    }

    elseif ($accion === 'pendientes') {
        $stmt = $db->prepare("
            SELECT a.*, p.name as producto_nombre, p.price as producto_precio
            FROM alertas_mantenimiento a
            LEFT JOIN products p ON a.producto_id = p.id
            WHERE (a.usuario_id = ? OR ? = 0 OR ? IN (SELECT id FROM admin_users))
            AND a.estado = 'pendiente'
            ORDER BY a.proximo_mantenimiento ASC
            LIMIT 30
        ");
        $stmt->execute([$usuarioId, $usuarioId, $usuarioId]);
        $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $proximas = [];
        $vencidas = [];
        foreach ($alertas as $a) {
            if (strtotime($a['proximo_mantenimiento']) <= time()) {
                $a['vencida'] = true;
                $vencidas[] = $a;
            } else {
                $a['vencida'] = false;
                $proximas[] = $a;
            }
        }

        responder(['success' => true, 'vencidas' => $vencidas, 'proximas' => $proximas, 'total' => count($alertas)]);
    }

    elseif ($accion === 'notificar') {
        $id = intval($input['id'] ?? 0);

        $stmt = $db->prepare("SELECT a.*, u.correo as usuario_email FROM alertas_mantenimiento a 
            LEFT JOIN users u ON a.usuario_id = u.id WHERE a.id = ?");
        $stmt->execute([$id]);
        $alerta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$alerta) responder(['error' => 'Alerta no encontrada'], 404);

        $stmt = $db->prepare("UPDATE alertas_mantenimiento SET estado = 'notificado' WHERE id = ?");
        $stmt->execute([$id]);

        logSistema("Alerta mantenimiento notificada: {$alerta['producto_nombre']} (ID: $id)", 'INFO');

        responder(['success' => true, 'message' => 'Alerta notificada']);
    }

    elseif ($accion === 'completar') {
        $id = intval($input['id'] ?? 0);

        $stmt = $db->prepare("UPDATE alertas_mantenimiento SET estado = 'completado' WHERE id = ?");
        $stmt->execute([$id]);

        responder(['success' => true, 'message' => 'Mantenimiento completado']);
    }

    elseif ($accion === 'intervalos') {
        responder([
            'success' => true,
            'intervalos' => [
                ['dias' => 30, 'label' => 'Cada mes'],
                ['dias' => 90, 'label' => 'Cada 3 meses'],
                ['dias' => 180, 'label' => 'Cada 6 meses'],
                ['dias' => 365, 'label' => 'Cada año'],
            ],
            'recomendaciones' => [
                'Contactores' => 90,
                'Variadores' => 180,
                'Sensores' => 180,
                'Fuentes de Poder' => 365,
                'Instrumentos de Medición' => 365,
                'Relés' => 180,
                'Motores' => 90,
            ]
        ]);
    }

    else {
        $stmt = $db->query("
            SELECT estado, COUNT(*) as total FROM alertas_mantenimiento GROUP BY estado
        ");
        $resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtVencidas = $db->query("
            SELECT COUNT(*) as total FROM alertas_mantenimiento 
            WHERE estado = 'pendiente' AND proximo_mantenimiento <= CURDATE()
        ");
        $vencidas = $stmtVencidas->fetch(PDO::FETCH_ASSOC)['total'];

        responder(['success' => true, 'resumen' => $resumen, 'vencidas_ahora' => $vencidas]);
    }

} catch (Exception $e) {
    responder(['error' => 'Error interno: ' . $e->getMessage()], 500);
}
