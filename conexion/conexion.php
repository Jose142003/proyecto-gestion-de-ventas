<?php
require_once __DIR__ . '/../config/database.php';

if (!class_exists('Database')) {
class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $opts = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                if (defined('DB_SSL') && DB_SSL) {
                    $opts[PDO::MYSQL_ATTR_SSL_CA] = defined('DB_SSL_CA') ? DB_SSL_CA : null;
                    $opts[PDO::MYSQL_ATTR_SSL_CERT] = defined('DB_SSL_CERT') ? DB_SSL_CERT : null;
                    $opts[PDO::MYSQL_ATTR_SSL_KEY] = defined('DB_SSL_KEY') ? DB_SSL_KEY : null;
                    $opts[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = defined('DB_SSL_VERIFY') ? DB_SSL_VERIFY : true;
                }
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $opts);
            } catch (PDOException $e) {
                error_log("Error de conexión BD: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$instance;
    }

    public static function setHeaders(): void {
        seguridadInit();

        header('Content-Type: application/json; charset=utf-8');
        $allowed_origin = defined('CORS_ORIGIN') ? rtrim(CORS_ORIGIN, '/') : (defined('BASE_URL') ? rtrim(BASE_URL, '/') : 'http://localhost');
        header("Access-Control-Allow-Origin: $allowed_origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    public static function getPdoOrError(): ?PDO {
        try {
            return self::getConnection();
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
            exit;
        }
    }
}
}

if (!function_exists('conectarDB')) {
    function conectarDB(): PDO {
        return Database::getConnection();
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse(mixed $data, int $status = 200): void {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('errorResponse')) {
    function errorResponse(string $message, int $status = 400): void {
        jsonResponse(['success' => false, 'message' => $message], $status);
    }
}

// ========== HELPER DE SESIÓN (CLIENTE + ADMIN) ==========

if (!function_exists('iniciarSesion')) {
    function iniciarSesion(): void {
        if (session_status() !== PHP_SESSION_NONE) return;

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' ||
            (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            Database::setHeaders();
        }

        seguridadConfigurarCookies();

        $referer = $_SERVER['HTTP_REFERER'] ?? '';

        if (strpos($referer, '/panel_admin/') !== false || strpos($referer, '/admin/') !== false) {
            session_start();
            if (empty($_SESSION['_session_initiated'])) {
                session_regenerate_id(true);
                $_SESSION['_session_initiated'] = true;
            }
            seguridadVerificarTimeoutSesion();
            seguridadRegenerarSesion();
            return;
        }

        if (isset($_COOKIE['CLIENTSESSID'])) {
            session_name('CLIENTSESSID');
            session_start();
            if (empty($_SESSION['_session_initiated'])) {
                session_regenerate_id(true);
                $_SESSION['_session_initiated'] = true;
            }
            if (isset($_SESSION['user_id'])) {
                seguridadVerificarTimeoutSesion();
                seguridadRegenerarSesion();
                return;
            }
            session_write_close();
        }

        session_start();
        if (empty($_SESSION['_session_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_session_initiated'] = true;
        }
        seguridadVerificarTimeoutSesion();
        seguridadRegenerarSesion();
    }
}

// ========== CSRF PROTECTION ==========

if (!function_exists('generarTokenCSRF')) {
    function generarTokenCSRF(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('validarTokenCSRF')) {
    function validarTokenCSRF(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['_csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }
}

if (!function_exists('verificarCSRF')) {
    function verificarCSRF(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
        if (!validarTokenCSRF($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            exit;
        }
    }
}

if (!function_exists('campoCSRF')) {
    function campoCSRF(): string {
        return '<input type="hidden" name="_csrf_token" value="' . generarTokenCSRF() . '">';
    }
}

if (!function_exists('headerCSRF')) {
    function headerCSRF(): void {
        header('X-CSRF-Token: ' . generarTokenCSRF());
    }
}

// ========== AUTH HELPERS ==========

if (!function_exists('requerirAdmin')) {
    function requerirAdmin(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true ||
            !isset($_SESSION['es_admin']) || $_SESSION['es_admin'] !== true) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }
}

if (!function_exists('requerirSesion')) {
    function requerirSesion(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autenticado']);
            exit;
        }
    }
}

if (!function_exists('esAdmin')) {
    function esAdmin(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true &&
               isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
    }
}

// ========== URL HELPER ==========

if (!function_exists('url')) {
    function url(string $path = ''): string {
        $base = rtrim(BASE_URL, '/');
        return $base . '/' . ltrim($path, '/');
    }
}

// ========== CSP NONCE HELPER ==========

if (!function_exists('cspNonce')) {
    function cspNonce(): string {
        if (!isset($GLOBALS['_csp_nonce'])) {
            $GLOBALS['_csp_nonce'] = base64_encode(random_bytes(16));
        }
        return $GLOBALS['_csp_nonce'];
    }
}

// ========== LOGGER ==========

if (!function_exists('logSistema')) {
    function logSistema(string $mensaje, string $nivel = 'INFO'): void {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $archivo = $logDir . '/sistema_' . date('Y-m-d') . '.log';
        $linea = '[' . date('Y-m-d H:i:s') . '] [' . $nivel . '] ' . $mensaje . PHP_EOL;
        @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
    }
}

// ========== AUDITORÍA CENTRALIZADA ==========

if (!function_exists('auditoriaRegistrar')) {
    function auditoriaRegistrar(string $accion, string $modulo, string $descripcion, ?int $usuarioId = null, ?string $usuarioNombre = null): void {
        try {
            $pdo = Database::getConnection();
            $usuarioId = $usuarioId ?? ($_SESSION['user_id'] ?? null);
            $usuarioNombre = $usuarioNombre ?? ($_SESSION['user_nombre'] ?? 'sistema');
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$usuarioId, $usuarioNombre, $accion, $modulo, $descripcion, $ip]);
        } catch (Exception $e) {
            logSistema("Error registrando auditoría: " . $e->getMessage(), 'ERROR');
        }
    }
}

if (!function_exists('auditoriaRegistrarConDetalle')) {
    function auditoriaRegistrarConDetalle(string $accion, string $modulo, string $descripcion, mixed $datosAntes = null, mixed $datosDespues = null): void {
        try {
            $pdo = Database::getConnection();
            $usuarioId = $_SESSION['user_id'] ?? null;
            $usuarioNombre = $_SESSION['user_nombre'] ?? 'sistema';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $stmt = $pdo->prepare("INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, datos_anteriores, datos_nuevos, ip_address, fecha_creacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $usuarioId, $usuarioNombre, $accion, $modulo, $descripcion,
                $datosAntes ? json_encode($datosAntes, JSON_UNESCAPED_UNICODE) : null,
                $datosDespues ? json_encode($datosDespues, JSON_UNESCAPED_UNICODE) : null,
                $ip
            ]);
        } catch (Exception $e) {
            logSistema("Error registrando auditoría con detalle: " . $e->getMessage(), 'ERROR');
        }
    }
}

// ========== ERROR HANDLER CENTRALIZADO ==========
require_once __DIR__ . '/error_handler.php';
errorHandlerInit();

// ========== SEGURIDAD CENTRALIZADA ==========
require_once __DIR__ . '/seguridad.php';