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
        $check = $this->pdo->prepare("SELECT id, stock FROM products WHERE id = ?");
        $check->execute([$productId]);
        $product = $check->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new \RuntimeException("Producto ID $productId no existe");
        }
        if ($product['stock'] < $quantity) {
            throw new \RuntimeException("Stock insuficiente para el producto ID: $productId (disponible: {$product['stock']}, solicitado: $quantity)");
        }
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);
            $this->logMovement($productId, $quantity, 'salida');
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function increaseStock(int $productId, int $quantity): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);
            $this->logMovement($productId, $quantity, 'entrada');
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
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
