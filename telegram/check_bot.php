<?php
require_once __DIR__ . '/../config/database.php';
$token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
$ch = curl_init('https://api.telegram.org/bot' . $token . '/getMe');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$r = curl_exec($ch);
$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $http\n";
echo "Response: $r\n";
