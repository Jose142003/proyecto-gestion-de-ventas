<?php
function telegramObtenerConfig($pdo): array {
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'telegram_%'");
    $config = [];
    while ($row = $stmt->fetch()) {
        $config[str_replace('telegram_', '', $row['clave'])] = $row['valor'];
    }
    return [
        'token' => $config['token'] ?? '',
        'chat_id' => $config['chat_id'] ?? ''
    ];
}

function telegramEnviar(string $token, string $chatId, string $mensaje): array {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $payload = [
        'chat_id' => $chatId,
        'text' => $mensaje,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => $error];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => 'Respuesta invalida de Telegram'];
    }

    if ($httpCode >= 200 && $httpCode < 300 && ($data['ok'] ?? false)) {
        return ['success' => true, 'http_code' => $httpCode];
    }

    return [
        'success' => false,
        'http_code' => $httpCode,
        'error' => $data['description'] ?? 'Error desconocido'
    ];
}

function telegramEnviarDocumento(string $token, string $chatId, string $filePath, string $caption = ''): array {
    if (!file_exists($filePath)) {
        return ['success' => false, 'error' => 'Archivo no encontrado: ' . $filePath];
    }

    $url = "https://api.telegram.org/bot{$token}/sendDocument";
    $file = new CURLFile($filePath);

    $post = [
        'chat_id' => $chatId,
        'document' => $file,
    ];
    if ($caption) {
        $post['caption'] = $caption;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => $error];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['success' => false, 'http_code' => $httpCode, 'error' => 'Respuesta invalida de Telegram'];
    }

    if ($httpCode >= 200 && $httpCode < 300 && ($data['ok'] ?? false)) {
        return ['success' => true, 'http_code' => $httpCode];
    }

    return [
        'success' => false,
        'http_code' => $httpCode,
        'error' => $data['description'] ?? 'Error enviando documento'
    ];
}

function sendTelegramMessage($chatId, $text) {
    $send = function($parseMode) use ($chatId, $text) {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'disable_web_page_preview' => false
        ];
        if ($parseMode) $data['parse_mode'] = $parseMode;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['result' => $result, 'http' => $httpCode];
    };

    $resp = $send('Markdown');
    $resultData = json_decode($resp['result'], true);

    if ($resp['http'] !== 200 || !($resultData['ok'] ?? false)) {
        $resp2 = $send(null);
        $resultData2 = json_decode($resp2['result'], true);
        if ($resp2['http'] !== 200 || !($resultData2['ok'] ?? false)) {
            error_log("Error Telegram API: " . ($resultData2['description'] ?? 'Unknown'));
        }
        return $resp2['result'];
    }
    return $resp['result'];
}
