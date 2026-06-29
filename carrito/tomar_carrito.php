<?php
session_start();
require_once __DIR__ . '/../conexion/conexion.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if ($user_id == 0) {
        echo json_encode(["success" => false, "message" => "ID de usuario no válido"]);
        exit;
    }
    
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        echo json_encode(["success" => false, "message" => "Debes iniciar sesión"]);
        exit;
    }
    
    $db = conectarDB();
    
    try {
        $query = "
            SELECT ci.*, p.name, p.price, p.image_url, p.description,
                   v.sku_variante, v.nombre_variante, v.precio_adicional, v.imagen_url as variant_image
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            LEFT JOIN producto_variantes v ON ci.variant_id = v.id
            WHERE ci.user_id = :user_id
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular el total
        $total = 0;
        foreach ($cartItems as &$item) {
            $precioBase = floatval($item['price']);
            $precioAdicional = floatval($item['precio_adicional'] ?? 0);
            $precioFinal = $precioBase + $precioAdicional;
            $item['precio_final'] = $precioFinal;
            $itemTotal = $precioFinal * intval($item['quantity']);
            $total += $itemTotal;
        }
        unset($item);
        
        echo json_encode([
            "success" => true,
            "items" => $cartItems,
            "count" => count($cartItems),
            "total" => $total
        ]);
        
    } catch (PDOException $e) {
        error_log("Error en tomar_carrito: " . $e->getMessage());
        echo json_encode([
            "success" => false, 
            "message" => "Error de base de datos"
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>