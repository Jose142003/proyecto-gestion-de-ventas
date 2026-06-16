<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/respuestas_bot.php';

if (strpos(TELEGRAM_BOT_TOKEN, 'AQUI_VA_TU') !== false) {
    die('ERROR: Token no configurado en config.php');
}

$mensajesDir = __DIR__ . '/mensajes';
if (!is_dir($mensajesDir)) mkdir($mensajesDir, 0777, true);

$offsetFile = __DIR__ . '/last_offset.txt';
$lastOffset = file_exists($offsetFile) ? intval(file_get_contents($offsetFile)) : 0;

$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getUpdates?offset=" . ($lastOffset + 1) . "&timeout=10";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$error = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($error) {
    echo "Error de conexion: $error\n";
    exit;
}

$data = json_decode($response, true);

if (!is_array($data) || !$data['ok']) {
    $desc = is_array($data) ? ($data['description'] ?? 'Desconocido') : 'Respuesta invalida';
    echo "Error de API: $desc\n";
    exit;
}

if (empty($data['result'])) {
    echo "No hay mensajes nuevos.\n";
    exit;
}

$processed = 0;
foreach ($data['result'] as $update) {
    $updateId = $update['update_id'] ?? 0;
    $lastOffset = $updateId;

    // Handle callback queries
    if (isset($update['callback_query'])) {
        $cq = $update['callback_query'];
        $chatId = $cq['message']['chat']['id'];
        $messageId = $cq['message']['message_id'];
        $callbackId = $cq['id'];
        $callbackData = $cq['data'];
        $firstName = $cq['from']['first_name'] ?? 'Cliente';

        echo "Callback: $firstName -> $callbackData\n";
        botManejarCallbackQuery($callbackData, $chatId, $messageId, $callbackId);

        file_put_contents(
            $mensajesDir . '/chat_' . $chatId . '.txt',
            date('Y-m-d H:i:s') . " | $firstName (callback): $callbackData\n",
            FILE_APPEND
        );
        $processed++;
        continue;
    }

    if (!isset($update['message'])) continue;

    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $firstName = $message['from']['first_name'] ?? 'Cliente';
    $username = $message['from']['username'] ?? '';

    echo "Procesando mensaje de $firstName: $text\n";

    file_put_contents(
        $mensajesDir . '/chat_' . $chatId . '.txt',
        date('Y-m-d H:i:s') . " | $firstName ($username): $text\n",
        FILE_APPEND
    );

    telegramSendAction($chatId, 'typing');

    $response = botResponder($text, $chatId, $firstName);

    if ($response && $response !== '') {
        sendTelegramMessage($chatId, $response);
        echo "Respuesta enviada a $firstName\n";
    }

    $processed++;
}

file_put_contents($offsetFile, $lastOffset);

echo "Procesados $processed mensaje(s).\n";
