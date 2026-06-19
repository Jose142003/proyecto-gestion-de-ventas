<?php
require_once __DIR__ . '/telegram/config.php';
$token = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';

if (empty($token)) {
    echo "TELEGRAM_BOT_TOKEN no configurado\n";
    exit;
}

$url = "https://api.telegram.org/bot$token/getUpdates?offset=0&timeout=5";
$data = json_decode(file_get_contents($url), true);
echo "OK: " . ($data['ok'] ? 'true' : 'false') . "\n";
echo "Count: " . count($data['result'] ?? []) . "\n";
foreach ($data['result'] ?? [] as $u) {
    $id = $u['update_id'];
    $msg = $u['message']['text'] ?? '(no text)';
    $from = $u['message']['from']['first_name'] ?? '?';
    echo "  update_id=$id from=$from msg=$msg\n";
}
