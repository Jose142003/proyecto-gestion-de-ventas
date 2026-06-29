<?php
require_once '../conexion/conexion.php';
iniciarSesion();

header('Content-Type: application/json');

// Desactivar mostrar errores
error_reporting(0); ini_set('display_errors', 0);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verificarCSRF();
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Si no se pudo decodificar JSON, intentar obtener datos del formulario
    if ($input === null && isset($_POST['user_id'])) {
        $input = $_POST;
    }
    
    // Depuración: registrar lo que se recibe
    error_log("Datos recibidos: " . print_r($input, true));
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $product_id = $input['product_id'] ?? 0;
    $variant_id = isset($input['variant_id']) ? intval($input['variant_id']) : 0;
    $quantity = $input['quantity'] ?? 1;
    $quantity = max(1, intval($quantity));
    
    error_log("user_id: $user_id, product_id: $product_id, variant_id: $variant_id, quantity: $quantity");
    
    if ($user_id == 0 || $product_id == 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Datos incompletos", "debug" => $input]);
        exit;
    }
    
    $db = conectarDB();
    
    // Verificar conexión
    if (!$db) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
        exit;
    }
    
    try {
        // Verificar que el usuario existe
        $check_user = "SELECT id FROM users WHERE id = :user_id";
        $stmt_user = $db->prepare($check_user);
        $stmt_user->bindParam(":user_id", $user_id);
        $stmt_user->execute();
        
        if ($stmt_user->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
            exit;
        }
        
        // Verificar que el producto existe
        $check_product = "SELECT id, name, price, stock FROM products WHERE id = :product_id";
        $stmt_check = $db->prepare($check_product);
        $stmt_check->bindParam(":product_id", $product_id);
        $stmt_check->execute();
        
        $product = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(["success" => false, "message" => "Producto no encontrado"]);
            exit;
        }
        
        $precio_final = (float)$product['price'];
        $stock_disponible = (int)$product['stock'];
        
        // Si tiene variante, verificar y usar su precio/stock
        if ($variant_id > 0) {
            $vStmt = $db->prepare("SELECT id, precio_adicional, stock, activo FROM producto_variantes WHERE id = ? AND producto_id = ?");
            $vStmt->execute([$variant_id, $product_id]);
            $variante = $vStmt->fetch(PDO::FETCH_ASSOC);
            if (!$variante || !$variante['activo']) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "Variante no encontrada"]);
                exit;
            }
            $precio_final += (float)$variante['precio_adicional'];
            $stock_disponible = (int)$variante['stock'];
        }
        
        // Verificar stock disponible
        if ($stock_disponible < $quantity) {
            http_response_code(400);
            echo json_encode([
                "success" => false, 
                "message" => "Stock insuficiente. Solo quedan {$stock_disponible} unidades"
            ]);
            exit;
        }
        
        // Verificar si el producto ya está en el carrito (misma variante)
        if ($variant_id > 0) {
            $check_cart = "SELECT id, quantity FROM cart_items WHERE user_id = :user_id AND product_id = :product_id AND variant_id = :variant_id";
        } else {
            $check_cart = "SELECT id, quantity FROM cart_items WHERE user_id = :user_id AND product_id = :product_id AND variant_id IS NULL";
        }
        $stmt_cart = $db->prepare($check_cart);
        $stmt_cart->bindParam(":user_id", $user_id);
        $stmt_cart->bindParam(":product_id", $product_id);
        if ($variant_id > 0) {
            $stmt_cart->bindParam(":variant_id", $variant_id);
        }
        $stmt_cart->execute();
        
        $existing_item = $stmt_cart->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_item) {
            // Verificar que no exceda el stock al actualizar
            $new_quantity = $existing_item['quantity'] + $quantity;
            if ($new_quantity > $stock_disponible) {
                http_response_code(400);
                echo json_encode([
                    "success" => false, 
                    "message" => "No hay suficiente stock. Máximo disponible: {$stock_disponible} unidades"
                ]);
                exit;
            }
            
            // Actualizar cantidad si ya existe
            $update_query = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
            $stmt_update = $db->prepare($update_query);
            $stmt_update->bindParam(":quantity", $new_quantity);
            $stmt_update->bindParam(":id", $existing_item['id']);
            
            if ($stmt_update->execute()) {
                $message = "Cantidad actualizada en el carrito";
                $action = "updated";
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al actualizar carrito"]);
                exit;
            }
        } else {
            // Insertar nuevo item
            $insert_query = "INSERT INTO cart_items (user_id, product_id, variant_id, quantity) VALUES (:user_id, :product_id, :variant_id, :quantity)";
            $stmt_insert = $db->prepare($insert_query);
            $stmt_insert->bindParam(":user_id", $user_id);
            $stmt_insert->bindParam(":product_id", $product_id);
            $variant_id_val = $variant_id > 0 ? $variant_id : null;
            $stmt_insert->bindParam(":variant_id", $variant_id_val, PDO::PARAM_INT);
            $stmt_insert->bindParam(":quantity", $quantity);
            
            if ($stmt_insert->execute()) {
                $message = "Producto añadido al carrito";
                $action = "added";
            } else {
                http_response_code(500);
                echo json_encode(["success" => false, "message" => "Error al añadir al carrito"]);
                exit;
            }
        }
        
        // Obtener el nuevo total de items en el carrito
        $count_query = "SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id";
        $stmt_count = $db->prepare($count_query);
        $stmt_count->bindParam(":user_id", $user_id);
        $stmt_count->execute();
        $count_result = $stmt_count->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            "success" => true, 
            "message" => $message,
            "action" => $action,
            "product" => [
                "id" => $product['id'],
                "name" => $product['name'],
                "price" => $precio_final
            ],
            "cart_count" => $count_result['count']
        ]);
        
    } catch (PDOException $e) {
        error_log("Error en anadir_carrito: " . $e->getMessage());
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