<?php
function authenticateRequest(): array {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) {
        $token = $_GET['api_token'] ?? '';
    }

    if (empty($token)) {
        return ['success' => false, 'message' => 'Token de API requerido'];
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, usuario_id, permisos, expires_at FROM api_tokens WHERE token = ? AND activo = TRUE");
        $stmt->execute([hash('sha256', $token)]);
        $apiToken = $stmt->fetch();

        if (!$apiToken) {
            return ['success' => false, 'message' => 'Token inválido'];
        }

        if ($apiToken['expires_at'] && strtotime($apiToken['expires_at']) < time()) {
            return ['success' => false, 'message' => 'Token expirado'];
        }

        $pdo->prepare("UPDATE api_tokens SET ultimo_uso = NOW() WHERE id = ?")->execute([$apiToken['id']]);

        return ['success' => true, 'user_id' => $apiToken['usuario_id'], 'permisos' => json_decode($apiToken['permisos'], true)];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error de autenticación'];
    }
}
