<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../telegram/helpers.php';
require_once __DIR__ . '/../usuarios/config_email.php';

try {
    $pdo = conectarDB();
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit;
    }

    $cliente_nombre = trim($input['cliente_nombre'] ?? '');
    $cliente_email = trim($input['cliente_email'] ?? '');
    $cliente_telefono = trim($input['cliente_telefono'] ?? '');
    $items = $input['items'] ?? [];

    if (!$cliente_nombre || !$cliente_email || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Nombre, email y productos requeridos']);
        exit;
    }

    if (!filter_var($cliente_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }

    $anio = date('Y');
    $seq = $pdo->query("SELECT COUNT(*) FROM cotizaciones WHERE YEAR(fecha_creacion) = $anio")->fetchColumn();
    $numero = 'COT-WEB-' . $anio . '-' . str_pad($seq + 1, 6, '0', STR_PAD_LEFT);

    $subtotal = 0;
    foreach ($items as $item) {
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        $precio = (float)($item['precio'] ?? 0);
        $subtotal += $cantidad * $precio;
    }
    $ivaPorcentaje = $pdo->query("SELECT valor FROM configuracion_sistema WHERE clave = 'iva_porcentaje'")->fetchColumn() ?: 16;
    $iva = $subtotal * ($ivaPorcentaje / 100);
    $total = $subtotal + $iva;

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO cotizaciones (numero_cotizacion, cliente_nombre, cliente_email, cliente_telefono, subtotal, iva, total, notas, fecha_creacion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pendiente')");
    $notas = "Solicitada vía web por {$cliente_nombre}";
    $stmt->execute([$numero, $cliente_nombre, $cliente_email, $cliente_telefono, $subtotal, $iva, $total, $notas]);
    $cotizacion_id = $pdo->lastInsertId();

    $detStmt = $pdo->prepare("INSERT INTO cotizacion_detalles (cotizacion_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $cantidad = max(1, (int)($item['cantidad'] ?? 1));
        $precio = (float)($item['precio'] ?? 0);
        $subtotal_item = $cantidad * $precio;
        $detStmt->execute([
            $cotizacion_id,
            !empty($item['id']) ? (int)$item['id'] : null,
            $item['nombre'] ?? 'Producto',
            $cantidad,
            $precio,
            $subtotal_item
        ]);
    }

    $pdo->commit();

    // Telegram notification
    try {
        $config = telegramObtenerConfig($pdo);
        if (!empty($config['token']) && !empty($config['chat_id'])) {
            $detallesMsg = "";
            foreach ($items as $item) {
                $detallesMsg .= "• {$item['nombre']} x{$item['cantidad']} = Bs " . number_format((float)$item['precio'] * (int)$item['cantidad'], 2) . "\n";
            }
            $mensaje = "📋 *Nueva Cotización Web*\n\n";
            $mensaje .= "*N°:* {$numero}\n";
            $mensaje .= "*Cliente:* {$cliente_nombre}\n";
            $mensaje .= "*Email:* {$cliente_email}\n";
            $mensaje .= "*Teléfono:* {$cliente_telefono}\n\n";
            $mensaje .= "*Productos:*\n{$detallesMsg}\n";
            $mensaje .= "*Total:* Bs " . number_format($total, 2) . "\n";
            $mensaje .= "📅 " . date('d/m/Y H:i');
            telegramEnviar($config['token'], $config['chat_id'], $mensaje);
        }
    } catch (Throwable $e) {
        error_log("Error enviando Telegram cotización: " . $e->getMessage());
    }

    // Email to client
    try {
        $productosHtml = "";
        foreach ($items as $item) {
            $sub = (float)$item['precio'] * (int)$item['cantidad'];
            $productosHtml .= "<tr><td style='padding:8px;border-bottom:1px solid #ddd'>{$item['nombre']}</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:center'>{$item['cantidad']}</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:right'>Bs " . number_format((float)$item['precio'], 2) . "</td><td style='padding:8px;border-bottom:1px solid #ddd;text-align:right'>Bs " . number_format($sub, 2) . "</td></tr>";
        }
        $html = "<html><body style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;padding:20px'>";
        $html .= "<div style='background:#2c3e50;color:white;padding:20px;text-align:center;border-radius:10px 10px 0 0'><h2 style='margin:0'>Cotización {$numero}</h2></div>";
        $html .= "<div style='background:white;padding:20px;border:1px solid #ddd'>";
        $html .= "<p>Hola <strong>{$cliente_nombre}</strong>,</p>";
        $html .= "<p>Gracias por solicitar una cotización. Hemos recibido tu solicitud y uno de nuestros asesores se pondrá en contacto contigo a la brevedad.</p>";
        $html .= "<h3 style='color:#2c3e50'>Productos solicitados:</h3>";
        $html .= "<table style='width:100%;border-collapse:collapse'><thead><tr style='background:#f5f5f5'><th style='padding:8px;text-align:left'>Producto</th><th style='padding:8px;text-align:center'>Cant</th><th style='padding:8px;text-align:right'>P/U</th><th style='padding:8px;text-align:right'>Subtotal</th></tr></thead><tbody>{$productosHtml}</tbody>";
        $html .= "<tfoot><tr><td colspan='3' style='padding:8px;text-align:right'><strong>Subtotal</strong></td><td style='padding:8px;text-align:right'>Bs " . number_format($subtotal, 2) . "</td></tr>";
        $html .= "<tr><td colspan='3' style='padding:8px;text-align:right'>IVA {$ivaPorcentaje}%</td><td style='padding:8px;text-align:right'>Bs " . number_format($iva, 2) . "</td></tr>";
        $html .= "<tr style='font-weight:bold;color:#2c3e50'><td colspan='3' style='padding:8px;text-align:right;border-top:2px solid #2c3e50'>TOTAL</td><td style='padding:8px;text-align:right;border-top:2px solid #2c3e50'>Bs " . number_format($total, 2) . "</td></tr>";
        $html .= "</tfoot></table>";
        $html .= "<p style='margin-top:20px;padding:15px;background:#f0f8ff;border-radius:5px'><strong>📞 ¿Necesitas ayuda?</strong><br>Contáctanos por WhatsApp o responde a este correo.</p>";
        $html .= "</div>";
        $html .= "<div style='text-align:center;padding:15px;color:#999;font-size:0.8em'>Proyectos Industriales del Centro &copy; " . date('Y') . "</div>";
        $html .= "</body></html>";

        enviarCorreo($cliente_email, "Cotización {$numero} - Proyectos Industriales del Centro", $html, 'Cotizaciones PIC');
    } catch (Throwable $e) {
        error_log("Error enviando email cotización: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Cotización solicitada exitosamente. Revisa tu correo para más detalles.',
        'data' => ['id' => $cotizacion_id, 'numero' => $numero]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al procesar solicitud: ' . $e->getMessage()]);
}
