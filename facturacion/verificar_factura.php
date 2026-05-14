<?php
// verificar_factura.php - Verificar si ya se generó factura para un pedido
session_start();

$pedido_id = $_GET['pedido_id'] ?? null;

if (!$pedido_id) {
    echo json_encode(['success' => false, 'message' => 'ID de pedido no válido']);
    exit;
}

// Configuración de la base de datos
$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id FROM facturas WHERE pedido_id = ?");
    $stmt->execute([$pedido_id]);
    $factura = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($factura) {
        echo json_encode([
            'success' => true,
            'factura_id' => $factura['id'],
            'message' => 'Factura encontrada'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Factura aún no generada'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>