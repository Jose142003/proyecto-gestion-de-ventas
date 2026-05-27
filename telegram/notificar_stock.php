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
require_once __DIR__ . '/helpers.php';
requerirAdmin();

try {
    $pdo = conectarDB();

    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

    $config = telegramObtenerConfig($pdo);
    $token = $config['token'];
    $chatId = $config['chat_id'];

    if (empty($token) || empty($chatId)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Telegram no configurado. Configure Token y Chat ID.',
            'demo_mode' => true
        ]);
        exit;
    }

    $productosCriticos = $pdo->query("
        SELECT id, name, stock, category
        FROM products p
        WHERE active = 1 AND deleted_at IS NULL AND stock > 0 AND stock <= 5
        ORDER BY stock ASC
    ")->fetchAll();

    $productosAgotados = $pdo->query("
        SELECT id, name, stock, category
        FROM products p
        WHERE active = 1 AND deleted_at IS NULL AND stock <= 0
        ORDER BY name ASC
    ")->fetchAll();

    $productosBajos = [];
    if ($accion !== 'critico_only') {
        $productosBajos = $pdo->query("
            SELECT id, name, stock, category
            FROM products p
            WHERE active = 1 AND deleted_at IS NULL AND stock > 5 AND stock <= 10
            ORDER BY stock ASC
        ")->fetchAll();
    }

    $totalProblemas = count($productosCriticos) + count($productosAgotados) + count($productosBajos);

    if ($totalProblemas === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'No hay productos con stock bajo. Todo está en orden.',
            'enviado' => false,
            'stats' => ['criticos' => 0, 'agotados' => 0, 'bajos' => 0]
        ]);
        exit;
    }

    $mensaje = "📦 *ALERTA DE STOCK - PIC*\n\n";

    if (count($productosAgotados) > 0) {
        $mensaje .= "🚫 *AGOTADOS:*\n";
        foreach ($productosAgotados as $p) {
            $mensaje .= "• {$p['name']} (ID: {$p['id']})\n";
        }
        $mensaje .= "\n";
    }

    if (count($productosCriticos) > 0) {
        $mensaje .= "🔴 *STOCK CRÍTICO (≤5):*\n";
        foreach ($productosCriticos as $p) {
            $mensaje .= "• {$p['name']}: {$p['stock']} uds | {$p['category']}\n";
        }
        $mensaje .= "\n";
    }

    if (count($productosBajos) > 0) {
        $mensaje .= "🟡 *STOCK BAJO (≤10):*\n";
        foreach ($productosBajos as $p) {
            $mensaje .= "• {$p['name']}: {$p['stock']} uds\n";
        }
    }

    $mensaje .= "\n📊 *Resumen:*\n";
    $mensaje .= "• Agotados: " . count($productosAgotados) . "\n";
    $mensaje .= "• Críticos: " . count($productosCriticos) . "\n";
    $mensaje .= "• Bajos: " . count($productosBajos) . "\n";
    $mensaje .= "• Total: $totalProblemas productos\n\n";
    $mensaje .= "⚠️ Revise el inventario en el panel de administración.";

    $resultado = telegramEnviar($token, $chatId, $mensaje);

    auditoriaRegistrar('notificar_stock_telegram', 'telegram',
        "Notificación de stock enviada por Telegram. Críticos: " . count($productosCriticos) .
        ", Agotados: " . count($productosAgotados) . ", Bajos: " . count($productosBajos)
    );

    if ($resultado['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificación de stock enviada por Telegram',
            'enviado' => true,
            'stats' => [
                'criticos' => count($productosCriticos),
                'agotados' => count($productosAgotados),
                'bajos' => count($productosBajos),
                'total' => $totalProblemas
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar: ' . ($resultado['error'] ?? 'desconocido'),
            'stats' => [
                'criticos' => count($productosCriticos),
                'agotados' => count($productosAgotados),
                'bajos' => count($productosBajos),
                'total' => $totalProblemas
            ]
        ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar notificación de stock']);
}
