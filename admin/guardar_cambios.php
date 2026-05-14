<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conexion.php';

$response = ['success' => false, 'message' => ''];

// Obtener datos de la solicitud
$data = json_decode(file_get_contents('php://input'), true);
$accion = isset($data['accion']) ? $data['accion'] : (isset($_POST['accion']) ? $_POST['accion'] : '');

try {
    if (empty($accion)) {
        throw new Exception('Acción no especificada');
    }
    
    switch ($accion) {
        case 'actualizar_estado_pedido':
            $pedido_id = intval($data['pedido_id'] ?? $_POST['pedido_id'] ?? 0);
            $nuevo_estado = $data['estado'] ?? $_POST['estado'] ?? '';
            
            if ($pedido_id <= 0 || empty($nuevo_estado)) {
                throw new Exception('ID de pedido y estado son requeridos');
            }
            
            // Validar estado
            $estados_validos = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
            if (!in_array($nuevo_estado, $estados_validos)) {
                throw new Exception('Estado no válido');
            }
            
            $sql = "UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('si', $nuevo_estado, $pedido_id);
            
            if ($stmt->execute()) {
                // Registrar actividad si se cancela
                if ($nuevo_estado == 'cancelled') {
                    // Aquí se podría enviar email de notificación
                }
                
                $response['success'] = true;
                $response['message'] = 'Estado del pedido actualizado exitosamente';
            } else {
                throw new Exception('Error al actualizar estado del pedido');
            }
            break;
            
        case 'actualizar_stock':
            $producto_id = intval($data['producto_id'] ?? $_POST['producto_id'] ?? 0);
            $nuevo_stock = intval($data['stock'] ?? $_POST['stock'] ?? 0);
            $notas = $data['notas'] ?? $_POST['notas'] ?? 'Ajuste manual de stock';
            
            if ($producto_id <= 0) {
                throw new Exception('ID de producto requerido');
            }
            
            // Obtener stock actual
            $sql_current = "SELECT stock FROM products WHERE id = ?";
            $stmt_current = $conn->prepare($sql_current);
            $stmt_current->bind_param('i', $producto_id);
            $stmt_current->execute();
            $result = $stmt_current->get_result();
            $current = $result->fetch_assoc();
            
            if (!$current) {
                throw new Exception('Producto no encontrado');
            }
            
            $stock_actual = $current['stock'];
            $diferencia = $nuevo_stock - $stock_actual;
            
            // Actualizar stock
            $sql_update = "UPDATE products SET stock = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('ii', $nuevo_stock, $producto_id);
            
            if ($stmt_update->execute()) {
                // Registrar movimiento de inventario
                $tipo_movimiento = $diferencia > 0 ? 'ajuste' : 'ajuste';
                
                $sql_movimiento = "INSERT INTO inventory_movements (product_id, movement_type, quantity, previous_stock, new_stock, notes) 
                                  VALUES (?, ?, ?, ?, ?, ?)";
                $stmt_movimiento = $conn->prepare($sql_movimiento);
                $stmt_movimiento->bind_param('isiiis', $producto_id, $tipo_movimiento, abs($diferencia), $stock_actual, $nuevo_stock, $notas);
                $stmt_movimiento->execute();
                
                $response['success'] = true;
                $response['message'] = 'Stock actualizado exitosamente';
            } else {
                throw new Exception('Error al actualizar stock');
            }
            break;
            
        case 'marcar_mensaje_leido':
            $mensaje_id = intval($data['mensaje_id'] ?? $_POST['mensaje_id'] ?? 0);
            
            if ($mensaje_id <= 0) {
                throw new Exception('ID de mensaje requerido');
            }
            
            $sql = "UPDATE contact_messages SET read_status = TRUE WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $mensaje_id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Mensaje marcado como leído';
            } else {
                throw new Exception('Error al marcar mensaje como leído');
            }
            break;
            
        case 'actualizar_configuracion':
            $config_key = $data['key'] ?? $_POST['key'] ?? '';
            $config_value = $data['value'] ?? $_POST['value'] ?? '';
            
            if (empty($config_key)) {
                throw new Exception('Clave de configuración requerida');
            }
            
            // Verificar si existe
            $sql_check = "SELECT id FROM system_settings WHERE setting_key = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param('s', $config_key);
            $stmt_check->execute();
            $result = $stmt_check->get_result();
            
            if ($result->num_rows > 0) {
                // Actualizar
                $sql = "UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ss', $config_value, $config_key);
            } else {
                // Insertar
                $sql = "INSERT INTO system_settings (setting_key, setting_value, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ss', $config_key, $config_value);
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Configuración actualizada exitosamente';
            } else {
                throw new Exception('Error al actualizar configuración');
            }
            break;
            
        case 'actualizar_tienda':
            $store_name = $data['store_name'] ?? $_POST['store_name'] ?? '';
            $store_email = $data['store_email'] ?? $_POST['store_email'] ?? '';
            $store_phone = $data['store_phone'] ?? $_POST['store_phone'] ?? '';
            $store_address = $data['store_address'] ?? $_POST['store_address'] ?? '';
            $tax_percentage = floatval($data['tax_percentage'] ?? $_POST['tax_percentage'] ?? 16);
            
            // Verificar si existe configuración
            $sql_check = "SELECT id FROM store_settings LIMIT 1";
            $result = $conn->query($sql_check);
            
            if ($result->num_rows > 0) {
                // Actualizar
                $sql = "UPDATE store_settings SET 
                       store_name = ?, 
                       store_email = ?, 
                       store_phone = ?, 
                       store_address = ?, 
                       tax_percentage = ?,
                       updated_at = CURRENT_TIMESTAMP 
                       WHERE id = 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssd', $store_name, $store_email, $store_phone, $store_address, $tax_percentage);
            } else {
                // Insertar
                $sql = "INSERT INTO store_settings (store_name, store_email, store_phone, store_address, tax_percentage) 
                       VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssd', $store_name, $store_email, $store_phone, $store_address, $tax_percentage);
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Configuración de tienda actualizada exitosamente';
            } else {
                throw new Exception('Error al actualizar configuración de tienda');
            }
            break;
            
        case 'actualizar_tasa_cambio':
            $rate = floatval($data['rate'] ?? $_POST['rate'] ?? 0);
            
            if ($rate <= 0) {
                throw new Exception('Tasa de cambio inválida');
            }
            
            // Desactivar tasas anteriores
            $sql_deactivate = "UPDATE exchange_rates SET is_active = FALSE WHERE is_active = TRUE";
            $conn->query($sql_deactivate);
            
            // Insertar nueva tasa
            $sql = "INSERT INTO exchange_rates (currency_from, currency_to, rate, source, valid_from) 
                   VALUES ('USD', 'Bs', ?, 'BCV', CURDATE())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('d', $rate);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Tasa de cambio actualizada exitosamente';
            } else {
                throw new Exception('Error al actualizar tasa de cambio');
            }
            break;
            
        default:
            throw new Exception('Acción no válida');
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>