<?php
declare(strict_types=1);
namespace PIC\Tests\Models;

use PIC\Tests\DatabaseTestCase;
use PIC\Models\User;

class UsuarioTest extends DatabaseTestCase
{
    private User $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->getConnection() === null) return;
        $this->userModel = new User($this->getConnection());
    }

    public function testCreateAndFindUser(): void
    {
        $id = $this->userModel->create([
            'nombre' => 'Juan Pérez',
            'correo' => 'juan-' . uniqid() . '@test.com',
            'password' => 'ClaveSegura2024!',
            'telefono' => '04141112233',
            'cedula' => 'V-87654321',
        ]);

        $this->assertGreaterThan(0, $id);

        $user = $this->userModel->findById($id);
        $this->assertNotNull($user);
        $this->assertEquals('Juan Pérez', $user['nombre']);
    }

    public function testFindByEmail(): void
    {
        $email = 'busqueda-' . uniqid() . '@test.com';
        $id = $this->userModel->create([
            'nombre' => 'Búsqueda Email',
            'correo' => $email,
            'password' => 'Test123!',
        ]);

        $found = $this->userModel->findByEmail($email);
        $this->assertNotNull($found);
        $this->assertEquals($id, (int)$found['id']);
    }

    public function testFindByEmailReturnsNullWhenNotFound(): void
    {
        $result = $this->userModel->findByEmail('no-existe-' . uniqid() . '@test.com');
        $this->assertNull($result);
    }

    public function testVerifyPassword(): void
    {
        $password = 'MiClaveFuerte!123';
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $this->assertTrue($this->userModel->verifyPassword($password, $hash));
        $this->assertFalse($this->userModel->verifyPassword('WrongPass', $hash));
        $this->assertFalse($this->userModel->verifyPassword('', $hash));
    }

    public function testDuplicateEmailFails(): void
    {
        $email = 'duplicado-' . uniqid() . '@test.com';
        $this->userModel->create([
            'nombre' => 'Primero',
            'correo' => $email,
            'password' => 'Pass123!',
        ]);

        $this->expectException(\PDOException::class);
        $this->userModel->create([
            'nombre' => 'Segundo',
            'correo' => $email,
            'password' => 'Pass456!',
        ]);
    }
}
