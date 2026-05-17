<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../conexion/conexion.php';
require_once __DIR__ . '/../config/database.php';

class DatabaseTest extends TestCase
{
    public function testDatabaseConstantsAreDefined(): void
    {
        $this->assertNotNull(DB_HOST);
        $this->assertNotNull(DB_NAME);
        $this->assertNotNull(DB_USER);
        $this->assertNotNull(DB_CHARSET);
    }

    public function testDatabaseConnectionReturnsPdoInstance(): void
    {
        try {
            $pdo = conectarDB();
            $this->assertInstanceOf(PDO::class, $pdo);
        } catch (PDOException $e) {
            $this->markTestSkipped(
                'No se pudo conectar a la base de datos: ' . $e->getMessage()
            );
        }
    }

    public function testDatabaseConnectionThrowsExceptionWithInvalidCredentials(): void
    {
        $originalHost = DB_HOST;
        $originalDb = DB_NAME;
        $originalUser = DB_USER;

        try {
            $pdo = new PDO(
                "mysql:host=invalid_host;dbname=invalid_db;charset=utf8mb4",
                "invalid_user",
                "invalid_pass"
            );
            $this->fail('Debería haber lanzado una excepción');
        } catch (PDOException $e) {
            $this->assertStringContainsString('SQLSTATE', $e->getMessage());
        }
    }
}
