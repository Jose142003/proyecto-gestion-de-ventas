<?php
session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../conexion/conexion.php';
requerirAdmin();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $pdo = conectarDB();

    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT at.id, at.usuario_id, au.nombre AS usuario_nombre, au.usuario AS usuario_login, at.nombre, at.token, at.permisos, at.ultimo_uso, at.expires_at, at.activo, at.created_at
                               FROM api_tokens at
                               JOIN admin_users au ON at.usuario_id = au.id
                               ORDER BY at.created_at DESC");
        $stmt->execute();
        $tokens = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $tokens,
            'total' => count($tokens)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        $accion = $input['accion'] ?? 'crear';

        if ($accion === 'crear') {
            $nombre = trim($input['nombre'] ?? '');
            $usuarioId = $input['usuario_id'] ?? null;
            $permisos = $input['permisos'] ?? ['*'];
            $expiresAt = $input['expires_at'] ?? null;

            if (!$nombre) {
                errorResponse('El nombre del token es requerido');
            }
            if (!$usuarioId) {
                errorResponse('El usuario_id es requerido');
            }

            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE id = ? AND activo = TRUE");
            $stmt->execute([$usuarioId]);
            if (!$stmt->fetch()) {
                errorResponse('Usuario administrador no encontrado o inactivo');
            }

            $tokenRaw = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $tokenRaw);

            $stmt = $pdo->prepare("INSERT INTO api_tokens (usuario_id, nombre, token, permisos, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$usuarioId, $nombre, $tokenHash, json_encode($permisos), $expiresAt]);

            require_once __DIR__ . '/../telegram/notificar_otros.php';
            telegramNotificarTokenGenerado($pdo, $nombre, $permisos);

            echo json_encode([
                'success' => true,
                'message' => 'Token creado exitosamente',
                'data' => [
                    'id' => (int)$pdo->lastInsertId(),
                    'nombre' => $nombre,
                    'token' => $tokenRaw,
                    'permisos' => $permisos,
                    'expires_at' => $expiresAt,
                ],
                'advertencia' => 'Guarde el token mostrado; no podrá verse nuevamente.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($accion === 'revocar') {
            $tokenId = $input['token_id'] ?? null;

            if (!$tokenId) {
                errorResponse('token_id es requerido');
            }

            $stmtNombre = $pdo->prepare("SELECT nombre FROM api_tokens WHERE id = ?");
            $stmtNombre->execute([$tokenId]);
            $tokenData = $stmtNombre->fetch();
            $nombreRevocar = $tokenData ? $tokenData['nombre'] : 'Desconocido';

            $stmt = $pdo->prepare("UPDATE api_tokens SET activo = FALSE WHERE id = ?");
            $stmt->execute([$tokenId]);

            if ($stmt->rowCount() === 0) {
                errorResponse('Token no encontrado', 404);
            }

            require_once __DIR__ . '/../telegram/notificar_otros.php';
            telegramNotificarTokenRevocado($pdo, $nombreRevocar);

            echo json_encode([
                'success' => true,
                'message' => 'Token revocado exitosamente'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($accion === 'activar') {
            $tokenId = $input['token_id'] ?? null;

            if (!$tokenId) {
                errorResponse('token_id es requerido');
            }

            $stmtNombre = $pdo->prepare("SELECT nombre FROM api_tokens WHERE id = ?");
            $stmtNombre->execute([$tokenId]);
            $tokenData = $stmtNombre->fetch();
            $nombreActivar = $tokenData ? $tokenData['nombre'] : 'Desconocido';

            $stmt = $pdo->prepare("UPDATE api_tokens SET activo = TRUE WHERE id = ?");
            $stmt->execute([$tokenId]);

            if ($stmt->rowCount() === 0) {
                errorResponse('Token no encontrado', 404);
            }

            require_once __DIR__ . '/../telegram/notificar_otros.php';
            telegramNotificarTokenActivado($pdo, $nombreActivar);

            echo json_encode([
                'success' => true,
                'message' => 'Token activado exitosamente'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        errorResponse("Acción no válida: $accion");
    }

    errorResponse('Método no permitido', 405);
} catch (Exception $e) {
    errorResponse('Error del servidor: ' . $e->getMessage(), 500);
}
