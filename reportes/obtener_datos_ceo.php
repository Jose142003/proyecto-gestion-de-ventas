<?php
// obtener_datos_ceo.php
header('Content-Type: application/json');
session_start();

// 1. Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    // Deshabilitar ONLY_FULL_GROUP_BY temporalmente para esta conexión
    $pdo->exec("SET SESSION sql_mode = ''");
    
    $user_id = $_SESSION['user_id'];

    // 2. Validar que el usuario sea un administrador activo
    $stmt_check = $pdo->prepare("SELECT rol FROM admin_users WHERE id = ? AND activo = 1");
    $stmt_check->execute([$user_id]);
    $admin = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        // También verificar en users con rol CEO/admin
        $stmt_check2 = $pdo->prepare("SELECT rol FROM users WHERE id = ? AND estado = 'activo'");
        $stmt_check2->execute([$user_id]);
        $user = $stmt_check2->fetch(PDO::FETCH_ASSOC);
        
        $roles_admin = ['admin', 'superadmin', 'ceo', 'CEO'];
        if (!$user || !in_array(strtolower($user['rol']), $roles_admin)) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado: No tienes permisos de administrador']);
            exit;
        }
    }

    // --- INICIO DE RECOLECCIÓN DE MÉTRICAS ---

    // Total de usuarios (Tabla users)
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM users WHERE estado = 'activo'")->fetchColumn();

    // Total de clientes registrados (Tabla clientes)
    $total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'activo'")->fetchColumn();

    // Total de productos en inventario
    $total_productos = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

    // Total de proveedores
    $total_proveedores = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado = 'activo'")->fetchColumn();

    // Ventas del mes actual
    $stmt_ventas = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE()) AND estado = 'pagada'");
    $ventas_mes = $stmt_ventas->fetchColumn() ?: 0;

    // Ventas totales
    $stmt_ventas_totales = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE estado = 'pagada'");
    $ventas_totales = $stmt_ventas_totales->fetchColumn() ?: 0;

    // Ventas de la semana
    $stmt_ventas_semana = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE WEEK(fecha_emision) = WEEK(CURDATE()) AND YEAR(fecha_emision) = YEAR(CURDATE()) AND estado = 'pagada'");
    $ventas_semana = $stmt_ventas_semana->fetchColumn() ?: 0;

    // Pedidos pendientes
    $stmt_pendientes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'");
    $pedidos_pendientes = $stmt_pendientes->fetchColumn() ?: 0;

    // Productos con stock bajo (menos de 10 unidades)
    $stmt_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10 AND stock > 0");
    $productos_stock_bajo = $stmt_stock->fetchColumn() ?: 0;

    // Productos agotados
    $stmt_agotados = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0");
    $productos_agotados = $stmt_agotados->fetchColumn() ?: 0;

    // Compras realizadas a proveedores este mes
    $stmt_compras = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM compras WHERE MONTH(fecha_orden) = MONTH(CURDATE()) AND YEAR(fecha_orden) = YEAR(CURDATE()) AND estado IN ('recibida_total', 'recibida_parcial')");
    $compras_mes = $stmt_compras->fetchColumn() ?: 0;

    // Utilidad estimada (ventas - compras)
    $utilidad_estimada = $ventas_mes - $compras_mes;

    // Ticket promedio
    $stmt_ticket = $pdo->query("SELECT COALESCE(AVG(total), 0) FROM facturas WHERE estado = 'pagada'");
    $ticket_promedio = $stmt_ticket->fetchColumn() ?: 0;

    // Crecimiento vs mes anterior
    $stmt_mes_anterior = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE MONTH(fecha_emision) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(fecha_emision) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND estado = 'pagada'");
    $ventas_mes_anterior = $stmt_mes_anterior->fetchColumn() ?: 0;
    
    $crecimiento = 0;
    if ($ventas_mes_anterior > 0) {
        $crecimiento = (($ventas_mes - $ventas_mes_anterior) / $ventas_mes_anterior) * 100;
    }

    // Ventas por mes (últimos 12 meses) - CORREGIDO
    $stmt_ventas_por_mes = $pdo->query("
        SELECT 
            DATE_FORMAT(fecha_emision, '%Y-%m') as mes,
            DATE_FORMAT(fecha_emision, '%b %Y') as mes_nombre,
            COALESCE(SUM(total), 0) as total
        FROM facturas 
        WHERE fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND estado = 'pagada'
        GROUP BY DATE_FORMAT(fecha_emision, '%Y-%m'), DATE_FORMAT(fecha_emision, '%b %Y')
        ORDER BY mes ASC
    ");
    $ventas_por_mes = $stmt_ventas_por_mes->fetchAll(PDO::FETCH_ASSOC);

    // Métodos de pago más usados - incluye todas las facturas (pagadas y pendientes)
    $stmt_metodos_pago = $pdo->query("
        SELECT 
            CASE 
                WHEN metodo_pago LIKE '%efectivo%' THEN 'Efectivo'
                WHEN metodo_pago LIKE '%transferencia%' THEN 'Transferencia'
                WHEN metodo_pago LIKE '%pago_movil%' OR metodo_pago LIKE '%pago móvil%' THEN 'Pago Móvil'
                WHEN metodo_pago LIKE '%mixto%' THEN 'Pago Mixto'
                WHEN metodo_pago LIKE '%tarjeta%' THEN 'Tarjeta'
                ELSE 'Otros'
            END as metodo_pago,
            COUNT(*) as cantidad,
            COALESCE(SUM(total), 0) as total
        FROM facturas 
        WHERE metodo_pago IS NOT NULL AND metodo_pago != ''
        GROUP BY 1
        ORDER BY total DESC
    ");
    $metodos_pago = $stmt_metodos_pago->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 productos más vendidos
    $stmt_top_productos = $pdo->query("
        SELECT 
            p.id,
            p.name as nombre,
            COALESCE(SUM(pd.cantidad), 0) as unidades_vendidas,
            COALESCE(SUM(pd.subtotal), 0) as ingresos
        FROM pedido_detalles pd
        JOIN products p ON pd.producto_id = p.id
        JOIN pedidos pe ON pd.pedido_id = pe.id
        WHERE pe.estado IN ('completado', 'facturado', 'entregado')
        GROUP BY p.id, p.name
        ORDER BY unidades_vendidas DESC
        LIMIT 5
    ");
    $top_productos = $stmt_top_productos->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 clientes
    $stmt_top_clientes = $pdo->query("
        SELECT 
            c.id,
            c.nombre,
            COUNT(p.id) as total_compras,
            COALESCE(SUM(p.total), 0) as monto_total
        FROM clientes c
        JOIN pedidos p ON c.id = p.cliente_id
        WHERE p.estado IN ('completado', 'facturado', 'entregado')
        GROUP BY c.id, c.nombre
        ORDER BY monto_total DESC
        LIMIT 5
    ");
    $top_clientes = $stmt_top_clientes->fetchAll(PDO::FETCH_ASSOC);

    // Top 5 vendedores (admin_users)
    $stmt_top_vendedores = $pdo->query("
        SELECT 
            a.id,
            a.nombre,
            COUNT(f.id) as total_ventas,
            COALESCE(SUM(f.total), 0) as monto_total
        FROM admin_users a
        LEFT JOIN facturas f ON a.id = f.usuario_id AND f.estado = 'pagada'
        GROUP BY a.id, a.nombre
        ORDER BY monto_total DESC
        LIMIT 5
    ");
    $top_vendedores = $stmt_top_vendedores->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay vendedores en admin_users, intentar con users
    if (empty($top_vendedores) || (isset($top_vendedores[0]['monto_total']) && $top_vendedores[0]['monto_total'] == 0)) {
        $stmt_top_vendedores = $pdo->query("
            SELECT 
                u.id,
                u.nombre,
                COUNT(f.id) as total_ventas,
                COALESCE(SUM(f.total), 0) as monto_total
            FROM users u
            LEFT JOIN facturas f ON u.id = f.usuario_id AND f.estado = 'pagada'
            WHERE u.rol IN ('admin', 'vendedor', 'superadmin')
            GROUP BY u.id, u.nombre
            ORDER BY monto_total DESC
            LIMIT 5
        ");
        $top_vendedores = $stmt_top_vendedores->fetchAll(PDO::FETCH_ASSOC);
    }

    // Clientes activos (con compras en los últimos 30 días)
    $stmt_clientes_activos = $pdo->query("
        SELECT COUNT(DISTINCT cliente_id) as total
        FROM pedidos 
        WHERE fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $clientes_activos = $stmt_clientes_activos->fetchColumn() ?: 0;

    // 3. Respuesta final
    echo json_encode([
        'success' => true,
        'ventas_totales' => (float)$ventas_totales,
        'ventas_mes' => (float)$ventas_mes,
        'ventas_semana' => (float)$ventas_semana,
        'crecimiento' => round($crecimiento, 2),
        'ticket_promedio' => (float)$ticket_promedio,
        'clientes_activos' => (int)$clientes_activos,
        'total_usuarios' => (int)$total_usuarios,
        'total_clientes' => (int)$total_clientes,
        'total_productos' => (int)$total_productos,
        'total_proveedores' => (int)$total_proveedores,
        'pedidos_pendientes' => (int)$pedidos_pendientes,
        'productos_stock_bajo' => (int)$productos_stock_bajo,
        'productos_agotados' => (int)$productos_agotados,
        'compras_mes' => (float)$compras_mes,
        'utilidad_estimada' => (float)$utilidad_estimada,
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
}