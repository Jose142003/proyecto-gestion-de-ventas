<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class SistemaTest extends TestCase
{
    private string $tempEnvFile;

    protected function setUp(): void
    {
        $this->tempEnvFile = sys_get_temp_dir() . '/test_env_' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            unlink($this->tempEnvFile);
        }
    }

    public function testDatabaseConstantsDefined(): void
    {
        $this->assertTrue(defined('DB_HOST'), 'DB_HOST should be defined');
        $this->assertTrue(defined('DB_NAME'), 'DB_NAME should be defined');
        $this->assertTrue(defined('DB_USER'), 'DB_USER should be defined');
        $this->assertTrue(defined('DB_CHARSET'), 'DB_CHARSET should be defined');
    }

    public function testBaseUrlConstantDefined(): void
    {
        $this->assertTrue(defined('BASE_URL'), 'BASE_URL should be defined');
        $this->assertEquals('/proyecto', BASE_URL);
    }

    public function testSmptConstantsDefined(): void
    {
        $this->assertTrue(defined('SMTP_HOST'), 'SMTP_HOST should be defined');
        $this->assertTrue(defined('SMTP_PORT'), 'SMTP_PORT should be defined');
    }

    public function testConfigEnvLoading(): void
    {
        $content = <<<ENV
DB_HOST=localhost
DB_NAME=test_db
DB_USER=test_user
DB_PASS=test_pass
ENV;
        file_put_contents($this->tempEnvFile, $content);

        $envFile = $this->tempEnvFile;
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $loaded = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $loaded[trim($key)] = trim($value);
            }
        }

        $this->assertEquals('test_db', $loaded['DB_NAME']);
        $this->assertEquals('test_user', $loaded['DB_USER']);
        $this->assertEquals('test_pass', $loaded['DB_PASS']);
    }

    public function testPasswordHashing(): void
    {
        $password = 'TestPass123!';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertNotFalse($hash);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('WrongPass', $hash));
    }

    public function testCsrfTokenGeneration(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $token = $_SESSION['_csrf_token'];

        $this->assertEquals(64, strlen($token));
        $this->assertTrue(ctype_xdigit($token));

        $valid = !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
        $this->assertTrue($valid);

        $invalid = !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], 'fake-token');
        $this->assertFalse($invalid);
    }

    public function testEmailValidation(): void
    {
        $validEmails = ['test@example.com', 'user.name@domain.co', 'admin@localhost'];
        $invalidEmails = ['not-an-email', '@domain.com', 'user@', '', null];

        foreach ($validEmails as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "Email should be valid: $email");
        }

        foreach (array_filter($invalidEmails, fn($e) => $e !== null) as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "Email should be invalid: $email");
        }
    }

    public function testJsonResponseStructure(): void
    {
        $data = ['success' => true, 'message' => 'OK', 'data' => ['id' => 1]];
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);
        $this->assertTrue($decoded['success']);
        $this->assertEquals('OK', $decoded['message']);
        $this->assertEquals(1, $decoded['data']['id']);
    }

    public function testStockValidation(): void
    {
        $this->assertStockCalculation(10, 3, 7);
        $this->assertStockCalculation(0, 1, -1);
        $this->assertStockCalculation(5, 5, 0);
    }

    private function assertStockCalculation(int $stock, int $cantidad, int $esperado): void
    {
        $resultado = $stock - $cantidad;
        $this->assertEquals($esperado, $resultado, 
            "Stock $stock - cantidad $cantidad debería ser $esperado");
    }
}
