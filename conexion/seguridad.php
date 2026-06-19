<?php
/**
 * seguridad.php - Capa central de seguridad
 * Incluir desde conexion.php para proteger todo el sistema
 */

// Polyfill para str_contains (PHP 7.x compat)
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return '' === $needle || false !== strpos($haystack, $needle);
    }
}

// ========== CONFIGURACIÓN ==========
define('SESSION_TIMEOUT_MINUTES', 30);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT_MINUTES', 15);
define('MAX_REQUESTS_PER_MINUTE', 60);
define('IP_BLOCK_DURATION_MINUTES', 60);
define('MAX_FAILED_ATTEMPTS_BEFORE_BLOCK', 10);

// ========== 1. HEADERS DE SEGURIDAD ADICIONALES ==========
function seguridadEnviarHeaders(): void {
    if (headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    if (defined('BASE_URL')) {
        $baseUrl = rtrim(BASE_URL, '/');
        header("Content-Security-Policy: default-src 'self' $baseUrl https:; style-src 'self' 'unsafe-inline' https:; script-src 'self' 'unsafe-inline' https:; font-src 'self' https:; img-src 'self' data: https:; connect-src 'self' https:;");
    }
}

// ========== 2. SESIÓN - TIMEOUT POR INACTIVIDAD ==========
function seguridadVerificarTimeoutSesion(): void {
    if (session_status() === PHP_SESSION_NONE) return;
    if (!isset($_SESSION['user_id'])) return;

    $timeout_minutos = defined('SESSION_TIMEOUT_MINUTES') ? SESSION_TIMEOUT_MINUTES : 30;
    $timeout_segundos = $timeout_minutos * 60;

    if (isset($_SESSION['_ultimo_acceso'])) {
        $inactividad = time() - $_SESSION['_ultimo_acceso'];
        if ($inactividad > $timeout_segundos) {
            $user_id = $_SESSION['user_id'] ?? 'desconocido';
            $es_admin = isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
            $tipo = $es_admin ? 'admin' : 'cliente';

            $_SESSION = [];

            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );

            if (isset($_COOKIE['persist_token'])) {
                setcookie('persist_token', '', time() - 42000, '/');
            }

            session_destroy();

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            error_log("[SEGURIDAD] Sesión expirada por inactividad - Usuario: $user_id, Tipo: $tipo, IP: $ip");

            if ($es_admin) {
                header('Location: /proyecto/interfaz_usuario/login.html?error=session_timeout');
                exit;
            }

            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Sesión expirada por inactividad']);
            exit;
        }
    }

    $_SESSION['_ultimo_acceso'] = time();
}

// ========== 3. SESIÓN - REGENERAR ID PERIÓDICAMENTE ==========
function seguridadRegenerarSesion(): void {
    if (session_status() === PHP_SESSION_NONE) return;
    if (!isset($_SESSION['user_id'])) return;

    if (!isset($_SESSION['_regenerado_en'])) {
        $_SESSION['_regenerado_en'] = time();
        return;
    }

    if (time() - $_SESSION['_regenerado_en'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_regenerado_en'] = time();
    }
}

// ========== 4. PROTECCIÓN BRUTE FORCE - BLOQUEO POR IP ==========
function seguridadObtenerIp(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    return '0.0.0.0';
}

function seguridadRegistrarIntentoFallido(string $tipo = 'login'): void {
    $ip = seguridadObtenerIp();
    $archivo = __DIR__ . '/../logs/intentos_fallidos.log';
    $linea = date('Y-m-d H:i:s') . "|$ip|$tipo" . PHP_EOL;
    @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
}

function seguridadVerificarBloqueoIp(): void {
    $ip = seguridadObtenerIp();
    $archivo = __DIR__ . '/../logs/intentos_fallidos.log';
    if (!file_exists($archivo)) return;

    $limite = defined('MAX_FAILED_ATTEMPTS_BEFORE_BLOCK') ? MAX_FAILED_ATTEMPTS_BEFORE_BLOCK : 10;
    $ventana = defined('IP_BLOCK_DURATION_MINUTES') ? IP_BLOCK_DURATION_MINUTES : 60;

    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lineas = array_reverse($lineas);

    $intentos = 0;
    $tiempo_limite = time() - ($ventana * 60);

    foreach ($lineas as $linea) {
        $partes = explode('|', $linea);
        if (count($partes) < 2) continue;

        $timestamp = strtotime($partes[0]);
        if ($timestamp === false || $timestamp < $tiempo_limite) break;

        if (trim($partes[1]) === $ip) {
            $intentos++;
        }
    }

    if ($intentos >= $limite) {
        error_log("[SEGURIDAD] IP BLOQUEADA por múltiples intentos fallidos - IP: $ip, Intentos: $intentos");
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Demasiados intentos fallidos. Intenta nuevamente más tarde.'
        ]);
        exit;
    }
}

// ========== 5. RATE LIMITING POR IP ==========
function seguridadVerificarRateLimit(): void {
    $ip = seguridadObtenerIp();
    $archivo = __DIR__ . '/../logs/rate_limit.log';
    $max_peticiones = defined('MAX_REQUESTS_PER_MINUTE') ? MAX_REQUESTS_PER_MINUTE : 60;

    $peticiones = [];
    if (file_exists($archivo)) {
        $contenido = @file_get_contents($archivo);
        if ($contenido !== false) {
            $peticiones = json_decode($contenido, true) ?: [];
        }
    }

    $ahora = time();
    $clave = hash('sha256', $ip);

    if (!isset($peticiones[$clave])) {
        $peticiones[$clave] = [];
    }

    $peticiones[$clave] = array_filter($peticiones[$clave], function($t) use ($ahora) {
        return $t > ($ahora - 60);
    });

    if (count($peticiones[$clave]) >= $max_peticiones) {
        error_log("[SEGURIDAD] Rate limit excedido - IP: $ip");
        http_response_code(429);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Demasiadas peticiones. Intenta nuevamente en un minuto.'
        ]);
        exit;
    }

    $peticiones[$clave][] = $ahora;
    @file_put_contents($archivo, json_encode($peticiones), LOCK_EX);
}

// ========== 6. VALIDACIÓN DE ORIGEN (REFERER) ==========
function seguridadValidarOrigen(): void {
    if (php_sapi_name() === 'cli') return;

    $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($metodo === 'GET') return;

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (empty($referer)) return;

    $origen_permitido = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/proyecto';

    $hostRef = parse_url($referer, PHP_URL_HOST) ?? '';
    $hostPerm = parse_url($origen_permitido, PHP_URL_HOST) ?? '';
    if ($hostRef !== $hostPerm && $hostRef !== 'localhost' && $hostRef !== '127.0.0.1') {
        error_log("[SEGURIDAD] Referer inválido: $referer");
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Origen de petición no válido']);
        exit;
    }
}

// ========== 7. COOKIES SEGURAS ==========
function seguridadConfigurarCookies(): void {
    if (session_status() === PHP_SESSION_NONE) return;
    if (headers_sent()) return;

    $params = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $params['lifetime'],
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// ========== 8. DETECCIÓN DE USER-AGENT ==========
function seguridadVerificarUserAgent(): void {
    if (php_sapi_name() === 'cli') return;

    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (empty($ua)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User-Agent requerido']);
        exit;
    }
}

// ========== 9. INICIALIZAR SEGURIDAD (LLAMAR AL INICIO) ==========
function seguridadInit(): void {
    seguridadConfigurarCookies();
    seguridadEnviarHeaders();
    seguridadVerificarBloqueoIp();
    seguridadVerificarRateLimit();
    seguridadValidarOrigen();
    seguridadVerificarTimeoutSesion();
    seguridadRegenerarSesion();
}

// ========== 10. HELPER - VERIFICAR CONTRASEÑA SEGURA ==========
function seguridadValidarPassword(string $password): array {
    $errores = [];

    if (strlen($password) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errores[] = 'Debe contener al menos una mayúscula';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errores[] = 'Debe contener al menos una minúscula';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errores[] = 'Debe contener al menos un número';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errores[] = 'Debe contener al menos un carácter especial';
    }

    return [
        'valida' => empty($errores),
        'errores' => $errores
    ];
}
