<?php
// procesar_factura.php - Procesar acciones de facturas (marcar pagada, anular, crear, etc.)
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
iniciarSesion();
verificarCSRF();

try {
    $pdo = conectarDB();
    $pdo->exec("SET time_zone = '-04:00'");
} catch (PDOException $e) {
    error_log("Error en procesar_factura.php (conexión): " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}

// Verificar permisos (solo admin pueden procesar facturas)
requerirAdmin();

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$accion = $data['accion'] ?? '';

// Si no hay acción pero viene data de creación (cliente_id + productos), crear factura
if (empty($accion) && isset($data['cliente_id']) && isset($data['productos'])) {
    $accion = 'crear';
}

try {
    switch ($accion) {
        case 'crear':
            crearFactura($pdo, $data);
            break;
        case 'marcar_pagada':
            $factura_id = $data['factura_id'] ?? 0;
            if (!$factura_id) {
                echo json_encode(['success' => false, 'message' => 'ID de factura no proporcionado']);
                exit;
            }
            marcarPagada($pdo, $factura_id);
            break;
        case 'anular':
            $factura_id = $data['factura_id'] ?? 0;
            if (!$factura_id) {
                echo json_encode(['success' => false, 'message' => 'ID de factura no proporcionado']);
                exit;
            }
            $motivo = $data['motivo'] ?? '';
            if (empty($motivo)) {
                echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo de anulación']);
                exit;
            }
            anularFactura($pdo, $factura_id, $motivo);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Crear una nueva factura desde datos JSON
 */
function crearFactura($pdo, $data) {
    try {
        $pdo->beginTransaction();

        $usuario_id = $data['usuario_id'] ?? $_SESSION['user_id'] ?? 1;
        $cliente_id = (int) $data['cliente_id'];
        $fecha_emision = $data['fecha_emision'] ?? date('Y-m-d');
        $fecha_vencimiento = $data['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days'));
        $metodo_pago = $data['metodo_pago'] ?? 'efectivo';
        $observaciones = $data['observaciones'] ?? '';
        $subtotal = (float) ($data['subtotal'] ?? 0);
        $iva = (float) ($data['iva'] ?? 0);
        $total = (float) ($data['total'] ?? 0);
        $estado = $data['estado'] ?? 'pendiente';

        // Generar número de factura
        $anio_actual = date('Y');
        $stmt = $pdo->prepare("SELECT numero_factura FROM facturas WHERE YEAR(fecha_emision) = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$anio_actual]);
        $ultima = $stmt->fetchColumn();
        if ($ultima) {
            $partes = explode('-', $ultima);
            $ultimo_numero = end($partes);
            $siguiente = intval($ultimo_numero) + 1;
            $numero_factura = "FAC-{$anio_actual}-" . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
        } else {
            $numero_factura = "FAC-{$anio_actual}-000001";
        }

        // Insertar factura
        $stmt = $pdo->prepare("
            INSERT INTO facturas (numero_factura, cliente_id, fecha_emision, fecha_vencimiento,
                                 subtotal, iva, total, metodo_pago, estado, usuario_id, observaciones)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $numero_factura, $cliente_id, $fecha_emision, $fecha_vencimiento,
            $subtotal, $iva, $total, $metodo_pago, $estado, $usuario_id, $observaciones
        ]);
        $factura_id = (int) $pdo->lastInsertId();

        // Insertar detalles y actualizar stock
        $productos = $data['productos'] ?? [];
        foreach ($productos as $p) {
            $producto_id = (int) $p['id'];
            $cantidad = (int) ($p['cantidad'] ?? 1);
            $precio = (float) ($p['precio'] ?? 0);
            $subtotal_item = (float) ($p['subtotal'] ?? ($precio * $cantidad));

            $stmt_d = $pdo->prepare("
                INSERT INTO factura_detalles (factura_id, producto_id, cantidad, precio_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_d->execute([$factura_id, $producto_id, $cantidad, $precio, $subtotal_item]);

            // Descontar stock
            $stmt_stock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt_stock->execute([$cantidad, $producto_id]);
        }

        $pdo->commit();

        // Enviar notificaciones por email/telegram si es efectivo o mixto
        $metodos_notificar = ['efectivo', 'mixto'];
        if (in_array($metodo_pago, $metodos_notificar)) {
            // Bufferizar para evitar que cualquier warning/error corrompa el JSON
            ob_start();
            try {
                require_once __DIR__ . '/../notificaciones/cola.php';
                colaNotificacionesAgregar('email_factura', null, $factura_id);

                require_once __DIR__ . '/../telegram/helpers.php';
                $config = telegramObtenerConfig($pdo);
                if (!empty($config['token']) && !empty($config['chat_id'])) {
                    $stmt_c = $pdo->prepare("SELECT nombre, email, telefono FROM clientes WHERE id = ?");
                    $stmt_c->execute([$cliente_id]);
                    $cliente = $stmt_c->fetch(PDO::FETCH_ASSOC);

                    $e = function ($v) { return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8'); };
                    $nombreFmt = number_format($total, 2, ',', '.');

                    $msg = "📄 <b>NUEVA FACTURA - PIC</b>\n\n";
                    $msg .= "📋 <b>Factura:</b> {$e($numero_factura)}\n";
                    $msg .= "📅 <b>Fecha:</b> {$e($fecha_emision)}\n";
                    $msg .= "👤 <b>Cliente:</b> {$e($cliente['nombre'] ?? 'N/A')}\n";
                    $msg .= "📧 <b>Email:</b> {$e($cliente['email'] ?? 'N/A')}\n";
                    if (!empty($cliente['telefono'])) {
                        $msg .= "📞 <b>Teléfono:</b> {$e($cliente['telefono'])}\n";
                    }
                    $msg .= "\n💰 <b>Total:</b> Bs. {$nombreFmt}\n";
                    $msg .= "💳 <b>Método:</b> {$e($metodo_pago)}\n";
                    $msg .= "📌 <b>Estado:</b> {$e($estado)}\n";

                    telegramEnviar($config['token'], $config['chat_id'], $msg);
                }

                colaNotificacionesDispararProcesador();
            } catch (Exception $e) {
                error_log("Error enviando notificaciones: " . $e->getMessage());
            }
            ob_end_clean();
        }

        echo json_encode([
            'success' => true,
            'message' => $estado === 'borrador' ? 'Borrador guardado exitosamente' : 'Factura generada exitosamente',
            'factura_id' => $factura_id,
            'numero_factura' => $numero_factura
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al crear factura: verifique los datos']);
    }
}

/**
 * Marcar una factura como pagada
 */
function marcarPagada($pdo, $factura_id) {
    try {
        $pdo->beginTransaction();
        
        // Obtener información de la factura
        $stmt = $pdo->prepare("
            SELECT f.*, p.estado as pedido_estado, p.id as pedido_id
            FROM facturas f
            LEFT JOIN pedidos p ON f.pedido_id = p.id
            WHERE f.id = ?
        ");
        $stmt->execute([$factura_id]);
        $factura = $stmt->fetch();
        
        if (!$factura) {
            throw new Exception('Factura no encontrada');
        }
        
        if ($factura['estado'] === 'pagada') {
            throw new Exception('La factura ya está pagada');
        }
        
        if ($factura['estado'] === 'anulada') {
            throw new Exception('No se puede pagar una factura anulada');
        }
        
        // Actualizar estado de la factura (solo actualizamos estado, sin columnas adicionales)
        $stmt_update = $pdo->prepare("UPDATE facturas SET estado = 'pagada' WHERE id = ?");
        $stmt_update->execute([$factura_id]);
        
        // Si la factura tiene un pedido asociado, actualizar su estado
        if (!empty($factura['pedido_id'])) {
            $stmt_update_pedido = $pdo->prepare("
                UPDATE pedidos 
                SET estado = 'facturado', 
                    fecha_facturacion = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt_update_pedido->execute([$factura['pedido_id']]);
        }
        
        // Registrar en auditoría
        registrarAuditoria($pdo, $factura_id, 'marcar_pagada', 
            "Factura #{$factura['numero_factura']} marcada como pagada. Total: Bs. " . number_format($factura['total'], 2));
        
        $pdo->commit();
        
        auditoriaRegistrar('procesar_factura', 'facturacion', "Factura #{$factura['numero_factura']} marcada como pagada. Total: Bs. " . number_format($factura['total'], 2));
        echo json_encode([
            'success' => true,
            'message' => 'Factura marcada como pagada exitosamente'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
}

/**
 * Anular una factura
 */
function anularFactura($pdo, $factura_id, $motivo) {
    try {
        $pdo->beginTransaction();
        
        // Obtener información de la factura
        $stmt = $pdo->prepare("
            SELECT f.*, p.id as pedido_id
            FROM facturas f
            LEFT JOIN pedidos p ON f.pedido_id = p.id
            WHERE f.id = ?
        ");
        $stmt->execute([$factura_id]);
        $factura = $stmt->fetch();
        
        if (!$factura) {
            throw new Exception('Factura no encontrada');
        }
        
        if ($factura['estado'] === 'anulada') {
            throw new Exception('La factura ya está anulada');
        }
        
        // Actualizar estado de la factura (solo actualizamos estado, sin observaciones)
        $stmt_update = $pdo->prepare("UPDATE facturas SET estado = 'anulada' WHERE id = ?");
        $stmt_update->execute([$factura_id]);
        
        // Si la factura tiene un pedido asociado, actualizar su estado y restaurar stock
        if (!empty($factura['pedido_id'])) {
            // Primero obtenemos los detalles del pedido para restaurar el stock
            $stmt_detalles = $pdo->prepare("
                SELECT producto_id, cantidad 
                FROM pedido_detalles 
                WHERE pedido_id = ?
            ");
            $stmt_detalles->execute([$factura['pedido_id']]);
            $detalles = $stmt_detalles->fetchAll();
            
            // Restaurar stock de productos
            foreach ($detalles as $detalle) {
                $stmt_restore = $pdo->prepare("
                    UPDATE products 
                    SET stock = stock + ? 
                    WHERE id = ?
                ");
                $stmt_restore->execute([$detalle['cantidad'], $detalle['producto_id']]);
            }
            
            // Actualizar estado del pedido (solo actualizamos estado, sin observaciones)
            $stmt_update_pedido = $pdo->prepare("
                UPDATE pedidos 
                SET estado = 'cancelado', 
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt_update_pedido->execute([$factura['pedido_id']]);
        }
        
        // Guardar el motivo de anulación en un log aparte (tabla auditoria_logs)
        registrarAuditoria($pdo, $factura_id, 'anular', 
            "Factura #{$factura['numero_factura']} anulada. Motivo: $motivo. Stock restaurado.");
        
        $pdo->commit();
        
        auditoriaRegistrar('procesar_factura', 'facturacion', "Factura #{$factura['numero_factura']} anulada. Motivo: $motivo");
        echo json_encode([
            'success' => true,
            'message' => 'Factura anulada exitosamente. Stock restaurado.'
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    }
}

/**
 * Registrar acción en la tabla de auditoría
 */
function registrarAuditoria($pdo, $factura_id, $accion, $descripcion) {
    try {
        // La tabla ya fue creada al inicio del proceso
        
        // Obtener datos del usuario desde la sesión
        $usuario_id = $_SESSION['user_id'] ?? null;
        $usuario_nombre = $_SESSION['user_nombre'] ?? $_SESSION['nombre'] ?? 'Sistema';
        $usuario_rol = $_SESSION['user_rol'] ?? 'admin';
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $pdo->prepare("
            INSERT INTO auditoria_logs (
                usuario_id, usuario_nombre, usuario_rol, accion, modulo, 
                descripcion, ip_address, tabla_afectada, registro_id, fecha_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $usuario_id,
            $usuario_nombre,
            $usuario_rol,
            $accion,
            'facturas',
            $descripcion,
            $ip_address,
            'facturas',
            $factura_id
        ]);
        
    } catch (Exception $e) {
        // Si falla la auditoría, solo registramos en error log
        error_log("Error al registrar auditoría: " . $e->getMessage());
    }
}
?>
