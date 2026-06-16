<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id']) && !isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$usuario_id = $_SESSION['user_id'] ?? $_SESSION['usuario_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['pedidos']) || empty($data['pedidos'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se seleccionaron pedidos']);
    exit;
}

$pedidos_ids = $data['pedidos'];

require_once __DIR__ . '/../conexion/conexion.php';
verificarCSRF();

try {
    $pdo = conectarDB();
    
    $anio = date('Y');
    $facturados = [];
    $errores = [];
    
    // Obtener estructuras de tablas una sola vez
    $stmt = $pdo->query("SHOW COLUMNS FROM facturas LIKE 'metodo_pago'");
    $columnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $enumValues = [];
    if ($columnInfo && preg_match("/^enum\((.*)\)$/", $columnInfo['Type'], $matches)) {
        $enumValues = array_map(function($value) { return trim($value, "'"); }, explode(',', $matches[1]));
    }
    
    $mapa_metodos = [
        'pago_movil' => 'transferencia',
        'zelle' => 'transferencia',
        'mixto' => 'efectivo',
        'transferencia' => 'transferencia',
        'efectivo' => 'efectivo',
        'tarjeta' => 'tarjeta',
        'paypal' => 'paypal',
        'cheque' => 'cheque'
    ];
    
    // PROCESAR CADA PEDIDO INDIVIDUALMENTE CON SU PROPIA TRANSACCIÓN
    foreach ($pedidos_ids as $pedido_id) {
        try {
            $pdo->beginTransaction();
            
            // Obtener el ÚLTIMO número de factura usado (sin bloqueos complejos)
            $stmt = $pdo->prepare("
                SELECT numero_factura FROM facturas 
                WHERE numero_factura LIKE :pattern 
                ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([':pattern' => "FAC-$anio-%"]);
            $last = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($last) {
                preg_match('/FAC-' . $anio . '-(\d+)/', $last['numero_factura'], $matches);
                $siguiente = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            } else {
                $siguiente = 1;
            }
            
            $numero_factura = "FAC-$anio-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
            
            // Verificar que no exista (por seguridad)
            $stmt = $pdo->prepare("SELECT id FROM facturas WHERE numero_factura = :num");
            $stmt->execute([':num' => $numero_factura]);
            if ($stmt->fetch()) {
                // Si existe, buscar el siguiente disponible
                do {
                    $siguiente++;
                    $numero_factura = "FAC-$anio-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
                    $stmt->execute([':num' => $numero_factura]);
                } while ($stmt->fetch());
            }
            
            // Obtener datos del pedido
            $stmt = $pdo->prepare("
                SELECT p.*, u.nombre as cliente_nombre, u.correo as cliente_email, u.cedula, u.direccion 
                FROM pedidos p 
                JOIN users u ON p.usuario_id = u.id 
                WHERE p.id = :id AND p.estado NOT IN ('facturado', 'cancelado')
            ");
            $stmt->execute([':id' => $pedido_id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pedido) {
                $errores[] = "Pedido $pedido_id no encontrado o ya facturado";
                $pdo->rollBack();
                continue;
            }
            
            // Mapear método de pago
            $metodo_pago = $mapa_metodos[$pedido['metodo_pago']] ?? 'efectivo';
            if (!in_array($metodo_pago, $enumValues)) {
                $metodo_pago = 'efectivo';
            }
            
            // Crear o obtener cliente
            $stmt = $pdo->prepare("SELECT id FROM clientes WHERE email = :email");
            $stmt->execute([':email' => $pedido['cliente_email']]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cliente) {
                $stmt = $pdo->prepare("
                    INSERT INTO clientes (tipo_documento, documento, nombre, email, telefono, direccion) 
                    VALUES ('cedula', :cedula, :nombre, :email, :telefono, :direccion)
                ");
                $stmt->execute([
                    ':cedula' => $pedido['cedula'] ?? '99999999',
                    ':nombre' => $pedido['cliente_nombre'],
                    ':email' => $pedido['cliente_email'],
                    ':telefono' => $pedido['telefono_contacto'] ?? '',
                    ':direccion' => $pedido['direccion_envio'] ?? $pedido['direccion'] ?? ''
                ]);
                $cliente_id = $pdo->lastInsertId();
            } else {
                $cliente_id = $cliente['id'];
            }
            
            // Crear factura
            $stmt = $pdo->prepare("
                INSERT INTO facturas (
                    numero_factura, cliente_id, pedido_id, fecha_emision, fecha_vencimiento, 
                    subtotal, iva, total, metodo_pago, estado, usuario_id
                ) VALUES (
                    :numero_factura, :cliente_id, :pedido_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 
                    :subtotal, :iva, :total, :metodo_pago, 'pendiente', :usuario_id
                )
            ");
            
            $stmt->execute([
                ':numero_factura' => $numero_factura,
                ':cliente_id' => $cliente_id,
                ':pedido_id' => $pedido_id,
                ':subtotal' => $pedido['subtotal'],
                ':iva' => $pedido['iva'],
                ':total' => $pedido['total'],
                ':metodo_pago' => $metodo_pago,
                ':usuario_id' => $usuario_id
            ]);
            
            $factura_id = $pdo->lastInsertId();
            
            // Copiar detalles (intentar ambas tablas)
            $detalles_copiados = false;
            
            // Intentar con pedido_detalles
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal)
                    SELECT :factura_id, producto_id, cantidad, precio_unitario, subtotal 
                    FROM pedido_detalles WHERE pedido_id = :pedido_id
                ");
                $stmt->execute([':factura_id' => $factura_id, ':pedido_id' => $pedido_id]);
                $detalles_copiados = true;
            } catch (PDOException $e) {
                // Intentar con pedido_items
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal)
                        SELECT :factura_id, producto_id, cantidad, precio, precio * cantidad
                        FROM pedido_items WHERE pedido_id = :pedido_id
                    ");
                    $stmt->execute([':factura_id' => $factura_id, ':pedido_id' => $pedido_id]);
                    $detalles_copiados = true;
                } catch (PDOException $e2) {
                    error_log("No se pudieron copiar detalles para pedido $pedido_id: " . $e2->getMessage());
                }
            }
            
            // Actualizar estado del pedido
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'facturado', fecha_facturacion = NOW() WHERE id = :id");
            $stmt->execute([':id' => $pedido_id]);
            
            $pdo->commit();
            $facturados[] = $pedido_id;

            require_once __DIR__ . '/../notificaciones/cola.php';
            colaNotificacionesAgregar('telegram_pedido', $pedido_id, $factura_id);
            colaNotificacionesDispararProcesador();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error interno del servidor";
            error_log("Error facturando pedido $pedido_id: " . $e->getMessage());
        }
    }
    
    $mensaje = count($facturados) . ' pedido(s) facturado(s) correctamente';
    if (count($errores) > 0) {
        $mensaje .= '. Errores: ' . implode(', ', $errores);
    }
    
    echo json_encode([
        'success' => count($facturados) > 0,
        'message' => $mensaje,
        'facturados' => $facturados,
        'errores' => $errores
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>