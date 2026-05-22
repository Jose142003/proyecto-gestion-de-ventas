<?php
namespace PIC\Models;

use PDO;

class User
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE correo = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO users (nombre, correo, password, telefono, cedula, is_active, estado, created_at)
            VALUES (?, ?, ?, ?, ?, 1, 'activo', NOW())
        ");
        $hash = password_hash($data['password'] ?? '', PASSWORD_BCRYPT);
        $stmt->execute([
            $data['nombre'] ?? '', $data['correo'] ?? '', $hash,
            $data['telefono'] ?? '', $data['cedula'] ?? ''
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
