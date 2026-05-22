<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log("Error de conexión BD: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$instance;
    }

    public static function setHeaders(): void {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: http://localhost');
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

function conectarDB(): PDO {
    return Database::getConnection();
}

function jsonResponse(mixed $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse(string $message, int $status = 400): void {
    jsonResponse(['success' => false, 'message' => $message], $status);
}

// ========== CSRF PROTECTION ==========

function generarTokenCSRF(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function validarTokenCSRF(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['_csrf_token'], $token);
}

function verificarCSRF(): void {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf_token'] ?? '';
    if (!validarTokenCSRF($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
        exit;
    }
}

function campoCSRF(): string {
    return '<input type="hidden" name="_csrf_token" value="' . generarTokenCSRF() . '">';
}

function headerCSRF(): void {
    header('X-CSRF-Token: ' . generarTokenCSRF());
}

// ========== AUTH HELPERS ==========

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

function esAdmin(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true &&
           isset($_SESSION['es_admin']) && $_SESSION['es_admin'] === true;
}

// ========== URL HELPER ==========

function url(string $path = ''): string {
    $base = rtrim(BASE_URL, '/');
    return $base . '/' . ltrim($path, '/');
}

// ========== LOGGER ==========

function logSistema(string $mensaje, string $nivel = 'INFO'): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $archivo = $logDir . '/sistema_' . date('Y-m-d') . '.log';
    $linea = '[' . date('Y-m-d H:i:s') . '] [' . $nivel . '] ' . $mensaje . PHP_EOL;
    @file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);
}

// ========== AUDITORÍA CENTRALIZADA ==========

function auditoriaRegistrar(string $accion, string $modulo, string $descripcion, ?int $usuarioId = null, ?string $usuarioNombre = null): void {
    try {
        $pdo = Database::getConnection();
        $usuarioId = $usuarioId ?? ($_SESSION['user_id'] ?? null);
        $usuarioNombre = $usuarioNombre ?? ($_SESSION['user_nombre'] ?? 'sistema');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, ip_address, fecha) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$usuarioId, $usuarioNombre, $accion, $modulo, $descripcion, $ip]);
    } catch (Exception $e) {
        logSistema("Error registrando auditoría: " . $e->getMessage(), 'ERROR');
    }
}

function auditoriaRegistrarConDetalle(string $accion, string $modulo, string $descripcion, mixed $datosAntes = null, mixed $datosDespues = null): void {
    try {
        $pdo = Database::getConnection();
        $usuarioId = $_SESSION['user_id'] ?? null;
        $usuarioNombre = $_SESSION['user_nombre'] ?? 'sistema';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO auditoria_logs (usuario_id, usuario_nombre, accion, modulo, descripcion, datos_antes, datos_despues, ip_address, fecha) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
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