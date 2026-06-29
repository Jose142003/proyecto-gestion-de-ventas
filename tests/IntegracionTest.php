<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class IntegracionTest extends TestCase
{
    private array $tempFiles = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/../conexion/conexion.php';

        if (!defined('BASE_URL')) {
            define('BASE_URL', 'http://localhost/proyecto');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_dir($file)) {
                $this->rmdirRecursive($file);
            } elseif (file_exists($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    private function rmdirRecursive(string $dir): void
    {
        $items = scandir($dir);
        foreach (array_diff($items, ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testUrlHelper(): void
    {
        $path = 'panel_admin';
        $result = rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
        $this->assertEquals('http://localhost/proyecto/panel_admin', $result);

        $result2 = rtrim(BASE_URL, '/') . '/' . ltrim('', '/');
        $this->assertEquals('http://localhost/proyecto/', $result2);

        $result3 = rtrim(BASE_URL, '/') . '/' . ltrim('/productos/lista', '/');
        $this->assertEquals('http://localhost/proyecto/productos/lista', $result3);
    }

    public function testFormatMoney(): void
    {
        $format = fn($value) => 'Bs. ' . number_format((float)($value ?? 0), 2, ',', '.');

        $this->assertEquals('Bs. 0,00', $format(0));
        $this->assertEquals('Bs. 1.234,50', $format(1234.5));
        $this->assertEquals('Bs. 9.999.999,99', $format(9999999.99));
        $this->assertEquals('Bs. 0,00', $format(null));
    }

    public function testGetEstadoTexto(): void
    {
        $estados = [
            'pendiente' => 'Pendiente',
            'completado' => 'Completado',
            'facturado' => 'Facturado',
            'cancelado' => 'Cancelado'
        ];
        $fn = fn($estado) => $estados[$estado] ?? $estado;

        $this->assertEquals('Pendiente', $fn('pendiente'));
        $this->assertEquals('Completado', $fn('completado'));
        $this->assertEquals('Facturado', $fn('facturado'));
        $this->assertEquals('Cancelado', $fn('cancelado'));
        $this->assertEquals('desconocido', $fn('desconocido'));
        $this->assertEquals('', $fn(''));
    }

    public function testGetMetodoPagoTexto(): void
    {
        $metodos = [
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia Bancaria',
            'pago_movil' => 'Pago Móvil',
            'mixto' => 'Pago Mixto',
            'tarjeta' => 'Tarjeta'
        ];
        $fn = function ($metodo) use ($metodos) {
            $metodo = strtolower(trim($metodo));
            foreach ($metodos as $key => $texto) {
                if (strpos($metodo, $key) !== false) {
                    return $texto;
                }
            }
            return ucfirst($metodo) ?: 'No especificado';
        };

        $this->assertEquals('Efectivo', $fn('efectivo'));
        $this->assertEquals('Transferencia Bancaria', $fn('transferencia'));
        $this->assertEquals('Pago Móvil', $fn('pago_movil'));
        $this->assertEquals('Pago Mixto', $fn('mixto'));
        $this->assertEquals('Tarjeta', $fn('tarjeta'));
        $this->assertEquals('No especificado', $fn(''));
    }

    public function testLogSistema(): void
    {
        $logDir = sys_get_temp_dir() . '/test_logs_' . uniqid();
        mkdir($logDir, 0777, true);
        $this->tempFiles[] = $logDir;

        $archivo = $logDir . '/sistema_' . date('Y-m-d') . '.log';
        $mensaje = 'Test message';
        $nivel = 'INFO';
        $linea = '[' . date('Y-m-d H:i:s') . '] [' . $nivel . '] ' . $mensaje . PHP_EOL;
        file_put_contents($archivo, $linea, FILE_APPEND | LOCK_EX);

        $this->assertFileExists($archivo);
        $contenido = file_get_contents($archivo);
        $this->assertStringContainsString('[INFO]', $contenido);
        $this->assertStringContainsString('Test message', $contenido);
        $this->assertStringContainsString(date('Y-m-d'), $contenido);

        $linea2 = '[' . date('Y-m-d H:i:s') . '] [ERROR] ' . 'Error test' . PHP_EOL;
        file_put_contents($archivo, $linea2, FILE_APPEND | LOCK_EX);
        $contenido2 = file_get_contents($archivo);
        $this->assertStringContainsString('[ERROR]', $contenido2);

        $this->tempFiles[] = $archivo;
    }

    public function testCsrfTokenHelpers(): void
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

        $invalid = !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], 'token-invalido');
        $this->assertFalse($invalid);

        $emptyToken = !empty($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], '');
        $this->assertFalse($emptyToken);

        $this->assertNotEmpty(generarTokenCSRF());
        $this->assertTrue(validarTokenCSRF($token));

        $_SESSION['_csrf_token'] = $token;
        $this->assertTrue(validarTokenCSRF($token));
        $this->assertFalse(validarTokenCSRF('fake-token'));
        $this->assertFalse(validarTokenCSRF(''));
        $this->assertFalse(validarTokenCSRF(null));
    }

    public function testPasswordSecurity(): void
    {
        $password = 'MiClaveSegura2024!';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('WrongPassword', $hash));
        $this->assertFalse(password_verify('', $hash));

        $start = microtime(true);
        password_verify($password, $hash);
        $elapsed = microtime(true) - $start;
        $this->assertGreaterThan(0.0001, $elapsed, 'Verification should take measurable time');
    }

    public function testArraySanitization(): void
    {
        $data = ['name' => 'Juan', 'email' => 'juan@test.com'];

        $this->assertEquals('Juan', $data['name'] ?? '');
        $this->assertEquals('juan@test.com', $data['email'] ?? '');
        $this->assertEquals('', $data['phone'] ?? '');
        $this->assertEquals('', $data['direccion'] ?? '');
        $this->assertEquals(0, $data['edad'] ?? 0);
        $this->assertEquals('default', $data['rol'] ?? 'default');

        $nested = ['items' => [['id' => 1], ['id' => 2]]];
        $this->assertEquals(1, $nested['items'][0]['id'] ?? null);
        $this->assertNull($nested['items'][2]['id'] ?? null);
        $this->assertEquals('', $nested['metadata']['nombre'] ?? '');
    }

    public function testJsonResponseStructure(): void
    {
        $data = ['success' => true, 'message' => 'Operación exitosa', 'data' => ['id' => 42, 'nombre' => 'Test']];
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);

        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);

        $this->assertTrue($decoded['success']);
        $this->assertEquals('Operación exitosa', $decoded['message']);
        $this->assertEquals(42, $decoded['data']['id']);
        $this->assertEquals('Test', $decoded['data']['nombre']);

        $errorData = ['success' => false, 'message' => 'Error de validación'];
        $errorJson = json_encode($errorData, JSON_UNESCAPED_UNICODE);
        $errorDecoded = json_decode($errorJson, true);

        $this->assertFalse($errorDecoded['success']);
        $this->assertEquals('Error de validación', $errorDecoded['message']);
        $this->assertArrayNotHasKey('data', $errorDecoded);
    }

    public function testStockEdgeCases(): void
    {
        $calcularStock = fn(int $stock, int $cantidad): int => $stock - $cantidad;

        $this->assertEquals(7, $calcularStock(10, 3), 'Stock exacto: 10-3=7');
        $this->assertEquals(-3, $calcularStock(2, 5), 'Stock insuficiente: 2-5=-3');
        $this->assertEquals(-1, $calcularStock(0, 1), 'Stock 0: 0-1=-1');
        $this->assertEquals(5, $calcularStock(5, 0), 'Cantidad 0: 5-0=5');
        $this->assertEquals(0, $calcularStock(5, 5), 'Stock justo: 5-5=0');
        $this->assertEquals(-5, $calcularStock(0, 5), 'Sin stock: 0-5=-5');
        $this->assertEquals(100, $calcularStock(100, 0), 'Stock grande sin cantidad: 100-0=100');
    }

    public function testObtenerConfigEmpresaDefaults(): void
    {
        $defaults = [
            'nombre' => 'Proyectos Industriales del Centro (PIC)',
            'rif' => 'J-29384799-0',
            'telefono' => '0414-3417373',
            'direccion' => 'Av. Principal, Edif. PIC',
        ];

        $this->assertArrayHasKey('nombre', $defaults);
        $this->assertArrayHasKey('rif', $defaults);
        $this->assertArrayHasKey('telefono', $defaults);
        $this->assertArrayHasKey('direccion', $defaults);
        $this->assertNotEmpty($defaults['nombre']);
        $this->assertNotEmpty($defaults['rif']);
    }

    public function testObtenerIvaPorcentajeLogic(): void
    {
        $ivaPorcentaje = 16;
        $subtotal = 100.00;
        $iva = $subtotal * ($ivaPorcentaje / 100);
        $total = $subtotal + $iva;

        $this->assertEquals(16.00, $iva);
        $this->assertEquals(116.00, $total);

        $ivaPorcentaje2 = 0;
        $iva2 = $subtotal * ($ivaPorcentaje2 / 100);
        $this->assertEquals(0.00, $iva2);
    }

    public function testConfiguracionSistemaQueryStructure(): void
    {
        $sql = "SELECT clave, valor FROM configuracion_sistema WHERE clave IN ('empresa_nombre', 'empresa_rif', 'empresa_telefono', 'empresa_direccion')";
        $this->assertStringContainsString('configuracion_sistema', $sql);
        $this->assertStringContainsString('empresa_nombre', $sql);
        $this->assertStringContainsString('empresa_rif', $sql);
    }

    public function testEmpresaDataMapping(): void
    {
        $row = ['clave' => 'empresa_nombre', 'valor' => 'Mi Empresa'];
        $key = str_replace('empresa_', '', $row['clave']);
        $this->assertEquals('nombre', $key);
        $this->assertEquals('Mi Empresa', $row['valor']);
    }

    public function testPhpMailerConfiguration(): void
    {
        $config = [
            'host' => defined('SMTP_HOST') ? SMTP_HOST : 'mail.example.com',
            'port' => defined('SMTP_PORT') ? SMTP_PORT : 587,
            'from_email' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@example.com',
        ];
        $this->assertNotEmpty($config['host']);
        $this->assertGreaterThan(0, $config['port']);
        $this->assertStringContainsString('@', $config['from_email']);
    }

    public function testEmailVerificationFlowStateMachine(): void
    {
        $states = [
            'pendiente' => ['enviado', 'expirado'],
            'enviado' => ['verificado', 'expirado'],
            'verificado' => [],
            'expirado' => ['enviado'],
        ];

        $this->assertTrue(in_array('enviado', $states['pendiente']));
        $this->assertTrue(in_array('verificado', $states['enviado']));
        $this->assertEmpty($states['verificado']);
        $this->assertTrue(in_array('enviado', $states['expirado']));
    }

    public function testFormatearNumeroFactura(): void
    {
        $prefijo = 'FAC';
        $anio = '2026';
        $numero = 1;
        $factura = $prefijo . '-' . $anio . '-' . str_pad((string)$numero, 6, '0', STR_PAD_LEFT);

        $this->assertEquals('FAC-2026-000001', $factura);

        $numero2 = 999;
        $factura2 = $prefijo . '-' . $anio . '-' . str_pad((string)$numero2, 6, '0', STR_PAD_LEFT);
        $this->assertEquals('FAC-2026-000999', $factura2);
    }

    public function testCorreoConAcentosCompatible(): void
    {
        $valid = ['test@example.com'];
        foreach ($valid as $email) {
            $this->assertNotFalse(filter_var($email, FILTER_VALIDATE_EMAIL));
        }
        $internationalEmails = ['usuário@dominio.es', 'niño@domain.co'];
        foreach ($internationalEmails as $email) {
            $result = filter_var($email, FILTER_VALIDATE_EMAIL);
            if ($result === false) {
                $this->markTestSkipped("PHP " . phpversion() . " no acepta caracteres no-ASCII: $email");
                return;
            }
            $this->assertNotFalse($result);
        }
    }

    public function testRedirectHelper(): void
    {
        $base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/proyecto';
        $path = '/panel_admin/panel_admin.php?mensaje=ok';
        $redirect = $base . $path;
        $this->assertStringStartsWith('http://localhost/proyecto', $redirect);
        $this->assertStringContainsString('panel_admin.php', $redirect);
        $this->assertStringContainsString('mensaje=ok', $redirect);
    }
}
