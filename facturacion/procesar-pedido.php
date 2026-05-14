<?php
session_start();

// 1. Configuración de conexión (Igual a tu factura.php)
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
    }
    
// 2. Verificar que el usuario Jose (ID 6) o cualquier otro esté logueado
if (!isset($_SESSION['user_id'])) {
    die("Error: Debes iniciar sesión para procesar un pedido.");
    }
    
$user_id = $_SESSION['user_id']; // Aquí capturamos el ID 6 automáticamente
$numero_pedido = "PED-" . date('Y') . "-" . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

try {
    $pdo->beginTransaction();
    
    // 3. Insertar el pedido asignándolo al usuario de la sesión
    $sqlPedido = "INSERT INTO pedidos (usuario_id, numero_pedido, subtotal, iva, total, metodo_pago, estado, created_at) 
                  VALUES (:user_id, :num, :sub, :iva, :total, 'transferencia', 'pagado', NOW())";
    
    $stmt = $pdo->prepare($sqlPedido);
    $stmt->execute([
        ':user_id' => $user_id, // ESTA ES LA CLAVE: Se guarda con tu ID (6)
        ':num'     => $numero_pedido,
        ':sub'     => 100.00, // Valores de prueba
        ':iva'     => 16.00,
        ':total'   => 116.00
    ]);
    
    $pedido_id = $pdo->lastInsertId();
    
    // 4. Insertar un detalle de prueba para que la factura no salga vacía
    $sqlDetalle = "INSERT INTO pedido_detalles (pedido_id, producto_id, cantidad, precio_unitario, subtotal) 
                   VALUES (:pid, 1, 1, 100.00, 100.00)";
    $stmtDet = $pdo->prepare($sqlDetalle);
    $stmtDet->execute([':pid' => $pedido_id]);

    $pdo->commit();
    
    // 5. Redirigir a tu factura.php con el nuevo ID
    header("Location: factura.php?id=" . $pedido_id);
    exit();
    
} catch (Exception $e) {
        $pdo->rollBack();
    echo "Error al crear el pedido: " . $e->getMessage();
}
?>