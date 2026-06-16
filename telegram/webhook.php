<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/respuestas_bot.php';

if (TELEGRAM_BOT_TOKEN === 'AQUI_VA_TU_TOKEN_DE_BOTFATHER') {
    http_response_code(500);
    echo json_encode(['error' => 'Bot no configurado.']);
    exit;
}

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update) {
    http_response_code(200);
    exit;
}

$logDir = __DIR__ . '/mensajes';
if (!is_dir($logDir)) mkdir($logDir, 0777, true);

// Handle callback queries (inline button presses)
if (isset($update['callback_query'])) {
    $cq = $update['callback_query'];
    $chatId = $cq['message']['chat']['id'];
    $messageId = $cq['message']['message_id'];
    $callbackId = $cq['id'];
    $callbackData = $cq['data'];
    $firstName = $cq['from']['first_name'] ?? 'Cliente';

    $logEntry = date('Y-m-d H:i:s') . " | $firstName (callback): $callbackData\n";
    file_put_contents($logDir . '/chat_' . $chatId . '.txt', $logEntry, FILE_APPEND);

    botManejarCallbackQuery($callbackData, $chatId, $messageId, $callbackId);
    http_response_code(200);
    exit;
}

// Handle regular text messages
if (!isset($update['message'])) {
    http_response_code(200);
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$firstName = $message['from']['first_name'] ?? 'Cliente';
$username = $message['from']['username'] ?? '';

$logFile = $logDir . '/chat_' . $chatId . '.txt';
$logEntry = date('Y-m-d H:i:s') . " | $firstName ($username): $text\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Show typing indicator
telegramSendAction($chatId, 'typing');

$response = botResponder($text, $chatId, $firstName);

if ($response && $response !== '') {
    sendTelegramMessage($chatId, $response);
}

http_response_code(200);
