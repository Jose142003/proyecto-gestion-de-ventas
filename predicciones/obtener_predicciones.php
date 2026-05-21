<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

try {
    $pdo = conectarDB();
    $tipo = $_GET['tipo'] ?? 'general';

    $response = ['success' => true];

    if ($tipo === 'general' || $tipo === 'tendencias') {
        $stmt = $pdo->query("
            SELECT 
                pv.mes, pv.anio,
                ROUND(SUM(pv.ventas_reales), 2) as ventas_reales,
                ROUND(SUM(pv.ventas_predichas), 2) as ventas_predichas,
                ROUND(AVG(pv.precision_prediccion), 1) as precision_promedio,
                ROUND(AVG(pv.nivel_confianza), 1) as confianza_promedio,
                COUNT(DISTINCT pv.producto_id) as productos_predichos
            FROM predicciones_ventas pv
            WHERE pv.anio = YEAR(CURRENT_DATE)
            GROUP BY pv.mes, pv.anio
            ORDER BY pv.mes ASC
        ");
        $response['tendencias'] = $stmt->fetchAll();

        $stmtAct = $pdo->query("
            SELECT 
                ROUND(AVG(precision_prediccion), 1) as precision_general,
                ROUND(AVG(nivel_confianza), 1) as confianza_general,
                COUNT(*) as total_predicciones,
                SUM(CASE WHEN tendencia = 'subiendo' THEN 1 ELSE 0 END) as productos_subiendo,
                SUM(CASE WHEN tendencia = 'bajando' THEN 1 ELSE 0 END) as productos_bajando,
                SUM(CASE WHEN tendencia = 'estable' THEN 1 ELSE 0 END) as productos_estables
            FROM predicciones_ventas
            WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE)
        ");
        $response['resumen'] = $stmtAct->fetch();
    }

    if ($tipo === 'productos' || $tipo === 'general') {
        $stmt = $pdo->query("
            SELECT 
                p.id, p.name, p.sku, p.category, p.stock,
                COALESCE(pv.ventas_predichas, 0) as ventas_esperadas,
                COALESCE(pv.ventas_reales, 0) as ventas_actuales,
                COALESCE(pv.tendencia, 'estable') as tendencia,
                COALESCE(pv.nivel_confianza, 0) as confianza,
                COALESCE(pv.stock_sugerido, 0) as stock_sugerido,
                CASE 
                    WHEN p.stock <= 0 THEN 'agotado'
                    WHEN p.stock <= 5 THEN 'critico'
                    WHEN p.stock <= pv.stock_sugerido * 0.5 THEN 'bajo'
                    WHEN p.stock >= pv.stock_sugerido * 2 THEN 'exceso'
                    ELSE 'normal'
                END as estado_stock,
                CASE 
                    WHEN pv.ventas_predichas > 0 AND pv.ventas_reales > 0 
                    THEN ROUND(((pv.ventas_predichas - pv.ventas_reales) / pv.ventas_reales) * 100, 1)
                    ELSE 0
                END as variacion_porcentual,
                CASE 
                    WHEN p.stock > 0 AND pv.ventas_predichas > 0 
                    THEN ROUND(p.stock / (pv.ventas_predichas / 30), 0)
                    ELSE 0
                END as dias_para_agotar
            FROM products p
            LEFT JOIN predicciones_ventas pv ON p.id = pv.producto_id 
                AND pv.mes = MONTH(CURRENT_DATE) 
                AND pv.anio = YEAR(CURRENT_DATE)
            WHERE p.active = 1 AND p.deleted_at IS NULL
            ORDER BY pv.ventas_predichas DESC
            LIMIT 50
        ");
        $response['productos'] = $stmt->fetchAll();
    }

    if ($tipo === 'alertas' || $tipo === 'general') {
        $stmt = $pdo->query("
            SELECT 
                a.*, 
                p.name as producto_nombre, 
                p.sku as productoSku
            FROM alertas_stock a
            JOIN products p ON a.producto_id = p.id
            WHERE a.resuelta = FALSE
            ORDER BY a.fecha_alerta DESC
            LIMIT 20
        ");
        $response['alertas'] = $stmt->fetchAll();

        $stmtCount = $pdo->query("
            SELECT 
                COUNT(*) as total_alertas,
                SUM(CASE WHEN tipo = 'critico' AND resuelta = FALSE THEN 1 ELSE 0 END) as criticas,
                SUM(CASE WHEN tipo = 'bajo' AND resuelta = FALSE THEN 1 ELSE 0 END) as bajas
            FROM alertas_stock
            WHERE resuelta = FALSE
        ");
        $response['conteo_alertas'] = $stmtCount->fetch();
    }

    if ($tipo === 'recomendaciones' || $tipo === 'general') {
        $stmt = $pdo->query("
            SELECT 
                p.id, p.name, p.sku, p.category, p.stock,
                pv.stock_sugerido,
                ROUND(pv.ventas_predichas, 0) as demanda_esperada,
                CASE 
                    WHEN pv.tendencia = 'subiendo' THEN 'Aumentar pedido'
                    WHEN pv.tendencia = 'bajando' THEN 'Mantener stock'
                    ELSE 'Mantener pedido actual'
                END as accion_recomendada,
                CASE 
                    WHEN pv.tendencia = 'subiendo' AND p.stock < pv.stock_sugerido THEN 'alta'
                    WHEN pv.tendencia = 'bajando' AND p.stock > pv.stock_sugerido * 1.5 THEN 'media'
                    ELSE 'baja'
                END as prioridad
            FROM products p
            JOIN predicciones_ventas pv ON p.id = pv.producto_id 
                AND pv.mes = MONTH(CURRENT_DATE) 
                AND pv.anio = YEAR(CURRENT_DATE)
            WHERE p.active = 1 AND p.deleted_at IS NULL
                AND (pv.tendencia = 'subiendo' OR p.stock < pv.stock_sugerido)
            ORDER BY 
                CASE 
                    WHEN tendencia = 'subiendo' AND p.stock < pv.stock_sugerido THEN 0
                    WHEN p.stock < 10 THEN 1
                    ELSE 2
                END ASC,
                pv.ventas_predichas DESC
            LIMIT 10
        ");
        $response['recomendaciones'] = $stmt->fetchAll();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error al obtener predicciones',
        'tendencias' => [],
        'productos' => [],
        'alertas' => [],
        'conteo_alertas' => ['total_alertas' => 0, 'criticas' => 0, 'bajas' => 0],
        'recomendaciones' => []
    ], JSON_UNESCAPED_UNICODE);
}
