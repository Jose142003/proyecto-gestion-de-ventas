<?php
$url = "https://api.telegram.org/bot8836060788:AAGLJ-wy5DfysdD0kWnzVTnaJqp85yHJOxY/getWebhookInfo";
$data = json_decode(file_get_contents($url), true);
echo "Webhook URL: " . ($data['result']['url'] ?? 'none') . "\n";
echo "Pending: " . ($data['result']['pending_update_count'] ?? 0) . "\n";
echo "Last error: " . ($data['result']['last_error_message'] ?? 'none') . "\n";
echo "Last error date: " . ($data['result']['last_error_date'] ?? 'none') . "\n";
