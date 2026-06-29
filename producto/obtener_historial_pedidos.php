<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/conexion.php';

session_start();
requerirSesion();

// Obtener el user_id de la sesión
$user_id = $_SESSION['user_id'];

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario no válido'
    ]);
    exit;
}

try {
    $conn = Database::getConnection();

    // Consulta para obtener los pedidos del usuario con detalles de productos y factura
    $sql = "SELECT 
                p.id,
                p.numero_pedido as order_number,
                p.subtotal,
                p.iva,
                p.total,
                p.metodo_pago as payment_method,
                p.observaciones,
                p.estado as status,
                p.created_at,
                f.numero_factura as invoice_number,
                f.estado as invoice_status,
                pd.cantidad as quantity,
                pd.precio_unitario as unit_price,
                pd.subtotal as item_subtotal,
                pd.producto_nombre as product_name
            FROM pedidos p
            LEFT JOIN facturas f ON p.id = f.pedido_id
            LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
            WHERE p.usuario_id = :user_id
            ORDER BY p.created_at DESC, pd.producto_nombre ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $historial = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pedidoId = $row['id'];
        
        // Si es un nuevo pedido, crear estructura
        if (!isset($historial[$pedidoId])) {
            $method = strtolower(trim($row['payment_method'] ?? ''));
            $obs = $row['observaciones'] ?? '';
            
            // Detectar pago mixto desde observaciones
            $es_mixto = ($method === 'mixto');
            $monto_transferencia = 0;
            $monto_efectivo = 0;
            
            if ($es_mixto && !empty($obs)) {
                if (preg_match('/Transferencia:\s*Bs\.?\s*([\d.,]+)/i', $obs, $m)) {
                    $monto_transferencia = floatval(str_replace(',', '', $m[1]));
                }
                if (preg_match('/Efectivo:\s*Bs\.?\s*([\d.,]+)/i', $obs, $m)) {
                    $monto_efectivo = floatval(str_replace(',', '', $m[1]));
                }
            }
            
            // Determinar estado de visualización
            $display_status = $row['status'];
            if (in_array($row['status'], ['facturado', 'completado', 'pagada'])) {
                $display_status = 'Pagado';
            } elseif ($row['status'] === 'pendiente') {
                $display_status = $es_mixto ? 'Pagado' : 'Pendiente';
            }
            
            $historial[$pedidoId] = [
                'id' => $pedidoId,
                'order_number' => $row['order_number'],
                'invoice_number' => $row['invoice_number'],
                'subtotal' => $row['subtotal'],
                'iva' => $row['iva'],
                'total' => $row['total'],
                'payment_method' => $method,
                'status' => $row['status'],
                'display_status' => $display_status,
                'es_mixto' => $es_mixto,
                'monto_transferencia' => $monto_transferencia,
                'monto_efectivo' => $monto_efectivo,
                'created_at' => date('Y-m-d H:i:s', strtotime($row['created_at'])),
                'items' => []
            ];
        }
        
        // Agregar ítem si existe producto
        if ($row['product_name']) {
            $historial[$pedidoId]['items'][] = [
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'unit_price' => $row['unit_price'],
                'item_subtotal' => $row['item_subtotal']
        ];
    }
    }
    
    // Convertir a array indexado
    $historialArray = array_values($historial);

    echo json_encode([
        'success' => true,
        'historial' => $historialArray
    ]);

} catch(PDOException $e) {
    error_log("Error al obtener historial: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar el historial de compras'
    ]);
}
?>