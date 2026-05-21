<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error interno',
            'tendencias' => [], 'productos' => [], 'alertas' => [],
            'conteo_alertas' => ['total_alertas' => 0, 'criticas' => 0, 'bajas' => 0],
            'recomendaciones' => []], JSON_UNESCAPED_UNICODE);
    }
});

set_error_handler(function () { return false; });

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

function tablaExiste($pdo, $tabla): bool {
    try { return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla))->fetch(); } catch (Throwable $e) { return false; }
}

function querySeguro($pdo, string $sql, array $params = []) {
    try { $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchAll(); } catch (Throwable $e) { return []; }
}

function scalarSeguro($pdo, string $sql, array $params = []) {
    try { $stmt = $pdo->prepare($sql); $stmt->execute($params); return $stmt->fetchColumn(); } catch (Throwable $e) { return null; }
}

try {
    $pdo = conectarDB();
    $tipo = $_GET['tipo'] ?? 'general';
    $response = ['success' => true];
    $tablaPredicciones = tablaExiste($pdo, 'predicciones_ventas');
    $tablaAlertas = tablaExiste($pdo, 'alertas_stock');

    if (($tipo === 'general' || $tipo === 'tendencias') && $tablaPredicciones) {
        $response['tendencias'] = querySeguro($pdo, "
            SELECT mes, anio,
                ROUND(SUM(ventas_reales), 2) as ventas_reales,
                ROUND(SUM(ventas_predichas), 2) as ventas_predichas,
                ROUND(AVG(precision_prediccion), 1) as precision_promedio,
                ROUND(AVG(nivel_confianza), 1) as confianza_promedio,
                COUNT(DISTINCT producto_id) as productos_predichos
            FROM predicciones_ventas
            WHERE anio = YEAR(CURRENT_DATE)
            GROUP BY mes, anio ORDER BY mes ASC
        ");
        $response['resumen'] = [
            'precision_general' => (float)(scalarSeguro($pdo, "SELECT ROUND(AVG(precision_prediccion), 1) FROM predicciones_ventas WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE)") ?: 0),
            'confianza_general' => (float)(scalarSeguro($pdo, "SELECT ROUND(AVG(nivel_confianza), 1) FROM predicciones_ventas WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE)") ?: 0),
            'total_predicciones' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM predicciones_ventas WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE)") ?: 0),
            'productos_subiendo' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM predicciones_ventas WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE) AND tendencia = 'subiendo'") ?: 0),
            'productos_bajando' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM predicciones_ventas WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE) AND tendencia = 'bajando'") ?: 0),
            'productos_estables' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM predicciones_ventas WHERE mes = MONTH(CURRENT_DATE) AND anio = YEAR(CURRENT_DATE) AND tendencia = 'estable'") ?: 0)
        ];
    } elseif ($tipo === 'general') {
        $response['tendencias'] = [];
        $response['resumen'] = ['precision_general' => 0, 'confianza_general' => 0, 'total_predicciones' => 0, 'productos_subiendo' => 0, 'productos_bajando' => 0, 'productos_estables' => 0];
    }

    if (($tipo === 'productos' || $tipo === 'general') && $tablaPredicciones) {
        $response['productos'] = querySeguro($pdo, "
            SELECT p.id, p.name, p.sku, p.category, p.stock,
                COALESCE(pv.ventas_predichas, 0) as ventas_esperadas,
                COALESCE(pv.ventas_reales, 0) as ventas_actuales,
                COALESCE(pv.tendencia, 'estable') as tendencia,
                COALESCE(pv.nivel_confianza, 0) as confianza,
                COALESCE(pv.stock_sugerido, 0) as stock_sugerido,
                CASE WHEN p.stock <= 0 THEN 'agotado' WHEN p.stock <= 5 THEN 'critico' WHEN p.stock <= 10 THEN 'bajo' ELSE 'normal' END as estado_stock,
                CASE WHEN p.stock > 0 AND pv.ventas_predichas > 0 THEN ROUND(p.stock / (pv.ventas_predichas / 30), 0) ELSE 0 END as dias_para_agotar
            FROM products p
            LEFT JOIN predicciones_ventas pv ON p.id = pv.producto_id AND pv.mes = MONTH(CURRENT_DATE) AND pv.anio = YEAR(CURRENT_DATE)
            WHERE p.active = 1 AND p.deleted_at IS NULL
            ORDER BY pv.ventas_predichas DESC LIMIT 50
        ");
    } elseif ($tipo === 'productos' || $tipo === 'general') {
        $response['productos'] = [];
    }

    if (($tipo === 'alertas' || $tipo === 'general') && $tablaAlertas) {
        $response['alertas'] = querySeguro($pdo, "
            SELECT a.*, p.name as producto_nombre, p.sku as productoSku
            FROM alertas_stock a JOIN products p ON a.producto_id = p.id
            WHERE a.resuelta = FALSE ORDER BY a.fecha_alerta DESC LIMIT 20
        ");
        $response['conteo_alertas'] = [
            'total_alertas' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM alertas_stock WHERE resuelta = FALSE") ?: 0),
            'criticas' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM alertas_stock WHERE tipo = 'critico' AND resuelta = FALSE") ?: 0),
            'bajas' => (int)(scalarSeguro($pdo, "SELECT COUNT(*) FROM alertas_stock WHERE tipo = 'bajo' AND resuelta = FALSE") ?: 0)
        ];
    } elseif ($tipo === 'general') {
        $response['alertas'] = [];
        $response['conteo_alertas'] = ['total_alertas' => 0, 'criticas' => 0, 'bajas' => 0];
    }

    if (($tipo === 'recomendaciones' || $tipo === 'general') && $tablaPredicciones) {
        $response['recomendaciones'] = querySeguro($pdo, "
            SELECT p.id, p.name, p.sku, p.category, p.stock,
                pv.stock_sugerido, ROUND(pv.ventas_predichas, 0) as demanda_esperada,
                CASE WHEN pv.tendencia = 'subiendo' THEN 'Aumentar pedido' WHEN pv.tendencia = 'bajando' THEN 'Mantener stock' ELSE 'Mantener pedido actual' END as accion_recomendada,
                CASE WHEN pv.tendencia = 'subiendo' AND p.stock < pv.stock_sugerido THEN 'alta' WHEN pv.tendencia = 'bajando' AND p.stock > pv.stock_sugerido * 1.5 THEN 'media' ELSE 'baja' END as prioridad
            FROM products p
            JOIN predicciones_ventas pv ON p.id = pv.producto_id AND pv.mes = MONTH(CURRENT_DATE) AND pv.anio = YEAR(CURRENT_DATE)
            WHERE p.active = 1 AND p.deleted_at IS NULL AND (pv.tendencia = 'subiendo' OR p.stock < pv.stock_sugerido)
            ORDER BY CASE WHEN tendencia = 'subiendo' AND p.stock < pv.stock_sugerido THEN 0 WHEN p.stock < 10 THEN 1 ELSE 2 END ASC, pv.ventas_predichas DESC LIMIT 10
        ");
    } elseif ($tipo === 'general') {
        $response['recomendaciones'] = [];
    }

    if (!$tablaPredicciones && !$tablaAlertas) {
        $response['migracion_pendiente'] = true;
        $response['mensaje_migracion'] = 'Ejecute sql/migracion_nuevas_funcionalidades.sql para activar IA Predictiva.';
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 'error' => 'Error de base de datos',
        'tendencias' => [], 'productos' => [], 'alertas' => [],
        'conteo_alertas' => ['total_alertas' => 0, 'criticas' => 0, 'bajas' => 0],
        'recomendaciones' => []
    ], JSON_UNESCAPED_UNICODE);
}
