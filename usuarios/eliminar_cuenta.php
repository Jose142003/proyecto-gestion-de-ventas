<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Usuario no autenticado"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user_id = $_SESSION['user_id'];
        $es_admin = $_SESSION['es_admin'] ?? false;
        $tabla_origen = $_SESSION['tabla_origen'] ?? null;
        
        require_once '../conexion/conexion.php';
        $db = conectarDB();
        
        $db->beginTransaction();
        
        // Determinar qué tabla de usuario está usando
        $es_admin_user = ($es_admin || $tabla_origen === 'admin_users');
        
        if ($es_admin_user) {
            // Es un administrador - NO eliminar, solo desactivar o mostrar error
            echo json_encode([
                "success" => false, 
                "message" => "Las cuentas de administrador no pueden ser eliminadas. Contacte al super administrador."
            ]);
            exit;
        }
        
        // PASO 1: Obtener información del usuario
        $stmt = $db->prepare("SELECT id, correo FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception("Usuario no encontrado");
        }
        
        // PASO 2: Buscar clientes relacionados
        $stmt = $db->prepare("SELECT id FROM clientes WHERE documento IN (SELECT cedula FROM users WHERE id = ?) OR email = ?");
        $stmt->execute([$user_id, $user['correo']]);
        $clientes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // PASO 3: Actualizar facturas que tienen este usuario como usuario_id
        $stmt = $db->prepare("UPDATE facturas SET usuario_id = NULL WHERE usuario_id = ?");
        $stmt->execute([$user_id]);
        
        // PASO 4: Actualizar movimientos_inventario
        $stmt = $db->prepare("UPDATE movimientos_inventario SET usuario_id = NULL WHERE usuario_id = ?");
        $stmt->execute([$user_id]);
        
        // PASO 5: Actualizar pagos
        $stmt = $db->prepare("UPDATE pagos SET usuario_id = NULL WHERE usuario_id = ?");
        $stmt->execute([$user_id]);
        
        // PASO 6: Actualizar comprobantes
        $stmt = $db->prepare("UPDATE comprobantes SET usuario_verificacion_id = NULL WHERE usuario_verificacion_id = ?");
        $stmt->execute([$user_id]);
        
        // PASO 7: Para cada cliente, eliminar sus facturas y dependencias
        foreach ($clientes as $cliente_id) {
            // Eliminar pagos de facturas del cliente
            $stmt = $db->prepare("
                DELETE p FROM pagos p
                INNER JOIN facturas f ON p.factura_id = f.id
                WHERE f.cliente_id = ?
            ");
            $stmt->execute([$cliente_id]);
            
            // Eliminar detalles de facturas
            $stmt = $db->prepare("
                DELETE fd FROM factura_detalles fd
                INNER JOIN facturas f ON fd.factura_id = f.id 
                WHERE f.cliente_id = ?
            ");
            $stmt->execute([$cliente_id]);
            
            // Eliminar facturas
            $stmt = $db->prepare("DELETE FROM facturas WHERE cliente_id = ?");
            $stmt->execute([$cliente_id]);
            
            // Eliminar comprobantes
            $stmt = $db->prepare("DELETE FROM comprobantes WHERE cliente_id = ?");
            $stmt->execute([$cliente_id]);
        }
        
        // PASO 8: Eliminar clientes
        if (!empty($clientes)) {
            $placeholders = implode(',', array_fill(0, count($clientes), '?'));
            $stmt = $db->prepare("DELETE FROM clientes WHERE id IN ($placeholders)");
            $stmt->execute($clientes);
        }
        
        // PASO 9: Eliminar items del carrito
        $stmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // PASO 10: Eliminar detalles de pedidos
        $stmt = $db->prepare("
            DELETE pd FROM pedido_detalles pd
            INNER JOIN pedidos p ON pd.pedido_id = p.id
            WHERE p.usuario_id = ?
        ");
        $stmt->execute([$user_id]);
        
        // PASO 11: Eliminar pedidos
        $stmt = $db->prepare("DELETE FROM pedidos WHERE usuario_id = ?");
        $stmt->execute([$user_id]);
        
        // PASO 12: Eliminar foto de perfil si existe
        $stmt = $db->prepare("SELECT foto_perfil FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $foto = $stmt->fetchColumn();
        if ($foto && file_exists($_SERVER['DOCUMENT_ROOT'] . $foto)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . $foto);
        }
        
        // PASO 13: Finalmente eliminar usuario
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        $db->commit();
        
        // Registrar en auditoría antes de destruir sesión
        try {
            $auditQuery = "INSERT INTO auditoria_logs (usuario_id, accion, modulo, descripcion, ip_address, fecha_creacion) 
                           VALUES (:usuario_id, 'eliminar_cuenta', 'seguridad', 'Usuario eliminó su cuenta', :ip, NOW())";
            $auditStmt = $db->prepare($auditQuery);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $auditStmt->bindParam(':usuario_id', $user_id);
            $auditStmt->bindParam(':ip', $ip);
            $auditStmt->execute();
        } catch (Exception $e) {
            // Ignorar errores de auditoría
        }
        
        session_destroy();
        
        echo json_encode(["success" => true, "message" => "Cuenta eliminada exitosamente"]);
        
    } catch (PDOException $e) {
        if (isset($db)) $db->rollBack();
        error_log("Error al eliminar cuenta: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
        
    } catch (Exception $e) {
        if (isset($db)) $db->rollBack();
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>