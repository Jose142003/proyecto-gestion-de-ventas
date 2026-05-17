<?php
header('Content-Type: application/json');
require_once '../conexion/conexion.php';

// Obtener el user_id de la solicitud
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if ($user_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de usuario no válido'
    ]);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();

    // Consulta para obtener los pedidos del usuario con detalles de productos
    $sql = "SELECT 
                p.id,
                p.numero_pedido as order_number,
                p.subtotal,
                p.iva,
                p.total,
                p.metodo_pago as payment_method,
                p.estado as status,
                p.created_at,
                pd.cantidad as quantity,
                pd.precio_unitario as unit_price,
                pd.subtotal as item_subtotal,
                pd.producto_nombre as product_name
            FROM pedidos p
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
            $historial[$pedidoId] = [
            'id' => $pedidoId,
                'order_number' => $row['order_number'],
                'invoice_number' => null, // No hay número de factura
                'subtotal' => $row['subtotal'],
                'iva' => $row['iva'],
                'total' => $row['total'],
            'payment_method' => $row['payment_method'],
            'status' => $row['status'],
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
        'message' => 'Error al cargar el historial de compras: ' . $e->getMessage()
    ]);
}
?>