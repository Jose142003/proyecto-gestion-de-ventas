<?php
require_once __DIR__ . '/../config/database.php';
$token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
$ch = curl_init('https://api.telegram.org/bot' . $token . '/getUpdates?offset=-1');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$r = curl_exec($ch);
$data = json_decode($r, true);
$lastId = 0;
if ($data && $data['ok'] && !empty($data['result'])) {
    $lastId = $data['result'][count($data['result'])-1]['update_id'];
}
curl_close($ch);
file_put_contents(__DIR__ . '/last_offset.txt', $lastId);
echo "Offset fijado a: $lastId\n";
