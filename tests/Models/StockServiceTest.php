<?php
declare(strict_types=1);
namespace PIC\Tests\Models;

use PIC\Tests\DatabaseTestCase;
use PIC\Services\StockService;

class StockServiceTest extends DatabaseTestCase
{
    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->getConnection() === null) return;
        $this->stockService = new StockService($this->getConnection());
    }

    public function testReduceStock(): void
    {
        $data = $this->createTestProduct(['stock' => 100]);

        $result = $this->stockService->reduceStock($data['id'], 30);
        $this->assertTrue($result);
    }

    public function testReduceStockThrowsWhenInsufficient(): void
    {
        $data = $this->createTestProduct(['stock' => 5]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stock insuficiente');
        $this->stockService->reduceStock($data['id'], 10);
    }

    public function testReduceStockThrowsWhenProductNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no existe');
        $this->stockService->reduceStock(99999, 1);
    }

    public function testIncreaseStock(): void
    {
        $data = $this->createTestProduct(['stock' => 50]);

        $this->stockService->increaseStock($data['id'], 25);

        $pdo = $this->getConnection();
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$data['id']]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals(75, (int)$product['stock']);
    }

    public function testGetLowStockProducts(): void
    {
        $this->createTestProduct(['name' => 'Stock Bajo 1', 'stock' => 2, 'sku' => 'LOW-' . uniqid()]);
        $this->createTestProduct(['name' => 'Stock Bajo 2', 'stock' => 5, 'sku' => 'LOW-' . uniqid()]);
        $this->createTestProduct(['name' => 'Stock Alto', 'stock' => 100, 'sku' => 'HIGH-' . uniqid()]);

        $lowStock = $this->stockService->getLowStockProducts(5);
        $names = array_column($lowStock, 'name');

        $this->assertContains('Stock Bajo 1', $names);
        $this->assertContains('Stock Bajo 2', $names);
        $this->assertNotContains('Stock Alto', $names);
    }
}
