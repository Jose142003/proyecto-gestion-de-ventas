<?php
// obtener_productos.php - VERSIÓN CORREGIDA
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

// Habilitar errores para depuración
error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    error_log("Conexión exitosa a la base de datos: $dbname");
    
    // ==============================================
    // VERIFICAR Y CREAR COLUMNA 'active' SI NO EXISTE
    // ==============================================
    $check_column = $pdo->query("SHOW COLUMNS FROM products LIKE 'active'");
    if ($check_column->rowCount() == 0) {
        $pdo->exec("ALTER TABLE products ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1");
        $pdo->exec("ALTER TABLE products ADD COLUMN deleted_at DATETIME NULL");
        error_log("Columnas 'active' y 'deleted_at' creadas correctamente");
    }
    
    // ==============================================
    // OBTENER PARÁMETROS DE FILTRO
    // ==============================================
    $incluir_ocultos = isset($_GET['incluir_ocultos']) && $_GET['incluir_ocultos'] === 'true';
    $solo_ocultos = isset($_GET['solo_ocultos']) && $_GET['solo_ocultos'] === 'true';
    $solo_visibles = isset($_GET['solo_visibles']) && $_GET['solo_visibles'] === 'true';
    
    error_log("Filtros - incluir_ocultos: $incluir_ocultos, solo_ocultos: $solo_ocultos, solo_visibles: $solo_visibles");
    
    // ==============================================
    // CONSTRUIR CONSULTA SQL CON SOPORTE PARA OCULTOS
    // ==============================================
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
    
    // Aplicar filtros según los parámetros recibidos
    if ($solo_ocultos) {
        $sql .= " AND active = 0";
        error_log("Filtrando SOLO productos OCULTOS");
    } elseif ($solo_visibles) {
        $sql .= " AND active = 1";
        error_log("Filtrando SOLO productos VISIBLES");
    } elseif (!$incluir_ocultos) {
        $sql .= " AND active = 1";
        error_log("Filtrando productos VISIBLES (por defecto)");
    } else {
        error_log("Mostrando TODOS los productos (incluyendo ocultos)");
    }
    
    $sql .= " ORDER BY id ASC";
    
    error_log("Ejecutando consulta SQL: " . $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $productos = $stmt->fetchAll();
    
    // Normalizar nombres de campos para consistencia
    foreach ($productos as &$producto) {
        // Asegurar que existe el campo 'nombre' (alias de name)
        if (isset($producto['name']) && !isset($producto['nombre'])) {
            $producto['nombre'] = $producto['name'];
        }
        // Asegurar que existe 'categoria' (alias de category)
        if (isset($producto['category']) && !isset($producto['categoria'])) {
            $producto['categoria'] = $producto['category'];
        }
        // Asegurar que existe 'precio' (alias de price)
        if (isset($producto['price']) && !isset($producto['precio'])) {
            $producto['precio'] = $producto['price'];
        }
        // Asegurar que 'active' es un entero
        $producto['active'] = (int)$producto['active'];
        $producto['activo'] = $producto['active']; // alias adicional
    }
    
    $totalProductos = count($productos);
    error_log("Productos encontrados: $totalProductos");
    
    if ($totalProductos > 0) {
        error_log("Primer producto: " . json_encode($productos[0]));
    }
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'total' => $totalProductos,
        'productos' => $productos,
        'filtro_aplicado' => [
            'incluir_ocultos' => $incluir_ocultos,
            'solo_ocultos' => $solo_ocultos,
            'solo_visibles' => $solo_visibles
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("Error de PDO en obtener_productos: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos',
        'message' => 'Error interno del servidor'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error general en obtener_productos: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error general',
        'message' => 'Error interno del servidor'
    ], JSON_UNESCAPED_UNICODE);
}
?>