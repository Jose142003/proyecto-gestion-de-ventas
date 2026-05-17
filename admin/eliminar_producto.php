<?php
session_start();
header('Content-Type: application/json');

require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Obtener datos
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos']);
        exit;
    }
    
    $producto_id = isset($input['id']) ? (int)$input['id'] : 0;
    $ocultar = isset($input['ocultar']) ? (bool)$input['ocultar'] : false;
    
    if ($producto_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }
    
    // Obtener nombre del producto
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producto) {
        echo json_encode(['success' => false, 'message' => 'Producto no existe']);
        exit;
    }
    
    // Cambiar estado: ocultar = true -> active = 0, ocultar = false -> active = 1
    $nuevo_estado = $ocultar ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE products SET active = ? WHERE id = ?");
    $stmt->execute([$nuevo_estado, $producto_id]);
    
    $mensaje = $ocultar 
        ? "Producto '{$producto['name']}' ocultado correctamente"
        : "Producto '{$producto['name']}' mostrado correctamente";
    
    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'nuevo_estado' => $nuevo_estado
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error DB: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>