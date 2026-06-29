<?php
require_once __DIR__ . '/helpers.php';

function telegramNotificarPagoCobro($pdo, int $cuenta_id, float $monto, string $metodo, string $nuevoEstado): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token']) || empty($config['chat_id'])) return false;

        $stmt = $pdo->prepare("
            SELECT cc.numero_documento, cc.saldo_pendiente, cl.id AS cliente_id, cl.nombre AS cliente_nombre
            FROM cuentas_cobrar cc
            JOIN clientes cl ON cc.cliente_id = cl.id
            WHERE cc.id = :id
        ");
        $stmt->execute([':id' => $cuenta_id]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) return false;

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };
        $estadoEtiqueta = $nuevoEstado === 'pagada' ? '✅ Pagada' : '🟡 Parcial';
        $metodoEtiqueta = match ($metodo) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'pago_movil' => 'Pago Móvil',
            'cheque' => 'Cheque',
            'tarjeta' => 'Tarjeta',
            'deposito' => 'Depósito',
            default => $metodo,
        };

        $mensaje = "💰 <b>PAGO RECIBIDO</b>\n\n"
                 . "📋 <b>Cuenta:</b> #{$cuenta_id}\n"
                 . "📄 <b>Documento:</b> {$e($cuenta['numero_documento'])}\n"
                 . "👤 <b>Cliente:</b> {$e($cuenta['cliente_nombre'])}\n\n"
                 . "💵 <b>Monto:</b> Bs. " . number_format($monto, 2, ',', '.') . "\n"
                 . "💳 <b>Método:</b> {$metodoEtiqueta}\n"
                 . "📌 <b>Estado:</b> {$estadoEtiqueta}\n"
                 . "📊 <b>Saldo restante:</b> Bs. " . number_format((float)$cuenta['saldo_pendiente'], 2, ',', '.') . "\n";

        $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarPagoCobro: " . $e->getMessage());
        return false;
    }
}

function telegramNotificarPagoProveedor($pdo, int $cuenta_id, float $monto, string $metodo, string $nuevoEstado): bool {
    try {
        $config = telegramObtenerConfig($pdo);
        if (empty($config['token']) || empty($config['chat_id'])) return false;

        $stmt = $pdo->prepare("
            SELECT cp.numero_documento, cp.saldo_pendiente, pv.id AS proveedor_id, pv.nombre_comercial AS proveedor_nombre
            FROM cuentas_pagar cp
            JOIN proveedores pv ON cp.proveedor_id = pv.id
            WHERE cp.id = :id
        ");
        $stmt->execute([':id' => $cuenta_id]);
        $cuenta = $stmt->fetch();
        if (!$cuenta) return false;

        $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };
        $estadoEtiqueta = $nuevoEstado === 'pagada' ? '✅ Pagada' : '🟡 Parcial';
        $metodoEtiqueta = match ($metodo) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque',
            'deposito' => 'Depósito',
            default => $metodo,
        };

        $mensaje = "🏢 <b>PAGO A PROVEEDOR</b>\n\n"
                 . "📋 <b>Cuenta:</b> #{$cuenta_id}\n"
                 . "📄 <b>Documento:</b> {$e($cuenta['numero_documento'])}\n"
                 . "👤 <b>Proveedor:</b> {$e($cuenta['proveedor_nombre'])}\n\n"
                 . "💵 <b>Monto:</b> Bs. " . number_format($monto, 2, ',', '.') . "\n"
                 . "💳 <b>Método:</b> {$metodoEtiqueta}\n"
                 . "📌 <b>Estado:</b> {$estadoEtiqueta}\n"
                 . "📊 <b>Saldo restante:</b> Bs. " . number_format((float)$cuenta['saldo_pendiente'], 2, ',', '.') . "\n";

        $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
        return $resultado['success'] ?? false;
    } catch (Throwable $e) {
        error_log("Error telegramNotificarPagoProveedor: " . $e->getMessage());
        return false;
    }
}
