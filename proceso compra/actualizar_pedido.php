<?php
// actualizar_pedido.php - Actualizar estado y notas de pedido
session_start();

header('Content-Type: application/json');

$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $pedido_id = $data['id'] ?? 0;
    
    if (!$pedido_id) {
        echo json_encode(['success' => false, 'message' => 'ID de pedido no proporcionado']);
        exit;
    }
    
    // Obtener usuario actual
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT nombre, rol FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $stmt = $pdo->prepare("SELECT nombre, rol FROM admin_users WHERE id = ?");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $usuario_nombre = $usuario['nombre'] ?? 'Sistema';
    $usuario_rol = $usuario['rol'] ?? 'admin';
    
    $updates = [];
    $params = [];
    
    // Actualizar estado
    if (isset($data['estado'])) {
        $updates[] = "estado = ?";
        $params[] = $data['estado'];
    }
    
    // Actualizar notas internas
    if (isset($data['notas_internas'])) {
        $updates[] = "notas_internas = ?";
        $params[] = $data['notas_internas'];
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
        exit;
    }
    
    $params[] = $pedido_id;
    $sql = "UPDATE pedidos SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Registrar en auditoría
    $accion = isset($data['estado']) ? 'CAMBIO_ESTADO' : 'ACTUALIZAR';
    $descripcion = isset($data['estado']) ? "Estado cambiado a: " . $data['estado'] : "Notas internas actualizadas";
    
    $stmt = $pdo->prepare("
        INSERT INTO auditoria_logs (usuario_id, usuario_nombre, usuario_rol, accion, modulo, descripcion, tabla_afectada, registro_id, ip_address) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, $usuario_nombre, $usuario_rol, $accion, 'pedidos', 
        $descripcion, 'pedidos', $pedido_id, $_SERVER['REMOTE_ADDR']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Pedido actualizado correctamente']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>