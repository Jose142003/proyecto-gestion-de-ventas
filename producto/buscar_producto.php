<?php
require_once '../conexion/conexion.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $search = $_GET['search'] ?? '';
    
    try {
        $db = conectarDB();
        
        $query = "SELECT id, name, price, image_url, description, rating, specs, stock 
                  FROM products 
                  WHERE (name LIKE :search OR description LIKE :search) AND active = 1";
        
        $stmt = $db->prepare($query);
        $searchTerm = "%" . $search . "%";
        $stmt->bindParam(":search", $searchTerm);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true,
            "products" => $products,
            "count" => count($products)
        ]);
        
    } catch (PDOException $e) {
        error_log("Error en buscar_producto: " . $e->getMessage());
        echo json_encode([
            "success" => false, 
            "message" => "Error en la búsqueda"
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Método no permitido"]);
}
?>