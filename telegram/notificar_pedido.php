<?php
require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/helpers.php';

function telegramNotificarClientePedido($pdo, $pedido): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token'])) return false;

        // Try user table first
        $userChatId = null;
        if (!empty($pedido['usuario_id'])) {
            $stmt = $pdo->prepare("SELECT telegram_chat_id FROM users WHERE id = ? AND telegram_chat_id IS NOT NULL");
            $stmt->execute([$pedido['usuario_id']]);
            $userChatId = $stmt->fetchColumn();
        }

        // Fallback: check clientes -> users via correo
        if (!$userChatId && !empty($pedido['cliente_id'])) {
            $stmt = $pdo->prepare("SELECT u.telegram_chat_id FROM users u JOIN clientes c ON c.email = u.correo WHERE c.id = ? AND u.telegram_chat_id IS NOT NULL");
            $stmt->execute([$pedido['cliente_id']]);
            $userChatId = $stmt->fetchColumn();
        }

        if (!$userChatId) return false;

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };
        $detalles = $pdo->prepare("SELECT pd.*, pr.name as producto_nombre FROM pedido_detalles pd JOIN products pr ON pd.producto_id = pr.id WHERE pd.pedido_id = ?");
        $detalles->execute([$pedido['id']]);
        $items = $detalles->fetchAll(PDO::FETCH_ASSOC);

        $mensaje = "✅ <b>Pedido Confirmado - PIC</b>\n\n"
                 . "Hola, tu pedido ha sido registrado exitosamente.\n\n"
                 . "📋 <b>Pedido:</b> {$e($pedido['numero_pedido'])}\n"
                 . "📅 <b>Fecha:</b> {$e($pedido['created_at'] ?? $pedido['fecha_pedido'])}\n\n"
                 . "<b>Productos:</b>\n";
        foreach ($items as $it) {
            $mensaje .= "• {$e($it['producto_nombre'])} x{$it['cantidad']} = Bs. " . number_format((float)$it['subtotal'], 2, ',', '.') . "\n";
        }
        $mensaje .= "\n💰 <b>Total:</b> Bs. " . number_format((float)($pedido['total'] ?? 0), 2, ',', '.') . "\n"
                  . "💳 <b>Pago:</b> {$e($pedido['metodo_pago'])}\n\n"
                  . "📌 <b>Estado:</b> Pendiente — estamos procesando tu pedido.\n\n"
                  . "Te notificaremos cuando haya cambios. Gracias por comprar con PIC 🤖";

        $resultado = telegramEnviar($config['token'], $userChatId, $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarCliente: " . $e->getMessage());
        return false;
    }
}

function telegramNotificarCambioEstado($pdo, int $pedido_id, string $estadoAnterior): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token'])) return false;

        $pedido = $pdo->prepare("
            SELECT p.*, u.nombre as user_nombre, u.telegram_chat_id
            FROM pedidos p
            LEFT JOIN users u ON p.usuario_id = u.id
            WHERE p.id = ?
        ");
        $pedido->execute([$pedido_id]);
        $p = $pedido->fetch(PDO::FETCH_ASSOC);
        if (!$p || empty($p['telegram_chat_id'])) return false;

        $mapa = [
            'pendiente' => '🟡 Pendiente',
            'procesando' => '🔵 Procesando',
            'enviado' => '🟢 Enviado',
            'entregado' => '✅ Entregado',
            'cancelado' => '🔴 Cancelado',
            'facturado' => '📄 Facturado',
        ];
        $estadoTexto = $mapa[$p['estado']] ?? $p['estado'];

        $mensaje = "🔄 <b>Actualización de tu Pedido</b>\n\n"
                 . "📋 <b>Pedido:</b> {$p['numero_pedido']}\n"
                 . "📌 <b>Estado:</b> {$estadoTexto}\n\n";

        if ($p['estado'] === 'enviado') {
            $mensaje .= "📦 ¡Tu pedido ya está en camino!\n\n";
        } elseif ($p['estado'] === 'entregado') {
            $mensaje .= "🎉 ¡Tu pedido ha sido entregado! Esperamos que disfrutes tu compra.\n\n";
        } elseif ($p['estado'] === 'cancelado') {
            $mensaje .= "Lamentamos informarte que tu pedido ha sido cancelado.\n"
                      . "Si tienes dudas, contáctanos al 0424-8323902.\n\n";
        } elseif ($p['estado'] === 'procesando') {
            $mensaje .= "Estamos preparando tu pedido para envío. Te avisaremos cuando esté listo.\n\n";
        }

        $mensaje .= "📞 ¿Dudas? Llámanos al 0424-8323902";

        $resultado = telegramEnviar($config['token'], $p['telegram_chat_id'], $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarCambioEstado: " . $e->getMessage());
        return false;
    }
}

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
            $cantidad = (int)$it['cantidad'];
            $subtotalItem = number_format((float)$it['subtotal'], 2, ',', '.');
            $mensaje .= "• {$producto} x{$cantidad} = Bs. {$subtotalItem}\n";
        }
        $mensaje .= "\n";

        $subtotal = number_format((float)($p['subtotal'] ?? 0), 2, ',', '.');
        $iva = number_format((float)($p['iva'] ?? 0), 2, ',', '.');
        $total = number_format((float)($p['total'] ?? 0), 2, ',', '.');
        $mensaje .= "💰 <b>Subtotal:</b> Bs. {$subtotal}\n";
        $mensaje .= "🧾 <b>IVA:</b> Bs. {$iva}\n";
        $mensaje .= "💵 <b>Total:</b> Bs. {$total}\n";
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

        // Also notify the customer if linked
        telegramNotificarClientePedido($pdo, $p);

        return $resultado['success'];

    } catch (Throwable $e) {
        error_log("Error telegramNotificarPedido: " . $e->getMessage());
        return false;
    }
}
