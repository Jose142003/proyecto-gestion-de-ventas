<?php
// /proyecto/proceso_compra/obtener_pedido.php
header('Content-Type: application/json');
session_start();
require_once '../conexion/conexion.php';

try {
    // Verificar autenticación
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado'
        ]);
        exit;
    }
    
    $pdo = conectarDB();
    
    // Obtener parámetros
    $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
    $usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;
    
    // Construir consulta base
    $sql = "SELECT 
            p.id,
            p.numero_pedido,
            p.usuario_id,
            u.nombre as cliente,
            u.correo as cliente_email,
            u.telefono as cliente_telefono,
            p.total,
            p.subtotal,
            p.iva,
            p.metodo_pago,
            p.estado,
            p.created_at as fecha,
            p.created_at as fecha_creacion,
            (SELECT COUNT(*) FROM pedido_detalles WHERE pedido_id = p.id) as total_productos,
            (SELECT SUM(cantidad) FROM pedido_detalles WHERE pedido_id = p.id) as total_unidades
        FROM pedidos p
        LEFT JOIN users u ON p.usuario_id = u.id
        WHERE 1=1";
    
    $params = [];
    
    if ($estado && $estado !== 'todos') {
        $sql .= " AND p.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if ($usuario_id > 0) {
        $sql .= " AND p.usuario_id = :usuario_id";
        $params[':usuario_id'] = $usuario_id;
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT 200";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear los datos
    foreach ($pedidos as &$pedido) {
        $pedido['total'] = floatval($pedido['total']);
        $pedido['subtotal'] = floatval($pedido['subtotal']);
        $pedido['iva'] = floatval($pedido['iva']);
        
        // Formatear la fecha correctamente
        if ($pedido['fecha']) {
            $fechaObj = new DateTime($pedido['fecha']);
            $pedido['fecha'] = $fechaObj->format('Y-m-d'); // Para el formato que espera formatDate()
            $pedido['fecha_formateada'] = $fechaObj->format('d/m/Y H:i'); // Para mostrar
        } else {
            $pedido['fecha'] = date('Y-m-d');
            $pedido['fecha_formateada'] = date('d/m/Y H:i');
        }
        
        // Si no hay nombre de cliente, usar un valor por defecto
        if (empty($pedido['cliente'])) {
            $pedido['cliente'] = 'Usuario ID: ' . $pedido['usuario_id'];
        }
        
        // Si no hay email, mostrar mensaje
        if (empty($pedido['cliente_email'])) {
            $pedido['cliente_email'] = 'No disponible';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pedidos,
        'total' => count($pedidos)
    ]);
    
} catch (PDOException $e) {
    error_log("Error al obtener pedidos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar pedidos: ' . $e->getMessage()
    ]);
}
?>