<?php
require_once __DIR__ . '/helpers.php';

function telegramNotificarNotaCreditoEmitida($pdo, int $nota_id, string $numeroNota, string $clienteNombre, float $total, string $motivo): bool
{
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;

    $motivoTexto = match ($motivo) {
        'devolucion' => 'Devolución',
        'descuento' => 'Descuento',
        'error_facturacion' => 'Error de Facturación',
        'anulacion' => 'Anulación',
        'otro' => 'Otro',
        default => $motivo
    };

    $mensaje = "&#x1F4DD; <b>Nota de Crédito Emitida</b>\n"
        . "─────────────────────\n"
        . "<b>Número:</b> {$numeroNota}\n"
        . "<b>Cliente:</b> {$clienteNombre}\n"
        . "<b>Total:</b> Bs. " . number_format($total, 2, ',', '.') . "\n"
        . "<b>Motivo:</b> {$motivoTexto}\n"
        . "<b>ID:</b> {$nota_id}";

    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarNotaCreditoAnulada($pdo, int $nota_id, string $numeroNota): bool
{
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;

    $mensaje = "&#x274C; <b>Nota de Crédito Anulada</b>\n"
        . "─────────────────────\n"
        . "<b>Número:</b> {$numeroNota}\n"
        . "<b>ID:</b> {$nota_id}";

    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarNotaDebitoEmitida($pdo, int $nota_id, string $numeroNota, string $clienteNombre, float $total, string $motivo): bool
{
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;

    $motivoTexto = match ($motivo) {
        'interes_mora' => 'Interés por Mora',
        'diferencia_precio' => 'Diferencia de Precio',
        'gastos_adicionales' => 'Gastos Adicionales',
        'error_cargo' => 'Error de Cargo',
        'otro' => 'Otro',
        default => $motivo
    };

    $mensaje = "&#x1F4CB; <b>Nota de Débito Emitida</b>\n"
        . "─────────────────────\n"
        . "<b>Número:</b> {$numeroNota}\n"
        . "<b>Cliente:</b> {$clienteNombre}\n"
        . "<b>Total:</b> Bs. " . number_format($total, 2, ',', '.') . "\n"
        . "<b>Motivo:</b> {$motivoTexto}\n"
        . "<b>ID:</b> {$nota_id}";

    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}

function telegramNotificarNotaDebitoAnulada($pdo, int $nota_id, string $numeroNota): bool
{
    $config = telegramObtenerConfig($pdo);
    if (empty($config['token']) || empty($config['chat_id'])) return false;

    $mensaje = "&#x274C; <b>Nota de Débito Anulada</b>\n"
        . "─────────────────────\n"
        . "<b>Número:</b> {$numeroNota}\n"
        . "<b>ID:</b> {$nota_id}";

    $resultado = telegramEnviar($config['token'], $config['chat_id'], $mensaje);
    return $resultado['success'] ?? false;
}
