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
requerirAdmin();

try {
    $pdo = conectarDB();

    $input = json_decode(file_get_contents('php://input'), true);
    $tipo = $input['tipo'] ?? $_POST['tipo'] ?? '';
    $destinatario = $input['destinatario'] ?? $_POST['destinatario'] ?? '';
    $mensaje = $input['mensaje'] ?? $_POST['mensaje'] ?? '';

    if (empty($mensaje)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mensaje requerido']);
        exit;
    }

    $config = obtenerConfigWhatsApp($pdo);
    $apiUrl = $config['api_url'];
    $apiToken = $config['api_token'];

    if (empty($apiUrl) || empty($apiToken)) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'WhatsApp no configurado. Configure la API URL y Token en Configuración.',
            'demo_mode' => true
        ]);
        exit;
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $destinatario,
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

    $stmt = $pdo->prepare("
        INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address)
        VALUES (?, ?, 'enviar_whatsapp', 'whatsapp', ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_SESSION['user_nombre'] ?? '',
        "WhatsApp enviado a $destinatario (tipo: $tipo, HTTP: $httpCode)",
        $_SERVER['REMOTE_ADDR'] ?? ''
    ]);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode([
            'success' => true,
            'message' => 'Mensaje enviado correctamente',
            'http_code' => $httpCode
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Error al enviar (HTTP $httpCode)",
            'http_code' => $httpCode,
            'response' => $response
        ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar notificación']);
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
        'numero' => $config['numero'] ?? ''
    ];
}
