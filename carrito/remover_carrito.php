<?php
require_once '../conexion/conexion.php';
iniciarSesion();

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verificarCSRF();
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    $variant_id = isset($input['variant_id']) ? intval($input['variant_id']) : 0;
    
    if ($user_id == 0 || $product_id == 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Datos incompletos"]);
        exit;
    }
    
    $db = conectarDB();
    
    try {
        if ($variant_id > 0) {
            $delete_query = "DELETE FROM cart_items WHERE user_id = :user_id AND product_id = :product_id AND variant_id = :variant_id";
        } else {
            $delete_query = "DELETE FROM cart_items WHERE user_id = :user_id AND product_id = :product_id AND variant_id IS NULL";
        }
        $stmt_delete = $db->prepare($delete_query);
        $stmt_delete->bindParam(":user_id", $user_id);
        $stmt_delete->bindParam(":product_id", $product_id);
        if ($variant_id > 0) {
            $stmt_delete->bindParam(":variant_id", $variant_id);
        }
        $stmt_delete->execute();
        
        if ($stmt_delete->rowCount() > 0) {
            echo json_encode([
                "success" => true, 
                "message" => "Producto eliminado del carrito"
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false, 
                "message" => "Producto no encontrado en el carrito"
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Error en remover_carrito: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            "success" => false, 
            "message" => "Error de base de datos"
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>