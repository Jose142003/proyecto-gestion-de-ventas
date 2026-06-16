<?php
require_once __DIR__ . '/helpers.php';

function botConectarDB() {
    $configFile = __DIR__ . '/../config/database.php';
    if (!file_exists($configFile)) return null;
    require_once $configFile;
    try {
        return new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Exception $e) {
        error_log("Error BD: " . $e->getMessage());
        return null;
    }
}

function botObtenerUrlTienda() {
    $url = getenv('APP_URL') ?: 'http://localhost/proyecto';
    return rtrim($url, '/') . '/interfaz_usuario/pagina_modernizada.html';
}

function botNormalizarTexto($texto) {
    $texto = mb_strtolower(trim($texto));
    $texto = preg_replace('/[?!.,;:()\[\]{}"\'\/\+¿¡]+/u', ' ', $texto);
    $texto = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $texto);
    $texto = preg_replace('/\s+/u', ' ', trim($texto));
    return $texto;
}

function botBuscarProducto($texto, $pdo) {
    if (!$pdo || strlen(trim($texto)) < 2) return null;
    try {
        $texto = trim($texto);
        $sqlBase = "SELECT id, name, price, stock, category, description, specs, image_url
                    FROM products WHERE active = 1 AND deleted_at IS NULL";

        // 1) Full phrase search
        $term = '%' . $texto . '%';
        $stmt = $pdo->prepare("$sqlBase AND (name LIKE ? OR description LIKE ? OR category LIKE ? OR specs LIKE ?) LIMIT 10");
        $stmt->execute([$term, $term, $term, $term]);
        $results = $stmt->fetchAll();
        if (count($results) > 0) return $results;

        // Extract significant words (≥2 chars)
        $words = array_filter(explode(' ', $texto), fn($w) => strlen(trim($w)) >= 2);
        $words = array_values(array_unique(array_map('trim', $words)));
        if (count($words) === 0) return null;

        // 2) Scoring search: collect ALL matches from ALL words, score by frequency
        $sql = "$sqlBase AND (name LIKE ? OR description LIKE ? OR category LIKE ? OR specs LIKE ?) LIMIT 10";
        $scored = [];

        foreach ($words as $w) {
            $variants = [$w];
            $stem = $w;
            if (str_ends_with($stem, 'es') && strlen($stem) > 5) $stem = substr($stem, 0, -2);
            elseif (str_ends_with($stem, 's') && strlen($stem) > 3) $stem = substr($stem, 0, -1);
            if ($stem !== $w && strlen($stem) >= 2) $variants[] = $stem;

            foreach ($variants as $v) {
                $p = '%' . $v . '%';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$p, $p, $p, $p]);
                foreach ($stmt->fetchAll() as $prod) {
                    $id = $prod['id'];
                    if (!isset($scored[$id])) {
                        $scored[$id] = $prod + ['_score' => 0];
                    }
                    $scored[$id]['_score']++;
                }
            }
        }

        if (!empty($scored)) {
            // Only products matching at least 2 words (or all words if <= 2)
            $minScore = count($words) >= 3 ? 2 : 1;
            $scored = array_filter($scored, fn($p) => $p['_score'] >= $minScore);
            if (empty($scored)) return null;
            usort($scored, fn($a, $b) => $b['_score'] - $a['_score']);
            $top = array_slice($scored, 0, 10);
            foreach ($top as &$p) unset($p['_score']);
            return $top;
        }

        return null;
    } catch (Exception $e) {
        error_log("Error buscando: " . $e->getMessage());
        return null;
    }
}

function botFormatearProductos($productos, $tiendaUrl) {
    if (!$productos || count($productos) === 0) return null;
    $texto = '';
    foreach (array_slice($productos, 0, 5) as $p) {
        $texto .= '*' . $p['name'] . "*\n";
        if (!empty($p['category'])) $texto .= "📂 {$p['category']}\n";
        $texto .= "💰 Precio: *Bs. " . number_format($p['price'], 2, ',', '.') . "*\n";
        if ($p['stock'] > 0) {
            $texto .= "📦 Stock: {$p['stock']} unidades\n";
            if ($p['stock'] <= 5) $texto .= "⚠️ Solo quedan {$p['stock']} unidades\n";
        } else {
            $texto .= "❌ Agotado\n";
        }
        if (!empty($p['description'])) {
            $desc = trim(mb_substr(strip_tags(html_entity_decode($p['description'])), 0, 250));
            if ($desc) $texto .= "📝 $desc\n";
        }
        if (!empty($p['specs'])) {
            $specs = trim(mb_substr(strip_tags(html_entity_decode($p['specs'])), 0, 250));
            if ($specs) $texto .= "🔧 $specs\n";
        }
        $texto .= "\n";
    }
    if (count($productos) > 5) $texto .= "Y " . (count($productos) - 5) . " productos más.\n";
    return $texto;
}

function botAprendizajeNormalizar($texto) {
    $stopWords = '/\b(a|al|ante|bajo|como|con|contra|cual|cuales|cuando|cuanto|cuantos|cuanta|cuantas|de|del|desde|donde|durante|e|el|ella|ellas|ellos|en|entre|es|esa|ese|eso|esos|esta|este|esto|fue|ha|hasta|la|las|le|les|lo|los|me|mi|mis|nada|no|nos|o|os|para|pero|por|porque|que|se|si|sin|sobre|su|sus|tambien|tengo|tu|tus|un|una|uno|unos|usted|ustedes|va|van|vas|y|ya|yo)\b/ui';
    $saludos = '/\b(hola|buenas|saludos|hi|hello|buen|buena|bueno|hey|ey|epa|gracias|chao|bye|adios|dale|ok|okay)\b/ui';
    $texto = mb_strtolower(trim($texto));
    $texto = preg_replace('/[?!.,;:()\[\]{}"\'\/\+¿¡]+/u', ' ', $texto);
    $texto = preg_replace($stopWords, ' ', $texto);
    $texto = preg_replace($saludos, ' ', $texto);
    $texto = preg_replace('/\b\d+\b/', '', $texto);
    $texto = preg_replace('/\s+/u', ' ', trim($texto));
    $palabras = array_filter(explode(' ', $texto), fn($w) => strlen(trim($w)) >= 3);
    $palabras = array_map('trim', $palabras);
    $palabras = array_map(function($w) {
        $w = rtrim($w, '.');
        if (str_ends_with($w, 'es') && strlen($w) > 4) return substr($w, 0, -2);
        if (str_ends_with($w, 's') && strlen($w) > 3 && !str_ends_with($w, 'as') && !str_ends_with($w, 'os')) return substr($w, 0, -1);
        return $w;
    }, $palabras);
    $palabras = array_unique($palabras);
    sort($palabras);
    return implode(' ', $palabras);
}

function botBuscarEnAprendizaje($pdo, $texto) {
    if (!$pdo) return null;
    try {
        $normalizada = botAprendizajeNormalizar($texto);
        if (strlen($normalizada) < 3) return null;
        $hash = md5($normalizada);
        $stmt = $pdo->prepare("SELECT respuesta FROM bot_aprendizaje WHERE pregunta_hash = ?");
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if ($row) {
            $pdo->prepare("UPDATE bot_aprendizaje SET frecuencia = frecuencia + 1, ultimo_acceso = NOW() WHERE pregunta_hash = ?")->execute([$hash]);
            return $row['respuesta'];
        }
    } catch (Exception $e) {
        error_log("Error leyendo aprendizaje: " . $e->getMessage());
    }
    return null;
}

function botGuardarEnAprendizaje($pdo, $textoOriginal, $respuesta) {
    if (!$pdo || empty($respuesta)) return;
    try {
        $normalizada = botAprendizajeNormalizar($textoOriginal);
        if (strlen($normalizada) < 3) return;
        $hash = md5($normalizada);
        $stmt = $pdo->prepare("SELECT id FROM bot_aprendizaje WHERE pregunta_hash = ?");
        $stmt->execute([$hash]);
        if ($stmt->fetch()) {
            $pdo->prepare("UPDATE bot_aprendizaje SET respuesta = ?, frecuencia = frecuencia + 1, ultimo_acceso = NOW(), pregunta_original = COALESCE(NULLIF(?, ''), pregunta_original) WHERE pregunta_hash = ?")->execute([$respuesta, $textoOriginal, $hash]);
        } else {
            $pdo->prepare("INSERT INTO bot_aprendizaje (pregunta_hash, pregunta_normalizada, pregunta_original, respuesta, frecuencia, creado_en, ultimo_acceso) VALUES (?, ?, ?, ?, 1, NOW(), NOW())")->execute([$hash, $normalizada, $textoOriginal, $respuesta]);
        }
    } catch (Exception $e) {
        error_log("Error guardando aprendizaje: " . $e->getMessage());
    }
}

function telegramEnviarConBotones($chatId, $text, $buttons, $parseMode = 'Markdown') {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => $parseMode, 'disable_web_page_preview' => true];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function telegramEditarMensaje($chatId, $messageId, $text, $buttons = null, $parseMode = 'Markdown') {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/editMessageText";
    $data = [
        'chat_id' => $chatId,
        'message_id' => $messageId,
        'text' => $text,
        'parse_mode' => $parseMode,
        'disable_web_page_preview' => true
    ];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function telegramEnviarFoto($chatId, $fotoUrl, $caption, $buttons = null) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendPhoto";
    $data = ['chat_id' => $chatId, 'photo' => $fotoUrl, 'caption' => $caption, 'parse_mode' => 'Markdown'];
    if ($buttons) $data['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function telegramSendAction($chatId, $action = 'typing') {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendChatAction";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chatId, 'action' => $action]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function botEnviarProductoConBotones($chatId, $producto, $tiendaUrl) {
    $texto = '*' . $producto['name'] . "*\n";
    $texto .= "💰 Precio: *Bs. " . number_format($producto['price'], 2, ',', '.') . "*\n";
    $texto .= "📦 Stock: " . ($producto['stock'] > 0 ? "{$producto['stock']} unidades" : "Agotado") . "\n";
    $texto .= "📂 {$producto['category']}\n";
    if (!empty($producto['description'])) {
        $desc = trim(mb_substr(strip_tags(html_entity_decode($producto['description'])), 0, 300));
        if ($desc) $texto .= "\n$desc\n";
    }
    $botones = [
        [['text' => 'Ver más detalles', 'callback_data' => 'ver_producto_' . $producto['id']]],
        [['text' => 'Cotizar', 'callback_data' => 'cotizar_' . $producto['id']]],
    ];
    if (!empty($producto['image_url'])) {
        telegramEnviarFoto($chatId, $producto['image_url'], $texto, $botones);
    } else {
        telegramEnviarConBotones($chatId, $texto, $botones);
    }
}

function botManejarCallbackQuery($callbackData, $chatId, $messageId, $callbackId) {
    $pdo = botConectarDB();
    $tiendaUrl = botObtenerUrlTienda();

    if (str_starts_with($callbackData, 'ver_producto_')) {
        $id = intval(substr($callbackData, 13));
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1 AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p) {
            $detalle = '*' . $p['name'] . "*\n\n";
            $detalle .= "💰 Precio: *Bs. " . number_format($p['price'], 2, ',', '.') . "*\n";
            $detalle .= "📦 Stock: " . ($p['stock'] > 0 ? "{$p['stock']} unidades" : "Agotado") . "\n";
            $detalle .= "📂 Categoría: {$p['category']}\n";
            if (!empty($p['description'])) {
                $desc = trim(strip_tags(html_entity_decode($p['description'])));
                if ($desc) $detalle .= "\n📝 $desc\n";
            }
            if (!empty($p['specs'])) {
                $specs = trim(strip_tags(html_entity_decode($p['specs'])));
                if ($specs) $detalle .= "\n🔧 *Especificaciones:*\n$specs\n";
            }
            if (!empty($p['weight'])) $detalle .= "\n⚖️ Peso: {$p['weight']} kg\n";
            if (!empty($p['dimensions'])) $detalle .= "📐 Dimensiones: {$p['dimensions']}\n";
            $detalle .= "\n🔗 Tienda: $tiendaUrl";
            telegramEnviarConBotones($chatId, $detalle, [
                [['text' => 'Cotizar', 'callback_data' => 'cotizar_' . $p['id']]]
            ]);
        }
    } elseif (str_starts_with($callbackData, 'cotizar_')) {
        $id = intval(substr($callbackData, 8));
        $stmt = $pdo->prepare("SELECT name, price FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if ($p) {
            telegramEnviarConBotones($chatId,
                "Gracias por tu interés en *{$p['name']}*.\n\n"
                . "Precio: *Bs. " . number_format($p['price'], 2, ',', '.') . "*\n\n"
                . "Para procesar tu cotización, un asesor se pondrá en contacto contigo.\n"
                . "También puedes llamarnos al +58 0424-8323902 o escribir a Picca.ventas@gmail.com",
                [['text' => 'Volver', 'callback_data' => 'volver']]
            );
        }
    } elseif ($callbackData === 'volver') {
        telegramEditarMensaje($chatId, $messageId, "¿En qué más puedo ayudarte?", [
            [['text' => 'Ver catálogo', 'callback_data' => 'ver_categorias']]
        ]);
    } elseif ($callbackData === 'ver_categorias') {
        $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE active = 1 AND deleted_at IS NULL AND category IS NOT NULL AND category != '' ORDER BY category");
        $cats = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $botones = [];
        foreach ($cats as $cat) {
            $botones[] = [['text' => $cat, 'callback_data' => 'categoria_' . $cat]];
        }
        $botones[] = [['text' => 'Buscar producto', 'callback_data' => 'buscar_producto']];
        telegramEditarMensaje($chatId, $messageId, "📂 *Categorías disponibles:*\nSelecciona una para ver sus productos:", $botones);
    } elseif (str_starts_with($callbackData, 'categoria_')) {
        $cat = substr($callbackData, 10);
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE active = 1 AND deleted_at IS NULL AND category = ? LIMIT 15");
        $stmt->execute([$cat]);
        $prods = $stmt->fetchAll();
        if ($prods) {
            $texto = "*📂 {$cat}*\n\n";
            foreach ($prods as $pr) {
                $texto .= "• {$pr['name']} — Bs. " . number_format($pr['price'], 2, ',', '.') . ($pr['stock'] > 0 ? "" : " ❌ Agotado") . "\n";
            }
            $texto .= "\n🔗 $tiendaUrl";
            telegramEnviarConBotones($chatId, $texto, [
                [['text' => 'Ver categorías', 'callback_data' => 'ver_categorias']]
            ]);
        } else {
            telegramEditarMensaje($chatId, $messageId, "No hay productos en esta categoría.", [
                [['text' => 'Ver categorías', 'callback_data' => 'ver_categorias']]
            ]);
        }
    } elseif ($callbackData === 'buscar_producto') {
        telegramEditarMensaje($chatId, $messageId, "Escribe el nombre del producto que buscas y te mostraré la información disponible.");
    } elseif ($callbackData === 'solicitar_pdf') {
        $pdfPath = __DIR__ . '/../catalogo/catalogo_pic.pdf';
        if (file_exists($pdfPath)) {
            telegramEnviarDocumento(TELEGRAM_BOT_TOKEN, $chatId, $pdfPath, "Aquí tienes nuestro catálogo completo");
            $resp = "Te envié el catálogo en PDF. También puedes consultarlo online:\n$tiendaUrl";
        } else {
            $resp = "El catálogo no está disponible en este momento. Consulta nuestra tienda online:\n$tiendaUrl";
        }
        telegramEnviarConBotones($chatId, $resp, [
            [['text' => '📂 Ver categorías', 'callback_data' => 'ver_categorias']]
        ]);
    } elseif ($callbackData === 'carrito_ver') {
        telegramEnviarConBotones($chatId, "Puedes ver tu carrito y realizar tu compra en nuestra tienda online:\n$tiendaUrl", [
            [['text' => 'Ir a la tienda', 'url' => $tiendaUrl]]
        ]);
    }
    // Acknowledge callback
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/answerCallbackQuery";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['callback_query_id' => $callbackId]),
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function botCargarVinculos(): array {
    $file = __DIR__ . '/mensajes/vinculos.json';
    if (!file_exists($file)) return [];
    $content = file_get_contents($file);
    if (!$content) return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function botGuardarVinculo(string $chatId, array $data): void {
    $vinculos = botCargarVinculos();
    $vinculos[$chatId] = $data;
    $file = __DIR__ . '/mensajes/vinculos.json';
    file_put_contents($file, json_encode($vinculos, JSON_PRETTY_PRINT));
}

function botBuscarPedidoPorNumero(string $numero, $pdo): ?array {
    if (!$pdo) return null;
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.nombre as user_nombre, u.correo
            FROM pedidos p
            LEFT JOIN users u ON p.usuario_id = u.id
            WHERE p.numero_pedido = ?
        ");
        $stmt->execute([$numero]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pedido ?: null;
    } catch (Exception $e) {
        error_log("Error buscando pedido: " . $e->getMessage());
        return null;
    }
}

function botEnviarResultadosConImagenes($chatId, $productos, $tiendaUrl): ?string {
    if (!$productos || count($productos) === 0) return null;
    $texto = '';
    $enviados = 0;
    foreach ($productos as $p) {
        if ($enviados >= 10) break;
        if (!empty($p['image_url']) && $enviados < 3) {
            botEnviarProductoConBotones($chatId, $p, $tiendaUrl);
            $enviados++;
        } else {
            $texto .= '*' . $p['name'] . "*\n";
            if (!empty($p['category'])) $texto .= "📂 {$p['category']}\n";
            $texto .= "💰 Precio: *Bs. " . number_format($p['price'], 2, ',', '.') . "*\n";
            if ($p['stock'] > 0) {
                $texto .= "📦 Stock: {$p['stock']} unidades\n";
                if ($p['stock'] <= 5) $texto .= "⚠️ Solo quedan {$p['stock']} unidades\n";
            } else {
                $texto .= "❌ Agotado\n";
            }
            if (!empty($p['description'])) {
                $desc = trim(mb_substr(strip_tags(html_entity_decode($p['description'])), 0, 200));
                if ($desc) $texto .= "📝 $desc\n";
            }
            $texto .= "\n";
            $enviados++;
        }
    }
    if (!$texto && $enviados > 0) return '';
    $texto .= "🔗 $tiendaUrl";
    return $texto;
}

function botEstadoPedidoTexto(string $estado): string {
    $mapa = [
        'pendiente' => '🟡 Pendiente — estamos revisando tu pedido',
        'procesando' => '🔵 Procesando — ya estamos preparando tu pedido',
        'enviado' => '🟢 Enviado — tu pedido está en camino',
        'entregado' => '✅ Entregado — recibiste tu pedido',
        'cancelado' => '🔴 Cancelado',
        'facturado' => '📄 Facturado — la factura ya fue generada',
    ];
    return $mapa[$estado] ?? "⚪ $estado";
}

function botResponder($text, $chatId, $firstName = 'Usuario') {
    $lowerText = botNormalizarTexto($text);
    $tiendaUrl = botObtenerUrlTienda();
    $pdo = botConectarDB();

    // 0. Rate limiting — prevent spam
    $rateFile = __DIR__ . '/mensajes/rate_' . $chatId . '.json';
    $rateData = [];
    if (file_exists($rateFile)) {
        $rateContent = file_get_contents($rateFile);
        if ($rateContent) $rateData = json_decode($rateContent, true) ?? [];
    }
    $now = time();
    $rateData = array_values(array_filter($rateData, fn($t) => $t > $now - 60));
    if (count($rateData) >= 20) {
        return "⏳ Estás enviando muchos mensajes. Espera un momento antes de continuar.";
    }
    $rateData[] = $now;
    file_put_contents($rateFile, json_encode(array_slice($rateData, -50)));

    // 0a. /start command — always show welcome
    if ($text === '/start') {
        telegramEnviarConBotones($chatId, "¡Bienvenido $firstName al asistente de *PIC*! 🤖\n\n"
             . "Soy el bot de *Proyectos Industriales del Centro*.\n\n"
             . "Puedo ayudarte con:\n"
             . "• *Precios y disponibilidad* de productos\n"
             . "• *Características técnicas* detalladas\n"
             . "• *Información de envíos* a todo Venezuela\n"
             . "• *Métodos de pago* disponibles\n"
             . "• Enviarte nuestro *catálogo PDF*\n\n"
             . "¿Qué producto buscas?", [
            [['text' => '📂 Ver catálogo', 'callback_data' => 'ver_categorias']],
            [['text' => '📄 Catálogo PDF', 'callback_data' => 'solicitar_pdf']],
            [['text' => '🌐 Tienda online', 'url' => $tiendaUrl]]
        ]);
        return null;
    }

    // 0b. Help command
    if ($text === '/help') {
        telegramEnviarConBotones($chatId, "🤖 *Ayuda - PIC Bot*\n\n"
            . "Puedes preguntarme:\n"
            . "• *Productos:* \"precio del at8n\", \"venden autonics\"\n"
            . "• *Catálogo:* \"quiero el catálogo\", \"pasame el pdf\"\n"
            . "• *Envíos:* \"hacen envíos\", \"cuánto tarda\"\n"
            . "• *Pagos:* \"métodos de pago\", \"cómo pagar\"\n"
            . "• *Compra:* \"cómo comprar\", \"quiero comprar\"\n"
            . "• *Pedidos:* \"cómo va mi pedido\" o /pedido\n"
            . "• *Ofertas:* /ofertas o /destacados\n\n"
            . "Comandos:\n"
            . "/pedido — consultar estado de tu pedido\n"
            . "/vincular — vincular tu cuenta de Telegram\n"
            . "/ofertas — ver productos destacados\n\n"
            . "También puedes explorar las categorías con el botón.", [
            [['text' => '📂 Ver catálogo', 'callback_data' => 'ver_categorias']]
        ]);
        return null;
    }

    // 0c. /vincular — link Telegram with user account
    if (preg_match('/^\/vincular\s+/', $text)) {
        $credencial = trim(substr($text, 10));
        $pdo = botConectarDB();
        if (!$pdo) {
            return "Lo siento, no pude conectar con la base de datos para verificar tu cuenta.";
        }
        try {
            $stmt = $pdo->prepare("SELECT id, nombre, correo FROM users WHERE correo = ? OR cedula = ?");
            $stmt->execute([$credencial, $credencial]);
            $user = $stmt->fetch();
            if ($user) {
                $upd = $pdo->prepare("UPDATE users SET telegram_chat_id = ? WHERE id = ?");
                $upd->execute([$chatId, $user['id']]);
                botGuardarVinculo($chatId, ['user_id' => $user['id'], 'nombre' => $user['nombre'], 'correo' => $user['correo'], 'vinculado' => date('Y-m-d H:i:s')]);
                telegramEnviarConBotones($chatId,
                    "✅ *¡Cuenta vinculada exitosamente!*\n\n"
                    . "Hola {$user['nombre']}, ahora puedes:\n"
                    . "• Consultar tus pedidos con: `/pedido PED-2026-XXXXXX`\n"
                    . "• Ver productos destacados con: `/ofertas`\n"
                    . "• Recibir notificaciones de tus pedidos aquí mismo.\n\n"
                    . "Gracias por vincular tu cuenta 🤖",
                    [['text' => 'Ver catálogo', 'callback_data' => 'ver_categorias']]
                );
                return null;
            } else {
                return "No encontré un usuario con ese correo o cédula. Asegúrate de usar el mismo que registraste en la tienda online.\n\n👉 $tiendaUrl";
            }
        } catch (Exception $e) {
            error_log("Error vinculando: " . $e->getMessage());
            return "Ocurrió un error al vincular tu cuenta. Intenta de nuevo más tarde.";
        }
    }
    if ($text === '/vincular') {
        return "Para vincular tu cuenta de Telegram con tu cuenta de PIC, escribe:\n\n"
             . "`/vincular tu_correo@ejemplo.com`\n\n"
             . "o\n\n"
             . "`/vincular tu_cedula`\n\n"
             . "Usa el mismo correo o cédula con la que te registraste en nuestra tienda online:\n$tiendaUrl";
    }

    // 0d. /pedido — check order status
    if (preg_match('/^\/pedido\s+/', $text)) {
        $numero = strtoupper(trim(substr($text, 8)));
        if (!preg_match('/^PED-\d{4}-\d{6}$/', $numero)) {
            return "El formato del número de pedido debe ser:\n`PED-2026-XXXXXX`\n\nPuedes encontrar este número en el correo de confirmación de tu compra.";
        }
        $pdo = botConectarDB();
        $pedido = botBuscarPedidoPorNumero($numero, $pdo);
        if (!$pedido) {
            return "No encontré ningún pedido con el número *$numero*.\n\nVerifica el número e inténtalo de nuevo.";
        }
        $detalles = $pdo ? $pdo->prepare("SELECT pd.*, pr.name as producto_nombre FROM pedido_detalles pd JOIN products pr ON pd.producto_id = pr.id WHERE pd.pedido_id = ?") : null;
        if ($detalles) {
            $detalles->execute([$pedido['id']]);
            $items = $detalles->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $items = [];
        }
        $resp = "*📋 Estado del Pedido*\n\n";
        $resp .= "🧾 *Número:* {$pedido['numero_pedido']}\n";
        $resp .= "📅 *Fecha:* " . date('d/m/Y H:i', strtotime($pedido['created_at'] ?? $pedido['fecha_pedido'])) . "\n";
        $resp .= "📌 *Estado:* " . botEstadoPedidoTexto($pedido['estado']) . "\n\n";
        if (!empty($pedido['metodo_pago'])) $resp .= "💳 *Pago:* {$pedido['metodo_pago']}\n";
        if (!empty($pedido['referencia_pago'])) $resp .= "🔢 *Ref:* {$pedido['referencia_pago']}\n";
        $resp .= "\n*Productos:*\n";
        foreach ($items as $it) {
            $resp .= "• {$it['producto_nombre']} x{$it['cantidad']} = Bs. " . number_format($it['subtotal'], 2, ',', '.') . "\n";
        }
        if (count($items) === 0) $resp .= "_(sin detalle)_\n";
        $resp .= "\n💰 *Total:* Bs. " . number_format($pedido['total'], 2, ',', '.') . "\n\n";
        $tel = "0424-8323902";
        $resp .= "¿Tienes dudas? Escríbenos al $tel";
        $resp .= "\n\n🔗 $tiendaUrl";
        return $resp;
    }
    if ($text === '/pedido') {
        return "Para consultar el estado de tu pedido, escribe:\n\n"
             . "`/pedido PED-2026-XXXXXX`\n\n"
             . "Reemplaza PED-2026-XXXXXX con el número de tu pedido (lo encuentras en el correo de confirmación).";
    }

    // 0e. /ofertas — featured/featured products
    if ($text === '/ofertas' || $text === '/destacados' || preg_match('/\b(?:ofertas?|destacados?|promociones?|descuentos?|especiales?|recomendados?|mas\s+vendidos?)\b/ui', $lowerText)) {
        $pdo = botConectarDB();
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT id, name, price, stock, category, description, specs, image_url FROM products WHERE active = 1 AND deleted_at IS NULL AND (is_featured = 1 OR stock > 0) ORDER BY is_featured DESC, views_count DESC LIMIT 8");
                $stmt->execute();
                $featured = $stmt->fetchAll();
                if ($featured) {
                    $restante = botEnviarResultadosConImagenes($chatId, $featured, $tiendaUrl);
                    if ($restante !== null && $restante !== '') {
                        telegramEnviarConBotones($chatId, "🔥 *Productos Destacados*\n\n$restante", [
                            [['text' => '📂 Ver catálogo', 'callback_data' => 'ver_categorias']]
                        ]);
                    }
                    return null;
                }
            } catch (Exception $e) {
                error_log("Error ofertas: " . $e->getMessage());
            }
        }
        return "Actualmente no tenemos productos destacados. Consulta nuestro catálogo completo:\n$tiendaUrl";
    }

    // 0.1. Gratitude — respond only if no product/business intent
    if (preg_match('/\b(?:gracias|graciass|gracis|grax|thank|thanks|thx|te\s+agradezco|muy\s+amable|excelente|perfecto|genial|buena\s+(?:atencion|respuesta|informacion)|eres\s+un\s+(?:gran\s+)?(?:ayuda|apoyo)|me\s+ayudaste|quedo\s+claro|claro|entendido|resuelto|solucionado)\b/ui', $lowerText)
        && !preg_match('/\b(?:producto|precio|costo|cuesta|tienen|tiene|venden|busco|necesito|quiero|dame|pago|pagar|envio|entrega|comprar|sensor|cable|contactor|breaker|variador|fuente|rele)\b/ui', $lowerText)) {
        $resp = "¡De nada $firstName! 😊 Estoy aquí para ayudarte.\n\n¿Necesitas algo más?";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 0.2. Learning cache
    if ($pdo) {
        $aprendido = botBuscarEnAprendizaje($pdo, $text);
        if ($aprendido !== null) return $aprendido . "\n\n[Respuesta aprendida]";
    }

    // 1. Greeting (only if no product/business intent)
    if (preg_match('/\b(?:hola|buenas?|bueno|buenos|saludos|hi|hello|buen\s+(?:dia|d[ií]a|tardes?)|que\s+tal|que\s+mas|como\s+estas?|muy\s+buenas|epa|ey|hey)\b/ui', $lowerText)
        && !preg_match('/\b(?:producto|precio|costo|cuesta|tienen|tiene|venden|busco|necesito|quiero|dame|pago|paho|pagar|pagos|envio|envian|entrega|comprar|sensor|contactor|breaker|variador|fuente|cable|rele|guardamotor)\b/ui', $lowerText)) {
        $resp = "¡Hola $firstName! Soy el asistente de PIC.\n\n"
              . "Puedo ayudarte con:\n"
              . "• Precios y disponibilidad de productos\n"
              . "• Características técnicas\n"
              . "• Información de envíos\n"
              . "• Métodos de pago\n\n"
              . "¿Qué producto buscas?";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // Extract search terms and run product search (for all non-greeting messages)
    $searchTerm = trim(preg_replace('/\b(hola|buenas|saludos|gracias|por\s+favor|quiero|necesito|busco|dame|precio|precios|costo|tienen|tiene|hay|venden|vende|pago|pagos|pagar|paho|metodo|metodos|entrega|envio|envios|comprar|compra|como|donde|cual|cuales|cuanto|me|te|le|lo|la|no|ni|un|una|el|los|las|de|del|que|se|en|por|para|con|sin|y|o|a|su|sus|tu|es|se|mas|muy|solo|tambien|sabes|dime|info|informacion|las|los|del|al|producto|productos|existe|existen|nuevo|busqueda|listado|listado|todos|varios|muchos|pocos|algunos|algun|nombre|llamo|llamas|soy|eres|apellido|llamarme|llamarse|apellidos)\b/ui', '', $lowerText));
    $productos = strlen($searchTerm) >= 2 ? botBuscarProducto($searchTerm, $pdo) : null;
    $productResp = $productos ? botFormatearProductos($productos, $tiendaUrl) : null;

    // 1.5. Catalog intent
    if (preg_match('/\b(?:catalogo|lista\s+de\s+(?:precios|productos)|tienen\s+catalogo|quiero\s+(?:ver|el)\s+catalogo|envia[rs]?\s+(?:me\s+)?el\s+catalogo|mande\s+(?:me\s+)?el\s+catalogo|brochure|folleto|que\s+(?:productos\s+)?venden|que\s+productos\s+(?:tienen|manejan)|productos\s+disponibles|que\s+vende\s+la\s+empresa|que\s+ofrecen|que\s+tienen\s+disponible|todos\s+los\s+productos)\b/ui', $lowerText)
        && !preg_match('/\b(?:precio|cuanto\s+cuesta|especificaciones)\b/ui', $lowerText)) {
        $pdfPath = __DIR__ . '/../catalogo/catalogo_pic.pdf';
        if (file_exists($pdfPath)) {
            telegramEnviarDocumento(TELEGRAM_BOT_TOKEN, $chatId, $pdfPath, "Aquí tienes nuestro catálogo completo");
            $resp = "Te envié el catálogo en PDF. También puedes consultarlo online:\n$tiendaUrl";
        } else {
            $resp = "El catálogo no está disponible en este momento. Consulta nuestra tienda online:\n$tiendaUrl";
        }
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 2. Shipping / delivery intent (include product results if found)
    if (preg_match('/\b(?:env[ií]o|env[ií]os|env[ií]an|env[ií]amos|enviad[ao]|entrega|mrw|tealca|zoom|domicilio|llegada|llegar[aá]n|gu[ií]a|flete|delivery|retiro|retirar|rastre[ao]|cuando\s+(?:llega|llegar[aá]|envian)|como\s+(?:llega|llegara|envian)|tiempo\s+de\s+entrega)\b/ui', $lowerText)) {
        $resp = "*Información de Envíos*\n\n"
              . "Realizamos envíos a todo Venezuela a través de:\n"
              . "• MRW — 24 a 72 horas hábiles\n"
              . "• Tealca — 24 a 72 horas hábiles\n"
              . "• Zoom — 24 a 72 horas hábiles\n\n"
              . "Una vez procesado tu pedido, te enviamos el número de guía para que puedas rastrear tu envío.\n\n"
              . "Si tienes un pedido en curso y quieres saber si fue enviado, escríbenos con tu número de pedido o nombre completo y lo consultamos.";
        if ($productResp) $resp .= "\n\n*Productos relacionados:*\n$productResp\n\n🔗 $tiendaUrl";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 3. Payment intent (include product results if found)
    if (preg_match('/\b(?:pago|pagos|pagar|pagas?|pagamos|pague|paho|pagan|pagando|transferencia|transferir|d[oó]lares?|dolar|divisas?|tarjeta|tarjetas|cr[eé]dito|d[eé]bito|contado|banco|bancos|dep[oó]sito|depositar|efectivo|pago\s+m[oó]vil|pagomovil|forma\s+de\s+pago|formas?\s+de\s+pago|como\s+pago|como\s+pagar|m[eé]tod[oa]\s+de\s+pago|m[eé]tod[oa]s?\s+de\s+pago|metod[oa]\s+de\s+pago|metodos\s+de\s+paho|que\s+pagos|que\s+metodos|como\s+se\s+paga|como\s+se\s+cancela)\b/ui', $lowerText)) {
        $resp = "*Métodos de Pago*\n\n"
              . "Aceptamos:\n\n"
               . "🏦 *Transferencia Bancaria* — disponible 24/7\n"
               . "📱 *Pago Móvil* — Mercantil y Provincial\n"
              . "💵 *Efectivo* — retirando en tienda\n"
              . "💲 *Dólares* — tasa BCV del día\n\n"
              . "Todos los precios están en Bolívares (Bs.) y no incluyen IVA.\n"
              . "Al pagar de contado aplican descuentos especiales.";
        if ($productResp) {
            $resp .= "\n\n*Productos relacionados:*\n$productResp\n\n🔗 $tiendaUrl";
        } else {
            $resp .= "\n\n¿Qué producto te interesa? Te paso el precio exacto.";
        }
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 3.5. Purchase intent (include product results if found)
    if (preg_match('/\b(?:comprar|compra|compro|adquirir|como\s+(?:comprar|adquirir|obtener|hago)|quiero\s+(?:comprar|adquirir)|donde\s+(?:comprar|consigo|encuentro|adquiero)|como\s+lo\s+(?:compro|obtengo|adquiero)|como\s+se\s+compra)\b/ui', $lowerText)
        && !preg_match('/\b(?:env[ií]o|pago|pagar|transferencia)\b/ui', $lowerText)) {
        $resp = "¡Claro $firstName! Puedes comprar nuestros productos de dos formas:\n\n"
              . "1️⃣ *Tienda Online* — visita nuestro catálogo y compra en línea:\n"
              . "$tiendaUrl\n\n"
              . "2️⃣ *Contactando a un asesor* — escríbenos por WhatsApp o llámanos:\n"
              . "📞 +58 0424-8323902\n"
              . "📧 Picca.ventas@gmail.com\n\n"
              . "¿Qué producto te interesa? ¡Dime el nombre y te paso precio y disponibilidad!";
        if ($productResp) $resp .= "\n\n*Productos relacionados:*\n$productResp";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 3.7. Order tracking intent ("mi pedido", "cómo va mi envío", "número de pedido")
    $numeroPedido = null;
    if (preg_match('/\b(?:pedido|pedi|compre|compr[eé]|carrito?|orden|compra)\b/ui', $lowerText)
        && preg_match('/\b(?:estado?|como\s+va|donde\s+esta|cu[aá]ndo\s+(?:llega|llegar[aá]|envian)|ya\s+(?:esta|fue|enviaron)|rastre[ao]|seguimiento|consulta|status|situacion|proceso|como\s+voy|va\s+mi|saber\s+como|verificar|ver\s+mi|consultar|informaci[oó]n)\b/ui', $lowerText)) {
        // Try to extract order number from message
        if (preg_match('/PED[-\s]?\d{4}[-\s]?\d{4,6}/i', $text, $m)) {
            $numeroPedido = strtoupper(preg_replace('/[\s-]+/', '-', $m[0]));
            if (!preg_match('/^PED-\d{4}-\d{6}$/', $numeroPedido)) {
                $numeroPedido = null;
            }
        }
        // If no order number, try to find linked user
        if (!$numeroPedido) {
            $vinculos = botCargarVinculos();
            if (isset($vinculos[$chatId]) && !empty($vinculos[$chatId]['user_id'])) {
                try {
                    $uid = $vinculos[$chatId]['user_id'];
                    $stmt = $pdo->prepare("SELECT numero_pedido FROM pedidos WHERE usuario_id = ? ORDER BY created_at DESC LIMIT 1");
                    $stmt->execute([$uid]);
                    $ultimo = $stmt->fetchColumn();
                    if ($ultimo) $numeroPedido = $ultimo;
                } catch (Exception $e) {
                    error_log("Error obteniendo ultimo pedido: " . $e->getMessage());
                }
            }
        }
        if ($numeroPedido) {
            $pedido = botBuscarPedidoPorNumero($numeroPedido, $pdo);
            if ($pedido) {
                $detalles = $pdo ? $pdo->prepare("SELECT pd.*, pr.name as producto_nombre FROM pedido_detalles pd JOIN products pr ON pd.producto_id = pr.id WHERE pd.pedido_id = ?") : null;
                if ($detalles) {
                    $detalles->execute([$pedido['id']]);
                    $items = $detalles->fetchAll(PDO::FETCH_ASSOC);
                } else { $items = []; }
                $resp = "*📋 Estado de tu Pedido*\n\n";
                $resp .= "🧾 *Número:* {$pedido['numero_pedido']}\n";
                $resp .= "📅 *Fecha:* " . date('d/m/Y H:i', strtotime($pedido['created_at'] ?? $pedido['fecha_pedido'])) . "\n";
                $resp .= "📌 *Estado:* " . botEstadoPedidoTexto($pedido['estado']) . "\n";
                if (!empty($pedido['metodo_pago'])) $resp .= "💳 *Pago:* {$pedido['metodo_pago']}\n\n";
                elseif (!empty($items)) $resp .= "\n";
                $resp .= "*Productos:*\n";
                foreach ($items as $it) {
                    $resp .= "• {$it['producto_nombre']} x{$it['cantidad']} = Bs. " . number_format($it['subtotal'], 2, ',', '.') . "\n";
                }
                $resp .= "\n💰 *Total:* Bs. " . number_format($pedido['total'], 2, ',', '.') . "\n\n";
                $resp .= "📞 ¿Dudas? Llámanos al 0424-8323902\n";
                $resp .= "🔗 $tiendaUrl";
                if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
                return $resp;
            }
        }
        // No order found — prompt user
        $resp = "Para consultar el estado de tu pedido, necesito el número.\n\n"
              . "Escribe: `/pedido PED-2026-XXXXXX`\n\n"
              . "Si no tienes el número, primero vincula tu cuenta:\n"
              . "`/vincular tu_correo@ejemplo.com`\n\n"
              . "🔗 $tiendaUrl";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 4. Product results (standalone) with images
    if ($productResp) {
        $restante = botEnviarResultadosConImagenes($chatId, $productos, $tiendaUrl);
        if ($restante === null || $restante === '') {
            $respTexto = $productResp;
        } else {
            $respTexto = $restante;
        }
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $respTexto);
        return $respTexto;
    }

    // 4.5. Off-topic: schedule, location, about us
    if (preg_match('/\b(?:horario|horarios|atienden|abren|abierto|cierran|cuando\s+(?:abren|atienden)|hasta\s+que\s+hora|fines?\s+de\s+semana|s[aá]bado|domingo|abrimos)\b/ui', $lowerText)) {
        $resp = "*Horario de Atención*\n\n"
              . "🕐 Lunes a Viernes: 8:00 AM - 5:00 PM\n"
              . "🕐 Sábados: 8:00 AM - 12:00 PM\n"
              . "❌ Domingos: Cerrado\n\n"
              . "📍 Zona Industrial, Centro Michelena\n"
              . "📞 +58 0424-8323902";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }
    if (preg_match('/\b(?:ubicaci[oó]n|ubicad[oó]|d[oó]nde\s+(?:est[aá]n|quedan|ubicados|encuentran|est[aá]|queda)|direcci[oó]n|zona\s+industrial|como\s+llegar|mapa)\b/ui', $lowerText)) {
        $resp = "*📍 Ubicación*\n\n"
              . "Estamos en la *Zona Industrial, Centro Michelena*\n"
              . "Estado Táchira, Venezuela\n\n"
              . "📞 +58 0424-8323902\n"
              . "📧 Picca.ventas@gmail.com\n\n"
              . "🔗 $tiendaUrl";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }
    if (preg_match('/\b(?:qui[eé]nes\s+sois|qui[eé]nes\s+son|qui[eé]n\s+es|nosotros|empresa|compa[ñn][ií]a|acerca\s+de|informaci[oó]n\s+de\s+la\s+empresa|que\s+es\s+pic|que\s+significa\s+pic|proyectos\s+industriales)\b/ui', $lowerText)) {
        $resp = "*Acerca de PIC*\n\n"
              . "*Proyectos Industriales del Centro* es una empresa especializada en:\n"
              . "• Venta de equipos industriales y herramientas\n"
              . "• Automatización y control industrial\n"
              . "• Sensores, variadores, contactores y más\n\n"
              . "Trabajamos con las mejores marcas del mercado.\n\n"
              . "📞 +58 0424-8323902\n"
              . "📧 Picca.ventas@gmail.com\n"
              . "🔗 $tiendaUrl";
        if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
        return $resp;
    }

    // 5. Fallback — friendly response for unrecognized queries
    $resp = "Disculpa $firstName, no entendí tu consulta.\n\n"
          . "Puedes preguntarme por:\n"
          . "• *Productos:* \"precio del at8n\", \"venden autonics\"\n"
          . "• *Catálogo:* \"quiero el catálogo\"\n"
          . "• *Envíos:* \"hacen envíos\"\n"
          . "• *Pagos:* \"métodos de pago\"\n"
          . "• *Pedidos:* /pedido PED-2026-XXXXXX\n\n"
          . "O escribe /help para ver la ayuda completa.";
    if ($pdo) botGuardarEnAprendizaje($pdo, $text, $resp);
    return $resp;
}
