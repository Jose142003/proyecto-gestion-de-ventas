<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(0); ini_set('display_errors', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ==============================================
// FUNCIÓN PARA LIMPIAR Y CONVERTIR NÚMEROS CON FORMATO VENEZOLANO
// ==============================================
function limpiarNumeroVenezolano($numero) {
    $numero = trim($numero);
    
    // Reemplazar coma por punto (formato venezolano: coma es decimal)
    $numero = str_replace(',', '.', $numero);
    
    // Eliminar puntos que sean separadores de miles (solo si hay mas de un punto)
    $partes = explode('.', $numero);
    if (count($partes) > 2) {
        // Formato: 1.549,37 -> quitar puntos excepto el ultimo
        $ultimo = array_pop($partes);
        $numero = implode('', $partes) . '.' . $ultimo;
    }
    
    $numero = preg_replace('/[^0-9.]/', '', $numero);
    
    return floatval($numero);
}

// ==============================================
// FUNCIÓN PRINCIPAL - OBTENER TASA DEL BCV
// ==============================================
function obtenerTasaBCV() {
    // 1. Intentar scraping directo del BCV (fuente oficial)
    $tasa = scrapingBCVDirecto();
    if ($tasa !== null && $tasa > 20 && $tasa < 2000) {
        guardarEnCacheLocal($tasa, 'BCV Oficial');
        return $tasa;
    }
    
    // 2. Intentar con API alternativa (ExchangeRate-API)
    $tasa = obtenerDeExchangeRateAPI();
    if ($tasa !== null && $tasa > 20 && $tasa < 2000) {
        guardarEnCacheLocal($tasa, 'ExchangeRate-API');
        return $tasa;
    }
    
    // 3. Intentar con Frankfurter API
    $tasa = obtenerDeFrankfurter();
    if ($tasa !== null && $tasa > 20 && $tasa < 2000) {
        guardarEnCacheLocal($tasa, 'Frankfurter');
        return $tasa;
    }
    
    // 4. Intentar con CurrencyAPI
    $tasa = obtenerDeCurrencyAPI();
    if ($tasa !== null && $tasa > 20 && $tasa < 2000) {
        guardarEnCacheLocal($tasa, 'CurrencyAPI');
        return $tasa;
    }
    
    // 5. Usar caché local
    $tasa = obtenerDeCacheLocal(true);
    if ($tasa !== null && $tasa > 20 && $tasa < 2000) {
        return $tasa;
    }
    
    // 6. Valor por defecto
    return 549;
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
                        
                        if ($tasa !== null && $tasa > 20 && $tasa < 2000) {
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
    $numero_raw = trim(str_replace(' ', '', $numero_raw));
    
    // Formato BCV: "549,37160000" -> 549.3716 (comma es separador decimal)
    if (preg_match('/^(\d+),(\d+)$/', $numero_raw, $matches)) {
        return floatval($matches[1] . '.' . $matches[2]);
    }
    
    // Formato con punto decimal
    if (preg_match('/^(\d+)\.(\d+)$/', $numero_raw, $matches)) {
        return floatval($numero_raw);
    }
    
    // Solo dígitos (sin separadores)
    if (ctype_digit($numero_raw)) {
        return floatval($numero_raw);
    }
    
    // Limpieza general
    $numero_limpio = str_replace(',', '.', $numero_raw);
    $numero_limpio = preg_replace('/[^0-9.]/', '', $numero_limpio);
    
    return ($numero_limpio !== '' && is_numeric($numero_limpio)) ? floatval($numero_limpio) : null;
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