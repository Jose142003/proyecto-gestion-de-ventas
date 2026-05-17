<?php
// /proyecto/producto/obtener_categorias.php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$host = 'localhost';
$dbname = 'carrito_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener categorías únicas de la tabla products
    $stmt = $pdo->query("SELECT DISTINCT category as nombre FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay categorías, devolver array vacío
    if (!$categorias) {
        $categorias = [];
    }
    
    echo json_encode([
        'success' => true,
        'categorias' => $categorias
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>