<?php
// /proyecto/reportes/reporte_stock.php
session_start();
header('Content-Type: application/json');
require_once '../conexion/conexion.php';

$response = ['success' => false, 'message' => '', 'data' => [], 'stats' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'No autorizado';
    echo json_encode($response);
    exit;
}

try {
    $db = conectarDB();
    
    $categoria = $_GET['categoria'] ?? '';
    $estado = $_GET['estado'] ?? '';
    
    // CORREGIDO: usar 'products' en lugar de 'productos'
    $sql = "SELECT 
                p.id, 
                p.name as nombre, 
                p.category as categoria, 
                p.price as precio, 
                p.stock,
                (SELECT COUNT(*) FROM pedido_detalles pd WHERE pd.producto_id = p.id) as veces_vendido,
                (SELECT COALESCE(SUM(pd.cantidad), 0) FROM pedido_detalles pd WHERE pd.producto_id = p.id) as unidades_vendidas
            FROM products p 
            WHERE 1=1";
    $params = [];
    
    if ($categoria) { 
        $sql .= " AND p.category = ?"; 
        $params[] = $categoria; 
    }
    if ($estado === 'critico') {
        $sql .= " AND p.stock > 0 AND p.stock <= 5";
    } elseif ($estado === 'bajo') {
        $sql .= " AND p.stock > 0 AND p.stock <= 10";
    } elseif ($estado === 'normal') {
        $sql .= " AND p.stock > 10";
    } elseif ($estado === 'agotado') {
        $sql .= " AND p.stock = 0";
    }
    
    $sql .= " ORDER BY p.stock ASC, p.name ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stats = [
        'total_productos' => count($productos), 
        'stock_critico' => 0, 
        'stock_bajo' => 0, 
        'stock_medio' => 0, 
        'stock_alto' => 0, 
        'agotados' => 0, 
        'valor_inventario' => 0
    ];
    
    foreach ($productos as $p) {
        $stats['valor_inventario'] += $p['precio'] * $p['stock'];
        if ($p['stock'] == 0) {
            $stats['agotados']++;
        } elseif ($p['stock'] <= 5) {
            $stats['stock_critico']++;
        } elseif ($p['stock'] <= 10) {
            $stats['stock_bajo']++;
        } elseif ($p['stock'] <= 20) {
            $stats['stock_medio']++;
        } else {
            $stats['stock_alto']++;
        }
    }
    
    $response['success'] = true;
    $response['data'] = $productos;
    $response['stats'] = $stats;
    
} catch (PDOException $e) { 
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>