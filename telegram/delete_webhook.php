<?php
require_once __DIR__ . '/../config/database.php';
$token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
$ch = curl_init('https://api.telegram.org/bot' . $token . '/deleteWebhook');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 10]);
$r = curl_exec($ch);
curl_close($ch);
echo $r . "\n";
