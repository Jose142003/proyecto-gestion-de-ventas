<?php
// Script para configurar el webhook del bot de Telegram
// Ejecutar UNA SOLA VEZ después de configurar el token en config.php
// Accede a: https://picindustrial.com/proyecto/telegram/set_webhook.php

require_once __DIR__ . '/config.php';

if (TELEGRAM_BOT_TOKEN === 'AQUI_VA_TU_TOKEN_DE_BOTFATHER') {
    die('❌ ERROR: Debes configurar TELEGRAM_BOT_TOKEN en config.php primero.' . "\n");
}

$webhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . '/proyecto/telegram/webhook.php';

$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/setWebhook?url=" . urlencode($webhookUrl);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
curl_close($ch);

$data = json_decode($result, true);

echo "<h2>Configuración de Webhook - Bot de Telegram</h2>";
echo "<p><strong>URL del Webhook:</strong> " . htmlspecialchars($webhookUrl) . "</p>";
echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";

if ($data && $data['ok']) {
    echo "<p style='color:green; font-weight:bold;'>✅ Webhook configurado exitosamente!</p>";
    echo "<p>Tu bot está listo. Los clientes pueden escribir a: <strong>@" . TELEGRAM_BOT_USERNAME . "</strong></p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ Error: " . htmlspecialchars($data['description'] ?? 'Desconocido') . "</p>";
}
