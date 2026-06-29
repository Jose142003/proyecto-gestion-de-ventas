<?php
declare(strict_types=1);
namespace PIC\Models;

use PDO;

class Product
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ?: null;
    }

    public function getAll(?string $category = null, ?string $search = null, int $limit = 20, int $offset = 0): array
    {
        $conditions = [];
        $params = [];

        if ($category) {
            $conditions[] = "category = ?";
            $params[] = $category;
        }
        if ($search) {
            $conditions[] = "(name LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $where = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";
        $stmt = $this->pdo->prepare("SELECT * FROM products {$where} LIMIT ? OFFSET ?");
        $i = 1;
        foreach ($params as $p) {
            $stmt->bindValue($i++, $p);
        }
        $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($i++, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (name, sku, description, price, stock, category, image_url, rating, specs, weight, dimensions, currency, is_featured, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $data['name'] ?? '', $data['sku'] ?? '', $data['description'] ?? '',
            $data['price'] ?? 0, $data['stock'] ?? 0, $data['category'] ?? '',
            $data['image_url'] ?? '', $data['rating'] ?? 0, $data['specs'] ?? '',
            $data['weight'] ?? 0, $data['dimensions'] ?? '', $data['currency'] ?? 'USD',
            $data['is_featured'] ?? 0
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE products SET name = ?, sku = ?, description = ?, price = ?, stock = ?,
            category = ?, image_url = ?, rating = ?, specs = ?, weight = ?, dimensions = ?,
            currency = ?, is_featured = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $data['name'] ?? '', $data['sku'] ?? '', $data['description'] ?? '', $data['price'] ?? 0,
            $data['stock'] ?? 0, $data['category'] ?? '', $data['image_url'] ?? '',
            $data['rating'] ?? 0, $data['specs'] ?? '', $data['weight'] ?? 0,
            $data['dimensions'] ?? '', $data['currency'] ?? 'USD', $data['is_featured'] ?? 0, $id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("UPDATE products SET active = 0 WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function updateStock(int $id, int $quantity): bool
    {
        $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$quantity, $id]);
        return $stmt->rowCount() > 0;
    }
}
