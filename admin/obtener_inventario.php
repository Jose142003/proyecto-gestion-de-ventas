<?php
require_once '../conexion/conexion.php';
requerirAdmin();
header('Content-Type: application/json');

try {
    $pdo = conectarDB();
    
    // Verificar qué columnas tiene la tabla products
    $check_sql = "SHOW COLUMNS FROM products";
    $check_result = $pdo->query($check_sql);
    $columns = [];
    while ($row = $check_result->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    // Determinar si existe una columna de fecha para products
    $has_created_at = in_array('created_at', $columns);
    
    // Verificar tablas inventory y product_stats
    $tables_exist = true;
    $inventory_exists = false;
    $product_stats_exists = false;
    
    $table_check = $pdo->query("SHOW TABLES LIKE 'inventory'");
    $inventory_exists = $table_check->rowCount() > 0;
    
    $table_check = $pdo->query("SHOW TABLES LIKE 'product_stats'");
    $product_stats_exists = $table_check->rowCount() > 0;
    
    // Construir la consulta principal según las tablas existentes
    if (!$inventory_exists && !$product_stats_exists) {
        // Solo tabla products existe
        $sql = "SELECT 
                    p.id,
                    p.name as producto_nombre,
                    p.description as descripcion,
                    p.category as categoria,
                    p.price as precio,
                    p.stock as stock_actual,
                    5 as stock_minimo, -- Valor por defecto
                    p.image_url as imagen,
                    p.rating,
                    
                    -- Calcular estado del stock
                    CASE 
                        WHEN p.stock <= 0 THEN 'sin_stock'
                        WHEN p.stock <= 5 THEN 'stock_bajo'
                        WHEN p.stock <= 10 THEN 'stock_medio'
                        ELSE 'stock_optimo'
                    END as estado_stock,
                    
                    -- Valores por defecto para estadísticas
                    0 as total_vendido,
                    0 as ingreso_total,
                    p.rating as rating_promedio,
                    
                    -- Información de inventario
                    NULL as ultima_reposicion,
                    0 as ultima_cantidad_reposicion,
                    0 as total_vendido_inventario,
                    NULL as ultima_venta,
                    
                    -- Valor en inventario
                    (p.price * p.stock) as valor_inventario,
                    
                    -- Días desde última venta
                    NULL as dias_sin_venta,
                    
                    -- Rotación de inventario
                    0 as indice_rotacion,
                    
                    -- Productos en carritos
                    (SELECT COUNT(*) FROM cart_items ci WHERE ci.product_id = p.id) as en_carritos
                    
                FROM products p
                ORDER BY 
                    CASE 
                        WHEN p.stock <= 0 THEN 1
                        WHEN p.stock <= 5 THEN 2
                        ELSE 3
                    END,
                    p.stock ASC,
                    p.name";
    } else {
        // Todas las tablas existen
        $sql = "SELECT 
                    p.id,
                    p.name as producto_nombre,
                    p.description as descripcion,
                    p.category as categoria,
                    p.price as precio,
                    p.stock as stock_actual,
                    COALESCE(i.minimum_stock, 5) as stock_minimo,
                    p.image_url as imagen,
                    p.rating,
                    
                    -- Calcular estado del stock
                    CASE 
                        WHEN p.stock <= 0 THEN 'sin_stock'
                        WHEN p.stock <= COALESCE(i.minimum_stock, 5) THEN 'stock_bajo'
                        WHEN p.stock <= (COALESCE(i.minimum_stock, 5) * 2) THEN 'stock_medio'
                        ELSE 'stock_optimo'
                    END as estado_stock,
                    
                    -- Estadísticas de ventas
                    COALESCE(ps.total_sold, 0) as total_vendido,
                    COALESCE(ps.total_revenue, 0) as ingreso_total,
                    COALESCE(ps.avg_rating, p.rating) as rating_promedio,
                    
                    -- Información de inventario
                    COALESCE(i.last_restock_date, 'Nunca') as ultima_reposicion,
                    COALESCE(i.last_restock_quantity, 0) as ultima_cantidad_reposicion,
                    COALESCE(i.total_sold, 0) as total_vendido_inventario,
                    COALESCE(i.last_sale_date, 'Nunca') as ultima_venta,
                    
                    -- Valor en inventario
                    (p.price * p.stock) as valor_inventario,
                    
                    -- Días desde última venta
                    CASE 
                        WHEN i.last_sale_date IS NOT NULL THEN DATEDIFF(NOW(), i.last_sale_date)
                        ELSE NULL
                    END as dias_sin_venta,
                    
                    -- Rotación de inventario
                    CASE 
                        WHEN p.stock > 0 THEN COALESCE(ps.total_sold, 0) / p.stock
                        ELSE 0
                    END as indice_rotacion,
                    
                    -- Productos en carritos
                    (SELECT COUNT(*) FROM cart_items ci WHERE ci.product_id = p.id) as en_carritos
                    
                FROM products p
                LEFT JOIN inventory i ON p.id = i.product_id
                LEFT JOIN product_stats ps ON p.id = ps.product_id
                ORDER BY 
                    CASE 
                        WHEN p.stock <= 0 THEN 1
                        WHEN p.stock <= COALESCE(i.minimum_stock, 5) THEN 2
                        ELSE 3
                    END,
                    p.stock ASC,
                    p.name";
    }
    
    $result = $pdo->query($sql);
    
    $inventario = [];
    $total_valor_inventario = 0;
    $total_productos = 0;
    $sin_stock = 0;
    $stock_bajo = 0;
    $stock_medio = 0;
    $stock_optimo = 0;
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $producto = [
            'id' => $row['id'],
            'producto_nombre' => $row['producto_nombre'],
            'descripcion' => $row['descripcion'],
            'categoria' => $row['categoria'],
            'precio' => floatval($row['precio']),
            'stock_actual' => intval($row['stock_actual']),
            'stock_minimo' => intval($row['stock_minimo']),
            'imagen' => $row['imagen'],
            'rating' => floatval($row['rating']),
            'estado_stock' => $row['estado_stock'],
            'total_vendido' => intval($row['total_vendido']),
            'ingreso_total' => floatval($row['ingreso_total']),
            'rating_promedio' => floatval($row['rating_promedio']),
            'ultima_reposicion' => $row['ultima_reposicion'],
            'ultima_cantidad_reposicion' => intval($row['ultima_cantidad_reposicion']),
            'total_vendido_inventario' => intval($row['total_vendido_inventario']),
            'ultima_venta' => $row['ultima_venta'],
            'valor_inventario' => floatval($row['valor_inventario']),
            'dias_sin_venta' => $row['dias_sin_venta'],
            'indice_rotacion' => floatval($row['indice_rotacion']),
            'en_carritos' => intval($row['en_carritos']),
            
            // Información adicional calculada
            'diferencia_stock' => intval($row['stock_actual']) - intval($row['stock_minimo']),
            'necesita_reposicion' => intval($row['stock_actual']) <= intval($row['stock_minimo']),
            'cantidad_sugerida_reposicion' => max(10, intval($row['stock_minimo']) * 2 - intval($row['stock_actual']))
        ];
        
        $inventario[] = $producto;
        
        // Contar por estado
        $total_productos++;
        $total_valor_inventario += $producto['valor_inventario'];
        
        switch ($row['estado_stock']) {
            case 'sin_stock':
                $sin_stock++;
                break;
            case 'stock_bajo':
                $stock_bajo++;
                break;
            case 'stock_medio':
                $stock_medio++;
                break;
            case 'stock_optimo':
                $stock_optimo++;
                break;
        }
    }
    
    // Obtener estadísticas por categoría
    $categorias_sql = "SELECT 
                        p.category as categoria,
                        COUNT(*) as total_productos,
                        SUM(p.stock) as total_stock,
                        SUM(p.price * p.stock) as valor_total,
                        AVG(p.price) as precio_promedio,
                        MIN(p.stock) as stock_minimo_categoria,
                        MAX(p.stock) as stock_maximo_categoria,
                        SUM(CASE WHEN p.stock <= 0 THEN 1 ELSE 0 END) as sin_stock,
                        SUM(CASE WHEN p.stock > 0 AND p.stock <= 5 THEN 1 ELSE 0 END) as stock_bajo
                      FROM products p
                      GROUP BY p.category
                      ORDER BY valor_total DESC";
    
    $categorias_result = $pdo->query($categorias_sql);
    $estadisticas_categorias = [];
    while ($row = $categorias_result->fetch(PDO::FETCH_ASSOC)) {
        $estadisticas_categorias[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'inventario' => $inventario,
        'estadisticas_generales' => [
            'total_productos' => $total_productos,
            'total_valor_inventario' => $total_valor_inventario,
            'sin_stock' => $sin_stock,
            'stock_bajo' => $stock_bajo,
            'stock_medio' => $stock_medio,
            'stock_optimo' => $stock_optimo,
            'valor_promedio_producto' => $total_productos > 0 ? $total_valor_inventario / $total_productos : 0,
            'stock_promedio' => $total_productos > 0 ? array_sum(array_column($inventario, 'stock_actual')) / $total_productos : 0
        ],
        'estadisticas_categorias' => $estadisticas_categorias,
        'total' => count($inventario)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'sugerencia' => 'Verifica que la tabla products exista en la base de datos.'
    ], JSON_UNESCAPED_UNICODE);
}
?>