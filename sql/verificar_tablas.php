<?php
require_once __DIR__ . '/../conexion/conexion.php';
try {
    $pdo = Database::getConnection();
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $needed = ['almacenes','producto_almacen','transferencias_almacen','cuentas_cobrar','cuentas_pagar','pagos_cobro','pagos_proveedor','notas_credito','notas_credito_detalles','notas_debito','notas_debito_detalles','producto_atributos','producto_variantes','api_tokens'];
    $missing = array_diff($needed, $tables);
    if (empty($missing)) {
        echo "OK - Todas las " . count($needed) . " tablas creadas\n";
    } else {
        echo "FALTAN: " . implode(', ', $missing) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
