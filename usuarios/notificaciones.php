<?php
require_once __DIR__ . '/../conexion/conexion.php';
iniciarSesion();
header('Content-Type: application/json');

$action = $_GET['action'] ?? 'listar';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$pdo = conectarDB();

switch ($action) {
    case 'listar':
        $stmt = $pdo->prepare("SELECT id, titulo, mensaje, tipo, referencia_id, leida, creada_en FROM notificaciones WHERE usuario_id = ? ORDER BY creada_en DESC LIMIT 20");
        $stmt->execute([$userId]);
        $notificaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmtNoLeidas = $pdo->prepare("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0");
        $stmtNoLeidas->execute([$userId]);
        $noLeidas = (int)$stmtNoLeidas->fetchColumn();

        echo json_encode([
            'success' => true,
            'notificaciones' => $notificaciones,
            'no_leidas' => $noLeidas
        ]);
        break;

    case 'marcar_leida':
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$id, $userId]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'marcar_todas_leidas':
        $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
