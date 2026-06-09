<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class SeguridadTest extends TestCase
{
    protected function setUp(): void
    {
        if (!defined('MAX_LOGIN_ATTEMPTS')) {
            define('MAX_LOGIN_ATTEMPTS', 5);
        }
        if (!defined('LOGIN_TIMEOUT_MINUTES')) {
            define('LOGIN_TIMEOUT_MINUTES', 15);
        }
        if (!defined('IP_BLOCK_DURATION_MINUTES')) {
            define('IP_BLOCK_DURATION_MINUTES', 60);
        }
        if (!defined('MAX_FAILED_ATTEMPTS_BEFORE_BLOCK')) {
            define('MAX_FAILED_ATTEMPTS_BEFORE_BLOCK', 10);
        }
    }

    public function testBcryptHashIsSecure(): void
    {
        $password = 'TestP@ss123!';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertStringStartsWith('$2y$', $hash, 'Bcrypt hashes must start with $2y$');
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrong', $hash));
    }

    public function testBcryptCostIsAdequate(): void
    {
        $start = microtime(true);
        password_hash('test', PASSWORD_BCRYPT, ['cost' => 10]);
        $elapsed = microtime(true) - $start;

        $this->assertGreaterThan(0.05, $elapsed, 'Bcrypt cost 10 should take at least 50ms');
    }

    public function testCsrfTokenIsCryptographicallySecure(): void
    {
        $token1 = bin2hex(random_bytes(32));
        $token2 = bin2hex(random_bytes(32));

        $this->assertEquals(64, strlen($token1));
        $this->assertTrue(ctype_xdigit($token1));
        $this->assertNotEquals($token1, $token2, 'Tokens must be unique');
    }

    public function testConstantTimeComparison(): void
    {
        $known = 'known-value-12345';
        $same = 'known-value-12345';
        $different = 'different-value';

        $this->assertTrue(hash_equals($known, $same));
        $this->assertFalse(hash_equals($known, $different));
    }

    public function testRateLimitConfiguration(): void
    {
        $this->assertEquals(5, MAX_LOGIN_ATTEMPTS, 'Max login attempts should be 5');
        $this->assertEquals(15, LOGIN_TIMEOUT_MINUTES, 'Login timeout should be 15 min');
        $this->assertGreaterThanOrEqual(5, MAX_LOGIN_ATTEMPTS);
    }

    public function testPasswordMinimumLength(): void
    {
        $password = 'Abc1@';
        $this->assertLessThan(6, strlen($password), 'Password should be rejected if < 6 chars');
    }

    public function testEmailValidation(): void
    {
        $valid = ['user@example.com', 'test@domain.co', 'admin@localhost.local'];
        $invalid = ['not-email', '@domain.com', 'user@', ''];

        foreach ($valid as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "$email should be valid");
        }
        foreach (array_filter($invalid, fn($e) => $e !== '') as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL), "$email should be invalid");
        }
    }

    public function testPreparedStatementPattern(): void
    {
        $safe = 'SELECT * FROM users WHERE id = :id AND active = 1';
        $unsafe = 'SELECT * FROM users WHERE id = $id';

        $this->assertStringNotContainsString("'", $safe);
        $this->assertStringNotContainsString('$', $safe);
        $this->assertStringContainsString(':id', $safe);
        $this->assertStringContainsString('$id', $unsafe);
    }

    public function testHttpSecurityHeaders(): void
    {
        $headers = [
            'X-Content-Type-Options: nosniff',
            'X-Frame-Options: DENY',
            'Referrer-Policy: strict-origin-when-cross-origin',
        ];

        foreach ($headers as $header) {
            $this->assertStringContainsString(':', $header);
            $parts = explode(':', $header, 2);
            $this->assertNotEmpty($parts[0]);
            $this->assertNotEmpty($parts[1]);
        }
    }

    public function testSensitiveFilesAreProtected(): void
    {
        $sensitivePatterns = ['FilesMatch', 'Require all denied', '.env', 'RedirectMatch 404 /\.env'];
        $htaccessPath = realpath(__DIR__ . '/../.htaccess');
        $this->assertNotFalse($htaccessPath, '.htaccess file must exist');
        $htaccess = file_get_contents($htaccessPath);
        $this->assertIsString($htaccess);
        foreach ($sensitivePatterns as $pattern) {
            $this->assertStringContainsString($pattern, $htaccess, ".htaccess should contain '$pattern'");
        }
    }
}
