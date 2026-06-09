<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();

    $cliente_id = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : 0;
    if ($cliente_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de cliente inválido']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT ci.id, ci.cliente_id, ci.usuario_id, ci.tipo, ci.titulo,
               ci.descripcion, ci.fecha_interaccion, ci.created_at,
               COALESCE(au.nombre, 'Sistema') as usuario_nombre
        FROM cliente_interacciones ci
        LEFT JOIN admin_users au ON ci.usuario_id = au.id
        WHERE ci.cliente_id = ?
        ORDER BY ci.fecha_interaccion DESC
    ");
    $stmt->execute([$cliente_id]);
    $interacciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($interacciones as &$int) {
        $int['id'] = (int)$int['id'];
        $int['cliente_id'] = (int)$int['cliente_id'];
        $int['usuario_id'] = $int['usuario_id'] ? (int)$int['usuario_id'] : null;
    }

    echo json_encode([
        'success' => true,
        'interacciones' => $interacciones,
        'total' => count($interacciones)
    ]);

} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error al obtener interacciones']);
}
