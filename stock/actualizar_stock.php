<?php
// actualizar_stock.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../conexion/conexion.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

verificarCSRF();

try {
    $pdo = Database::getConnection();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['producto_id']) || !isset($input['cantidad'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Datos incompletos. Se requiere producto_id y cantidad.'
        ]);
        exit();
    }
    
    $producto_id = intval($input['producto_id']);
    $cantidad = intval($input['cantidad']);
    $usuario_id = isset($input['usuario_id']) ? intval($input['usuario_id']) : null;
    
    $pdo->beginTransaction();
    
    try {
        // Verificar stock disponible
        $sql = "SELECT stock, name as nombre FROM products WHERE id = ? FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$producto) {
            throw new PDOException("Producto no encontrado");
        }
        
        if ($producto['stock'] < $cantidad) {
            throw new PDOException("Stock insuficiente");
        }
        
        // Actualizar stock
        $sql_update = "UPDATE products SET stock = stock - ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$cantidad, $producto_id]);
        
        // Registrar movimiento en historial si hay usuario
        if ($usuario_id) {
            $stock_nuevo = $producto['stock'] - $cantidad;
            $sql_historial = "INSERT INTO historial_stock (producto_id, usuario_id, cantidad, stock_anterior, stock_nuevo, tipo, fecha) 
                              VALUES (?, ?, ?, ?, ?, 'venta', NOW())";
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([$producto_id, $usuario_id, $cantidad, $producto['stock'], $stock_nuevo]);
        }
        
        // Verificar si queda stock bajo después de la venta
        $sql_check = "SELECT stock FROM products WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$producto_id]);
        $nuevo_stock = $stmt_check->fetchColumn();
        
        $pdo->commit();
        
        auditoriaRegistrar('actualizar_stock', 'stock', "Stock actualizado: {$producto['nombre']} (ID: $producto_id) - Cantidad: $cantidad - Stock anterior: {$producto['stock']} - Stock actual: $nuevo_stock");

        $stockBajo = $nuevo_stock < 5;
        $agotado = $nuevo_stock <= 0;
        $whatsappEnviado = false;
        $telegramEnviado = false;

        if (($stockBajo || $agotado) && $nuevo_stock < $producto['stock']) {
            $wspConfig = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'whatsapp_%'")->fetchAll();
            $wsp = [];
            foreach ($wspConfig as $row) {
                $wsp[str_replace('whatsapp_', '', $row['clave'])] = $row['valor'];
            }
            if (!empty($wsp['api_url']) && !empty($wsp['api_token']) && !empty($wsp['numero']) && ($wsp['notificaciones_stock'] ?? '0') === '1') {
                $wspMensaje = "⚠️ *ALERTA DE STOCK - PIC*\n\n";
                $wspMensaje .= "📦 *Producto:* {$producto['nombre']}\n";
                $wspMensaje .= $agotado ? "🚫 *Estado:* AGOTADO\n" : "🔴 *Estado:* Stock Crítico\n";
                $wspMensaje .= "📉 *Stock anterior:* {$producto['stock']}\n";
                $wspMensaje .= "📊 *Stock actual:* $nuevo_stock\n";
                $wspMensaje .= "🔗 *Panel:* " . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost') . "/proyecto/panel_admin/panel_admin.php\n\n";
                $wspMensaje .= "_Revise el inventario para reabastecer._";

                $wspPayload = [
                    'messaging_product' => 'whatsapp',
                    'to' => $wsp['numero'],
                    'type' => 'text',
                    'text' => ['body' => $wspMensaje]
                ];

                $wspCh = curl_init($wsp['api_url']);
                curl_setopt_array($wspCh, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($wspPayload),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $wsp['api_token']],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 3
                ]);
                curl_exec($wspCh);
                $wspHttpCode = curl_getinfo($wspCh, CURLINFO_HTTP_CODE);
                curl_close($wspCh);
                $whatsappEnviado = $wspHttpCode >= 200 && $wspHttpCode < 300;
                if ($whatsappEnviado) {
                    auditoriaRegistrar('notificar_stock_automatico', 'whatsapp',
                        "Notificación automática de stock bajo enviada para {$producto['nombre']} (ID: $producto_id) - Stock: $nuevo_stock"
                    );
                }
            }

            $tgConfig = $pdo->query("SELECT clave, valor FROM configuracion_sistema WHERE clave LIKE 'telegram_%'")->fetchAll();
            $tg = [];
            foreach ($tgConfig as $row) {
                $tg[str_replace('telegram_', '', $row['clave'])] = $row['valor'];
            }
            if (!empty($tg['token']) && !empty($tg['chat_id'])) {
                $tgMensaje = "⚠️ *ALERTA DE STOCK - PIC*\n\n";
                $tgMensaje .= "📦 *Producto:* {$producto['nombre']}\n";
                $tgMensaje .= $agotado ? "🚫 *Estado:* AGOTADO\n" : "🔴 *Estado:* Stock Crítico\n";
                $tgMensaje .= "📉 *Stock anterior:* {$producto['stock']}\n";
                $tgMensaje .= "📊 *Stock actual:* $nuevo_stock\n";
                $tgMensaje .= "🆔 *ID:* $producto_id\n";
                $tgMensaje .= "🔗 *Panel:* " . ($_SERVER['HTTP_ORIGIN'] ?? 'http://localhost') . "/proyecto/panel_admin/panel_admin.php";

                $tgUrl = "https://api.telegram.org/bot{$tg['token']}/sendMessage";
                $tgPayload = [
                    'chat_id' => $tg['chat_id'],
                    'text' => $tgMensaje,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true
                ];

                $tgCh = curl_init($tgUrl);
                curl_setopt_array($tgCh, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($tgPayload),
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 5,
                    CURLOPT_CONNECTTIMEOUT => 3
                ]);
                $tgResponse = curl_exec($tgCh);
                $tgHttpCode = curl_getinfo($tgCh, CURLINFO_HTTP_CODE);
                curl_close($tgCh);
                $tgData = json_decode($tgResponse, true);
                $telegramEnviado = ($tgData['ok'] ?? false) === true;
                if ($telegramEnviado) {
                    auditoriaRegistrar('notificar_stock_automatico', 'telegram',
                        "Notificación automática de stock bajo enviada por Telegram para {$producto['nombre']} (ID: $producto_id) - Stock: $nuevo_stock"
                    );
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Stock actualizado correctamente',
            'data' => [
                'producto_id' => $producto_id,
                'nombre' => $producto['nombre'],
                'cantidad_vendida' => $cantidad,
                'stock_anterior' => $producto['stock'],
                'stock_actual' => $nuevo_stock,
                'stock_bajo' => $stockBajo,
                'agotado' => $agotado,
                'whatsapp_notificado' => $whatsappEnviado,
                'telegram_notificado' => $telegramEnviado
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor'
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>