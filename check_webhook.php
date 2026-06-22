<?php
require_once __DIR__ . '/config/database.php';
$token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
if (empty($token)) {
    echo "TELEGRAM_BOT_TOKEN no configurado\n";
    exit;
}
$url = "https://api.telegram.org/bot$token/getWebhookInfo";
$data = json_decode(@file_get_contents($url), true);
echo "Webhook URL: " . ($data['result']['url'] ?? 'none') . "\n";
echo "Pending: " . ($data['result']['pending_update_count'] ?? 0) . "\n";
echo "Last error: " . ($data['result']['last_error_message'] ?? 'none') . "\n";
echo "Last error date: " . ($data['result']['last_error_date'] ?? 'none') . "\n";
