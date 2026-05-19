<?php
namespace PIC\Services;

use PDO;

class StockService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function reduceStock(int $productId, int $quantity): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?
        ");
        $stmt->execute([$quantity, $productId, $quantity]);
        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("Stock insuficiente para el producto ID: $productId");
        }
        $this->logMovement($productId, $quantity, 'salida');
        return true;
    }

    public function increaseStock(int $productId, int $quantity): void
    {
        $stmt = $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmt->execute([$quantity, $productId]);
        $this->logMovement($productId, $quantity, 'entrada');
    }

    private function logMovement(int $productId, int $quantity, string $type): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO historial_stock (producto_id, cantidad, tipo_movimiento, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$productId, $quantity, $type]);
    }

    public function getLowStockProducts(int $threshold = 5): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name, sku, stock FROM products WHERE stock <= ?
        ");
        $stmt->execute([$threshold]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
