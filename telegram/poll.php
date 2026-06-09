<?php
// Polling para el Bot de Telegram (funciona en localhost sin webhook)
// 
// USO:
//   1. Mantén esta página abierta en una pestaña del navegador
//      o configúrala como tarea programada en Windows.
//
//   2. Para ejecutar desde CLI:
//      php C:\laragon\www\proyecto\telegram\poll.php
//
//   3. Para Windows Task Scheduler (cada minuto):
//      php C:\laragon\www\proyecto\telegram\poll.php
//
//   4. En producción, usa webhook: visita set_webhook.php

require_once __DIR__ . '/config.php';

if (strpos(TELEGRAM_BOT_TOKEN, 'AQUI_VA_TU') !== false) {
    die('❌ ERROR: Token no configurado en config.php');
}

// Archivo para llevar el último update_id procesado
$offsetFile = __DIR__ . '/last_offset.txt';
$lastOffset = file_exists($offsetFile) ? intval(file_get_contents($offsetFile)) : 0;

// Obtener actualizaciones desde Telegram
$url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getUpdates?offset=" . ($lastOffset + 1) . "&timeout=10";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
$error = curl_error($ch);

if ($error) {
    echo "Error de conexión: $error\n";
    exit;
}

$data = json_decode($response, true);

if (!$data || !$data['ok']) {
    echo "Error de API: " . ($data['description'] ?? 'Desconocido') . "\n";
    exit;
}

if (empty($data['result'])) {
    echo "No hay mensajes nuevos.\n";
    exit;
}

$processed = 0;
foreach ($data['result'] as $update) {
    $updateId = $update['update_id'] ?? 0;
    $lastOffset = $updateId;
    
    if (!isset($update['message'])) continue;
    
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = $message['text'] ?? '';
    $firstName = $message['from']['first_name'] ?? 'Cliente';
    $username = $message['from']['username'] ?? '';
    
    echo "Procesando mensaje de $firstName: $text\n";
    
    // Guardar mensaje localmente
    $logDir = __DIR__ . '/mensajes';
    if (!is_dir($logDir)) mkdir($logDir, 0777, true);
    $logFile = $logDir . '/chat_' . $chatId . '.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | $firstName ($username): $text\n", FILE_APPEND);
    
    // Determinar respuesta
    $response = getAutoResponse($text, $firstName);
    
    if ($response) {
        sendTelegramMessage($chatId, $response);
        echo "Respuesta enviada a $firstName\n";
    }
    
    $processed++;
}

// Guardar el último offset procesado
file_put_contents($offsetFile, $lastOffset);

echo "Procesados $processed mensaje(s).\n";

// ===== FUNCIONES =====

function getAutoResponse($text, $firstName) {
    $lowerText = mb_strtolower(trim($text));
    
    if (preg_match('/\b(hola|buenas|saludos|hi|hello|buen dia|buenas tardes)\b/i', $lowerText)) {
        return "¡Hola $firstName! 👋 Bienvenido a **Proyectos Industriales del Centro**.\n\n"
            . "Soy el asistente virtual. Estas son mis opciones:\n\n"
            . "🔹 *Productos* — Ver nuestro catálogo\n"
            . "🔹 *Contacto* — Información de contacto\n"
            . "🔹 *Horario* — Horario de atención\n"
            . "🔹 *Precios* — Consultar precios\n\n"
            . "O simplemente escríbenos tu consulta y te atenderemos pronto.";
    }
    
    if (preg_match('/\b(producto|catálogo|catalogo|precio|lista|vender|comprar|tienda)\b/i', $lowerText)) {
        return "📦 *Catálogo de Productos*\n\n"
            . "Puedes ver nuestro catálogo completo en:\n"
            . "https://picindustrial.com/proyecto/interfaz_usuario/pagina_modernizada.html\n\n"
            . "O escríbenos el nombre del producto que buscas y te daremos información específica.";
    }
    
    if (preg_match('/\b(contacto|teléfono|telefono|dirección|ubicación|ubicacion|whatsapp|cómo|como|donde|dónde|chat)\b/i', $lowerText)) {
        return "📞 *Información de Contacto*\n\n"
            . "📍 *Dirección:* Zona Industrial, Centro Michelena\n"
            . "📱 *Teléfono:* +58 0424-8323902\n"
            . "📧 *Email:* Picca.ventas@gmail.com\n"
            . "🌐 *Web:* https://picindustrial.com\n\n"
            . "Horario: Lun-Vie 8:00 AM - 5:00 PM";
    }
    
    if (preg_match('/\b(horario|hora|abierto|abren|cierran)\b/i', $lowerText)) {
        return "🕐 *Horario de Atención*\n\n"
            . "Lunes a Viernes: 8:00 AM - 5:00 PM\n"
            . "Sábados: 8:00 AM - 12:00 PM\n"
            . "Domingos: Cerrado\n\n"
            . "📍 Zona Industrial, Centro Michelena";
    }
    
    if (preg_match('/\b(gracias|thanks|thank|ok|perfecto|excelente)\b/i', $lowerText)) {
        return "🙌 ¡Gracias a ti, $firstName! Si tienes más preguntas, aquí estaremos. \n\n"
            . "No olvides visitar nuestra tienda: https://picindustrial.com";
    }
    
    if (preg_match('/\b(menu|ayuda|help|opciones|comandos)\b/i', $lowerText)) {
        return "🤖 *Menú de Opciones*\n\n"
            . "Puedes preguntarme sobre:\n\n"
            . "🔹 *Productos* — Catálogo y precios\n"
            . "🔹 *Contacto* — Cómo ubicarnos\n"
            . "🔹 *Horario* — Horario de atención\n"
            . "🔹 *Precios* — Consultar precios\n\n"
            . "O simplemente escribe tu mensaje y te responderemos.";
    }
    
    // Respuesta por defecto para cualquier otro mensaje
    return "✅ Hemos recibido tu mensaje, $firstName. Te responderemos a la brevedad.\n\n"
        . "Mientras tanto, puedes:\n\n"
        . "🌐 Visitar nuestra tienda: https://picindustrial.com\n"
        . "📱 Llamarnos: +58 0424-8323902\n\n"
        . "Usa *Menú* para ver las opciones disponibles.";
}

function sendTelegramMessage($chatId, $text) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => false
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $result = curl_exec($ch);
    $error = curl_error($ch);

    if ($error) {
        echo "Error Telegram API: $error\n";
    }
    return $result;
}
