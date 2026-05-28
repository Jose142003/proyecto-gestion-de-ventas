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

try {
    $pdo = conectarDB();

    $accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

    $config = obtenerConfigWhatsApp($pdo);
    $apiUrl = $config['api_url'];
    $apiToken = $config['api_token'];
    $numeroEmpresa = $config['numero'];
    $notifStockActivada = $config['notificaciones_stock'] ?? '0';

    if (empty($apiUrl) || empty($apiToken) || empty($numeroEmpresa)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'WhatsApp no configurado. Configure la API URL, Token y Número.',
            'demo_mode' => true
        ]);
        exit;
    }

    $productosCriticos = $pdo->query("
        SELECT id, name, stock, category, price,
            (SELECT COALESCE(SUM(pd.cantidad), 0) FROM pedido_detalles pd WHERE pd.producto_id = p.id) as veces_vendido
        FROM products p
        WHERE active = 1 AND deleted_at IS NULL AND stock > 0 AND stock <= 5
        ORDER BY stock ASC
    ")->fetchAll();

    $productosAgotados = $pdo->query("
        SELECT id, name, stock, category, price
        FROM products p
        WHERE active = 1 AND deleted_at IS NULL AND stock <= 0
        ORDER BY name ASC
    ")->fetchAll();

    $productosBajos = [];
    if ($accion !== 'critico_only') {
        $productosBajos = $pdo->query("
            SELECT id, name, stock, category, price
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
            $mensaje .= "• {$p['name']}: {$p['stock']} uds | Cat: {$p['category']}\n";
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
    $mensaje .= "⚠️ _Revise el inventario en el panel de administración._";

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $numeroEmpresa,
        'type' => 'text',
        'text' => ['body' => $mensaje]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiToken
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    auditoriaRegistrar('notificar_stock_bajo', 'whatsapp',
        "Notificación de stock bajo enviada por WhatsApp. Críticos: " . count($productosCriticos) .
        ", Agotados: " . count($productosAgotados) . ", Bajos: " . count($productosBajos) .
        " (HTTP: $httpCode)"
    );

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode([
            'success' => true,
            'message' => 'Notificación de stock enviada correctamente por WhatsApp',
            'enviado' => true,
            'http_code' => $httpCode,
            'stats' => [
                'criticos' => count($productosCriticos),
                'agotados' => count($productosAgotados),
                'bajos' => count($productosBajos),
                'total' => $totalProblemas
            ],
            'destinatario' => $numeroEmpresa
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Error al enviar notificación de stock (HTTP $httpCode)",
            'http_code' => $httpCode,
            'response' => $response,
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

function obtenerConfigWhatsApp($pdo): array {
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'whatsapp_%'");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[str_replace('whatsapp_', '', $row['clave'])] = $row['valor'];
    }
    return [
        'api_url' => $config['api_url'] ?? '',
        'api_token' => $config['api_token'] ?? '',
        'numero' => $config['numero'] ?? '',
        'notificaciones_pedido' => $config['notificaciones_pedido'] ?? '0',
        'notificaciones_stock' => $config['notificaciones_stock'] ?? '0'
    ];
}
