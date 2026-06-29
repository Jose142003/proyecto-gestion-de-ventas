<?php
require_once __DIR__ . '/helpers.php';

function telegramNotificarVarianteCreada($pdo, int $producto_id, string $sku, string $nombreVariante): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $mensaje = "<b>🏷️ Nueva variante creada</b>\n\n"
             . "Producto ID: <code>{$producto_id}</code>\n"
             . "SKU: <code>{$sku}</code>\n"
             . "Nombre: {$nombreVariante}";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarVarianteModificada($pdo, int $variante_id, string $sku): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $mensaje = "<b>🏷️ Variante modificada</b>\n\n"
             . "ID: <code>{$variante_id}</code>\n"
             . "SKU: <code>{$sku}</code>";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarVarianteEliminada($pdo, int $variante_id, string $sku): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $mensaje = "<b>🏷️ Variante desactivada</b>\n\n"
             . "ID: <code>{$variante_id}</code>\n"
             . "SKU: <code>{$sku}</code>";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarAtributosGuardados($pdo, int $producto_id, int $totalAtributos): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $mensaje = "<b>🎨 Atributos guardados</b>\n\n"
             . "Producto ID: <code>{$producto_id}</code>\n"
             . "Total atributos: {$totalAtributos}";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarTokenGenerado($pdo, string $nombre, $permisos): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $permisosStr = is_array($permisos) ? implode(', ', $permisos) : $permisos;
    $mensaje = "<b>🔑 Nuevo token generado</b>\n\n"
             . "Nombre: <code>{$nombre}</code>\n"
             . "Permisos: <code>{$permisosStr}</code>";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarTokenRevocado($pdo, string $nombre): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $mensaje = "<b>🔑 Token revocado</b>\n\n"
             . "Nombre: <code>{$nombre}</code>";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarTokenActivado($pdo, string $nombre): bool {
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;
    $mensaje = "<b>🔑 Token activado</b>\n\n"
             . "Nombre: <code>{$nombre}</code>";
    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}
