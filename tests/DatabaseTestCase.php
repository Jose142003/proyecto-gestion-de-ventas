<?php
declare(strict_types=1);
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;
use PDO;

abstract class DatabaseTestCase extends TestCase
{
    protected static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        if (self::$pdo !== null) return;

        try {
            $host = getenv('DB_HOST') ?: 'localhost';
            $name = getenv('DB_NAME') ?: 'carrito_db_test';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASS') ?: '';

            self::$pdo = new PDO(
                "mysql:host=$host;charset=utf8mb4",
                $user, $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            self::$pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            self::$pdo->exec("USE `$name`");

            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS products (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    sku VARCHAR(100) NOT NULL UNIQUE,
                    description TEXT,
                    price DECIMAL(10,2) DEFAULT 0,
                    stock INT DEFAULT 0,
                    category VARCHAR(100) DEFAULT '',
                    image_url VARCHAR(500) DEFAULT '',
                    rating DECIMAL(3,2) DEFAULT 0,
                    specs TEXT,
                    weight DECIMAL(10,2) DEFAULT 0,
                    dimensions VARCHAR(100) DEFAULT '',
                    currency VARCHAR(10) DEFAULT 'USD',
                    is_featured TINYINT(1) DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    nombre VARCHAR(255) NOT NULL,
                    correo VARCHAR(255) NOT NULL UNIQUE,
                    password VARCHAR(255) NOT NULL,
                    telefono VARCHAR(20) DEFAULT '',
                    cedula VARCHAR(20) DEFAULT '',
                    is_active TINYINT(1) DEFAULT 1,
                    estado VARCHAR(20) DEFAULT 'activo',
                    last_login DATETIME DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            self::$pdo->exec("DROP TABLE IF EXISTS facturas");
            self::$pdo->exec("
                CREATE TABLE facturas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    numero_factura VARCHAR(50) NOT NULL,
                    usuario_id INT DEFAULT NULL,
                    cliente_id INT DEFAULT NULL,
                    total DECIMAL(10,2) DEFAULT 0,
                    estado VARCHAR(20) DEFAULT 'pendiente',
                    metodo_pago VARCHAR(50) DEFAULT '',
                    fecha_emision TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS historial_stock (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    producto_id INT NOT NULL,
                    cantidad INT NOT NULL,
                    tipo VARCHAR(50) NOT NULL,
                    stock_anterior INT DEFAULT 0,
                    stock_nuevo INT DEFAULT 0,
                    referencia VARCHAR(255) DEFAULT '',
                    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

        } catch (\PDOException $e) {
            self::markTestSkipped("MySQL no disponible: " . $e->getMessage());
        }
    }

    protected function setUp(): void
    {
        if (self::$pdo === null) {
            $this->markTestSkipped('Base de datos no disponible');
        }
        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$pdo !== null && self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }

    protected function getConnection(): PDO
    {
        return self::$pdo;
    }

    protected function createTestProduct(array $overrides = []): array
    {
        $data = array_merge([
            'name' => 'Producto Test',
            'sku' => 'TEST-' . uniqid(),
            'description' => 'Descripción de prueba',
            'price' => 100.00,
            'stock' => 50,
            'category' => 'Pruebas',
            'currency' => 'USD',
        ], $overrides);

        $stmt = self::$pdo->prepare("
            INSERT INTO products (name, sku, description, price, stock, category, currency, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([$data['name'], $data['sku'], $data['description'], $data['price'], $data['stock'], $data['category'], $data['currency']]);
        $data['id'] = (int) self::$pdo->lastInsertId();
        return $data;
    }

    protected function createTestUser(array $overrides = []): array
    {
        $data = array_merge([
            'nombre' => 'Usuario Test',
            'correo' => 'test-' . uniqid() . '@test.com',
            'password' => password_hash('Test123!', PASSWORD_BCRYPT),
            'telefono' => '04121234567',
            'cedula' => 'V-12345678',
        ], $overrides);

        $stmt = self::$pdo->prepare("
            INSERT INTO users (nombre, correo, password, telefono, cedula, is_active, estado, created_at)
            VALUES (?, ?, ?, ?, ?, 1, 'activo', NOW())
        ");
        $stmt->execute([$data['nombre'], $data['correo'], $data['password'], $data['telefono'], $data['cedula']]);
        $data['id'] = (int) self::$pdo->lastInsertId();
        return $data;
    }
}
