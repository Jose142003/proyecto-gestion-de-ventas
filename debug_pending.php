<?php
$token = '8836060788:AAGLJ-wy5DfysdD0kWnzVTnaJqp85yHJOxY';

// Check getUpdates with offset=0 to see all pending
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
