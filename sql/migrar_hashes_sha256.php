<?php
/**
 * MIGRAR_HASHES_SHA256.PHP — Escáner de hashes SHA-256 legacy
 * 
 * Propósito:
 *   Identificar usuarios en admin_users y users cuyas contraseñas están
 *   almacenadas como SHA-256 simple (64 caracteres hex), lo cual es inseguro.
 *   Como no podemos revertir el hash, la única solución es que el usuario
 *   inicie sesión (el login ya migra automáticamente a bcrypt).
 * 
 * Uso:
 *   php sql/migrar_hashes_sha256.php
 * 
 * Seguridad:
 *   - READ-ONLY: no modifica NINGUNA contraseña.
 *   - Idempotente: se puede ejecutar N veces sin efectos secundarios.
 *   - Solo LOGEA y REPORTA.
 */

require_once __DIR__ . '/../conexion/conexion.php';

// Colores para salida CLI
define('CLI_GREEN',  "\033[32m");
define('CLI_YELLOW', "\033[33m");
define('CLI_RED',    "\033[31m");
define('CLI_CYAN',   "\033[36m");
define('CLI_RESET',  "\033[0m");

echo CLI_CYAN . "============================================" . CLI_RESET . "\n";
echo CLI_CYAN . "  MIGRADOR DE HASHES SHA-256 LEGACY" . CLI_RESET . "\n";
echo CLI_CYAN . "  Modo: SOLO LECTURA (reporte)" . CLI_RESET . "\n";
echo CLI_CYAN . "============================================" . CLI_RESET . "\n\n";

try {
    $pdo = conectarDB();
} catch (Exception $e) {
    echo CLI_RED . "ERROR: No se pudo conectar a la base de datos: " . $e->getMessage() . CLI_RESET . "\n";
    exit(1);
}

/**
 * Determina si un string parece un hash SHA-256 legacy (64 caracteres hex).
 */
function esSHA256Legacy(string $hash): bool {
    return strlen($hash) === 64 && ctype_xdigit($hash);
}

/**
 * Crea la tabla migracion_hashes_log si no existe.
 */
function asegurarTablaLog(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS migracion_hashes_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tabla VARCHAR(20) NOT NULL COMMENT 'admin_users o users',
        usuario_id INT NOT NULL,
        nombre VARCHAR(100) NOT NULL DEFAULT '',
        correo VARCHAR(100) NOT NULL DEFAULT '',
        hash_anterior VARCHAR(255) NOT NULL,
        hash_tipo VARCHAR(20) NOT NULL DEFAULT 'sha256_legacy',
        accion VARCHAR(50) NOT NULL DEFAULT 'pendiente_migracion',
        ip_origen VARCHAR(45) DEFAULT NULL,
        migrated_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tabla_usuario (tabla, usuario_id),
        INDEX idx_accion (accion)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
      COMMENT='Registro de usuarios con hashes legacy pendientes de migrar'");
}

/**
 * Busca usuarios legacy en una tabla dada y devuelve un array de registros.
 */
function detectarLegacyEnTabla(PDO $pdo, string $tabla, string $colPassword): array {
    $stmt = $pdo->prepare("SELECT id, nombre, correo, $colPassword AS password FROM $tabla WHERE 1=1");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $encontrados = [];
    foreach ($usuarios as $u) {
        // También detectar SHA-256 en mayúsculas (64 chars uppercase hex)
        if (esSHA256Legacy($u['password']) || esSHA256Legacy(strtolower($u['password']))) {
            $u['hash_original'] = $u['password'];
            $encontrados[] = $u;
        }
    }
    return $encontrados;
}

/**
 * Registra un usuario legacy en la tabla de log (si no estaba ya registrado).
 */
function registrarEnLog(PDO $pdo, string $tabla, array $usuario): void {
    $check = $pdo->prepare("SELECT id FROM migracion_hashes_log WHERE tabla = ? AND usuario_id = ? AND accion = 'pendiente_migracion'");
    $check->execute([$tabla, $usuario['id']]);
    if ($check->fetch()) {
        return; // Ya registrado, no duplicar
    }

    $stmt = $pdo->prepare("INSERT INTO migracion_hashes_log (tabla, usuario_id, nombre, correo, hash_anterior, hash_tipo, accion)
                           VALUES (?, ?, ?, ?, ?, 'sha256_legacy', 'pendiente_migracion')");
    $stmt->execute([$tabla, $usuario['id'], $usuario['nombre'], $usuario['correo'], $usuario['hash_original']]);
}

// ──────────────────────────────────────────────────────
// 1. Asegurar tabla de log
// ──────────────────────────────────────────────────────
asegurarTablaLog($pdo);
echo CLI_GREEN . "[OK] Tabla migracion_hashes_log lista" . CLI_RESET . "\n\n";

// ──────────────────────────────────────────────────────
// 2. Escanear admin_users → columna: contrasena
// ──────────────────────────────────────────────────────
echo CLI_CYAN . "── Escaneando admin_users (columna: contrasena) ──" . CLI_RESET . "\n";
$adminLegacy = detectarLegacyEnTabla($pdo, 'admin_users', 'contrasena');

if (empty($adminLegacy)) {
    echo CLI_GREEN . "  ✓ No se encontraron hashes SHA-256 legacy en admin_users." . CLI_RESET . "\n";
} else {
    echo CLI_YELLOW . "  ⚠  Se encontraron " . count($adminLegacy) . " administrador(es) con hash SHA-256 legacy:" . CLI_RESET . "\n";
    foreach ($adminLegacy as $u) {
        echo "     • ID {$u['id']}: {$u['nombre']} <{$u['correo']}> - hash: {$u['hash_original']}\n";
        registrarEnLog($pdo, 'admin_users', $u);
    }
}

echo "\n";

// ──────────────────────────────────────────────────────
// 3. Escanear users → columna: password
// ──────────────────────────────────────────────────────
echo CLI_CYAN . "── Escaneando users (columna: password) ──" . CLI_RESET . "\n";
$usersLegacy = detectarLegacyEnTabla($pdo, 'users', 'password');

if (empty($usersLegacy)) {
    echo CLI_GREEN . "  ✓ No se encontraron hashes SHA-256 legacy en users." . CLI_RESET . "\n";
} else {
    echo CLI_YELLOW . "  ⚠  Se encontraron " . count($usersLegacy) . " cliente(s) con hash SHA-256 legacy:" . CLI_RESET . "\n";
    foreach ($usersLegacy as $u) {
        echo "     • ID {$u['id']}: {$u['nombre']} <{$u['correo']}> - hash: {$u['hash_original']}\n";
        registrarEnLog($pdo, 'users', $u);
    }
}

echo "\n";

// ──────────────────────────────────────────────────────
// 4. Resumen y recomendaciones
// ──────────────────────────────────────────────────────
$total = count($adminLegacy) + count($usersLegacy);

echo CLI_CYAN . "============================================" . CLI_RESET . "\n";
echo CLI_CYAN . "  RESUMEN FINAL" . CLI_RESET . "\n";
echo CLI_CYAN . "============================================" . CLI_RESET . "\n";
echo "  Admin con SHA-256 legacy: " . count($adminLegacy) . "\n";
echo "  Clientes con SHA-256 legacy: " . count($usersLegacy) . "\n";
echo "  TOTAL: " . $total . "\n\n";

if ($total > 0) {
    echo CLI_YELLOW . "  ⚠  ACCIÓN REQUERIDA:" . CLI_RESET . "\n";
    echo "  Los hashes SHA-256 NO pueden convertirse a bcrypt sin la contraseña\n";
    echo "  en texto plano (imposible criptográficamente).\n\n";
    echo "  Solución recomendada:\n";
    echo "  1. Notificar a los usuarios que inicien sesión (el login los migrará\n";
    echo "     automáticamente a bcrypt al ingresar).\n";
    echo "  2. Como alternativa drástica, ejecutar:\n";
    echo "       php sql/forzar_rehash.php --force\n";
    echo "     (genera contraseñas temporales y obliga a resetear).\n\n";
    echo "  Los usuarios pendientes están registrados en migracion_hashes_log\n";
    echo "  con accion = 'pendiente_migracion'.\n";
} else {
    echo CLI_GREEN . "  ✓ Todos los hashes están actualizados. No se requiere acción." . CLI_RESET . "\n";
}

echo "\n";
