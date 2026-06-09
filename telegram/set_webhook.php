<?php
// Script para configurar el webhook del bot de Telegram
// Ejecutar UNA SOLA VEZ después de configurar el token en config.php
// 
// Uso desde navegador:
//   https://tudominio.com/proyecto/telegram/set_webhook.php
//   https://tudominio.com/proyecto/telegram/set_webhook.php?url=https://tudominio.com/proyecto/telegram/webhook.php
//
// Uso desde CLI (desarrollo local con ngrok):
//   php set_webhook.php https://tungrok.ngrok.io/proyecto/telegram/webhook.php

require_once __DIR__ . '/config.php';

if (strpos(TELEGRAM_BOT_TOKEN, 'AQUI_VA_TU') !== false) {
    die('❌ ERROR: Debes configurar TELEGRAM_BOT_TOKEN en config.php primero.' . "\n");
}

// Permitir URL manual por parámetro GET o argumento CLI
$webhookUrl = $_GET['url'] ?? ($argv[1] ?? null);

if (!$webhookUrl) {
    $webhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . '/proyecto/telegram/webhook.php';
}

$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook?url=" . urlencode($webhookUrl);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);

$data = json_decode($result, true);

echo "<h2>Configuración de Webhook - Bot de Telegram</h2>";
echo "<p><strong>URL del Webhook:</strong> " . htmlspecialchars($webhookUrl) . "</p>";
echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";

if ($data && $data['ok']) {
    echo "<p style='color:green; font-weight:bold;'>✅ Webhook configurado exitosamente!</p>";
    echo "<p>Tu bot está listo. Los clientes pueden escribir a: <strong>@" . TELEGRAM_BOT_USERNAME . "</strong></p>";
    echo "<p>Link directo: <a href='https://t.me/" . TELEGRAM_BOT_USERNAME . "' target='_blank'>t.me/" . TELEGRAM_BOT_USERNAME . "</a></p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . htmlspecialchars($data['description'] ?? 'Desconocido') . "</p>";
    
    if (strpos($data['description'] ?? '', 'Unauthorized') !== false) {
        echo "<p style='color:orange;'>El token no es válido. Verifica en @BotFather que el token sea correcto.</p>";
    }
    if (strpos($data['description'] ?? '', 'webhook') !== false) {
        echo "<p style='color:orange;'>La URL debe ser HTTPS y accesible desde internet.</p>";
        echo "<p>Para desarrollo local, usa <strong>ngrok</strong>:<br>";
        echo "<code>ngrok http 80</code><br>";
        echo "Luego ejecuta: <br>";
        echo "<code>php set_webhook.php https://tungrok.ngrok.io/proyecto/telegram/webhook.php</code></p>";
    }
}
