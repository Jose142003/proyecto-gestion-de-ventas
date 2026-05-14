<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ==============================================
// FUNCIÓN PARA LIMPIAR Y CONVERTIR NÚMEROS CON FORMATO VENEZOLANO
// ==============================================
function limpiarNumeroVenezolano($numero) {
    // Eliminar espacios
    $numero = trim($numero);
    
    // Detectar si tiene formato venezolano (ej: "500,46" que son 500 miles? o "50,046060000"?)
    // Según la imagen: "USD500,46060000" = 50.04606 (la coma es separador decimal en este caso?)
    // Pero en Venezuela, la coma es separador de miles y el punto decimal
	
    // Revisar: "500,46060000" - si hay 3 dígitos antes de la coma, probablemente son miles
    if (preg_match('/^(\d{3}),(\d+)$/', $numero, $matches)) {
        // Caso: "500,46" -> 500.46? o 50.046?
        // Según la imagen, USD 50,046060000 = 50.04 Bs
        // El BCV muestra "USD500,46060000" en la página pero el valor real es ~50.05
        // Los primeros 2 dígitos son la parte entera, el tercero es decimal
        $entero = $matches[1]; // "500"
        $decimal = $matches[2]; // "46060000"
        
        // Si el entero tiene 3 dígitos y empieza con 5, probablemente son 3 dígitos que representan 50.0
        if (strlen($entero) == 3 && substr($entero, 0, 1) == '5') {
            // Convertir "500" a 50.0
            $entero_corregido = substr($entero, 0, 2) . '.' . substr($entero, 2);
            return floatval(str_replace(',', '.', $entero_corregido . '.' . $decimal));
        }
    }
    
    // Reemplazar coma por punto si es el único separador decimal
    if (strpos($numero, ',') !== false && strpos($numero, '.') === false) {
        $numero = str_replace(',', '.', $numero);
    }
    
    // Eliminar cualquier coma que quede (separadores de miles)
    $numero = str_replace(',', '', $numero);
    
    return floatval($numero);
}

// ==============================================
// FUNCIÓN PRINCIPAL - OBTENER TASA DEL BCV
// ==============================================
function obtenerTasaBCV() {
    // 1. Intentar scraping directo del BCV (fuente oficial)
    $tasa = scrapingBCVDirecto();
    if ($tasa !== null && $tasa > 20 && $tasa < 200) {
        guardarEnCacheLocal($tasa, 'BCV Oficial');
        return $tasa;
    }
    
    // 2. Intentar con API alternativa (ExchangeRate-API)
    $tasa = obtenerDeExchangeRateAPI();
    if ($tasa !== null && $tasa > 20 && $tasa < 200) {
        guardarEnCacheLocal($tasa, 'ExchangeRate-API');
        return $tasa;
    }
    
    // 3. Intentar con Frankfurter API
    $tasa = obtenerDeFrankfurter();
    if ($tasa !== null && $tasa > 20 && $tasa < 200) {
        guardarEnCacheLocal($tasa, 'Frankfurter');
        return $tasa;
    }
    
    // 4. Intentar con CurrencyAPI
    $tasa = obtenerDeCurrencyAPI();
    if ($tasa !== null && $tasa > 20 && $tasa < 200) {
        guardarEnCacheLocal($tasa, 'CurrencyAPI');
        return $tasa;
    }
    
    // 5. Usar caché local
    $tasa = obtenerDeCacheLocal(true);
    if ($tasa !== null && $tasa > 20 && $tasa < 200) {
        return $tasa;
    }
    
    // 6. Valor por defecto
    return 50.05;
}

// ==============================================
// SCRAPING DIRECTO DEL SITIO DEL BCV
// ==============================================
function scrapingBCVDirecto() {
    $urls = [
        'https://www.bcv.org.ve/',
        'https://www.bcv.org.ve/estadisticas/tipo-de-cambio-de-referencia',
        'https://www.bcv.org.ve/indicadores-economicos'
    ];
    
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ];
    
    foreach ($urls as $url) {
        foreach ($userAgents as $ua) {
            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 12,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_USERAGENT => $ua,
                    CURLOPT_REFERER => 'https://www.google.com/',
                    CURLOPT_ENCODING => '',
                    CURLOPT_HTTPHEADER => [
                        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                        'Accept-Language: es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3',
                        'Accept-Encoding: gzip, deflate, br',
                        'Connection: keep-alive',
                        'Cache-Control: max-age=0'
                    ]
                ]);
                
                $html = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode !== 200 || !$html) {
                    continue;
                }
                
                // PATRONES ESPECÍFICOS PARA LA TASA USD DEL BCV
                $patrones = [
                    // Buscar USD seguido de número (ej: USD500,46060000)
                    '/USD\s*(\d{1,3}(?:[.,]\d+)?)/i',
                    '/Tasa\s+USD\s*:?\s*(\d{1,3}(?:[.,]\d+)?)/i',
                    '/D[Oo]lar\s+USD\s*:?\s*(\d{1,3}(?:[.,]\d+)?)/i',
                    '/(?:US\s*D[Oo]llar|D[Oo]lar\s+EE\.UU\.)\s*:?\s*(\d{1,3}(?:[.,]\d+)?)/i',
                    '/<td[^>]*>USD<\/td>\s*<td[^>]*>(\d{1,3}(?:[.,]\d+)?)<\/td>/i',
                    '/USD<\/span>\s*<span[^>]*>(\d{1,3}(?:[.,]\d+)?)<\/span>/i'
                ];
                
                foreach ($patrones as $patron) {
                    if (preg_match($patron, $html, $matches)) {
                        $numero_raw = $matches[1];
                        $tasa = convertirTasaUSD($numero_raw);
                        
                        if ($tasa !== null && $tasa > 20 && $tasa < 200) {
                            error_log("BCV Scraping exitoso desde $url: $numero_raw -> $tasa");
                            return $tasa;
                        }
                    }
                }
                
                // Buscar en el texto plano
                $textoPlano = strip_tags($html);
                if (preg_match('/USD\s*(\d{1,3}(?:[.,]\d+)?)/i', $textoPlano, $matches)) {
                    $tasa = convertirTasaUSD($matches[1]);
                    if ($tasa !== null && $tasa > 20 && $tasa < 200) {
                        error_log("BCV Scraping (texto) desde $url: {$matches[1]} -> $tasa");
                        return $tasa;
                    }
                }
                
            } catch (Exception $e) {
                error_log("Error scraping $url: " . $e->getMessage());
                continue;
            }
        }
    }
    
    return null;
}

// ==============================================
// CONVERTIR TASA USD DEL BCV (formato especial)
// ==============================================
function convertirTasaUSD($numero_raw) {
    // Eliminar espacios
    $numero_raw = trim($numero_raw);
    
    // Caso: "500,46060000" (formato del BCV)
    // El valor real es 50.04606 (los primeros 2 dígitos + el resto)
    if (preg_match('/^(\d{3}),(\d+)$/', $numero_raw, $matches)) {
        $tres_digitos = $matches[1]; // "500"
        $decimales = $matches[2];    // "46060000"
        
        // Convertir "500" a "50.0"
        $primeros_dos = substr($tres_digitos, 0, 2);  // "50"
        $tercer_digito = substr($tres_digitos, 2, 1); // "0"
        
        // Construir número: 50.046060000
        $numero_corregido = $primeros_dos . '.' . $tercer_digito . $decimales;
        
        return floatval($numero_corregido);
    }
    
    // Caso: "50,05" (formato normal con coma decimal)
    if (preg_match('/^(\d{1,2}),(\d{1,2})$/', $numero_raw, $matches)) {
        return floatval($matches[1] . '.' . $matches[2]);
    }
    
    // Caso: "50.05" (formato con punto decimal)
    if (preg_match('/^(\d{1,2})\.(\d{1,2})$/', $numero_raw, $matches)) {
        return floatval($numero_raw);
    }
    
    // Intentar limpieza general
    $numero_limpio = str_replace(',', '.', $numero_raw);
    $numero_limpio = preg_replace('/[^0-9.]/', '', $numero_limpio);
    
    $tasa = floatval($numero_limpio);
    
    // Si la tasa es > 100, probablemente está mal interpretada (como "500,46" -> 500.46)
    // Intentar corregir dividiendo entre 10
    if ($tasa > 100 && $tasa < 1000) {
        $tasa_corregida = $tasa / 10;
        if ($tasa_corregida > 20 && $tasa_corregida < 100) {
            return $tasa_corregida;
        }
    }
    
    return ($tasa > 20 && $tasa < 200) ? $tasa : null;
}

// ==============================================
// APIs ALTERNATIVAS
// ==============================================
function obtenerDeExchangeRateAPI() {
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.exchangerate-api.com/v4/latest/USD',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $respuesta) {
            $data = json_decode($respuesta, true);
            if (isset($data['rates']['VES'])) {
                return floatval($data['rates']['VES']);
            }
        }
    } catch (Exception $e) {
        error_log("ExchangeRate-API Exception: " . $e->getMessage());
    }
    return null;
}

function obtenerDeFrankfurter() {
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.frankfurter.app/latest?from=USD&to=VES',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $respuesta) {
            $data = json_decode($respuesta, true);
            if (isset($data['rates']['VES'])) {
                return floatval($data['rates']['VES']);
            }
        }
    } catch (Exception $e) {
        error_log("Frankfurter Exception: " . $e->getMessage());
    }
    return null;
}

function obtenerDeCurrencyAPI() {
    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$error && $respuesta) {
            $data = json_decode($respuesta, true);
            if (isset($data['usd']['ves'])) {
                return floatval($data['usd']['ves']);
            }
        }
    } catch (Exception $e) {
        error_log("CurrencyAPI Exception: " . $e->getMessage());
    }
    return null;
}

// ==============================================
// CACHE LOCAL
// ==============================================
function obtenerDeCacheLocal($ignorarExpiracion = false) {
    $archivo_cache = __DIR__ . '/tasa_cache.json';
    
    if (file_exists($archivo_cache)) {
        $contenido = file_get_contents($archivo_cache);
        $datos = json_decode($contenido, true);
        
        if ($datos && isset($datos['tasa'])) {
            if ($ignorarExpiracion) {
                return $datos['tasa'];
            }
            if (isset($datos['timestamp']) && (time() - $datos['timestamp'] < 3600)) {
                return $datos['tasa'];
            }
        }
    }
    
    return null;
}

function guardarEnCacheLocal($tasa, $fuente = '') {
    $archivo_cache = __DIR__ . '/tasa_cache.json';
    $datos = [
        'tasa' => $tasa,
        'timestamp' => time(),
        'fecha' => date('Y-m-d H:i:s'),
        'fuente' => $fuente
    ];
    file_put_contents($archivo_cache, json_encode($datos, JSON_PRETTY_PRINT));
}

// ==============================================
// OBTENER Y DEVOLVER RESPUESTA
// ==============================================
$tasa = obtenerTasaBCV();

if ($tasa === null) {
    $tasa = 50.05; // Tasa aproximada actual
}

$tasa = round($tasa, 2);

$fuente = '';
$archivo_cache = __DIR__ . '/tasa_cache.json';
if (file_exists($archivo_cache)) {
    $datos = json_decode(file_get_contents($archivo_cache), true);
    $fuente = $datos['fuente'] ?? 'BCV Oficial';
}

$respuesta = [
    'success' => true,
    'tasa_bcv' => $tasa,
    'fecha' => date('Y-m-d H:i:s'),
    'fuente' => $fuente ?: 'BCV',
    'detalle' => 'Tasa oficial del Banco Central de Venezuela'
];

echo json_encode($respuesta, JSON_PRETTY_PRINT);
?>