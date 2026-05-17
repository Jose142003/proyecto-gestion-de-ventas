<?php
require_once '../conexion/conexion.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $input['user_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    
    if ($user_id == 0 || $product_id == 0) {
        echo json_encode(["success" => false, "message" => "Datos incompletos"]);
        exit;
    }
    
    $db = conectarDB();
    
    try {
        $delete_query = "DELETE FROM cart_items WHERE user_id = :user_id AND product_id = :product_id";
        $stmt_delete = $db->prepare($delete_query);
        $stmt_delete->bindParam(":user_id", $user_id);
        $stmt_delete->bindParam(":product_id", $product_id);
        $stmt_delete->execute();
        
        if ($stmt_delete->rowCount() > 0) {
            echo json_encode([
                "success" => true, 
                "message" => "Producto eliminado del carrito"
            ]);
        } else {
            echo json_encode([
                "success" => false, 
                "message" => "Producto no encontrado en el carrito"
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Error en remover_carrito: " . $e->getMessage());
        echo json_encode([
            "success" => false, 
            "message" => "Error de base de datos"
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>