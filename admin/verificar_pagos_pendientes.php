<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $db = conectarDB();
    $ultimo_id = (int)($_GET['ultimo_id'] ?? 0);

    // Buscar pedidos NUEVOS (solo últimos 5 min en primera carga)
    $sql = "SELECT p.id, p.numero_pedido, p.total, p.metodo_pago, p.estado, p.created_at,
                   u.nombre as cliente_nombre
            FROM pedidos p
            LEFT JOIN users u ON p.usuario_id = u.id
            WHERE p.metodo_pago IN ('pago_movil', 'transferencia') ";
    $params = [];

    if ($ultimo_id > 0) {
        // Polling normal: solo lo que no hemos visto
        $sql .= "AND p.id > ? ";
        $params[] = $ultimo_id;
    } else {
        // Primera carga: solo pedidos de los últimos 5 minutos
        $sql .= "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) ";
    }
    $sql .= "ORDER BY p.id ASC LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $nuevos = [];
    foreach ($pedidos as $p) {
        $metodo = strtolower(trim($p['metodo_pago']));
        $mensaje = '';
        $icono = '';

        if (in_array($metodo, ['pago_movil', 'transferencia'])) {
            // Estos métodos se facturan automáticamente al seleccionarlos
            $tipo_label = $metodo === 'pago_movil' ? 'Pago móvil' : 'Transferencia';
            $icono = $metodo === 'pago_movil' ? '📱' : '🏦';
            $mensaje = "$icono $tipo_label de {$p['cliente_nombre']} - Pedido {$p['numero_pedido']} - Ya facturado";
        }

        if ($mensaje) {
            $nuevos[] = [
                'id' => (int)$p['id'],
                'numero_pedido' => $p['numero_pedido'],
                'total' => (float)$p['total'],
                'cliente' => $p['cliente_nombre'] ?? 'Cliente',
                'metodo_pago' => $metodo,
                'mensaje' => $mensaje
            ];
        }
    }

    // Obtener el max_id general de TODOS los pedidos para no reeditar viejos
    $stmtMax = $db->query("SELECT COALESCE(MAX(id), 0) FROM pedidos");
    $max_id = (int)$stmtMax->fetchColumn();

    echo json_encode(['success' => true, 'nuevos' => $nuevos, 'max_id' => $max_id]);
} catch (Exception $e) {
    error_log("Error verificar pagos: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error']);
}
