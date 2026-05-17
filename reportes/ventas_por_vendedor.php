<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

// Habilitar errores para depuración
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../conexion/conexion.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

try {
    $pdo = conectarDB();
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

try {
    // Obtener el parámetro del vendedor
    $vendedor_id = isset($_GET['vendedor_id']) ? (int)$_GET['vendedor_id'] : 0;
    
    // ============================================================================
    // CORRECCIÓN: Leer de admin_users (sin columna telefono que no existe)
    // ============================================================================
    $sql = "SELECT id, nombre, correo as email, rol 
            FROM admin_users 
            WHERE rol IN ('admin', 'vendedor', 'superadmin') AND activo = 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay vendedores en admin_users, buscar también en users como fallback
    if (empty($vendedores)) {
        $sqlFallback = "SELECT id, nombre, correo, telefono, rol 
                        FROM users 
                        WHERE rol IN ('admin', 'vendedor', 'superadmin')";
        $stmtFallback = $pdo->prepare($sqlFallback);
        $stmtFallback->execute();
        $vendedores = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $resultado = [];
    
    foreach ($vendedores as $v) {
        // Verificar qué columnas existen en la tabla pedidos
        $columns = $pdo->query("DESCRIBE pedidos")->fetchAll(PDO::FETCH_COLUMN);
        
        // Construir condición dinámica según las columnas disponibles
        $condiciones = [];
        
        if (in_array('usuario_procesa_id', $columns)) {
            $condiciones[] = "p.usuario_procesa_id = :vendedor_id";
        }
        if (in_array('vendedor_id', $columns)) {
            $condiciones[] = "p.vendedor_id = :vendedor_id";
        }
        if (in_array('usuario_id', $columns)) {
            $condiciones[] = "p.usuario_id = :vendedor_id";
        }
        if (in_array('created_by', $columns)) {
            $condiciones[] = "p.created_by = :vendedor_id";
        }
        
        // Si no hay condiciones, usar una que siempre sea falsa
        if (empty($condiciones)) {
            $condiciones[] = "1 = 0";
        }
        
        $condiciones_str = implode(' OR ', $condiciones);
        
        // Consulta para obtener ventas del vendedor
        $sqlVentas = "SELECT 
                        COUNT(DISTINCT p.id) as total_ventas,
                        COALESCE(SUM(p.total), 0) as monto_total,
                        COALESCE(SUM(pd.cantidad), 0) as total_productos,
                        MAX(p.created_at) as ultima_venta
                      FROM pedidos p
                      LEFT JOIN pedido_detalles pd ON p.id = pd.pedido_id
                      WHERE ($condiciones_str)
                        AND p.estado IN ('completado', 'facturado', 'pagada', 'entregado', 'pagado', 'pendiente')";
        
        $stmtVentas = $pdo->prepare($sqlVentas);
        $stmtVentas->bindParam(':vendedor_id', $v['id'], PDO::PARAM_INT);
        $stmtVentas->execute();
        $ventas = $stmtVentas->fetch(PDO::FETCH_ASSOC);
        
        $total_ventas = (int)($ventas['total_ventas'] ?? 0);
        $monto_total = (float)($ventas['monto_total'] ?? 0);
        $total_productos = (int)($ventas['total_productos'] ?? 0);
        $promedio = $total_ventas > 0 ? $monto_total / $total_ventas : 0;
        
        // Formatear fecha de última venta
        $fecha_formateada = '';
        if ($total_ventas > 0 && !empty($ventas['ultima_venta']) && $ventas['ultima_venta'] !== '0000-00-00 00:00:00') {
            try {
                $fecha_obj = new DateTime($ventas['ultima_venta']);
                $fecha_formateada = $fecha_obj->format('Y-m-d');
            } catch(Exception $e) {
                $fecha_formateada = '';
            }
        }
        
        // Para admin_users no hay teléfono, para users sí
        $email = isset($v['email']) ? $v['email'] : (isset($v['correo']) ? $v['correo'] : 'N/A');
        
        $resultado[] = [
            'id' => $v['id'],
            'nombre' => $v['nombre'],
            'email' => $email,
            'total_ventas' => $total_ventas,
            'total_productos' => $total_productos,
            'monto_total' => $monto_total,
            'promedio' => round($promedio, 2),
            'ultima_venta' => $fecha_formateada
        ];
    }
    
    // Ordenar por monto total descendente
    usort($resultado, function($a, $b) {
        return $b['monto_total'] <=> $a['monto_total'];
    });
    
    echo json_encode($resultado);
    
} catch(PDOException $e) {
    error_log("Error en ventas_por_vendedor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Error general en ventas_por_vendedor.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}
?>