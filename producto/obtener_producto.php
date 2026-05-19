<?php
// obtener_producto.php - VERSIÓN CORREGIDA (COPIA DE obtener_productos.php que SÍ funciona)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Desactivar mostrar errores
error_reporting(0); ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Verificar y crear columna 'active' si no existe
    $check_column = $pdo->query("SHOW COLUMNS FROM products LIKE 'active'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
        $pdo->exec("ALTER TABLE products ADD COLUMN deleted_at DATETIME NULL");
    }
    
    // Obtener parámetros de filtro
    $incluir_ocultos = isset($_GET['incluir_ocultos']) && $_GET['incluir_ocultos'] === 'true';
    $solo_ocultos = isset($_GET['solo_ocultos']) && $_GET['solo_ocultos'] === 'true';
    $solo_visibles = isset($_GET['solo_visibles']) && $_GET['solo_visibles'] === 'true';
    
    // Construir consulta SQL
    $sql = "SELECT 
                id,
                sku,
                name,
                price,
                image_url,
                description,
                category,
                rating,
                views_count,
                specs,
                stock,
                is_featured,
                weight,
                dimensions,
                currency,
                COALESCE(active, 1) as active,
                created_at
            FROM products 
            WHERE 1=1";
    
    // Aplicar filtros
    if ($solo_ocultos) {
        $sql .= " AND active = 0";
    } elseif ($solo_visibles) {
        $sql .= " AND active = 1";
    } elseif (!$incluir_ocultos) {
        $sql .= " AND active = 1";
    }
    
    $sql .= " ORDER BY id DESC";  // ← CAMBIADO a DESC para mostrar los nuevos primero
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $productos = $stmt->fetchAll();
    
    // Normalizar nombres de campos para consistencia con pagina_modernizada
    $productos_normalizados = [];
    foreach ($productos as $producto) {
        $productos_normalizados[] = [
            'id' => (int)$producto['id'],
            'sku' => $producto['sku'] ?? '',
            'name' => $producto['name'],
            'nombre' => $producto['name'],  // Alias
            'price' => (float)$producto['price'],
            'precio' => (float)$producto['price'],  // Alias
            'image' => !empty($producto['image_url']) ? $producto['image_url'] : 'https://via.placeholder.com/300x300?text=Sin+Imagen',
            'image_url' => $producto['image_url'],
            'description' => $producto['description'] ?? '',
            'category' => $producto['category'] ?? 'General',
            'categoria' => $producto['category'] ?? 'General',  // Alias
            'rating' => (float)($producto['rating'] ?? 0),
            'stock' => (int)($producto['stock'] ?? 0),
            'active' => (int)$producto['active'],
            'activo' => (int)$producto['active']  // Alias
        ];
    }
    
    $response = [
        'success' => true,
        'total' => count($productos_normalizados),
        'products' => $productos_normalizados,  // ← Para pagina_modernizada
        'productos' => $productos_normalizados,  // ← Para compatibilidad
        'filtro_aplicado' => [
            'incluir_ocultos' => $incluir_ocultos,
            'solo_ocultos' => $solo_ocultos,
            'solo_visibles' => $solo_visibles
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Error en obtener_producto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => 'Error interno del servidor',
        'products' => []
    ], JSON_UNESCAPED_UNICODE);
}
?>