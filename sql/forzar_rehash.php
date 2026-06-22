<?php
/**
 * FORZAR_REHASH.PHP — Fuerza migración de hashes SHA-256 legacy
 * 
 * Propósito:
 *   Para usuarios con contraseñas almacenadas como SHA-256 simple (inseguro),
 *   genera una contraseña temporal aleatoria, la almacena como bcrypt, y marca
 *   al usuario para que restablezca su contraseña en el próximo inicio de sesión.
 * 
 *   Útil cuando no se puede esperar a que cada usuario inicie sesión por su
 *   cuenta (ej: violación de seguridad, migración forzosa, política interna).
 * 
 * Uso:
 *   php sql/forzar_rehash.php --force
 * 
 *   SIN --force el script se niega a ejecutar (medida de seguridad).
 * 
 * Flags:
 *   --force       Ejecuta la migración (requerido).
 *   --dry-run     Solo muestra lo que haría sin modificar nada.
 *   --verbose     Muestra las contraseñas temporales generadas (peligroso).
 *                 Por defecto solo muestra el ID y nombre del usuario.
 * 
 * Advertencias:
 *   - Los usuarios PERDERÁN su contraseña actual y recibirán una temporal.
 *   - Las contraseñas temporales se muestran UNA SOLA VEZ por pantalla.
 *   - Se recomienda distribuir las contraseñas de forma segura a cada usuario.
 *   - IDEMPOTENTE: si se ejecuta de nuevo, omite a los ya migrados.
 */

require_once __DIR__ . '/../conexion/conexion.php';

// ──────────────────────────────────────────────────────
// Colores CLI
// ──────────────────────────────────────────────────────
define('CLI_GREEN',  "\033[32m");
define('CLI_YELLOW', "\033[33m");
define('CLI_RED',    "\033[31m");
define('CLI_CYAN',   "\033[36m");
define('CLI_MAGENTA',"\033[35m");
define('CLI_RESET',  "\033[0m");

// ──────────────────────────────────────────────────────
// Parsear argumentos
// ──────────────────────────────────────────────────────
$args = $argv ?? [];
$hasForce   = in_array('--force', $args);
$isDryRun   = in_array('--dry-run', $args);
$isVerbose  = in_array('--verbose', $args);

// Banner
echo CLI_MAGENTA . "============================================" . CLI_RESET . "\n";
echo CLI_MAGENTA . "  FORZAR REHASH — Migración SHA-256 → bcrypt" . CLI_RESET . "\n";
echo CLI_MAGENTA . "============================================" . CLI_RESET . "\n\n";

if (!$hasForce) {
    echo CLI_RED . "  ⛔  SEGURIDAD: Debes usar --force para ejecutar este script." . CLI_RESET . "\n";
    echo "  Ejemplo: php sql/forzar_rehash.php --force\n";
    echo "  Prueba:   php sql/forzar_rehash.php --dry-run (simulación)\n\n";
    exit(1);
}

if ($isDryRun) {
    echo CLI_YELLOW . "  ▶  MODO DRY-RUN: No se modificará nada." . CLI_RESET . "\n\n";
} else {
    echo CLI_RED . "  ⚠  ATENCIÓN: Este script MODIFICARÁ contraseñas." . CLI_RESET . "\n\n";
}

// ──────────────────────────────────────────────────────
// Conectar BD
// ──────────────────────────────────────────────────────
try {
    $pdo = conectarDB();
} catch (Exception $e) {
    echo CLI_RED . "ERROR: " . $e->getMessage() . CLI_RESET . "\n";
    exit(1);
}

// ──────────────────────────────────────────────────────
// Funciones auxiliares
// ──────────────────────────────────────────────────────

/**
 * Determina si un string parece un hash SHA-256 legacy.
 */
function esSHA256Legacy(string $hash): bool {
    return strlen($hash) === 64 && ctype_xdigit($hash);
}

/**
 * Genera una contraseña temporal segura y legible.
 */
function generarTempPassword(): string {
    $mayus = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
    $minus = 'abcdefghijkmnpqrstuvwxyz';
    $digits = '23456789';
    $chars = $mayus . $minus . $digits;
    $password = '';
    $password .= $mayus[random_int(0, strlen($mayus) - 1)];
    $password .= $minus[random_int(0, strlen($minus) - 1)];
    $password .= $digits[random_int(0, strlen($digits) - 1)];
    for ($i = 0; $i < 9; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return str_shuffle($password);
}

/**
 * Asegura que exista la columna password_reset_required en una tabla.
 */
function asegurarColumnaReset(PDO $pdo, string $tabla): void {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$tabla` LIKE 'password_reset_required'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE `$tabla` ADD COLUMN `password_reset_required` TINYINT(1) NOT NULL DEFAULT 0
                    COMMENT '1 = debe cambiar contraseña en el próximo login'");
        echo CLI_GREEN . "  [OK] Columna password_reset_required agregada a {$tabla}" . CLI_RESET . "\n";
    }
}

/**
 * Asegura que exista la tabla de log.
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

/**
 * Obtiene los usuarios legacy de una tabla que aún no han sido migrados.
 */
function obtenerPendientes(PDO $pdo, string $tabla, string $colPassword): array {
    $registrados = $pdo->prepare("SELECT usuario_id FROM migracion_hashes_log WHERE tabla = ? AND accion IN ('forzado_rehash', 'migrado_login')");
    $registrados->execute([$tabla]);
    $idsMigrados = $registrados->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare("SELECT id, nombre, correo, $colPassword AS password FROM $tabla WHERE 1=1");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pendientes = [];
    foreach ($usuarios as $u) {
        if (in_array($u['id'], $idsMigrados)) {
            continue; // Ya procesado
        }
        if (esSHA256Legacy($u['password']) || esSHA256Legacy(strtolower($u['password']))) {
            $pendientes[] = $u;
        }
    }
    return $pendientes;
}

/**
 * Procesa la migración forzada de un usuario.
 */
function migrarUsuario(PDO $pdo, string $tabla, string $colPassword, array $usuario, string $tempPassword, bool $dryRun, bool $verbose): void {
    $hashBcrypt = password_hash($tempPassword, PASSWORD_BCRYPT);

    if ($verbose) {
        echo "     └─ Temp password: " . CLI_YELLOW . $tempPassword . CLI_RESET . "\n";
    } else {
        echo "     └─ (usa --verbose para ver la contraseña temporal)\n";
    }

    if (!$dryRun) {
        // Actualizar contraseña a bcrypt de la temp
        $update = $pdo->prepare("UPDATE `$tabla` SET `$colPassword` = ?, `password_reset_required` = 1 WHERE id = ?");
        $update->execute([$hashBcrypt, $usuario['id']]);

        // Registrar en log
        $check = $pdo->prepare("SELECT id FROM migracion_hashes_log WHERE tabla = ? AND usuario_id = ?");
        $check->execute([$tabla, $usuario['id']]);
        if ($existing = $check->fetch()) {
            $pdo->prepare("UPDATE migracion_hashes_log SET accion = 'forzado_rehash', migrated_at = NOW() WHERE id = ?")
                ->execute([$existing['id']]);
        } else {
            $pdo->prepare("INSERT INTO migracion_hashes_log (tabla, usuario_id, nombre, correo, hash_anterior, hash_tipo, accion, migrated_at)
                           VALUES (?, ?, ?, ?, ?, 'sha256_legacy', 'forzado_rehash', NOW())")
                ->execute([$tabla, $usuario['id'], $usuario['nombre'], $usuario['correo'], $usuario['password']]);
        }
    }
}

// ──────────────────────────────────────────────────────
// EJECUCIÓN PRINCIPAL
// ──────────────────────────────────────────────────────

echo CLI_CYAN . "── Preparando entorno ──" . CLI_RESET . "\n";
asegurarTablaLog($pdo);
echo CLI_GREEN . "  [OK] Tabla migracion_hashes_log lista" . CLI_RESET . "\n";

if (!$dryRun) {
    asegurarColumnaReset($pdo, 'admin_users');
    asegurarColumnaReset($pdo, 'users');
}
echo "\n";

// ──────────────────────────────────────────────────────
// admin_users
// ──────────────────────────────────────────────────────
echo CLI_CYAN . "── Procesando admin_users ──" . CLI_RESET . "\n";
$pendientesAdmin = obtenerPendientes($pdo, 'admin_users', 'contrasena');

if (empty($pendientesAdmin)) {
    echo CLI_GREEN . "  ✓ No hay administradores pendientes de migrar." . CLI_RESET . "\n";
} else {
    echo CLI_YELLOW . "  ⚠  " . count($pendientesAdmin) . " administrador(es) pendiente(s):" . CLI_RESET . "\n";
    foreach ($pendientesAdmin as $u) {
        $tempPass = generarTempPassword();
        echo "  • ID {$u['id']}: {$u['nombre']} <{$u['correo']}>\n";
        migrarUsuario($pdo, 'admin_users', 'contrasena', $u, $tempPass, $isDryRun, $isVerbose);
    }
}

echo "\n";

// ──────────────────────────────────────────────────────
// users
// ──────────────────────────────────────────────────────
echo CLI_CYAN . "── Procesando users ──" . CLI_RESET . "\n";
$pendientesUsers = obtenerPendientes($pdo, 'users', 'password');

if (empty($pendientesUsers)) {
    echo CLI_GREEN . "  ✓ No hay clientes pendientes de migrar." . CLI_RESET . "\n";
} else {
    echo CLI_YELLOW . "  ⚠  " . count($pendientesUsers) . " cliente(s) pendiente(s):" . CLI_RESET . "\n";
    foreach ($pendientesUsers as $u) {
        $tempPass = generarTempPassword();
        echo "  • ID {$u['id']}: {$u['nombre']} <{$u['correo']}>\n";
        migrarUsuario($pdo, 'users', 'password', $u, $tempPass, $isDryRun, $isVerbose);
    }
}

echo "\n";

// ──────────────────────────────────────────────────────
// Resumen final
// ──────────────────────────────────────────────────────
$totalPendientes = count($pendientesAdmin) + count($pendientesUsers);

echo CLI_MAGENTA . "============================================" . CLI_RESET . "\n";
echo CLI_MAGENTA . "  RESUMEN" . CLI_RESET . "\n";
echo CLI_MAGENTA . "============================================" . CLI_RESET . "\n";
echo "  Administradores procesados: " . count($pendientesAdmin) . "\n";
echo "  Clientes procesados:        " . count($pendientesUsers) . "\n";
echo "  TOTAL:                      " . $totalPendientes . "\n\n";

if ($isDryRun) {
    echo CLI_YELLOW . "  ▶  Dry-run completado. Ejecuta sin --dry-run para aplicar." . CLI_RESET . "\n";
} elseif ($totalPendientes > 0) {
    echo CLI_RED . "  ⚠  IMPORTANTE:" . CLI_RESET . "\n";
    echo "  Las contraseñas temporales se mostraron arriba (usa --verbose).\n";
    echo "  Si no usaste --verbose, vuelve a ejecutar con --dry-run --verbose\n";
    echo "  para consultar las contraseñas antes de aplicar.\n";
    echo "  Distribuye las contraseñas de forma segura a cada usuario.\n";
    echo "  Los usuarios tienen password_reset_required = 1 para forzar\n";
    echo "  el cambio de contraseña en su próximo inicio de sesión.\n";
} else {
    echo CLI_GREEN . "  ✓ Todos los usuarios ya están migrados o no tenían SHA-256." . CLI_RESET . "\n";
}

echo "\n";
