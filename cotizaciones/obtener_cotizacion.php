<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID requerido']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT c.*, u.nombre as usuario_nombre FROM cotizaciones c LEFT JOIN admin_users u ON c.usuario_id = u.id WHERE c.id = ?");
    $stmt->execute([$id]);
    $cotizacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cotizacion) {
        echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
        exit;
    }

    $stmtDet = $pdo->prepare("SELECT cd.*, p.name as producto_nombre_real, p.sku FROM cotizacion_detalles cd LEFT JOIN products p ON cd.producto_id = p.id WHERE cd.cotizacion_id = ?");
    $stmtDet->execute([$id]);
    $detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

    $cotizacion['detalles'] = $detalles;

    echo json_encode(['success' => true, 'data' => $cotizacion], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
