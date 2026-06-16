<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/respuestas_bot.php';

$chatId = 'conv_debug_' . time();
$firstName = 'Carlos';

$tests = [
    "Cuál es el horario",
    "Qué marcas trabajan",
    "Tienen garantía",
    "Quiénes son ustedes",
];

foreach ($tests as $msg) {
    $lowerText = botNormalizarTexto($msg);
    $searchTerm = trim(preg_replace('/\b(hola|buenas|saludos|gracias|por\s+favor|quiero|necesito|busco|dame|precio|precios|costo|tienen|tiene|hay|venden|vende|pago|pagos|pagar|paho|metodo|metodos|entrega|envio|envios|comprar|compra|como|donde|cual|cuales|cuanto|me|te|le|lo|la|no|ni|un|una|el|los|las|de|del|que|se|en|por|para|con|sin|y|o|a|su|sus|tu|es|se|mas|muy|solo|tambien|sabes|dime|info|informacion|las|los|del|al|producto|productos|existe|existen|nuevo|busqueda|listado|listado|todos|varios|muchos|pocos|algunos|algun|nombre|llamo|llamas|soy|eres|apellido|llamarme|llamarse|apellidos)\b/ui', '', $lowerText));
    $palabrasGenericas = ['horario', 'horarios', 'marcas', 'garantía', 'garantia', 'contacto', 'contactos',
        'ubicación', 'ubicacion', 'dirección', 'direccion', 'envíos', 'envios', 'pagos',
        'quienes', 'quien', 'empresa', 'compañia', 'compañía', 'certificaciones', 'recomiendas',
        'sugieres', 'novedades', 'descuentos', 'ofertas', 'promociones', 'pedido', 'pedidos'];
    $palabrasExtraidas = array_filter(explode(' ', $searchTerm), fn($w) => strlen($w) >= 3);
    $esSoloPalabraGenerica = count($palabrasExtraidas) === 1 && in_array(reset($palabrasExtraidas), $palabrasGenericas);

    echo "Original: \"$msg\"\n";
    echo "  lowerText: \"$lowerText\"\n";
    echo "  searchTerm: \"$searchTerm\"\n";
    echo "  palabrasExtraidas: [" . implode(", ", $palabrasExtraidas) . "]\n";
    echo "  count: " . count($palabrasExtraidas) . "\n";
    echo "  esSoloPalabraGenerica: " . ($esSoloPalabraGenerica ? "SI" : "NO") . "\n";
    if (count($palabrasExtraidas) === 1) {
        echo "  primer valor: \"" . reset($palabrasExtraidas) . "\"\n";
    }
    echo "\n";
}

$files = glob(__DIR__ . '/mensajes/*conv_debug_*');
foreach ($files as $f) @unlink($f);
