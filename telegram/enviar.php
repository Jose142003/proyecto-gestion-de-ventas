<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/helpers.php';
requerirAdmin();

try {
    $pdo = conectarDB();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) $input = [];
    $mensaje = $input['mensaje'] ?? $_POST['mensaje'] ?? '';

    if (empty($mensaje)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Mensaje requerido']);
        exit;
    }

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

    $resultado = telegramEnviar($token, $chatId, $mensaje);

    if ($resultado['success']) {
        auditoriaRegistrar('enviar_telegram', 'telegram', "Mensaje enviado por Telegram a chat $chatId");
        echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar: ' . ($resultado['error'] ?? 'desconocido'),
            'http_code' => $resultado['http_code'] ?? 0
        ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al enviar mensaje']);
}
