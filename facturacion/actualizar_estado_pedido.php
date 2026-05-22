<?php
// /proyecto/admin/actualizar_estado_pedido.php
header('Content-Type: application/json');
session_start();
require_once '../conexion/conexion.php';
verificarCSRF();

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado'
        ]);
        exit;
    }
    
    $es_admin = ($_SESSION['user_rol'] === 'admin' || $_SESSION['user_rol'] === 'superadmin');
    
    if (!$es_admin && $_SESSION['user_rol'] !== 'vendedor') {
        echo json_encode([
            'success' => false,
            'message' => 'Permisos insuficientes'
        ]);
        exit;
    }
    
    // Obtener datos POST
    $input = json_decode(file_get_contents('php://input'), true);
    $pedido_id = isset($input['pedido_id']) ? intval($input['pedido_id']) : 0;
    $nuevo_estado = isset($input['estado']) ? $input['estado'] : '';
    $notas = isset($input['notas']) ? $input['notas'] : '';
    
    $estados_validos = ['pendiente', 'procesando', 'facturado', 'completado', 'cancelado'];
    
    if ($pedido_id <= 0 || !in_array($nuevo_estado, $estados_validos)) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos inválidos'
        ]);
        exit;
    }
    
    $pdo = conectarDB();
    
    // Verificar que el pedido existe
    $check_stmt = $pdo->prepare("SELECT id, estado, usuario_id FROM pedidos WHERE id = ?");
    $check_stmt->execute([$pedido_id]);
    $pedido = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'Pedido no encontrado'
        ]);
        exit;
    }
    
    $estado_anterior = $pedido['estado'];
    
    // Actualizar estado
    $update_stmt = $pdo->prepare("
        UPDATE pedidos 
        SET estado = :estado, 
            usuario_procesa_id = :usuario_id,
            notas_internas = CONCAT(IFNULL(notas_internas, ''), :notas),
            updated_at = NOW()
        WHERE id = :pedido_id
    ");
    
    $notas_con_formato = "\n[" . date('Y-m-d H:i:s') . "] Estado cambiado de '$estado_anterior' a '$nuevo_estado' por " . $_SESSION['user_name'] . ": $notas";
    
    $update_stmt->execute([
        ':estado' => $nuevo_estado,
        ':usuario_id' => $_SESSION['user_id'],
        ':notas' => $notas_con_formato,
        ':pedido_id' => $pedido_id
    ]);
    
    // Registrar en historial
    try {
        // Crear tabla de historial si no existe
        $create_table = "
            CREATE TABLE IF NOT EXISTS pedido_historial (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pedido_id INT NOT NULL,
                usuario_id INT NOT NULL,
                accion VARCHAR(100),
                estado_anterior VARCHAR(50),
                estado_nuevo VARCHAR(50),
                observaciones TEXT,
                fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pedido_id (pedido_id),
                INDEX idx_fecha (fecha)
            )
        ";
        $pdo->exec($create_table);
        
        $historial_stmt = $pdo->prepare("
            INSERT INTO pedido_historial (pedido_id, usuario_id, accion, estado_anterior, estado_nuevo, observaciones)
            VALUES (?, ?, 'cambio_estado', ?, ?, ?)
        ");
        $historial_stmt->execute([$pedido_id, $_SESSION['user_id'], $estado_anterior, $nuevo_estado, $notas]);
    } catch (Exception $e) {
        error_log("Error al registrar historial: " . $e->getMessage());
    }
    
    // Si el estado es 'facturado', verificar si ya tiene factura
    if ($nuevo_estado === 'facturado') {
        $factura_check = $pdo->prepare("SELECT id FROM facturas WHERE pedido_id = ?");
        $factura_check->execute([$pedido_id]);
        $tiene_factura = $factura_check->fetch();
        
        if (!$tiene_factura) {
        auditoriaRegistrar('actualizar_estado_pedido', 'facturacion', "Pedido ID $pedido_id: estado '$estado_anterior' -> '$nuevo_estado' (sin factura)");
        echo json_encode([
            'success' => true,
            'message' => 'Estado actualizado. Nota: El pedido no tiene factura asociada aún.',
            'estado_anterior' => $estado_anterior,
            'nuevo_estado' => $nuevo_estado,
            'sin_factura' => true
        ]);
        exit;
    }
}

auditoriaRegistrar('actualizar_estado_pedido', 'facturacion', "Pedido ID $pedido_id: estado '$estado_anterior' -> '$nuevo_estado'");
echo json_encode([
        'success' => true,
        'message' => 'Estado actualizado correctamente',
        'estado_anterior' => $estado_anterior,
        'nuevo_estado' => $nuevo_estado
    ]);
    
} catch (PDOException $e) {
    error_log("Error al actualizar estado del pedido: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
} catch (Exception $e) {
    error_log("Error general: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>