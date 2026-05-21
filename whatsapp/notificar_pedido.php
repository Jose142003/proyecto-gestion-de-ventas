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

set_error_handler(function () { return false; });

require_once __DIR__ . '/../conexion/conexion.php';

$pedido_id = (int)($_GET['pedido_id'] ?? $_POST['pedido_id'] ?? 0);

if (!$pedido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de pedido requerido']);
    exit;
}

try {
    $pdo = conectarDB();

    $stmt = $pdo->prepare("
        SELECT pe.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono
        FROM pedidos pe
        JOIN clientes c ON pe.cliente_id = c.id
        WHERE pe.id = ?
    ");
    $stmt->execute([$pedido_id]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Pedido no encontrado']);
        exit;
    }

    $stmtDetalles = $pdo->prepare("
        SELECT pd.*, p.name 
        FROM pedido_detalles pd
        JOIN products p ON pd.producto_id = p.id
        WHERE pd.pedido_id = ?
    ");
    $stmtDetalles->execute([$pedido_id]);
    $detalles = $stmtDetalles->fetchAll();

    $productosList = [];
    foreach ($detalles as $d) {
        $productosList[] = "• {$d['name']} x{$d['cantidad']} = Bs. {$d['subtotal']}";
    }

    $configStmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'whatsapp_%'");
    $config = [];
    while ($row = $configStmt->fetch()) {
        $config[str_replace('whatsapp_', '', $row['clave'])] = $row['valor'];
    }

    $notificarPedido = $config['notificaciones_pedido'] ?? '0';
    $apiUrl = $config['api_url'] ?? '';
    $apiToken = $config['api_token'] ?? '';
    $numeroEmpresa = $config['numero'] ?? '';

    $mensaje = "🛒 *NUEVO PEDIDO - PIC*\n\n";
    $mensaje .= "📋 *Pedido:* {$pedido['numero_pedido']}\n";
    $mensaje .= "👤 *Cliente:* {$pedido['cliente_nombre']}\n";
    $mensaje .= "💰 *Total:* Bs. {$pedido['total']}\n";
    $mensaje .= "💳 *Método Pago:* {$pedido['metodo_pago']}\n";
    $mensaje .= "📅 *Fecha:* {$pedido['fecha_pedido']}\n\n";
    $mensaje .= "*Productos:*\n" . implode("\n", $productosList) . "\n\n";
    $mensaje .= "✅ *Estado:* Pendiente de procesar";

    $resultados = [];

    if ($notificarPedido === '1' && !empty($apiUrl) && !empty($apiToken)) {
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
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resultados['whatsapp'] = ['success' => $httpCode >= 200 && $httpCode < 300, 'http_code' => $httpCode];
    } else {
        $resultados['whatsapp'] = ['success' => false, 'message' => 'WhatsApp no configurado', 'demo' => true];
    }

    $stmtLog = $pdo->prepare("
        INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address)
        VALUES (NULL, 'sistema', 'notificar_pedido_whatsapp', 'whatsapp', ?, ?)
    ");
    $stmtLog->execute([
        "Notificación WhatsApp para pedido #{$pedido['numero_pedido']}",
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Notificación procesada',
        'resultados' => $resultados,
        'pedido' => $pedido['numero_pedido']
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar notificación']);
}
