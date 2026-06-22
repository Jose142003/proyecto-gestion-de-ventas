<?php
// /proyecto/proceso_compra/cancelar_pedido.php
header('Content-Type: application/json');
session_start();
require_once '../conexion/conexion.php';
verificarCSRF();

try {
    // Verificar que el usuario sea administrador
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_rol'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $pedido_id = isset($data['pedido_id']) ? intval($data['pedido_id']) : 0;
    $motivo = isset($data['motivo']) ? $data['motivo'] : 'Cancelado por administrador';
    
    if ($pedido_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de pedido no válido'
        ]);
        exit;
    }
    
    $pdo = conectarDB();
    $pdo->beginTransaction();
    
    // Leer estado actual antes de modificarlo
    $stmt = $pdo->prepare("SELECT estado FROM pedidos WHERE id = ?");
    $stmt->execute([$pedido_id]);
    $pedidoActual = $stmt->fetch(PDO::FETCH_ASSOC);
    $estado_anterior = $pedidoActual ? $pedidoActual['estado'] : 'pendiente';
    
    // Actualizar estado del pedido
    $sql = "UPDATE pedidos SET estado = 'cancelado', observaciones = CONCAT(IFNULL(observaciones, ''), ' | Cancelado: ', :motivo), updated_at = NOW() WHERE id = :pedido_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':motivo' => $motivo,
        ':pedido_id' => $pedido_id
    ]);
    
    // Devolver stock a los productos
    $sql_productos = "SELECT producto_id, cantidad FROM pedido_detalles WHERE pedido_id = :pedido_id";
    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute([':pedido_id' => $pedido_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($productos as $producto) {
        $sql_stock = "UPDATE products SET stock = stock + :cantidad WHERE id = :producto_id";
        $stmt_stock = $pdo->prepare($sql_stock);
        $stmt_stock->execute([
            ':cantidad' => $producto['cantidad'],
            ':producto_id' => $producto['producto_id']
        ]);
    }

    // Registrar en historial_stock
    try {
        $check_historial = $pdo->query("SHOW TABLES LIKE 'historial_stock'");
        if ($check_historial->rowCount() > 0) {
            foreach ($productos as $producto) {
                $sql_historial = "INSERT INTO historial_stock (producto_id, tipo, cantidad, referencia, usuario_id, fecha) VALUES (?, 'entrada', ?, ?, ?, NOW())";
                $stmt_historial = $pdo->prepare($sql_historial);
                $stmt_historial->execute([$producto['producto_id'], $producto['cantidad'], "Cancelación pedido #$pedido_id", $_SESSION['user_id'] ?? null]);
            }
        }
    } catch (Exception $e) {
        error_log("Error al registrar historial_stock: " . $e->getMessage());
    }
    
    $pdo->commit();

    // Notificar al cliente vía Telegram
    require_once __DIR__ . '/../telegram/notificar_pedido.php';
    telegramNotificarCambioEstado($pdo, $pedido_id, $estado_anterior);

    echo json_encode([
        'success' => true,
        'message' => 'Pedido cancelado correctamente'
    ]);
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al cancelar pedido: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>