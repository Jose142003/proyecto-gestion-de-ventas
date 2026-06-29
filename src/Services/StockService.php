<?php
declare(strict_types=1);
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
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $check = $this->pdo->prepare("SELECT id, stock FROM products WHERE id = ? FOR UPDATE");
            $check->execute([$productId]);
            $product = $check->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new \RuntimeException("Producto ID $productId no existe");
            }
            if ($product['stock'] < $quantity) {
                throw new \RuntimeException("Stock insuficiente para el producto ID: $productId (disponible: {$product['stock']}, solicitado: $quantity)");
            }
            $stockAnterior = (int)$product['stock'];
            $stmt = $this->pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);
            $this->logMovement($productId, $quantity, 'venta', $stockAnterior, $stockAnterior - $quantity);
            if (!$inTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function increaseStock(int $productId, int $quantity): void
    {
        $inTransaction = $this->pdo->inTransaction();
        if (!$inTransaction) {
            $this->pdo->beginTransaction();
        }
        try {
            $check = $this->pdo->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
            $check->execute([$productId]);
            $product = $check->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new \RuntimeException("Producto ID $productId no existe");
            }
            $stockAnterior = (int)$product['stock'];
            $stmt = $this->pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$quantity, $productId]);
            $this->logMovement($productId, $quantity, 'compra', $stockAnterior, $stockAnterior + $quantity);
            if (!$inTransaction) {
                $this->pdo->commit();
            }
        } catch (\Exception $e) {
            if (!$inTransaction) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function logMovement(int $productId, int $quantity, string $type, int $stockAnterior, int $stockNuevo): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO historial_stock (producto_id, cantidad, tipo, stock_anterior, stock_nuevo, referencia, fecha)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$productId, $quantity, $type, $stockAnterior, $stockNuevo, 'StockService']);
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
