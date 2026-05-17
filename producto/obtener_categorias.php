<?php
// /proyecto/producto/obtener_categorias.php
header('Content-Type: application/json');
session_start();
require_once '../conexion/conexion.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $pdo = conectarDB();
    
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