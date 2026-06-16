<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/respuestas_bot.php';

if (strpos(TELEGRAM_BOT_TOKEN, 'AQUI_VA_TU') !== false) {
    die('ERROR: Token no configurado en config.php');
}

$logFile = __DIR__ . '/bot.log';
function botLog($msg) {
    global $logFile;
    $line = "[" . date('Y-m-d H:i:s') . "] $msg\n";
    echo $line;
    file_put_contents($logFile, $line, FILE_APPEND);
}

botLog("Bot iniciado en modo continuo");
botLog("Escuchando mensajes en @piccavzlabot");

$mensajesDir = __DIR__ . '/mensajes';
if (!is_dir($mensajesDir)) mkdir($mensajesDir, 0777, true);

$offsetFile = __DIR__ . '/last_offset.txt';
$lastOffset = file_exists($offsetFile) ? intval(file_get_contents($offsetFile)) : 0;
$errorCount = 0;

while (true) {
    try {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getUpdates?offset=" . ($lastOffset + 1) . "&timeout=30";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 35,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($error) {
            botLog("Error de conexion: $error");
            $errorCount++;
            if ($errorCount > 10) {
                botLog("Demasiados errores. Esperando 30 segundos...");
                sleep(30);
                $errorCount = 0;
            }
            sleep(2);
            continue;
        }

        if ($httpCode !== 200) {
            botLog("HTTP $httpCode");
            sleep(5);
            continue;
        }

        $data = json_decode($response, true);

        if (!is_array($data) || !$data['ok']) {
            $desc = is_array($data) ? ($data['description'] ?? 'Desconocido') : 'Respuesta invalida';
            botLog("Error API: $desc");
            if (strpos($desc, 'Conflict') !== false) {
                botLog("Webhook activo. Eliminalo primero.");
                break;
            }
            sleep(5);
            continue;
        }

        $errorCount = 0;

        if (empty($data['result'])) {
            sleep(2);
            continue;
        }

        foreach ($data['result'] as $update) {
            $updateId = $update['update_id'] ?? 0;
            $lastOffset = $updateId;

            // Handle callback queries (inline button presses)
            if (isset($update['callback_query'])) {
                $cq = $update['callback_query'];
                $chatId = $cq['message']['chat']['id'];
                $messageId = $cq['message']['message_id'];
                $callbackId = $cq['id'];
                $callbackData = $cq['data'];
                $firstName = $cq['from']['first_name'] ?? 'Cliente';

                botLog("Callback: $firstName -> $callbackData");
                botManejarCallbackQuery($callbackData, $chatId, $messageId, $callbackId);

                file_put_contents(
                    $mensajesDir . '/chat_' . $chatId . '.txt',
                    date('Y-m-d H:i:s') . " | $firstName (callback): $callbackData\n",
                    FILE_APPEND
                );
                continue;
            }

            // Handle regular text messages
            if (!isset($update['message'])) continue;

            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            $firstName = $message['from']['first_name'] ?? 'Cliente';
            $username = $message['from']['username'] ?? '';

            file_put_contents(
                $mensajesDir . '/chat_' . $chatId . '.txt',
                date('Y-m-d H:i:s') . " | $firstName ($username): $text\n",
                FILE_APPEND
            );

            botLog("$firstName: $text");

            // Show typing indicator while processing
            telegramSendAction($chatId, 'typing');

            $response = botResponder($text, $chatId, $firstName);

            if ($response && $response !== '') {
                sendTelegramMessage($chatId, $response);
                botLog("Respuesta enviada a $firstName");
            }
        }

        file_put_contents($offsetFile, $lastOffset);

    } catch (Throwable $e) {
        botLog("Error: " . $e->getMessage());
        sleep(5);
    }
}


