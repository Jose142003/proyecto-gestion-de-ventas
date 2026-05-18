<?php
// guardar_pedido.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight request de CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit();
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Validar datos requeridos
    $usuario_id = $input['usuario_id'] ?? null;
    $productos = $input['productos'] ?? [];
    $metodo_pago = $input['metodo_pago'] ?? 'transferencia';
    $total = floatval($input['total'] ?? 0);
    $subtotal = floatval($input['subtotal'] ?? $total / 1.16); // Si no viene subtotal, calcular asumiendo 16% IVA
    $iva = floatval($input['iva'] ?? $total - $subtotal);
    
    // Datos de contacto
    $nombre_receptor = $input['nombre'] ?? $input['cliente_nombre'] ?? '';
    $telefono_contacto = $input['telefono'] ?? $input['cliente_telefono'] ?? '';
    $direccion_entrega = $input['direccion'] ?? $input['direccion_entrega'] ?? '';
    $observaciones = $input['observaciones'] ?? '';
    
    if (!$usuario_id) {
        throw new Exception('ID de usuario no proporcionado');
    }
    
    if (empty($productos)) {
        throw new Exception('No hay productos en el pedido');
    }
    
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$usuario_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Usuario no encontrado');
    }
    
    // Generar número de pedido usando el procedimiento almacenado
    $stmt = $pdo->prepare("CALL sp_generar_numero_pedido(@numero_pedido)");
    $stmt->execute();
    $stmt = $pdo->query("SELECT @numero_pedido as numero_pedido");
    $numero_pedido = $stmt->fetch(PDO::FETCH_ASSOC)['numero_pedido'];
    
    // Crear el pedido usando el procedimiento almacenado
    $stmt = $pdo->prepare("CALL sp_crear_pedido(?, ?, ?, ?, ?, ?, @pedido_id, @numero_pedido)");
    $stmt->execute([
        $usuario_id,
        $metodo_pago,
        $direccion_entrega,
        $telefono_contacto,
        $nombre_receptor,
        $observaciones
    ]);
    
    // Obtener el ID del pedido creado
    $stmt = $pdo->query("SELECT @pedido_id as pedido_id, @numero_pedido as numero_pedido");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pedido_id = $result['pedido_id'];
    $numero_pedido = $result['numero_pedido'];
    
    // Agregar cada producto al pedido usando el procedimiento almacenado
    foreach ($productos as $producto) {
        $producto_id = $producto['id'] ?? $producto['producto_id'] ?? null;
        $cantidad = intval($producto['cantidad'] ?? 1);
        
        if (!$producto_id) {
            throw new Exception('Producto sin ID');
        }
        
        // Verificar que el producto existe
        $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ?");
        $stmt->execute([$producto_id]);
        $prod = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$prod) {
            throw new Exception("Producto ID $producto_id no encontrado");
        }
        
        // Verificar stock suficiente
        if ($prod['stock'] < $cantidad) {
            throw new Exception("Stock insuficiente para el producto ID $producto_id");
        }
        
        // Agregar producto al pedido
        $stmt = $pdo->prepare("CALL sp_agregar_producto_pedido(?, ?, ?)");
        $stmt->execute([$pedido_id, $producto_id, $cantidad]);
    }
    
    // Confirmar transacción
    $pdo->commit();
    
    // Obtener el pedido completo para devolverlo
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.nombre as cliente_nombre,
            u.correo as cliente_email,
            u.telefono as cliente_telefono
        FROM pedidos p
        JOIN users u ON p.usuario_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener detalles del pedido
    $stmt = $pdo->prepare("
        SELECT 
            pd.*,
            pr.name as producto_nombre,
            pr.image_url as producto_imagen,
            pr.sku as producto_sku
        FROM pedido_detalles pd
        JOIN products pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $pedido['productos'] = $detalles;
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Pedido guardado correctamente',
        'pedido_id' => $pedido_id,
        'numero_pedido' => $numero_pedido,
        'pedido' => $pedido
    ]);
    
} catch (PDOException $e) {
    // Revertir transacción en caso de error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>