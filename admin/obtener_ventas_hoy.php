<?php
session_start();
header('Content-Type: application/json');

// Verificar si es admin
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || 
    !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== 1) {
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
    
    // Obtener ventas de hoy
    $hoy = date('Y-m-d');
    
    $query = "
        SELECT COALESCE(SUM(total), 0) as total_ventas
        FROM pedidos
        WHERE DATE(created_at) = :hoy
        AND estado IN ('confirmado', 'completado', 'facturado', 'pagada')
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['hoy' => $hoy]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // También obtenemos el número de facturas de hoy
    $queryCount = "
        SELECT COUNT(*) as total_facturas
        FROM pedidos
        WHERE DATE(created_at) = :hoy
        AND estado IN ('confirmado', 'completado', 'facturado', 'pagada')
    ";
    
    $stmtCount = $pdo->prepare($queryCount);
    $stmtCount->execute(['hoy' => $hoy]);
    $countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total' => floatval($resultado['total_ventas']),
        'total_formateado' => 'Bs. ' . number_format(floatval($resultado['total_ventas']), 2, ',', '.'),
        'cantidad_facturas' => intval($countResult['total_facturas']),
        'fecha' => $hoy
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>