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
        'parse_mode' => 'Markdown',
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

    $data = json_decode($response, true);

    if ($httpCode >= 200 && $httpCode < 300 && ($data['ok'] ?? false)) {
        return ['success' => true, 'http_code' => $httpCode];
    }

    return [
        'success' => false,
        'http_code' => $httpCode,
        'error' => $data['description'] ?? 'Error desconocido'
    ];
}
