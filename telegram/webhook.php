<?php
// Webhook del Bot de Telegram para PIC
// Recibe mensajes de clientes y los reenvía por correo
// Además responde automáticamente con información básica

require_once __DIR__ . '/config.php';

if (TELEGRAM_BOT_TOKEN === 'AQUI_VA_TU_TOKEN_DE_BOTFATHER') {
    http_response_code(500);
    echo json_encode(['error' => 'Bot no configurado. Sigue las instrucciones en config.example.php']);
    exit;
}

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

$message = $update['message'];
$chatId = $message['chat']['id'];
$text = $message['text'] ?? '';
$firstName = $message['from']['first_name'] ?? 'Cliente';
$username = $message['from']['username'] ?? '';

// Guardar mensaje localmente
$logDir = __DIR__ . '/mensajes';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/chat_' . $chatId . '.txt';
$logEntry = date('Y-m-d H:i:s') . " | $firstName ($username): $text\n";
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Enviar notificación por correo al administrador
if (defined('SMTP_FROM_EMAIL') || file_exists(__DIR__ . '/../config/database.php')) {
    try {
        $asunto = "Telegram PIC - Mensaje de $firstName";
        $mensajeHtml = "
        <html><body>
        <h3>Nuevo mensaje de Telegram</h3>
        <p><strong>Nombre:</strong> $firstName</p>
        <p><strong>Username:</strong> @$username</p>
        <p><strong>Chat ID:</strong> $chatId</p>
        <p><strong>Mensaje:</strong></p>
        <blockquote>" . nl2br(htmlspecialchars($text)) . "</blockquote>
        <hr><small>" . date('Y-m-d H:i:s') . "</small>
        </body></html>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: telegram@picindustrial.com\r\n";
        mail('Picca.ventas@gmail.com', $asunto, $mensajeHtml, $headers);
    } catch (Exception $e) {
        error_log("Error enviando correo desde Telegram bot: " . $e->getMessage());
    }
}

// Respuesta automática según el mensaje
$response = '';
$lowerText = mb_strtolower(trim($text));

if (preg_match('/\b(hola|buenas|saludos|hi|hello)\b/i', $lowerText)) {
    $response = "¡Hola $firstName! 👋 Bienvenido a Proyectos Industriales del Centro.\n\n"
        . "Soy el asistente virtual. Puedes consultar:\n"
        . "🔹 /productos - Ver nuestros productos\n"
        . "🔹 /contacto - Información de contacto\n"
        . "🔹 /horario - Nuestro horario\n"
        . "🔹 /precios - Consultar precios\n\n"
        . "O simplemente escríbenos tu consulta y te atenderemos pronto.";
} elseif (preg_match('/\b(producto|catálogo|catalogo|precio|lista)\b/i', $lowerText)) {
    $response = "📦 Puedes ver nuestro catálogo completo en:\n"
        . "https://picindustrial.com/proyecto/interfaz_usuario/pagina_modernizada.html\n\n"
        . "O escríbenos el producto que buscas y te daremos información.";
} elseif (preg_match('/\b(contacto|teléfono|telefono|dirección|ubicación|ubicacion|whatsapp)\b/i', $lowerText)) {
    $response = "📞 Información de contacto:\n\n"
        . "📍 Zona Industrial, Centro Michelena\n"
        . "📱 +58 0424-8323902\n"
        . "📧 Picca.ventas@gmail.com\n"
        . "🌐 https://picindustrial.com\n\n"
        . "Horario: Lun-Vie 8:00 AM - 5:00 PM";
} elseif (preg_match('/\b(horario|hora|abierto|abren)\b/i', $lowerText)) {
    $response = "🕐 Horario de atención:\n\n"
        . "Lunes a Viernes: 8:00 AM - 5:00 PM\n"
        . "Sábados: 8:00 AM - 12:00 PM\n"
        . "Domingos: Cerrado";
} elseif (preg_match('/\b(gracias|thanks|thank)\b/i', $lowerText)) {
    $response = "🙌 ¡Gracias a ti, $firstName! Si tienes más preguntas, aquí estaremos.";
} else {
    $response = "✅ Hemos recibido tu mensaje, $firstName. Te responderemos a la brevedad.\n\n"
        . "Mientras tanto, puedes visitar nuestra tienda:\n"
        . "🌐 https://picindustrial.com/proyecto/interfaz_usuario/pagina_modernizada.html\n\n"
        . "Usa /menu para ver las opciones disponibles.";
}

sendTelegramMessage($chatId, $response);

http_response_code(200);

function sendTelegramMessage($chatId, $text) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
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
    curl_close($ch);

    if ($error) {
        error_log("Telegram API error: $error");
    }
    return $result;
}
