<?php
// actualizar_pedido.php - Actualizar estado y notas de pedido
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo = conectarDB();
    
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
    
    // State machine: transiciones permitidas
    $allowedTransitions = [
        'pendiente' => ['confirmado', 'cancelado'],
        'confirmado' => ['enviado', 'cancelado'],
        'enviado' => ['entregado', 'cancelado'],
        'entregado' => [],
        'cancelado' => [],
        'facturado' => ['entregado', 'cancelado'],
    ];

    $updates = [];
    $params = [];
    
    // Actualizar estado con validación de máquina de estados
    if (isset($data['estado'])) {
        $nuevoEstado = $data['estado'];
        
        // Obtener estado actual del pedido
        $stmtEstado = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
        $stmtEstado->execute([$pedido_id]);
        $pedidoActual = $stmtEstado->fetch(PDO::FETCH_ASSOC);
        
        if (!$pedidoActual) {
            echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
            exit;
        }
        
        $estadoActual = $pedidoActual['estado'];
        
        if (!isset($allowedTransitions[$estadoActual])) {
            echo json_encode(['success' => false, 'message' => "Estado actual '$estadoActual' no es válido"]);
            exit;
        }
        
        if (!in_array($nuevoEstado, $allowedTransitions[$estadoActual])) {
            echo json_encode(['success' => false, 'message' => "No se puede cambiar de '$estadoActual' a '$nuevoEstado'"]);
            exit;
        }
        
        $updates[] = "estado = ?";
        $params[] = $nuevoEstado;
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
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>