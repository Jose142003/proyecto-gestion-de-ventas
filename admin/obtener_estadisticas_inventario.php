<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conexion.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Resumen de inventario
    $sql_summary = "SELECT 
                   COUNT(*) as total_productos,
                   SUM(stock) as stock_total,
                   SUM(price * stock) as valor_inventario,
                   AVG(price) as precio_promedio,
                   COUNT(CASE WHEN stock <= 0 THEN 1 END) as agotados,
                   COUNT(CASE WHEN stock BETWEEN 1 AND 5 THEN 1 END) as bajo_stock,
                   COUNT(CASE WHEN stock > 5 THEN 1 END) as en_stock
                   FROM products";
    
    $result = $conn->query($sql_summary);
    $summary = $result->fetch_assoc();
    
    // Productos agotados
    $sql_out_of_stock = "SELECT 
                        id, 
                        name, 
                        sku, 
                        category, 
                        price, 
                        stock,
                        image_url
                        FROM products 
                        WHERE stock <= 0
                        ORDER BY name";
    
    $result = $conn->query($sql_out_of_stock);
    $out_of_stock = [];
    while ($row = $result->fetch_assoc()) {
        $out_of_stock[] = $row;
    }
    
    // Productos con bajo stock
    $sql_low_stock = "SELECT 
                     id, 
                     name, 
                     sku, 
                     category, 
                     price, 
                     stock,
                     image_url
                     FROM products 
                     WHERE stock BETWEEN 1 AND 5
                     ORDER BY stock, name";
    
    $result = $conn->query($sql_low_stock);
    $low_stock = [];
    while ($row = $result->fetch_assoc()) {
        $low_stock[] = $row;
    }
    
    // Productos más vendidos (movimiento de inventario)
    $sql_top_selling = "SELECT 
                       p.id, 
                       p.name, 
                       p.sku, 
                       p.category,
                       p.price,
                       p.stock,
                       COALESCE(SUM(im.quantity), 0) as salidas_totales
                       FROM products p
                       LEFT JOIN inventory_movements im ON p.id = im.product_id 
                       AND im.movement_type IN ('venta', 'salida')
                       GROUP BY p.id
                       HAVING salidas_totales > 0
                       ORDER BY salidas_totales DESC
                       LIMIT 10";
    
    $result = $conn->query($sql_top_selling);
    $top_selling = [];
    while ($row = $result->fetch_assoc()) {
        $top_selling[] = $row;
    }
    
    // Movimientos recientes de inventario
    $sql_recent_movements = "SELECT 
                            im.id,
                            im.product_id,
                            p.name as producto,
                            p.sku,
                            im.movement_type as tipo_movimiento,
                            im.quantity as cantidad,
                            im.previous_stock as stock_anterior,
                            im.new_stock as stock_nuevo,
                            im.reference_type as tipo_referencia,
                            im.reference_id as referencia_id,
                            im.notes as notas,
                            DATE_FORMAT(im.created_at, '%d/%m/%Y %H:%i') as fecha
                            FROM inventory_movements im
                            JOIN products p ON im.product_id = p.id
                            ORDER BY im.created_at DESC
                            LIMIT 50";
    
    $result = $conn->query($sql_recent_movements);
    $recent_movements = [];
    while ($row = $result->fetch_assoc()) {
        $recent_movements[] = $row;
    }
    
    // Inventario por categoría
    $sql_inventory_by_category = "SELECT 
                                 category,
                                 COUNT(*) as cantidad_productos,
                                 SUM(stock) as stock_total,
                                 SUM(price * stock) as valor_total,
                                 AVG(price) as precio_promedio,
                                 COUNT(CASE WHEN stock <= 0 THEN 1 END) as agotados,
                                 COUNT(CASE WHEN stock BETWEEN 1 AND 5 THEN 1 END) as bajo_stock
                                 FROM products
                                 WHERE category IS NOT NULL AND category != ''
                                 GROUP BY category
                                 ORDER BY valor_total DESC";
    
    $result = $conn->query($sql_inventory_by_category);
    $inventory_by_category = [];
    while ($row = $result->fetch_assoc()) {
        $inventory_by_category[] = $row;
    }
    
    // Valor de inventario histórico
    $sql_inventory_value_history = "SELECT 
                                   DATE(created_at) as fecha,
                                   COUNT(DISTINCT product_id) as productos,
                                   SUM(quantity) as movimiento_total,
                                   COUNT(CASE WHEN movement_type IN ('entrada', 'compra') THEN 1 END) as entradas,
                                   COUNT(CASE WHEN movement_type IN ('venta', 'salida') THEN 1 END) as salidas
                                   FROM inventory_movements
                                   WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                   GROUP BY DATE(created_at)
                                   ORDER BY fecha";
    
    $result = $conn->query($sql_inventory_value_history);
    $inventory_history = [];
    while ($row = $result->fetch_assoc()) {
        $inventory_history[] = $row;
    }
    
    // Productos con mayor rotación (ratio ventas/stock)
    $sql_rotation_rate = "SELECT 
                         p.id,
                         p.name,
                         p.sku,
                         p.category,
                         p.stock,
                         p.price,
                         COALESCE(SUM(CASE WHEN im.movement_type IN ('venta', 'salida') THEN im.quantity ELSE 0 END), 0) as ventas_30dias,
                         CASE 
                           WHEN p.stock > 0 THEN 
                             COALESCE(SUM(CASE WHEN im.movement_type IN ('venta', 'salida') THEN im.quantity ELSE 0 END), 0) / p.stock 
                           ELSE 0 
                         END as ratio_rotacion
                         FROM products p
                         LEFT JOIN inventory_movements im ON p.id = im.product_id 
                         AND im.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         GROUP BY p.id
                         HAVING ventas_30dias > 0
                         ORDER BY ratio_rotacion DESC
                         LIMIT 10";
    
    $result = $conn->query($sql_rotation_rate);
    $rotation_rates = [];
    while ($row = $result->fetch_assoc()) {
        $rotation_rates[] = $row;
    }
    
    $response = [
        'success' => true,
        'data' => [
            'summary' => $summary,
            'out_of_stock' => $out_of_stock,
            'low_stock' => $low_stock,
            'top_selling' => $top_selling,
            'recent_movements' => $recent_movements,
            'inventory_by_category' => $inventory_by_category,
            'inventory_history' => $inventory_history,
            'rotation_rates' => $rotation_rates
        ],
        'message' => 'Estadísticas de inventario obtenidas exitosamente'
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>