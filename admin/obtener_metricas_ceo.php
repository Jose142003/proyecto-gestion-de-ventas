<?php
// obtener_metricas_ceo.php - VERSIÓN CORREGIDA
// Soporta múltiples métodos de pago y divisas (USD/BS)
// CORREGIDO: Normalización de métodos de pago para gráficos

header('Content-Type: application/json');
session_start();

// 1. Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
    
    $user_id = $_SESSION['user_id'];

    // 2. Validar que el usuario sea un administrador activo
    $stmt_check = $pdo->prepare("SELECT rol FROM admin_users WHERE id = ? AND activo = 1");
    $stmt_check->execute([$user_id]);
    $admin = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado: No tienes permisos de administrador']);
        exit;
    }

    // ============================================================
    // FUNCIÓN AUXILIAR: Obtener tasa de cambio USD/BS desde configuración
    // ============================================================
    function getDollarRate($pdo) {
        $stmt = $pdo->prepare("SELECT valor FROM configuracion_sistema WHERE clave = 'tasa_dolar' LIMIT 1");
        $stmt->execute();
        $tasa = $stmt->fetchColumn();
        return $tasa ? floatval($tasa) : 40.00; // Valor por defecto si no está configurado
    }

    $tasa_dolar = getDollarRate($pdo);

    // ============================================================
    // FUNCIÓN AUXILIAR: Sumar ventas con soporte para múltiples monedas
    // ============================================================
    function sumarVentas($pdo, $whereCondition, $params = [], $tasa_dolar = 40.00) {
        $sql = "SELECT 
                    SUM(
                        CASE 
                            WHEN f.currency = 'USD' THEN f.total * :tasa
                            WHEN f.currency = 'BS' OR f.currency IS NULL THEN f.total
                            ELSE f.total
                        END
                    ) as total
                FROM facturas f 
                WHERE $whereCondition AND f.estado = 'pagada'";
        
        $stmt = $pdo->prepare($sql);
        $params[':tasa'] = $tasa_dolar;
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        $result = $stmt->fetchColumn();
        return $result ? floatval($result) : 0;
    }

    // ============================================================
    // FUNCIÓN AUXILIAR: Normalizar métodos de pago
    // ============================================================
    function normalizarMetodoPago($metodo) {
        if (empty($metodo) || $metodo === null) {
            return 'No especificado';
        }
        
        $metodo_lower = strtolower(trim($metodo));
        
        // Mapeo de posibles variaciones
        if (strpos($metodo_lower, 'efectivo') !== false || $metodo_lower === 'cash') {
            return 'Efectivo';
        } elseif (strpos($metodo_lower, 'transferencia') !== false || strpos($metodo_lower, 'transfer') !== false) {
            return 'Transferencia';
        } elseif (strpos($metodo_lower, 'pago_movil') !== false || strpos($metodo_lower, 'pago movil') !== false || strpos($metodo_lower, 'pago móvil') !== false) {
            return 'Pago Móvil';
        } elseif (strpos($metodo_lower, 'tarjeta') !== false || strpos($metodo_lower, 'credito') !== false || strpos($metodo_lower, 'débito') !== false || strpos($metodo_lower, 'debito') !== false) {
            return 'Tarjeta';
        } elseif (strpos($metodo_lower, 'mixto') !== false) {
            return 'Mixto';
        } elseif (strpos($metodo_lower, 'cheque') !== false) {
            return 'Cheque';
        } elseif (strpos($metodo_lower, 'paypal') !== false) {
            return 'PayPal';
        } elseif (strpos($metodo_lower, 'zelle') !== false) {
            return 'Zelle';
        } else {
            // Mantener el original pero capitalizado
            return ucfirst(strtolower($metodo));
        }
    }

    // ============================================================
    // 1. MÉTRICAS BÁSICAS
    // ============================================================
    
    // Total de usuarios (admin_users + users)
    $total_usuarios = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() + 
                      $pdo->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
    
    // Total de clientes (tabla clientes)
    $total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    
    // Total de productos
    $total_productos = $pdo->query("SELECT COUNT(*) FROM products WHERE active = 1")->fetchColumn();
    
    // Total de proveedores
    $total_proveedores = $pdo->query("SELECT COUNT(*) FROM proveedores WHERE estado = 'activo'")->fetchColumn();

    // ============================================================
    // 2. VENTAS DEL MES (con soporte para todas las monedas)
    // ============================================================
    $ventas_mes = sumarVentas($pdo, 
        "MONTH(f.fecha_emision) = MONTH(CURDATE()) AND YEAR(f.fecha_emision) = YEAR(CURDATE())",
        [],
        $tasa_dolar
    );
    
    // Ventas de la semana (últimos 7 días)
    $ventas_semana = sumarVentas($pdo,
        "f.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
        [],
        $tasa_dolar
    );
    
    // Ventas de hoy
    $ventas_hoy = sumarVentas($pdo,
        "DATE(f.fecha_emision) = CURDATE()",
        [],
        $tasa_dolar
    );
    
    // Ventas totales
    $ventas_totales = sumarVentas($pdo, "1=1", [], $tasa_dolar);
    
    // Ventas del mes anterior (para calcular crecimiento)
    $ventas_mes_anterior = sumarVentas($pdo,
        "MONTH(f.fecha_emision) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) 
         AND YEAR(f.fecha_emision) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))",
        [],
        $tasa_dolar
    );
    
    // Crecimiento porcentual
    $crecimiento = 0;
    if ($ventas_mes_anterior > 0) {
        $crecimiento = round((($ventas_mes - $ventas_mes_anterior) / $ventas_mes_anterior) * 100, 2);
    }

    // ============================================================
    // 3. ESTADÍSTICAS DE PEDIDOS
    // ============================================================
    $pedidos_pendientes = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'pendiente'")->fetchColumn() ?: 0;
    $pedidos_completados = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'completado'")->fetchColumn() ?: 0;
    $pedidos_totales = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn() ?: 0;
    
    // Ticket promedio (de facturas)
    $stmt_ticket = $pdo->query("SELECT AVG(total) FROM facturas WHERE estado = 'pagada'");
    $ticket_promedio = $stmt_ticket->fetchColumn() ?: 0;

    // ============================================================
    // 4. STOCK
    // ============================================================
    $productos_stock_bajo = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 10 AND stock > 0 AND active = 1")->fetchColumn() ?: 0;
    $productos_agotados = $pdo->query("SELECT COUNT(*) FROM products WHERE stock = 0 AND active = 1")->fetchColumn() ?: 0;
    $valor_inventario = $pdo->query("SELECT SUM(precio * stock) FROM products WHERE stock > 0 AND active = 1")->fetchColumn() ?: 0;

    // ============================================================
    // 5. COMPRAS DEL MES
    // ============================================================
    $compras_mes = $pdo->query("SELECT SUM(total) FROM compras 
                               WHERE MONTH(fecha_orden) = MONTH(CURDATE()) 
                               AND YEAR(fecha_orden) = YEAR(CURDATE()) 
                               AND estado IN ('recibida_total', 'recibida_parcial')")->fetchColumn() ?: 0;
    
    // Utilidad estimada (ventas - compras)
    $utilidad_estimada = $ventas_mes - $compras_mes;

    // ============================================================
    // 6. VENTAS POR MÉTODO DE PAGO (CORREGIDO - CON NORMALIZACIÓN)
    // ============================================================
    $stmt_metodos = $pdo->prepare("
        SELECT 
            f.metodo_pago,
            SUM(
                CASE 
                    WHEN f.currency = 'USD' THEN f.total * :tasa
                    ELSE f.total
                END
            ) as total
        FROM facturas f 
        WHERE f.estado = 'pagada'
        GROUP BY f.metodo_pago
        ORDER BY total DESC
    ");
    $stmt_metodos->execute([':tasa' => $tasa_dolar]);
    $metodos_raw = $stmt_metodos->fetchAll(PDO::FETCH_ASSOC);

    // Normalizar los nombres de métodos de pago para agrupar correctamente
    $metodos_normalizados = [];
    foreach ($metodos_raw as $mp) {
        $metodo_normalizado = normalizarMetodoPago($mp['metodo_pago']);
        $total = floatval($mp['total']);
        
        if (!isset($metodos_normalizados[$metodo_normalizado])) {
            $metodos_normalizados[$metodo_normalizado] = 0;
        }
        $metodos_normalizados[$metodo_normalizado] += $total;
    }

    // Convertir a array para la respuesta
    $metodos_pago = [];
    foreach ($metodos_normalizados as $nombre => $total) {
        $metodos_pago[] = [
            'metodo_pago' => $nombre,
            'total' => $total
        ];
    }

    // Ordenar por total descendente
    usort($metodos_pago, function($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    // ============================================================
    // 7. VENTAS POR MES (últimos 12 meses)
    // ============================================================
    $stmt_ventas_mensuales = $pdo->prepare("
        SELECT 
            DATE_FORMAT(f.fecha_emision, '%Y-%m') as mes,
            DATE_FORMAT(f.fecha_emision, '%b %Y') as mes_nombre,
            SUM(
                CASE 
                    WHEN f.currency = 'USD' THEN f.total * :tasa
                    ELSE f.total
                END
            ) as total
        FROM facturas f 
        WHERE f.estado = 'pagada'
            AND f.fecha_emision >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(f.fecha_emision, '%Y-%m')
        ORDER BY mes ASC
    ");
    $stmt_ventas_mensuales->execute([':tasa' => $tasa_dolar]);
    $ventas_por_mes = $stmt_ventas_mensuales->fetchAll(PDO::FETCH_ASSOC);
    
    // Si no hay datos, crear meses ficticios con 0
    if (empty($ventas_por_mes)) {
        $ventas_por_mes = [];
        for ($i = 11; $i >= 0; $i--) {
            $fecha = new DateTime();
            $fecha->modify("-$i months");
            $ventas_por_mes[] = [
                'mes' => $fecha->format('Y-m'),
                'mes_nombre' => $fecha->format('M Y'),
                'total' => 0
            ];
        }
    }

    // ============================================================
    // 8. TOP PRODUCTOS MÁS VENDIDOS
    // ============================================================
    $stmt_top_productos = $pdo->prepare("
        SELECT 
            p.id,
            p.name as nombre,
            p.category as categoria,
            COALESCE(SUM(dp.cantidad), 0) as unidades_vendidas,
            COALESCE(COUNT(DISTINCT dp.pedido_id), 0) as veces_vendido,
            COALESCE(SUM(dp.subtotal), 0) as ingresos,
            COALESCE(p.stock, 0) as stock_actual
        FROM products p
        LEFT JOIN detalle_pedidos dp ON p.id = dp.producto_id
        LEFT JOIN pedidos ped ON dp.pedido_id = ped.id
        WHERE p.active = 1
        GROUP BY p.id
        ORDER BY unidades_vendidas DESC
        LIMIT 5
    ");
    $stmt_top_productos->execute();
    $top_productos = $stmt_top_productos->fetchAll(PDO::FETCH_ASSOC);

    // ============================================================
    // 9. TOP CLIENTES
    // ============================================================
    $stmt_top_clientes = $pdo->prepare("
        SELECT 
            c.id,
            c.nombre,
            c.email,
            c.telefono,
            COUNT(p.id) as total_compras,
            COALESCE(SUM(p.total), 0) as monto_total,
            MAX(p.fecha_pedido) as ultima_compra
        FROM clientes c
        LEFT JOIN pedidos p ON c.id = p.cliente_id
        WHERE p.estado IN ('completado', 'facturado', 'entregado')
        GROUP BY c.id
        ORDER BY monto_total DESC
        LIMIT 5
    ");
    $stmt_top_clientes->execute();
    $top_clientes = $stmt_top_clientes->fetchAll(PDO::FETCH_ASSOC);

    // ============================================================
    // 10. TOP VENDEDORES
    // ============================================================
    $stmt_top_vendedores = $pdo->prepare("
        SELECT 
            u.id,
            u.nombre,
            u.correo as email,
            COUNT(p.id) as total_ventas,
            COALESCE(SUM(p.total), 0) as monto_total,
            COALESCE(SUM(dp.cantidad), 0) as productos_vendidos
        FROM users u
        INNER JOIN pedidos p ON u.id = p.usuario_id
        INNER JOIN detalle_pedidos dp ON p.id = dp.pedido_id
        WHERE p.estado IN ('completado', 'facturado', 'entregado')
        GROUP BY u.id
        ORDER BY monto_total DESC
        LIMIT 5
    ");
    $stmt_top_vendedores->execute();
    $top_vendedores = $stmt_top_vendedores->fetchAll(PDO::FETCH_ASSOC);

    // ============================================================
    // 11. MÉTRICAS ADICIONALES PARA CEO
    // ============================================================
    
    // Clientes activos (clientes con al menos una compra en los últimos 30 días)
    $stmt_clientes_activos = $pdo->prepare("
        SELECT COUNT(DISTINCT c.id) as total
        FROM clientes c
        INNER JOIN pedidos p ON c.id = p.cliente_id
        WHERE p.fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND p.estado IN ('completado', 'facturado', 'entregado')
    ");
    $stmt_clientes_activos->execute();
    $clientes_activos = $stmt_clientes_activos->fetchColumn() ?: 0;

    // Porcentaje de cumplimiento de pedidos
    $total_pedidos = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn() ?: 1;
    $pedidos_completados_total = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado IN ('completado', 'facturado', 'entregado')")->fetchColumn() ?: 0;
    $tasa_cumplimiento = round(($pedidos_completados_total / $total_pedidos) * 100, 2);

    // Productos más rentables (mayor margen)
    $stmt_productos_rentables = $pdo->prepare("
        SELECT 
            p.id,
            p.name as nombre,
            p.price as precio,
            COALESCE(SUM(dp.cantidad), 0) as unidades_vendidas,
            COALESCE(SUM(dp.subtotal), 0) as ingresos_totales
        FROM products p
        LEFT JOIN detalle_pedidos dp ON p.id = dp.producto_id
        LEFT JOIN pedidos ped ON dp.pedido_id = ped.id
        WHERE ped.estado IN ('completado', 'facturado', 'entregado')
        GROUP BY p.id
        HAVING unidades_vendidas > 0
        ORDER BY ingresos_totales DESC
        LIMIT 5
    ");
    $stmt_productos_rentables->execute();
    $productos_rentables = $stmt_productos_rentables->fetchAll(PDO::FETCH_ASSOC);

    // ============================================================
    // RESPUESTA - Estructura que espera panel_admin.php
    // ============================================================
    echo json_encode([
        'success' => true,
        // Métricas básicas
        'total_usuarios' => (int)$total_usuarios,
        'total_clientes' => (int)$total_clientes,
        'total_productos' => (int)$total_productos,
        'total_proveedores' => (int)$total_proveedores,
        
        // Ventas
        'ventas_mes' => (float)$ventas_mes,
        'ventas_semana' => (float)$ventas_semana,
        'ventas_hoy' => (float)$ventas_hoy,
        'ventas_totales' => (float)$ventas_totales,
        
        // Crecimiento
        'crecimiento' => (float)$crecimiento,
        
        // Pedidos
        'pedidos_pendientes' => (int)$pedidos_pendientes,
        'pedidos_completados' => (int)$pedidos_completados,
        'pedidos_totales' => (int)$pedidos_totales,
        'ticket_promedio' => (float)$ticket_promedio,
        
        // Stock
        'productos_stock_bajo' => (int)$productos_stock_bajo,
        'productos_agotados' => (int)$productos_agotados,
        'valor_inventario' => (float)$valor_inventario,
        
        // Compras y utilidad
        'compras_mes' => (float)$compras_mes,
        'utilidad_estimada' => (float)$utilidad_estimada,
        
        // Datos para gráficos
        'ventas_por_mes' => $ventas_por_mes,
        'metodos_pago' => $metodos_pago,  // <-- AHORA CON MÚLTIPLES MÉTODOS NORMALIZADOS
        'top_productos' => $top_productos,
        'top_clientes' => $top_clientes,
        'top_vendedores' => $top_vendedores,
        
        // Métricas adicionales para CEO
        'clientes_activos' => (int)$clientes_activos,
        'tasa_cumplimiento' => (float)$tasa_cumplimiento,
        'productos_rentables' => $productos_rentables,
        
        // Tasa de cambio actual
        'tasa_dolar' => (float)$tasa_dolar
    ]);

} catch (PDOException $e) {
    error_log("Error en obtener_metricas_ceo: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general en obtener_metricas_ceo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>