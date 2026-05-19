<?php
// procesar_factura.php - Procesar acciones de facturas (marcar pagada, anular, etc.)
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../conexion/conexion.php';
verificarCSRF();

try {
    $pdo = conectarDB();
    $pdo->exec("SET time_zone = '-04:00'");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
    exit;
}

// Verificar permisos (solo admin o superadmin pueden procesar facturas)
$es_admin = isset($_SESSION['user_rol']) && ($_SESSION['user_rol'] === 'admin' || $_SESSION['user_rol'] === 'superadmin');
if (!$es_admin) {
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit;
}

// Obtener datos del POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$accion = $data['accion'] ?? '';
$factura_id = $data['factura_id'] ?? 0;
$motivo = $data['motivo'] ?? '';

if (!$factura_id) {
    echo json_encode(['success' => false, 'message' => 'ID de factura no proporcionado']);
    exit;
}

try {
    switch ($accion) {
        case 'marcar_pagada':
            marcarPagada($pdo, $factura_id);
            break;
        case 'anular':
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
        // Verificar si la tabla auditoria_logs existe
        $stmt_check = $pdo->query("SHOW TABLES LIKE 'auditoria_logs'");
        if ($stmt_check->rowCount() == 0) {
            // Si no existe la tabla, crearla (adaptada a tu SQL existente)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS auditoria_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NULL,
                    usuario_nombre VARCHAR(100) NULL,
                    usuario_rol VARCHAR(50) NULL,
                    accion VARCHAR(100) NOT NULL,
                    modulo VARCHAR(50) NOT NULL,
                    descripcion TEXT NULL,
                    ip_address VARCHAR(45) NULL,
                    tabla_afectada VARCHAR(100) NULL,
                    registro_id INT NULL,
                    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
                    edit_count INT NOT NULL DEFAULT 0,
                    edit_history TEXT NULL,
                    last_edit_by INT NULL,
                    last_edit_at DATETIME NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
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