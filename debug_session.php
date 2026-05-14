<?php
// debug_session.php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookie_params' => session_get_cookie_params(),
    'session_status' => session_status()
], JSON_PRETTY_PRINT);
?>