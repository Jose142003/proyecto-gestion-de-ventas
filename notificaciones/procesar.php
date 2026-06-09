<?php
/**
 * Procesador de cola de notificaciones (ejecutar en segundo plano)
 * Uso: php procesar.php
 * Es llamado automáticamente desde colaNotificacionesDispararProcesador()
 */

// Evitar ejecución desde web
if (php_sapi_name() !== 'cli' && !isset($GLOBALS['_DISPARO_INTERNO'])) {
    die("Este script solo puede ejecutarse en segundo plano.\n");
}

require_once __DIR__ . '/cola.php';
require_once __DIR__ . '/../usuarios/enviar_factura_email.php';
require_once __DIR__ . '/../telegram/notificar_pedido.php';
require_once __DIR__ . '/../admin/enviar_encuesta_satisfaccion.php';

set_time_limit(120);
ignore_user_abort(true);

$max_ejecucion = 60;
$inicio = time();
$procesados = 0;

do {
    $items = colaNotificacionesObtenerPendientes(5);

    if (empty($items)) {
        break;
    }

    foreach ($items as $item) {
        if ((time() - $inicio) >= $max_ejecucion) {
            break 2;
        }

        $id = (int) $item['id'];
        $tipo = $item['tipo'];
        $pedido_id = $item['pedido_id'] ? (int) $item['pedido_id'] : null;
        $factura_id = $item['factura_id'] ? (int) $item['factura_id'] : null;
        $datos_extra = $item['datos_extra'] ? json_decode($item['datos_extra'], true) : null;

        colaNotificacionesMarcar($id, 'procesando');

        try {
            $pdo = conectarDB();

            switch ($tipo) {
                case 'email_factura':
                    if ($factura_id) {
                        $stmt_f = $pdo->prepare("SELECT f.*, c.nombre as cliente_nombre, c.email as cliente_email, c.documento as cliente_documento, c.telefono as cliente_telefono, c.direccion as cliente_direccion, p.metodo_pago as pedido_metodo_pago, p.referencia_pago as pedido_referencia_pago, p.observaciones as pedido_observaciones, a.nombre as vendedor_nombre, a.correo as vendedor_email FROM facturas f LEFT JOIN clientes c ON f.cliente_id = c.id LEFT JOIN admin_users a ON f.usuario_id = a.id LEFT JOIN pedidos p ON f.pedido_id = p.id WHERE f.id = ?");
                        $stmt_f->execute([$factura_id]);
                        $factura_data = $stmt_f->fetch(PDO::FETCH_ASSOC);

                        if ($factura_data && !empty($factura_data['cliente_email'])) {
                            $stmt_d = $pdo->prepare("SELECT fd.*, p.name as producto_nombre, p.sku FROM factura_detalles fd LEFT JOIN products p ON fd.producto_id = p.id WHERE fd.factura_id = ?");
                            $stmt_d->execute([$factura_id]);
                            $detalles_data = $stmt_d->fetchAll(PDO::FETCH_ASSOC);

                            $html = generarHTMLFacturaEmail($factura_data, $detalles_data);
                            enviarCorreo($factura_data['cliente_email'], 'Factura Electrónica #' . $factura_data['numero_factura'] . ' - PIC Sistema', $html, 'PIC Sistema de Facturación');
                        }
                    }
                    break;

                case 'telegram_pedido':
                    if ($pedido_id) {
                        telegramNotificarPedido($pdo, $pedido_id);
                    }
                    break;

                case 'encuesta_satisfaccion':
                    if ($pedido_id && $datos_extra) {
                        enviarEncuestaSatisfaccion(
                            $pdo,
                            $pedido_id,
                            $datos_extra['email'] ?? '',
                            $datos_extra['nombre'] ?? 'Cliente',
                            $datos_extra['numero_factura'] ?? ''
                        );
                    }
                    break;

                default:
                    throw new Exception("Tipo de notificación desconocido: $tipo");
            }

            colaNotificacionesMarcar($id, 'completado');
            $procesados++;

        } catch (Throwable $e) {
            $error = $e->getMessage();
            error_log("Error procesando notificación #$id ($tipo): $error");

            $intentos = (int) ($item['intentos'] ?? 0) + 1;
            $max_intentos = (int) ($item['max_intentos'] ?? 3);

            if ($intentos >= $max_intentos) {
                colaNotificacionesMarcar($id, 'fallido', $error);
            } else {
                colaNotificacionesMarcar($id, 'pendiente', $error);
            }
        }
    }
} while ((time() - $inicio) < $max_ejecucion);
