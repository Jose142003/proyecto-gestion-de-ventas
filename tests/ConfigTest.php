<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../config/database.php';

class ConfigTest extends TestCase
{
    public function testDatabaseConstants(): void
    {
        $this->assertIsString(DB_HOST);
        $this->assertIsString(DB_NAME);
        $this->assertIsString(DB_USER);
        $this->assertIsString(DB_PASS);
        $this->assertEquals('utf8mb4', DB_CHARSET);
    }

    public function testAppConstants(): void
    {
        $this->assertIsString(APP_NAME);
        $this->assertIsString(APP_URL);
        $this->assertIsString(APP_ENV);
    }

    public function testDatabaseNameIsNotEmpty(): void
    {
        $this->assertNotEmpty(DB_NAME);
    }
}
