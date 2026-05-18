<?php
session_start();
header('Content-Type: application/json');
error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Verificar sesión
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado. Inicie sesión primero.']);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $user = null;
    $es_admin = false;
    
    // ========== PRIMERO: BUSCAR EN admin_users ==========
    $stmt_admin = $pdo->prepare("SELECT id, nombre, correo, rol, activo FROM admin_users WHERE id = ? AND activo = 1");
    $stmt_admin->execute([$user_id]);
    $admin_user = $stmt_admin->fetch(PDO::FETCH_ASSOC);
    
    if ($admin_user) {
        $user = $admin_user;
        $es_admin = true;
    } else {
        // ========== SEGUNDO: BUSCAR EN users ==========
        $stmt_user = $pdo->prepare("SELECT id, nombre, correo, rol, is_active, estado FROM users WHERE id = ? AND is_active = 1 AND estado = 'activo'");
        $stmt_user->execute([$user_id]);
        $normal_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
        
        if ($normal_user) {
            $user = $normal_user;
            $roles_admin = ['admin', 'superadmin', 'ceo', 'gerente'];
            $es_admin = in_array(strtolower($user['rol']), $roles_admin);
        }
    }
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o inactivo']);
        exit;
    }
    
    if (!$es_admin) {
        echo json_encode(['success' => false, 'message' => 'Se requieren permisos de administrador']);
        exit;
    }
    
    // ========== CONSULTAS ==========
    
    // 1. VENTAS TOTALES (desde facturas)
    $query = "SELECT COALESCE(SUM(total), 0) as total FROM facturas WHERE estado != 'anulada'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ventas_totales = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 2. VENTAS DEL MES
    $query = "SELECT COALESCE(SUM(total), 0) as total FROM facturas 
              WHERE MONTH(fecha_emision) = MONTH(CURDATE()) 
              AND YEAR(fecha_emision) = YEAR(CURDATE())
              AND estado != 'anulada'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ventas_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 3. VENTAS DE LA SEMANA
    $query = "SELECT COALESCE(SUM(total), 0) as total FROM facturas 
              WHERE YEARWEEK(fecha_emision) = YEARWEEK(CURDATE())
              AND estado != 'anulada'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ventas_semana = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 4. CRECIMIENTO vs MES ANTERIOR
    $query_mes_anterior = "SELECT COALESCE(SUM(total), 0) as total FROM facturas 
                            WHERE MONTH(fecha_emision) = MONTH(CURDATE() - INTERVAL 1 MONTH)
                            AND YEAR(fecha_emision) = YEAR(CURDATE() - INTERVAL 1 MONTH)
                            AND estado != 'anulada'";
    $stmt = $pdo->prepare($query_mes_anterior);
    $stmt->execute();
    $ventas_mes_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $crecimiento = 0;
    if ($ventas_mes_anterior > 0) {
        $crecimiento = round((($ventas_mes - $ventas_mes_anterior) / $ventas_mes_anterior) * 100, 2);
    }
    
    // 5. TICKET PROMEDIO
    $query = "SELECT COALESCE(AVG(total), 0) as promedio FROM facturas WHERE estado != 'anulada'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ticket_promedio = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'];
    
    // 6. CLIENTES ACTIVOS (últimos 30 días)
    $query = "SELECT COUNT(DISTINCT cliente_id) as total FROM facturas 
              WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND estado = 'pagada'";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $clientes_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // 7. VENTAS POR MES (últimos 12 meses)
    $query = "SELECT 
                DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
                DATE_FORMAT(fecha_emision, '%b %Y') as mes_nombre,
                COALESCE(SUM(total), 0) as total
              FROM facturas 
              WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              AND estado != 'anulada'
              GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m'), DATE_FORMAT(fecha_emision, '%b %Y')
              ORDER BY mes ASC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $ventas_por_mes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 8. MÉTODOS DE PAGO
    $query = "SELECT 
                metodo_pago,
                COALESCE(SUM(total), 0) as total
              FROM facturas 
              WHERE estado != 'anulada' AND metodo_pago IS NOT NULL AND metodo_pago != ''
              GROUP BY metodo_pago
              ORDER BY total DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $metodos_pago = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($metodos_pago)) {
        $metodos_pago = [['metodo_pago' => 'Sin datos', 'total' => 0]];
    }
    
    // 9. TOP 5 PRODUCTOS MÁS VENDIDOS
    $query = "SELECT 
                p.id,
                p.name as nombre,
                COALESCE(SUM(pd.cantidad), 0) as unidades,
                COALESCE(SUM(pd.subtotal), 0) as ingresos
              FROM products p
              LEFT JOIN pedido_detalles pd ON p.id = pd.producto_id
              LEFT JOIN pedidos ped ON pd.pedido_id = ped.id AND ped.estado IN ('entregado', 'facturado', 'completado')
              GROUP BY p.id, p.name
              ORDER BY unidades DESC
              LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $top_productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 10. TOP 5 CLIENTES
    $query = "SELECT 
                c.id,
                c.nombre,
                c.email,
                COUNT(f.id) as total_compras,
                COALESCE(SUM(f.total), 0) as monto_total
              FROM clientes c
              LEFT JOIN facturas f ON c.id = f.cliente_id AND f.estado = 'pagada'
              GROUP BY c.id, c.nombre, c.email
              ORDER BY monto_total DESC
              LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 11. TOP 5 VENDEDORES (desde admin_users)
    $query = "SELECT 
                au.id,
                au.nombre,
                au.correo as email,
                COUNT(f.id) as total_ventas,
                COALESCE(SUM(f.total), 0) as monto_total
              FROM admin_users au
              LEFT JOIN facturas f ON au.id = f.usuario_id AND f.estado = 'pagada'
              GROUP BY au.id, au.nombre, au.correo
              ORDER BY monto_total DESC
              LIMIT 5";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $top_vendedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($top_vendedores)) {
        $top_vendedores = [['id' => 0, 'nombre' => 'Sin datos', 'email' => '-', 'total_ventas' => 0, 'monto_total' => 0]];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'ventas_totales' => floatval($ventas_totales),
        'ventas_mes' => floatval($ventas_mes),
        'ventas_semana' => floatval($ventas_semana),
        'crecimiento' => $crecimiento,
        'ticket_promedio' => floatval($ticket_promedio),
        'clientes_activos' => intval($clientes_activos),
        'ventas_por_mes' => $ventas_por_mes,
        'metodos_pago' => $metodos_pago,
        'top_productos' => $top_productos,
        'top_clientes' => $top_clientes,
        'top_vendedores' => $top_vendedores
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>