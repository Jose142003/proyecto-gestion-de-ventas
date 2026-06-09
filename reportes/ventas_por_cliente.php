<?php
header('Content-Type: application/json');
error_reporting(0); ini_set('display_errors', 0);

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (Exception $e) {
    echo json_encode([]);
    exit;
}

$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// Solo devolver nombre e id para el CRM (evitar consultas pesadas innecesarias cuando no hay búsqueda)
$modo_simple = !empty($_GET['simple']);

if ($modo_simple) {
    $sql = "SELECT id, nombre, correo as email FROM users WHERE rol = 'usuario'";
    $params = [];
    if (!empty($buscar)) {
        $sql .= " AND (nombre LIKE ? OR correo LIKE ?)";
        $param = "%$buscar%";
        $params = [$param, $param];
    }
    $sql .= " ORDER BY nombre ASC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultado);
    exit;
}

// — Modo completo (con datos de ventas) —
$sqlClientes = "SELECT id, nombre, correo as email, telefono, cedula as documento 
                FROM users WHERE rol = 'usuario'";
$params = [];
if (!empty($buscar)) {
    $sqlClientes .= " AND (nombre LIKE ? OR correo LIKE ? OR cedula LIKE ?)";
    $p = "%$buscar%";
    $params = [$p, $p, $p];
}
$sqlClientes .= " ORDER BY nombre ASC LIMIT 50";

$stmt = $pdo->prepare($sqlClientes);
$stmt->execute($params);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = [];
foreach ($clientes as $cliente) {
    try {
        $stmtV = $pdo->prepare("SELECT COUNT(DISTINCT p.id) as total_ventas,
                                       COALESCE(SUM(p.total), 0) as monto_total,
                                       COALESCE(SUM(pd.cantidad), 0) as total_productos,
                                       MAX(p.created_at) as ultima_compra
                                FROM pedidos p
                                LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
                                WHERE p.usuario_id = ? AND p.estado IS NOT NULL");
        $stmtV->execute([$cliente['id']]);
        $ventas = $stmtV->fetch(PDO::FETCH_ASSOC);

        $uc = '';
        if (($ventas['total_ventas'] ?? 0) > 0 && !empty($ventas['ultima_compra']) && $ventas['ultima_compra'] !== '0000-00-00 00:00:00') {
            try { $uc = (new DateTime($ventas['ultima_compra']))->format('Y-m-d'); } catch (Exception $e) { $uc = ''; }
        }

        $resultado[] = [
            'id' => (int)$cliente['id'],
            'nombre' => $cliente['nombre'] ?? 'N/A',
            'email' => $cliente['email'] ?? 'N/A',
            'telefono' => $cliente['telefono'] ?? 'N/A',
            'documento' => $cliente['documento'] ?? '',
            'total_ventas' => (int)($ventas['total_ventas'] ?? 0),
            'total_productos' => (int)($ventas['total_productos'] ?? 0),
            'monto_total' => (float)($ventas['monto_total'] ?? 0),
            'ultima_compra' => $uc
        ];
    } catch (Exception $e) {
        continue;
    }
}

usort($resultado, fn($a, $b) => $b['monto_total'] <=> $a['monto_total']);

echo json_encode($resultado);
?>