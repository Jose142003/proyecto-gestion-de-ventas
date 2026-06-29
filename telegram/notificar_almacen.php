<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/helpers.php';

function telegramNotificarTransferencia($pdo, int $producto_id, int $origen_id, int $destino_id, int $cantidad, string $productoNombre): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token']) || empty($config['chat_id'])) return false;

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };

        $stmt = $pdo->prepare("SELECT id, nombre FROM almacenes WHERE id IN (?, ?)");
        $stmt->execute([$origen_id, $destino_id]);
        $almacenes = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $origenNombre = $almacenes[$origen_id] ?? 'Desconocido';
        $destinoNombre = $almacenes[$destino_id] ?? 'Desconocido';

        $mensaje = "🔄 <b>Transferencia de Stock</b>\n\n"
                 . "📦 <b>Producto:</b> {$e($productoNombre)}\n"
                 . "🔢 <b>Cantidad:</b> " . number_format($cantidad, 0, ',', '.') . " uds\n"
                 . "🏭 <b>Origen:</b> {$e($origenNombre)}\n"
                 . "🏭 <b>Destino:</b> {$e($destinoNombre)}\n\n"
                 . "📅 <b>Fecha:</b> " . date('d/m/Y H:i') . "\n\n"
                 . "✅ Transferencia completada exitosamente.";

        $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarTransferencia: " . $e->getMessage());
        return false;
    }
}

function telegramNotificarNuevoAlmacen($pdo, int $almacen_id, string $codigo, string $nombre): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token']) || empty($config['chat_id'])) return false;

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };

        $mensaje = "🏭 <b>Nuevo Almacén Creado</b>\n\n"
                 . "🆔 <b>ID:</b> {$almacen_id}\n"
                 . "🔤 <b>Código:</b> {$e($codigo)}\n"
                 . "📛 <b>Nombre:</b> {$e($nombre)}\n\n"
                 . "📅 <b>Fecha:</b> " . date('d/m/Y H:i') . "\n\n"
                 . "✅ Almacén registrado exitosamente en el sistema.";

        $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarNuevoAlmacen: " . $e->getMessage());
        return false;
    }
}

function telegramNotificarBajoStockAlmacen($pdo, int $producto_id, string $productoNombre, int $almacen_id, string $almacenNombre, int $stockActual, int $stockMinimo): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token']) || empty($config['chat_id'])) return false;

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };

        $mensaje = "⚠️ <b>Stock Bajo en Almacén</b>\n\n"
                 . "📦 <b>Producto:</b> {$e($productoNombre)}\n"
                 . "🏭 <b>Almacén:</b> {$e($almacenNombre)}\n"
                 . "📊 <b>Stock Actual:</b> " . number_format($stockActual, 0, ',', '.') . " uds\n"
                 . "📉 <b>Stock Mínimo:</b> " . number_format($stockMinimo, 0, ',', '.') . " uds\n\n"
                 . "🔴 Se requiere reposición de inventario.";

        $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarBajoStockAlmacen: " . $e->getMessage());
        return false;
    }
}
