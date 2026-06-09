<?php
/**
 * Sistema de cola de notificaciones asíncronas
 * Permite encolar y procesar notificaciones (email, telegram, encuestas)
 * en segundo plano sin bloquear la respuesta HTTP.
 */

require_once __DIR__ . '/../conexion/conexion.php';

function colaNotificacionesCrearTabla(): void {
    $pdo = conectarDB();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cola_notificaciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(30) NOT NULL,
            pedido_id INT DEFAULT NULL,
            factura_id INT DEFAULT NULL,
            estado ENUM('pendiente','procesando','completado','fallido') DEFAULT 'pendiente',
            intentos INT DEFAULT 0,
            max_intentos INT DEFAULT 3,
            datos_extra TEXT DEFAULT NULL,
            error TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            procesado_at DATETIME DEFAULT NULL,
            INDEX idx_estado (estado),
            INDEX idx_tipo (tipo),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function colaNotificacionesAgregar(string $tipo, ?int $pedido_id = null, ?int $factura_id = null, ?array $datos_extra = null): int {
    colaNotificacionesCrearTabla();

    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        INSERT INTO cola_notificaciones (tipo, pedido_id, factura_id, datos_extra, estado, created_at)
        VALUES (?, ?, ?, ?, 'pendiente', NOW())
    ");
    $stmt->execute([
        $tipo,
        $pedido_id,
        $factura_id,
        $datos_extra ? json_encode($datos_extra, JSON_UNESCAPED_UNICODE) : null
    ]);

    return (int) $pdo->lastInsertId();
}

function colaNotificacionesObtenerPendientes(int $limite = 5): array {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        SELECT * FROM cola_notificaciones
        WHERE estado = 'pendiente'
        ORDER BY id ASC
        LIMIT ?
    ");
    $stmt->execute([$limite]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function colaNotificacionesMarcar(int $id, string $estado, ?string $error = null): void {
    $pdo = conectarDB();
    if ($estado === 'completado' || $estado === 'fallido') {
        $stmt = $pdo->prepare("
            UPDATE cola_notificaciones
            SET estado = ?, error = ?, intentos = intentos + 1, procesado_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$estado, $error, $id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE cola_notificaciones
            SET estado = ?, error = ?
            WHERE id = ?
        ");
        $stmt->execute([$estado, $error, $id]);
    }
}

function colaNotificacionesDispararProcesador(): void {
    $scriptPath = __DIR__ . '/procesar.php';
    $phpBin = PHP_BINARY;
    if (PHP_OS_FAMILY === 'Windows') {
        $cmd = 'start /B "" "' . $phpBin . '" "' . $scriptPath . '" > NUL 2>&1';
        pclose(popen($cmd, 'r'));
    } else {
        $cmd = '"' . $phpBin . '" "' . $scriptPath . '" > /dev/null 2>&1 &';
        exec($cmd);
    }
}
