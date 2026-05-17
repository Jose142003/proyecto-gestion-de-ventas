<?php
session_start();
require_once '../conexion/conexion.php';
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $user_id = $_GET['user_id'] ?? 0;
    
    if ($user_id == 0) {
        echo json_encode(["success" => false, "message" => "ID de usuario no válido"]);
        exit;
    }
    
    $db = conectarDB();
    
    try {
        $query = "
            SELECT ci.*, p.name, p.price, p.image_url, p.description 
            FROM cart_items ci 
            JOIN products p ON ci.product_id = p.id 
            WHERE ci.user_id = :user_id
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular el total
        $total = 0;
        foreach ($cartItems as $item) {
            $itemTotal = floatval($item['price']) * intval($item['quantity']);
            $total += $itemTotal;
        }
        
        echo json_encode([
            "success" => true,
            "items" => $cartItems,
            "count" => count($cartItems),
            "total" => $total  // Agregar el total calculado
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