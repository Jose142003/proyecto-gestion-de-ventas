<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';

try {
    $pdo = conectarDB();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['success' => false, 'message' => 'JSON inválido'], 400);
    }

    $producto_id = (int)($input['producto_id'] ?? 0);
    $usuario_id = (int)($input['usuario_id'] ?? 0);
    $pedido_id = isset($input['pedido_id']) ? (int)$input['pedido_id'] : null;
    $puntuacion = (int)($input['puntuacion'] ?? 0);
    $titulo = trim($input['titulo'] ?? '');
    $comentario = trim($input['comentario'] ?? '');

    if ($producto_id <= 0 || $usuario_id <= 0) {
        jsonResponse(['success' => false, 'message' => 'producto_id y usuario_id son requeridos'], 400);
    }

    if ($puntuacion < 1 || $puntuacion > 5) {
        jsonResponse(['success' => false, 'message' => 'puntuacion debe estar entre 1 y 5'], 400);
    }

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$producto_id]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'El producto no existe'], 404);
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$usuario_id]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'El usuario no existe'], 404);
    }

    $stmt = $pdo->prepare("SELECT id FROM resenas WHERE producto_id = ? AND usuario_id = ?");
    $stmt->execute([$producto_id, $usuario_id]);
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Ya has reseñado este producto'], 409);
    }

    $es_compra_verificada = 0;
    if ($pedido_id) {
        $stmt = $pdo->prepare("
            SELECT pd.id FROM pedido_detalles pd
            JOIN pedidos p ON pd.pedido_id = p.id
            WHERE p.id = ? AND p.usuario_id = ? AND pd.producto_id = ?
            LIMIT 1
        ");
        $stmt->execute([$pedido_id, $usuario_id, $producto_id]);
        if ($stmt->fetch()) {
            $es_compra_verificada = 1;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO resenas (producto_id, usuario_id, pedido_id, puntuacion, titulo, comentario, es_compra_verificada, moderado, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
    ");
    $stmt->execute([$producto_id, $usuario_id, $pedido_id, $puntuacion, $titulo, $comentario, $es_compra_verificada]);

    $resena_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT * FROM resenas WHERE id = ?");
    $stmt->execute([$resena_id]);
    $resena = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'message' => 'Reseña agregada correctamente',
        'resena' => $resena
    ], 201);

} catch (PDOException $e) {
    error_log("Error en agregar_resena: " . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Error interno del servidor'], 500);
}
