<?php
session_start();

header('Content-Type: application/json');

// Solo disponible para administradores autenticados
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
    !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookie_params' => session_get_cookie_params(),
    'session_status' => session_status()
], JSON_PRETTY_PRINT);
