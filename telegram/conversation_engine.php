<?php
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/respuestas_bot.php';

// ============================================================
// CONVERSATION HISTORY
// ============================================================

function botHistorialCargar($chatId) {
    $file = __DIR__ . '/mensajes/historial_' . $chatId . '.json';
    if (!file_exists($file)) return [];
    $fh = fopen($file, 'r');
    if (!$fh || !flock($fh, LOCK_SH)) { if ($fh) fclose($fh); return []; }
    $data = json_decode(stream_get_contents($fh), true);
    flock($fh, LOCK_UN); fclose($fh);
    return is_array($data) ? $data : [];
}

function botHistorialGuardar($chatId, $historial) {
    $file = __DIR__ . '/mensajes/historial_' . $chatId . '.json';
    $historial = array_slice($historial, -20);
    $tmpFile = $file . '.tmp';
    $fh = fopen($tmpFile, 'w');
    if ($fh && flock($fh, LOCK_EX)) {
        fwrite($fh, json_encode($historial, JSON_UNESCAPED_UNICODE));
        flock($fh, LOCK_UN); fclose($fh);
        rename($tmpFile, $file);
    } else {
        if ($fh) fclose($fh);
    }
}

function botHistorialAgregar($chatId, $rol, $mensaje) {
    $historial = botHistorialCargar($chatId);
    $historial[] = ['rol' => $rol, 'mensaje' => $mensaje, 'tiempo' => time()];
    $historial = array_slice($historial, -20);
    botHistorialGuardar($chatId, $historial);
    return $historial;
}

// ============================================================
// TOOLS FOR AI FUNCTION CALLING
// ============================================================

function botToolBuscarProductos($termino) {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion a BD'];
    try {
        $termino = trim($termino);
        if (strlen($termino) < 2) return ['error' => 'Termino muy corto'];
        $results = botBuscarProductoFiltrado($pdo, $termino);
        if (!$results) return ['mensaje' => 'No encontre productos con "' . $termino . '".'];
        $items = [];
        foreach (array_slice($results, 0, 8) as $p) {
            $items[] = [
                'id' => $p['id'], 'nombre' => $p['name'], 'precio' => (float)$p['price'],
                'stock' => (int)$p['stock'], 'categoria' => $p['category'] ?? '',
                'descripcion' => mb_substr(strip_tags(html_entity_decode($p['description'] ?? '')), 0, 200),
            ];
        }
        return ['productos' => $items, 'total' => count($results)];
    } catch (Exception $e) { return ['error' => 'Error buscando productos']; }
}

function botBuscarProductoFiltrado($pdo, $texto) {
    $sqlBase = "SELECT id, name, price, stock, category, description, specs, image_url FROM products WHERE active = 1 AND deleted_at IS NULL";
    $term = '%' . $texto . '%';
    $stmt = $pdo->prepare("$sqlBase AND (name LIKE ? OR description LIKE ? OR category LIKE ?) LIMIT 10");
    $stmt->execute([$term, $term, $term]);
    $results = $stmt->fetchAll();
    if (count($results) > 0) return $results;
    $words = array_filter(explode(' ', $texto), fn($w) => strlen(trim($w)) >= 2);
    $words = array_slice(array_values(array_unique(array_map('trim', $words))), 0, 4);
    if (count($words) === 0) return null;
    $scored = [];
    foreach ($words as $w) {
        $p = '%' . $w . '%';
        $stmt = $pdo->prepare("$sqlBase AND (name LIKE ? OR description LIKE ? OR category LIKE ?) LIMIT 10");
        $stmt->execute([$p, $p, $p]);
        foreach ($stmt->fetchAll() as $prod) { $id = $prod['id']; if (!isset($scored[$id])) $scored[$id] = $prod + ['_score' => 0]; $scored[$id]['_score']++; }
    }
    if (empty($scored)) return null;
    $minScore = count($words) >= 3 ? 2 : 1;
    $scored = array_filter($scored, fn($p) => $p['_score'] >= $minScore);
    if (empty($scored)) return null;
    usort($scored, fn($a, $b) => $b['_score'] - $a['_score']);
    return array_map(fn($p) => array_diff_key($p, ['_score' => 1]), array_slice($scored, 0, 10));
}

function botToolObtenerProducto($id) {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1 AND deleted_at IS NULL");
    $stmt->execute([intval($id)]);
    $p = $stmt->fetch();
    if (!$p) return ['error' => 'Producto no encontrado'];
    return [
        'id' => $p['id'], 'nombre' => $p['name'], 'precio' => (float)$p['price'],
        'stock' => (int)$p['stock'], 'categoria' => $p['category'] ?? '',
        'descripcion' => strip_tags(html_entity_decode($p['description'] ?? '')),
        'especificaciones' => strip_tags(html_entity_decode($p['specs'] ?? '')),
        'peso' => $p['weight'], 'dimensiones' => $p['dimensions'], 'imagen' => $p['image_url'] ?? '',
    ];
}

function botToolConsultarPedido($numero) {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion'];
    $pedido = botBuscarPedidoPorNumero($numero, $pdo);
    if (!$pedido) return ['error' => 'Pedido no encontrado'];
    $detalles = $pdo->prepare("SELECT pd.*, pr.name as producto_nombre FROM pedido_detalles pd JOIN products pr ON pd.producto_id = pr.id WHERE pd.pedido_id = ?");
    $detalles->execute([$pedido['id']]);
    $items = $detalles->fetchAll(PDO::FETCH_ASSOC);
    return [
        'numero' => $pedido['numero_pedido'], 'fecha' => $pedido['created_at'] ?? $pedido['fecha_pedido'],
        'estado' => $pedido['estado'], 'total' => (float)$pedido['total'],
        'metodo_pago' => $pedido['metodo_pago'] ?? '', 'referencia' => $pedido['referencia_pago'] ?? '',
        'productos' => array_map(fn($it) => ['nombre' => $it['producto_nombre'], 'cantidad' => (int)$it['cantidad'], 'subtotal' => (float)$it['subtotal']], $items),
    ];
}

function botToolInfoEmpresa() {
    return [
        'nombre' => 'Proyectos Industriales del Centro (PIC)',
        'descripcion' => 'Empresa especializada en venta de equipos industriales, herramientas, automatizacion y control industrial.',
        'productos' => 'Sensores, variadores de velocidad, contactores, breakers, fuentes, cables, reles, guardamotores y mas.',
        'marcas' => 'Autonics, Siemens, Schneider, UNI-T, Exceline, Omron, Mitsubishi, ABB, WEG, Delta, LS, Telemecanique, Allen-Bradley, Honeywell, Eaton, Phoenix Contact, Weidmuller, Crouzet, Finder, Legrand, Panasonic, Fuji, Yaskawa, Sanyo.',
        'telefono' => '+58 0424-8323902', 'email' => 'Picca.ventas@gmail.com',
        'direccion' => 'Zona Industrial, Centro Michelena, Estado Tachira, Venezuela',
        'horario' => 'Lunes a Viernes 8:00 AM - 5:00 PM, Sabados 8:00 AM - 12:00 PM',
    ];
}

function botToolInfoEnvio() {
    return [
        'transportistas' => ['MRW (24-72 hrs)', 'Tealca (24-72 hrs)', 'Zoom (24-72 hrs)'],
        'cobertura' => 'Todo Venezuela',
        'nota' => 'Una vez procesado el pedido, se envia el numero de guia para rastreo.',
    ];
}

function botToolInfoPago() {
    return [
        'metodos' => ['Transferencia Bancaria', 'Pago Movil (Mercantil y Provincial)', 'Efectivo (en tienda)', 'Dolares (tasa BCV)'],
        'moneda' => 'Bolivares (Bs.)',
        'nota' => 'Precios no incluyen IVA. Descuentos especiales al contado.',
    ];
}

function botToolListarCategorias() {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion'];
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE active = 1 AND deleted_at IS NULL AND category IS NOT NULL AND category != '' ORDER BY category");
    return ['categorias' => $stmt->fetchAll(PDO::FETCH_COLUMN)];
}

function botToolProductosPorCategoria($categoria) {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion'];
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE active = 1 AND deleted_at IS NULL AND category = ? LIMIT 15");
    $stmt->execute([$categoria]);
    $prods = $stmt->fetchAll();
    if (!$prods) return ['error' => 'Categoria no encontrada'];
    return ['categoria' => $categoria, 'productos' => array_map(fn($p) => ['id' => $p['id'], 'nombre' => $p['name'], 'precio' => (float)$p['price'], 'stock' => (int)$p['stock']], $prods)];
}

function botToolProductosDestacados() {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion'];
    $stmt = $pdo->prepare("SELECT id, name, price, stock, category, image_url FROM products WHERE active = 1 AND deleted_at IS NULL AND (is_featured = 1 OR stock > 0) ORDER BY is_featured DESC, views_count DESC LIMIT 8");
    $stmt->execute();
    $prods = $stmt->fetchAll();
    return ['productos' => array_map(fn($p) => ['id' => $p['id'], 'nombre' => $p['name'], 'precio' => (float)$p['price'], 'stock' => (int)$p['stock'], 'categoria' => $p['category'] ?? '', 'imagen' => $p['image_url'] ?? ''], $prods)];
}

function botToolPrecioProducto($nombre) {
    $pdo = botConectarDB();
    if (!$pdo) return ['error' => 'Error de conexion'];
    $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE active = 1 AND deleted_at IS NULL AND (name LIKE ? OR name LIKE ?) LIMIT 1");
    $term = '%' . $nombre . '%';
    $stmt->execute([$nombre, $term]);
    $p = $stmt->fetch();
    if (!$p) return ['error' => 'Producto no encontrado con ese nombre'];
    return ['id' => $p['id'], 'nombre' => $p['name'], 'precio' => (float)$p['price'], 'stock' => (int)$p['stock']];
}

function botEjecutarTool($nombre, $args) {
    return match($nombre) {
        'buscar_productos' => botToolBuscarProductos($args['termino'] ?? ''),
        'obtener_producto' => botToolObtenerProducto($args['id'] ?? 0),
        'precio_producto' => botToolPrecioProducto($args['nombre'] ?? ''),
        'consultar_pedido' => botToolConsultarPedido($args['numero'] ?? ''),
        'info_empresa' => botToolInfoEmpresa(),
        'info_envio' => botToolInfoEnvio(),
        'info_pago' => botToolInfoPago(),
        'listar_categorias' => botToolListarCategorias(),
        'productos_por_categoria' => botToolProductosPorCategoria($args['categoria'] ?? ''),
        'productos_destacados' => botToolProductosDestacados(),
        default => ['error' => 'Herramienta desconocida: ' . $nombre],
    };
}

// ============================================================
// OPENAI FUNCTION CALLING
// ============================================================

function botToolsDefiniciones(): array {
    return [
        ['type' => 'function', 'function' => ['name' => 'buscar_productos', 'description' => 'Busca productos en el catalogo por nombre, descripcion o categoria.', 'parameters' => ['type' => 'object', 'properties' => ['termino' => ['type' => 'string', 'description' => 'Termino de busqueda (ej: sensor temperatura, autonics at8n)']], 'required' => ['termino']]]],
        ['type' => 'function', 'function' => ['name' => 'obtener_producto', 'description' => 'Obtiene informacion detallada de un producto por su ID numerico.', 'parameters' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer', 'description' => 'ID del producto']], 'required' => ['id']]]],
        ['type' => 'function', 'function' => ['name' => 'precio_producto', 'description' => 'Consulta el precio y stock de un producto buscando por su nombre.', 'parameters' => ['type' => 'object', 'properties' => ['nombre' => ['type' => 'string', 'description' => 'Nombre del producto']], 'required' => ['nombre']]]],
        ['type' => 'function', 'function' => ['name' => 'consultar_pedido', 'description' => 'Consulta el estado de un pedido por su numero. Formato: PED-ANO-NUMERO (ej: PED-2026-000040).', 'parameters' => ['type' => 'object', 'properties' => ['numero' => ['type' => 'string', 'description' => 'Numero de pedido completo']], 'required' => ['numero']]]],
        ['type' => 'function', 'function' => ['name' => 'info_empresa', 'description' => 'Obtiene informacion general sobre la empresa PIC: horario, contacto, ubicacion.', 'parameters' => ['type' => 'object', 'properties' => []]]],
        ['type' => 'function', 'function' => ['name' => 'info_envio', 'description' => 'Obtiene informacion sobre metodos de envio, transportistas y cobertura.', 'parameters' => ['type' => 'object', 'properties' => []]]],
        ['type' => 'function', 'function' => ['name' => 'info_pago', 'description' => 'Obtiene informacion sobre metodos de pago aceptados.', 'parameters' => ['type' => 'object', 'properties' => []]]],
        ['type' => 'function', 'function' => ['name' => 'listar_categorias', 'description' => 'Obtiene la lista de categorias de productos disponibles.', 'parameters' => ['type' => 'object', 'properties' => []]]],
        ['type' => 'function', 'function' => ['name' => 'productos_por_categoria', 'description' => 'Obtiene los productos de una categoria especifica.', 'parameters' => ['type' => 'object', 'properties' => ['categoria' => ['type' => 'string', 'description' => 'Nombre exacto de la categoria']], 'required' => ['categoria']]]],
        ['type' => 'function', 'function' => ['name' => 'productos_destacados', 'description' => 'Obtiene los productos destacados, mas vendidos u ofertas del catalogo.', 'parameters' => ['type' => 'object', 'properties' => []]]],
    ];
}

function botSystemPrompt(): string {
    $info = botToolInfoEmpresa();
    $envio = botToolInfoEnvio();
    $pago = botToolInfoPago();
    return "Eres PIC Bot, el asistente virtual de {$info['nombre']} en Telegram.

INFORMACION DE LA EMPRESA:
- Nombre: {$info['nombre']}
- Descripcion: {$info['descripcion']}
- Productos que venden: {$info['productos']}
- Marcas: {$info['marcas']}
- Telefono: {$info['telefono']}
- Email: {$info['email']}
- Direccion: {$info['direccion']}
- Horario: {$info['horario']}
- Envios: " . implode(', ', $envio['transportistas']) . " a {$envio['cobertura']}.
- Metodos de pago: " . implode(', ', $pago['metodos']) . ".

REGLAS DE CONDUCTA:
1. Responde SIEMPRE en espanol, con tono amable, cercano y profesional. Usa el nombre del cliente cuando lo sepas.
2. Usa las herramientas (tools) disponibles para consultar productos, precios y pedidos. NUNCA inventes precios ni existencias.
3. Si el cliente solo saluda, presentate brevemente y preguntale que producto o informacion necesita.
4. Cuando encuentres productos, muestra nombre, precio y si hay stock. Si hay pocas unidades, adviertelo.
5. Si no encuentras un producto, sugiere terminos alternativos o preguntar directamente en la tienda.
6. Manten conversacion fluida: responde preguntas de seguimiento, aunque cambien de tema.
7. Si preguntan algo que no sabes, se honesto y ofrece ayudar con lo que este a tu alcance.
8. Para precios de productos especificos, usa precio_producto. Para busquedas generales, usa buscar_productos.
9. Si el cliente quiere ver productos destacados, usa productos_destacados.
10. NUNCA inventes numeros de pedido ni informacion falsa.";
}

function botLlamarOpenAI($apiKey, $mensajes, $tools): ?string {
    $url = 'https://api.openai.com/v1/chat/completions';
    $maxIteraciones = 5;
    $iteracion = 0;

    do {
        $iteracion++;
        $payload = json_encode([
            'model' => OPENAI_MODEL,
            'messages' => $mensajes,
            'tools' => $tools,
            'tool_choice' => 'auto',
            'temperature' => 0.7,
            'max_tokens' => 800,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) { error_log("OpenAI error: $error"); return null; }
        if ($httpCode !== 200) { error_log("OpenAI HTTP $httpCode: " . mb_substr($response, 0, 200)); return null; }

        $data = json_decode($response, true);
        if (!$data || !isset($data['choices'][0]['message'])) return null;

        $message = $data['choices'][0]['message'];
        $respuestaTexto = $message['content'] ?? '';

        // Handle tool calls
        if (isset($message['tool_calls']) && is_array($message['tool_calls'])) {
            $mensajes[] = $message;
            foreach ($message['tool_calls'] as $tc) {
                $toolName = $tc['function']['name'];
                $args = json_decode($tc['function']['arguments'], true) ?? [];
                $result = botEjecutarTool($toolName, $args);
                $mensajes[] = [
                    'role' => 'tool',
                    'tool_call_id' => $tc['id'],
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
            if ($iteracion >= $maxIteraciones) {
                $respuestaTexto = 'Luego de varias consultas, estos son los resultados obtenidos. ¿Necesitas algo más?';
                break;
            }
        } else {
            break;
        }
    } while (true);

    return $respuestaTexto;
}

// ============================================================
// MAIN CONVERSATION RESPONDER
// ============================================================

// Circuit breaker para OpenAI
$GLOBALS['_openai_fallos_consecutivos'] = 0;
define('OPENAI_MAX_FALLOS', 5);

function botConversar($text, $chatId, $firstName): ?string {
    $apiKey = OPENAI_API_KEY;

    // Si OpenAI falló muchas veces, no intentarlo
    $usarOpenAI = $apiKey && $GLOBALS['_openai_fallos_consecutivos'] < OPENAI_MAX_FALLOS;

    // Try AI mode first
    if ($usarOpenAI) {
        // Load conversation history
        $historial = botHistorialCargar($chatId);
        $system = ['role' => 'system', 'content' => botSystemPrompt()];

        // Build messages array from history (last 10 exchanges)
        $mensajes = [$system];
        foreach (array_slice($historial, -10) as $h) {
            $role = match($h['rol']) { 'asistente' => 'assistant', 'usuario' => 'user', default => 'user' };
            $mensajes[] = ['role' => $role, 'content' => $h['mensaje']];
        }

        // Add current user message
        $mensajes[] = ['role' => 'user', 'content' => $text];

        $tools = botToolsDefiniciones();
        $respuesta = botLlamarOpenAI($apiKey, $mensajes, $tools);
        if ($respuesta !== null && trim($respuesta) !== '') {
            $GLOBALS['_openai_fallos_consecutivos'] = 0;
            botHistorialAgregar($chatId, 'usuario', $text);
            botHistorialAgregar($chatId, 'asistente', $respuesta);
            return $respuesta;
        }
        $GLOBALS['_openai_fallos_consecutivos']++;
    }

    // Fallback: use existing rule-based responder
    $respuesta = botResponder($text, $chatId, $firstName);
    if ($respuesta !== null) {
        botHistorialAgregar($chatId, 'usuario', $text);
        botHistorialAgregar($chatId, 'asistente', $respuesta);
    }
    return $respuesta;
}
