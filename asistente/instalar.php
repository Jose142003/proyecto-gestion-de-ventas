<?php
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../conexion/conexion.php';

try {
    $db = Database::getConnection();
    $sql = file_get_contents(__DIR__ . '/../sql/asistente_tecnico.sql');
    
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    $statements = explode(';', $sql);
    $count = 0;
    
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if (!empty($stmt)) {
            try {
                $db->exec($stmt);
                $count++;
            } catch (PDOException $e) {
                echo "<span style='color:#dc3545'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
            }
        }
    }
    
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "<div style='font-family:sans-serif;padding:20px;background:#d4edda;border-radius:8px;max-width:600px;margin:40px auto'>";
    echo "<h2 style='color:#155724'>✓ Asistente Técnico instalado correctamente</h2>";
    echo "<p>Tablas creadas: formulas_tecnicas, compatibilidad_marcas, configuraciones_tablero, alertas_mantenimiento</p>";
    echo "<p><a href='/proyecto/admin/asistente_tecnico.php' style='color:#155724;font-weight:700'>Ir al Asistente Técnico →</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='font-family:sans-serif;padding:20px;background:#f8d7da;border-radius:8px;max-width:600px;margin:40px auto'>";
    echo "<h2 style='color:#721c24'>✗ Error de conexión</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
