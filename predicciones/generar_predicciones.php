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
        echo json_encode(['success' => false, 'message' => 'Error interno']);
    }
});

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

function tablaExiste($pdo, $tabla): bool {
    try { return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla))->fetch(); } catch (Throwable $e) { return false; }
}

try {
    $pdo = conectarDB();

    if (!tablaExiste($pdo, 'predicciones_ventas')) {
        echo json_encode(['success' => false, 'message' => 'Migración pendiente. Ejecute sql/migracion_nuevas_funcionalidades.sql', 'migracion_pendiente' => true]);
        exit;
    }

    $pdo->beginTransaction();

    $mesActual = (int)date('m');
    $anioActual = (int)date('Y');

    $pdo->prepare("DELETE FROM predicciones_ventas WHERE mes = ? AND anio = ?")->execute([$mesActual, $anioActual]);

    $productos = $pdo->query("SELECT id, name, stock FROM products WHERE active = 1 AND deleted_at IS NULL")->fetchAll();

    $stmtInsert = $pdo->prepare("INSERT INTO predicciones_ventas (producto_id, categoria, mes, anio, ventas_reales, ventas_predichas, precision_prediccion, tendencia, nivel_confianza, stock_sugerido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmtInsertAlerta = $pdo->prepare("INSERT INTO alertas_stock (producto_id, tipo, nivel_actual, nivel_sugerido, mensaje) VALUES (?, ?, ?, ?, ?)");

    $prediccionesGeneradas = 0;

    foreach ($productos as $producto) {
        $historial = $pdo->prepare("SELECT DATE_FORMAT(pe.fecha_pedido, '%Y-%m') as mes_anio, SUM(pd.cantidad) as total_vendido FROM pedido_detalles pd JOIN pedidos pe ON pd.pedido_id = pe.id WHERE pd.producto_id = ? AND pe.estado NOT IN ('cancelado') AND pe.fecha_pedido >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(pe.fecha_pedido, '%Y-%m') ORDER BY mes_anio ASC");
        $historial->execute([$producto['id']]);
        $historialRows = $historial->fetchAll();

        $ventasReales = 0; $promedioMensual = 5; $tendencia = 'estable'; $confianza = 50; $prediccion = 5; $precision = 50;

        if (!empty($historialRows)) {
            $valores = array_column($historialRows, 'total_vendido');
            $ventasReales = (int)end($valores);
            $promedioMensual = max(array_sum($valores) / count($valores), 3);
            $ultimos3 = array_slice($valores, -3);
            $promedioReciente = count($ultimos3) > 0 ? array_sum($ultimos3) / count($ultimos3) : $promedioMensual;
            $primeros3 = array_slice($valores, 0, 3);
            $promedioAntiguo = count($primeros3) > 0 ? array_sum($primeros3) / count($primeros3) : $promedioMensual;
            $variacion = $promedioAntiguo > 0 ? (($promedioReciente - $promedioAntiguo) / $promedioAntiguo) * 100 : 0;

            if ($variacion > 15) { $tendencia = 'subiendo'; $confianza = 70; $prediccion = $promedioReciente * 1.2; }
            elseif ($variacion < -15) { $tendencia = 'bajando'; $confianza = 65; $prediccion = max($promedioReciente * 0.8, 3); }
            else { $tendencia = 'estable'; $confianza = 80; $prediccion = $promedioReciente * 1.05; }

            $precision = min(95, 60 + count($valores) * 3);
            $confianza = min(95, $confianza + count($valores) * 2);
        }

        $stockSugerido = max(ceil($prediccion * 1.5), 10);

        $stmtInsert->execute([$producto['id'], 'General', $mesActual, $anioActual, $ventasReales, round($prediccion, 2), min(95, round($precision)), $tendencia, min(95, round($confianza)), $stockSugerido]);
        $prediccionesGeneradas++;

        if ($producto['stock'] <= 10) {
            try {
                $existe = $pdo->prepare("SELECT id FROM alertas_stock WHERE producto_id = ? AND resuelta = FALSE ORDER BY fecha_alerta DESC LIMIT 1");
                $existe->execute([$producto['id']]);
                if (!$existe->fetch()) {
                    $tipoAlerta = $producto['stock'] <= 0 ? 'critico' : ($producto['stock'] <= 5 ? 'critico' : 'bajo');
                    $stmtInsertAlerta->execute([$producto['id'], $tipoAlerta, $producto['stock'], $stockSugerido, "Stock $tipoAlerta: '{$producto['name']}' tiene {$producto['stock']} unidades (sugerido: $stockSugerido)"]);
                }
            } catch (Throwable $e) {
                error_log("Error generando alerta stock: " . $e->getMessage());
            }
        }
    }

    $pdo->commit();

    $alertasNuevas = $pdo->query("
        SELECT COUNT(*) FROM alertas_stock WHERE resuelta = FALSE AND leida = FALSE
    ")->fetchColumn();

    if ($alertasNuevas > 0) {
        $wspCfg = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'whatsapp_%'")->fetchAll();
        $wsp = [];
        foreach ($wspCfg as $row) {
            $wsp[str_replace('whatsapp_', '', $row['clave'])] = $row['valor'];
        }
        if (!empty($wsp['api_url']) && !empty($wsp['api_token']) && !empty($wsp['numero']) && ($wsp['notificaciones_stock'] ?? '0') === '1') {
            $criticos = $pdo->query("SELECT COUNT(*) FROM alertas_stock WHERE tipo='critico' AND resuelta=FALSE")->fetchColumn();
            $bajos = $pdo->query("SELECT COUNT(*) FROM alertas_stock WHERE tipo='bajo' AND resuelta=FALSE")->fetchColumn();
            $wspMsg = "📊 *REPORTE IA - Alertas de Stock*\n\n";
            $wspMsg .= "🔴 Críticos: $criticos\n";
            $wspMsg .= "🟡 Bajos: $bajos\n";
            $wspMsg .= "📦 Total: " . ($criticos + $bajos) . " productos\n\n";
            $wspMsg .= "⚠️ _Revise el panel IA Predictiva para más detalles._";

            $wspPayload = [
                'messaging_product' => 'whatsapp',
                'to' => $wsp['numero'],
                'type' => 'text',
                'text' => ['body' => $wspMsg]
            ];
            $ch = curl_init($wsp['api_url']);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($wspPayload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $wsp['api_token']],
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5
            ]);
            curl_exec($ch);

            $pdo->exec("UPDATE alertas_stock SET leida = TRUE WHERE leida = FALSE");
        }

        $tgCfg = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'telegram_%'")->fetchAll();
        $tg = [];
        foreach ($tgCfg as $row) {
            $tg[str_replace('telegram_', '', $row['clave'])] = $row['valor'];
        }
        if (!empty($tg['token']) && !empty($tg['chat_id'])) {
            $criticos = $pdo->query("SELECT COUNT(*) FROM alertas_stock WHERE tipo='critico' AND resuelta=FALSE")->fetchColumn();
            $bajos = $pdo->query("SELECT COUNT(*) FROM alertas_stock WHERE tipo='bajo' AND resuelta=FALSE")->fetchColumn();
            $tgMsg = "📊 *REPORTE IA - Alertas de Stock*\n\n";
            $tgMsg .= "🔴 Críticos: $criticos\n";
            $tgMsg .= "🟡 Bajos: $bajos\n";
            $tgMsg .= "📦 Total: " . ($criticos + $bajos) . " productos\n\n";
            $tgMsg .= "⚠️ Revise el panel IA Predictiva para más detalles.";

            $tgUrl = "https://api.telegram.org/bot{$tg['token']}/sendMessage";
            $tgPayload = [
                'chat_id' => $tg['chat_id'],
                'text' => $tgMsg,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => true
            ];
            $ch = curl_init($tgUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($tgPayload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5
            ]);
            curl_exec($ch);
        }
    }

    echo json_encode(['success' => true, 'message' => "Predicciones generadas para $prediccionesGeneradas productos", 'total' => $prediccionesGeneradas], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al generar predicciones']);
}
