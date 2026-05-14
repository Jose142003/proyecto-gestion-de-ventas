<?php
// obtener_estadisticas_admin.php
header('Content-Type: application/json');
require_once '../conexion/conexion.php';

try {
    // Obtener conexión
    $database = new Database();
    $pdo = $database->conectar();
    
    // Verificar que se seleccionó la base de datos correcta
    $pdo->exec("USE carrito_db");
    
    // Array para almacenar todas las estadísticas
    $estadisticas = [];
    
    // 1. Total de productos
    $query = "SELECT COUNT(*) as total FROM productos";
    $stmt = $pdo->query($query);
    $estadisticas['total_productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. Total de categorías
    $query = "SELECT COUNT(*) as total FROM categorias";
    $stmt = $pdo->query($query);
    $estadisticas['total_categorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 3. Total de usuarios
    $query = "SELECT COUNT(*) as total FROM usuarios";
    $stmt = $pdo->query($query);
    $estadisticas['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 4. Total de ventas (si existe tabla de ventas)
    $query = "SELECT COUNT(*) as total FROM ventas";
    $stmt = $pdo->query($query);
    $estadisticas['total_ventas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 5. Ingresos totales
    $query = "SELECT COALESCE(SUM(total), 0) as total_ingresos FROM ventas";
    $stmt = $pdo->query($query);
    $estadisticas['ingresos_totales'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_ingresos'];
    
    // 6. Productos más vendidos (si existe tabla de detalles_venta)
    $query = "
        SELECT p.nombre, SUM(dv.cantidad) as total_vendido 
        FROM detalles_venta dv 
        INNER JOIN productos p ON dv.producto_id = p.id 
        GROUP BY p.id, p.nombre 
        ORDER BY total_vendido DESC 
        LIMIT 5
    ";
    $stmt = $pdo->query($query);
    $estadisticas['productos_mas_vendidos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 7. Ventas por día (últimos 7 días)
    $query = "
        SELECT DATE(fecha) as fecha, COUNT(*) as cantidad_ventas, SUM(total) as ingresos 
        FROM ventas 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(fecha) 
        ORDER BY fecha DESC
    ";
    $stmt = $pdo->query($query);
    $estadisticas['ventas_ultimos_7_dias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. Productos con stock bajo (menos de 10 unidades)
    $query = "
        SELECT nombre, stock 
        FROM productos 
        WHERE stock < 10 
        ORDER BY stock ASC 
        LIMIT 5
    ";
    $stmt = $pdo->query($query);
    $estadisticas['productos_stock_bajo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 9. Ventas por categoría
    $query = "
        SELECT c.nombre as categoria, SUM(dv.cantidad * dv.precio_unitario) as total_ventas 
        FROM detalles_venta dv 
        INNER JOIN productos p ON dv.producto_id = p.id 
        INNER JOIN categorias c ON p.categoria_id = c.id 
        GROUP BY c.id, c.nombre 
        ORDER BY total_ventas DESC
    ";
    $stmt = $pdo->query($query);
    $estadisticas['ventas_por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 10. Usuarios registrados por mes (últimos 6 meses)
    $query = "
        SELECT DATE_FORMAT(fecha_registro, '%Y-%m') as mes, COUNT(*) as nuevos_usuarios 
        FROM usuarios 
        WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_registro, '%Y-%m') 
        ORDER BY mes DESC
    ";
    $stmt = $pdo->query($query);
    $estadisticas['usuarios_por_mes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. Productos por categoría
    $query = "
        SELECT c.nombre as categoria, COUNT(p.id) as cantidad_productos 
        FROM categorias c 
        LEFT JOIN productos p ON c.id = p.categoria_id 
        GROUP BY c.id, c.nombre 
        ORDER BY cantidad_productos DESC
    ";
    $stmt = $pdo->query($query);
    $estadisticas['productos_por_categoria'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 12. Ticket promedio
    $query = "
        SELECT COALESCE(AVG(total), 0) as ticket_promedio 
        FROM ventas
    ";
    $stmt = $pdo->query($query);
    $estadisticas['ticket_promedio'] = $stmt->fetch(PDO::FETCH_ASSOC)['ticket_promedio'];
    
    // 13. Producto más caro y más barato
    $query = "
        SELECT 
            MAX(precio) as producto_mas_caro,
            MIN(precio) as producto_mas_barato
        FROM productos
    ";
    $stmt = $pdo->query($query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $estadisticas['producto_mas_caro'] = $result['producto_mas_caro'];
    $estadisticas['producto_mas_barato'] = $result['producto_mas_barato'];
    
    // 14. Porcentaje de conversión (visitas a ventas) - ajusta según tu estructura
    // Nota: Esto asume que tienes una tabla de visitas o sesiones
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'data' => $estadisticas,
        'message' => 'Estadísticas obtenidas correctamente'
    ]);
    
} catch (PDOException $e) {
    // Error de base de datos
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage(),
        'data' => null
    ]);
} catch (Exception $e) {
    // Error general
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => null
    ]);
}
?>