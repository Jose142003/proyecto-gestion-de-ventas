<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../conexion/conexion.php';

try {
    $pdo = conectarDB();
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de conexión: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

// Verificar si el usuario es administrador
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT rol FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$es_admin = false;
$roles_admin = ['admin', 'superadmin', 'ceo'];
if ($user && in_array(strtolower($user['rol']), $roles_admin)) {
    $es_admin = true;
}

// Verificar en admin_users también
if (!$es_admin) {
    $stmt = $pdo->prepare("SELECT rol FROM admin_users WHERE id = ?");
    $stmt->execute([$user_id]);
    $admin_user = $stmt->fetch();
    if ($admin_user) {
        $es_admin = true;
    }
}

if (!$es_admin) {
    echo json_encode(['success' => false, 'error' => 'No tienes permisos de administrador']);
    exit;
}

try {
    // Verificar si la tabla existe, si no, crearla
    $checkTable = $pdo->query("SHOW TABLES LIKE 'configuracion_sistema'");
    
    if ($checkTable->rowCount() == 0) {
        // Crear la tabla según tu SQL
        $createSQL = "CREATE TABLE configuracion_sistema (
            id INT AUTO_INCREMENT PRIMARY KEY,
            clave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT NULL,
            tipo VARCHAR(50) DEFAULT 'text',
            grupo VARCHAR(50) DEFAULT 'general',
            descripcion VARCHAR(255) NULL,
            editable BOOLEAN DEFAULT TRUE,
            orden INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_grupo (grupo),
            INDEX idx_clave (clave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createSQL);
        
        // Insertar valores por defecto
        $defaults = [
            ['empresa_nombre', 'PIC - Productos Industriales y Comerciales', 'text', 'empresa', 'Nombre de la empresa', 1, 1],
            ['empresa_rif', 'J-12345678-9', 'text', 'empresa', 'RIF de la empresa', 1, 2],
            ['empresa_direccion', 'Av. Principal, Zona Industrial, Caracas', 'text', 'empresa', 'Dirección de la empresa', 1, 3],
            ['empresa_telefono', '0212-5551234', 'text', 'empresa', 'Teléfono de contacto', 1, 4],
            ['empresa_email', 'info@pic.com.ve', 'email', 'empresa', 'Email de contacto', 1, 5],
            ['iva_porcentaje', '16', 'number', 'facturacion', 'Porcentaje de IVA aplicado', 1, 10],
            ['moneda_principal', 'Bs', 'text', 'facturacion', 'Moneda principal del sistema', 1, 11],
            ['factura_prefijo', 'FAC', 'text', 'facturacion', 'Prefijo para números de factura', 1, 12],
            ['factura_longitud', '6', 'number', 'facturacion', 'Longitud del correlativo', 1, 13],
            ['notificaciones_email', '1', 'boolean', 'notificaciones', 'Enviar notificaciones por email', 1, 20],
            ['notificaciones_whatsapp', '0', 'boolean', 'notificaciones', 'Enviar notificaciones por WhatsApp', 1, 21],
            ['stock_minimo_alerta', '5', 'number', 'inventario', 'Stock mínimo para alertas', 1, 30],
            ['modo_mantenimiento', '0', 'boolean', 'sistema', 'Modo mantenimiento del sistema', 1, 40],
            ['version_sistema', '2.0.0', 'text', 'sistema', 'Versión actual del sistema', 0, 41]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO configuracion_sistema (clave, valor, tipo, grupo, descripcion, editable, orden) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($defaults as $default) {
            try {
                $stmt->execute($default);
            } catch (PDOException $e) {
                // Ignorar duplicados
            }
        }
    }

    // Obtener configuraciones
    $query = "SELECT clave, valor, tipo, descripcion, editable, grupo, orden 
              FROM configuracion_sistema 
              ORDER BY grupo ASC, orden ASC";
    
    $stmt = $pdo->query($query);
    $configuraciones = [];
    
    while ($row = $stmt->fetch()) {
        $configuraciones[] = [
            'clave' => $row['clave'],
            'valor' => $row['valor'] !== null ? (string)$row['valor'] : '',
            'tipo' => $row['tipo'],
            'descripcion' => $row['descripcion'] ?: '',
            'editable' => (bool)$row['editable'],
            'grupo' => $row['grupo'],
            'orden' => (int)$row['orden']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $configuraciones,
        'total' => count($configuraciones)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log("Error en obtener_configuracion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error en obtener_configuracion.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>