<?php
// /proyecto/proceso_compra/obtener_productos_pedido.php
header('Content-Type: application/json');
session_start();
require_once '../conexion/conexion.php';

try {
    // Verificar que el usuario esté autenticado
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No autorizado'
        ]);
        exit;
    }

    $pedido_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0);
    $usuario_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    
    if ($pedido_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'ID de pedido no válido'
        ]);
        exit;
    }

    $pdo = conectarDB();
    
    // Verificar que el pedido pertenezca al usuario (a menos que sea admin)
    $es_admin = isset($_SESSION['user_rol']) && ($_SESSION['user_rol'] === 'admin' || $_SESSION['user_rol'] === 'superadmin');
    
    if (!$es_admin) {
        $check_query = "SELECT id FROM pedidos WHERE id = :pedido_id AND usuario_id = :usuario_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([':pedido_id' => $pedido_id, ':usuario_id' => $usuario_id]);
        if (!$check_stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'No tienes permiso para ver este pedido'
            ]);
            exit;
        }
    }
    
    // Obtener información del pedido
    $sql_pedido = "SELECT 
        p.*,
        u.nombre as cliente_nombre,
        u.correo as cliente_email,
        u.telefono as cliente_telefono,
        u.cedula as cliente_cedula
    FROM pedidos p
    LEFT JOIN users u ON p.usuario_id = u.id
    WHERE p.id = :pedido_id";
    
    $stmt_pedido = $pdo->prepare($sql_pedido);
    $stmt_pedido->execute([':pedido_id' => $pedido_id]);
    $pedido = $stmt_pedido->fetch(PDO::FETCH_ASSOC);
    
    if (!$pedido) {
        echo json_encode([
            'success' => false,
            'message' => 'Pedido no encontrado'
        ]);
        exit;
    }
    
    // Obtener productos del pedido
    $sql_productos = "SELECT 
        pd.id,
        pd.producto_id,
        pd.cantidad,
        pd.precio_unitario,
        pd.subtotal,
        COALESCE(p.name, pd.producto_nombre) as producto_nombre,
        p.sku as producto_sku,
        p.image_url,
        p.category as categoria
    FROM pedido_detalles pd
    LEFT JOIN products p ON pd.producto_id = p.id
    WHERE pd.pedido_id = :pedido_id";
    
    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute([':pedido_id' => $pedido_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear productos
    foreach ($productos as &$prod) {
        $prod['precio'] = floatval($prod['precio_unitario']);
        $prod['total'] = floatval($prod['subtotal']);
    }
    
    // Calcular resumen
    $subtotal = 0;
    foreach ($productos as $producto) {
        $subtotal += floatval($producto['subtotal']);
    }
    
    $resumen = [
        'subtotal' => $subtotal,
        'iva' => floatval($pedido['iva'] ?? ($subtotal * obtenerIvaPorcentaje($pdo) / 100)),
        'total' => floatval($pedido['total'] ?? ($subtotal * 1.16))
    ];
    
    echo json_encode([
        'success' => true,
        'pedido' => $pedido,
        'productos' => $productos,
        'resumen' => $resumen
    ]);
    
} catch (PDOException $e) {
    error_log("Error al obtener productos del pedido: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar productos: ' . $e->getMessage()
    ]);
}
?>