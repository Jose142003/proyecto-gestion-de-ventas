<?php
// funciones_pedido.php

function obtener_pedido_cliente($pdo, $pedido_id, $user_id) {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$pedido_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtener_detalles_pedido($pdo, $pedido_id) {
    $stmt = $pdo->prepare("
        SELECT pd.*, pr.name as producto_nombre, pr.image_url
        FROM pedido_detalles pd
        JOIN products pr ON pd.producto_id = pr.id
        WHERE pd.pedido_id = ?
    ");
    $stmt->execute([$pedido_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtener_detalles_pedido_cliente($pdo, $pedido_id, $user_id) {
    $pedido = obtener_pedido_cliente($pdo, $pedido_id, $user_id);
    if (!$pedido) {
        return [];
    }
    return obtener_detalles_pedido($pdo, $pedido_id);
}
?>