<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

echo "=== Seed: Datos de prueba para PIC ===\n\n";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

    echo "✓ Base de datos seleccionada\n";

    // Admin por defecto
    $adminPass = password_hash('Admin123!', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin_users (username, email, password_hash, rol, activo) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute(['admin', 'admin@pic.com', $adminPass, 'superadmin']);
    echo "✓ Admin creado (admin@pic.com / Admin123!)\n";

    // Categorías
    $categorias = ['Herramientas Eléctricas', 'Tuberías y Conexiones', 'Válvulas Industriales', 'Equipos de Protección', 'Lubricantes', 'Instrumentación'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name) VALUES (?)");
    foreach ($categorias as $cat) {
        $stmt->execute([$cat]);
    }
    echo "✓ " . count($categorias) . " categorías creadas\n";

    // Productos
    $productos = [
        ['Taladro Percutor 650W', 'HER-001', 120.50, 50, 'Herramientas Eléctricas'],
        ['Tubería PVC 2" x 3m', 'TUB-001', 8.75, 200, 'Tuberías y Conexiones'],
        ['Válvula de Compuerta 4"', 'VAL-001', 45.00, 30, 'Válvulas Industriales'],
        ['Casco de Seguridad', 'EPP-001', 15.99, 100, 'Equipos de Protección'],
        ['Aceite Lubricante 5L', 'LUB-001', 28.50, 60, 'Lubricantes'],
        ['Manómetro 0-100 PSI', 'INS-001', 35.00, 40, 'Instrumentación'],
        ['Amoladora Angular 4½"', 'HER-002', 85.00, 35, 'Herramientas Eléctricas'],
        ['Codo PVC 2" 90°', 'TUB-002', 2.50, 500, 'Tuberías y Conexiones'],
        ['Válvula de Bola 2"', 'VAL-002', 22.00, 45, 'Válvulas Industriales'],
        ['Guantes de Seguridad Talla L', 'EPP-002', 12.00, 150, 'Equipos de Protección'],
        ['Grasa Industrial 1kg', 'LUB-002', 18.00, 80, 'Lubricantes'],
        ['Termómetro Digital -50 a 300°C', 'INS-002', 42.00, 25, 'Instrumentación'],
    ];

    $stmtProd = $pdo->prepare("INSERT IGNORE INTO products (name, sku, description, price, stock, category, image_url, rating, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, '', 0, NOW(), NOW())");
    foreach ($productos as $p) {
        $stmtProd->execute([$p[0], $p[1], "Descripción de {$p[0]}", $p[2], $p[3], $p[4]]);
    }
    echo "✓ " . count($productos) . " productos creados\n";

    // Cliente de prueba
    $clientPass = password_hash('Cliente123!', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (nombre, correo, password, telefono, cedula, is_active, estado, created_at) VALUES (?, ?, ?, ?, ?, 1, 'activo', NOW())");
    $stmt->execute(['Cliente Prueba', 'cliente@pic.com', $clientPass, '04121234567', 'V-12345678']);
    echo "✓ Cliente creado (cliente@pic.com / Cliente123!)\n";

    echo "\n=== Seed completado exitosamente ===\n";

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
