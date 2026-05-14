<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conexion.php';

$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    // Obtener todas las categorías con estructura jerárquica
    $sql = "SELECT 
           c.id,
           c.name,
           c.slug,
           c.description,
           c.parent_id,
           c.image_url,
           c.display_order,
           c.is_active,
           c.created_at,
           c.updated_at,
           (SELECT COUNT(*) FROM products p WHERE p.category = c.name) as product_count,
           (SELECT name FROM categories WHERE id = c.parent_id) as parent_name
           FROM categories c
           ORDER BY c.parent_id IS NULL DESC, c.display_order, c.name";
    
    $result = $conn->query($sql);
    
    $categorias = [];
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
    
    // Organizar jerárquicamente
    function buildCategoryTree(&$categories, $parent_id = null) {
        $tree = [];
        foreach ($categories as $key => $category) {
            if ($category['parent_id'] == $parent_id) {
                $children = buildCategoryTree($categories, $category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
                unset($categories[$key]);
            }
        }
        return $tree;
    }
    
    $category_tree = buildCategoryTree($categorias);
    
    // Obtener categorías únicas de productos (para filtros)
    $sql_product_categories = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
    $result_pc = $conn->query($sql_product_categories);
    $product_categories = [];
    while ($row = $result_pc->fetch_assoc()) {
        $product_categories[] = $row['category'];
    }
    
    // Estadísticas de categorías
    $sql_stats = "SELECT 
                 c.name,
                 COUNT(p.id) as total_productos,
                 SUM(p.stock) as stock_total,
                 SUM(CASE WHEN p.stock <= 0 THEN 1 ELSE 0 END) as productos_agotados,
                 SUM(CASE WHEN p.stock BETWEEN 1 AND 5 THEN 1 ELSE 0 END) as productos_bajo_stock,
                 COALESCE(AVG(p.price), 0) as precio_promedio
                 FROM categories c
                 LEFT JOIN products p ON p.category = c.name
                 GROUP BY c.name
                 ORDER BY total_productos DESC";
    
    $result_stats = $conn->query($sql_stats);
    $category_stats = [];
    while ($row = $result_stats->fetch_assoc()) {
        $category_stats[] = $row;
    }
    
    $response = [
        'success' => true,
        'data' => [
            'categorias' => $category_tree,
            'categorias_planas' => $categorias, // Originales sin procesar
            'product_categories' => $product_categories,
            'category_stats' => $category_stats,
            'total_categories' => count($categorias)
        ],
        'message' => 'Categorías obtenidas exitosamente'
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>