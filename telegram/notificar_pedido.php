<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/helpers.php';

function telegramNotificarPedido($pdo, int $pedido_id): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token']) || empty($config['chat_id'])) return false;

        $pedido = $pdo->prepare("
            SELECT p.*, u.nombre as user_nombre, u.correo as user_correo, 
                   u.telefono as user_telefono, u.cedula, u.direccion,
                   c.nombre as cliente_nombre, c.email as cliente_email,
                   c.telefono as cliente_telefono
            FROM pedidos p
            LEFT JOIN users u ON p.usuario_id = u.id
            LEFT JOIN clientes c ON p.cliente_id = c.id
            WHERE p.id = ?
        ");
        $pedido->execute([$pedido_id]);
        $p = $pedido->fetch(PDO::FETCH_ASSOC);

        if (!$p) return false;

        $detalles = $pdo->prepare("
            SELECT pd.*, pr.name as producto_nombre
            FROM pedido_detalles pd
            JOIN products pr ON pd.producto_id = pr.id
            WHERE pd.pedido_id = ?
        ");
        $detalles->execute([$pedido_id]);
        $items = $detalles->fetchAll(PDO::FETCH_ASSOC);

        $nombre = $p['cliente_nombre'] ?: $p['user_nombre'] ?: 'N/A';
        $correo = $p['cliente_email'] ?: $p['user_correo'] ?: 'N/A';
        $telefono = $p['cliente_telefono'] ?: $p['user_telefono'] ?: 'N/A';

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };

        $mensaje = "🛒 <b>NUEVO PEDIDO - PIC</b>\n\n";
        $mensaje .= "📋 <b>Pedido:</b> {$e($p['numero_pedido'])}\n";
        $mensaje .= "📅 <b>Fecha:</b> {$e($p['created_at'] ?? $p['fecha_pedido'])}\n\n";

        $mensaje .= "👤 <b>Cliente:</b> {$e($nombre)}\n";
        $mensaje .= "📧 <b>Email:</b> {$e($correo)}\n";
        $mensaje .= "📞 <b>Teléfono:</b> {$e($telefono)}\n";
        if (!empty($p['cedula'])) $mensaje .= "🆔 <b>Cédula:</b> {$e($p['cedula'])}\n";
        if (!empty($p['direccion'])) $mensaje .= "📍 <b>Dirección:</b> {$e($p['direccion'])}\n";
        $mensaje .= "\n";

        $mensaje .= "🧾 <b>Productos:</b>\n";
        foreach ($items as $it) {
            $producto = $e($it['producto_nombre']);
            $mensaje .= "• {$producto} x{$it['cantidad']} = Bs. {$it['subtotal']}\n";
        }
        $mensaje .= "\n";

        $mensaje .= "💰 <b>Subtotal:</b> Bs. {$p['subtotal']}\n";
        $mensaje .= "🧾 <b>IVA:</b> Bs. {$p['iva']}\n";
        $mensaje .= "💵 <b>Total:</b> Bs. {$p['total']}\n";
        $mensaje .= "💳 <b>Método:</b> {$e($p['metodo_pago'])}\n";
        if (!empty($p['referencia_pago'])) {
            $mensaje .= "🔢 <b>Referencia:</b> {$e($p['referencia_pago'])}\n";
        }

        $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);

        if ($resultado['success']) {
            auditoriaRegistrar('notificar_pedido_telegram', 'telegram',
                "Notificación de nuevo pedido #{$p['numero_pedido']} enviada por Telegram"
            );
        }

        return $resultado['success'];

    } catch (Throwable $e) {
        error_log("Error telegramNotificarPedido: " . $e->getMessage());
        return false;
    }
}
