<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Configuración de conexión
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// Obtener el parámetro de búsqueda
$buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

// ============================================================================
// CORRECCIÓN: Solo obtener clientes de la tabla users (rol = 'usuario')
// ============================================================================
$sqlClientes = "SELECT id, nombre, correo as email, telefono, cedula as documento 
                FROM users 
                WHERE rol = 'usuario'";
                
if (!empty($buscar)) {
    $sqlClientes .= " AND (nombre LIKE :buscar OR correo LIKE :buscar OR cedula LIKE :buscar)";
}

$stmtClientes = $pdo->prepare($sqlClientes);
if (!empty($buscar)) {
    $stmtClientes->bindValue(':buscar', "%$buscar%");
}
$stmtClientes->execute();
$clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);

$resultado = [];

foreach ($clientes as $cliente) {
    // Buscar pedidos de este cliente (usando usuario_id)
    $sqlVentas = "SELECT 
                    COUNT(DISTINCT p.id) as total_ventas,
                    COALESCE(SUM(p.total), 0) as monto_total,
                    COALESCE(SUM(pd.cantidad), 0) as total_productos,
                    MAX(p.created_at) as ultima_compra
                  FROM pedidos p
                  LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
                  WHERE p.usuario_id = :cliente_id
                    AND p.estado IS NOT NULL";
    
    $stmtVentas = $pdo->prepare($sqlVentas);
    $stmtVentas->bindValue(':cliente_id', $cliente['id']);
    $stmtVentas->execute();
    $ventas = $stmtVentas->fetch(PDO::FETCH_ASSOC);
    
    $total_ventas = (int)($ventas['total_ventas'] ?? 0);
    $monto_total = (float)($ventas['monto_total'] ?? 0);
    $total_productos = (int)($ventas['total_productos'] ?? 0);
    
    // Formatear fecha de última compra
    $ultima_compra_formateada = '';
    if ($total_ventas > 0 && !empty($ventas['ultima_compra']) && $ventas['ultima_compra'] !== '0000-00-00 00:00:00') {
        try {
            $fecha_obj = new DateTime($ventas['ultima_compra']);
            $ultima_compra_formateada = $fecha_obj->format('Y-m-d');
        } catch(Exception $e) {
            $ultima_compra_formateada = '';
        }
    }
    
    $resultado[] = [
        'id' => (int)$cliente['id'],
        'nombre' => $cliente['nombre'] ?? 'N/A',
        'email' => $cliente['email'] ?? 'N/A',
        'telefono' => $cliente['telefono'] ?? 'N/A',
        'total_ventas' => $total_ventas,
        'total_productos' => $total_productos,
        'monto_total' => $monto_total,
        'ultima_compra' => $ultima_compra_formateada
    ];
}

// Ordenar por monto total descendente
usort($resultado, function($a, $b) {
    return $b['monto_total'] <=> $a['monto_total'];
});

echo json_encode($resultado);
?>