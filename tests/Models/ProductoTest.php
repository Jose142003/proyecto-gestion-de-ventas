<?php
declare(strict_types=1);
namespace PIC\Tests\Models;

use PIC\Tests\DatabaseTestCase;
use PIC\Models\Product;

class ProductoTest extends DatabaseTestCase
{
    private Product $productModel;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->getConnection() === null) return;
        $this->productModel = new Product($this->getConnection());
    }

    public function testCreateAndFindProduct(): void
    {
        $id = $this->productModel->create([
            'name' => 'Taladro Industrial',
            'sku' => 'TAL-001',
            'description' => 'Taladro percutor 650W',
            'price' => 120.50,
            'stock' => 30,
            'category' => 'Herramientas',
        ]);

        $this->assertGreaterThan(0, $id);

        $product = $this->productModel->findById($id);
        $this->assertNotNull($product);
        $this->assertEquals('Taladro Industrial', $product['name']);
        $this->assertEquals('TAL-001', $product['sku']);
        $this->assertEquals(120.50, (float)$product['price']);
        $this->assertEquals(30, (int)$product['stock']);
    }

    public function testUpdateProduct(): void
    {
        $data = $this->createTestProduct();
        $updated = $this->productModel->update($data['id'], [
            'name' => 'Nombre Actualizado',
            'price' => 150.00,
            'stock' => 20,
            'category' => 'Nueva Categoría',
        ]);

        $this->assertTrue($updated);

        $product = $this->productModel->findById($data['id']);
        $this->assertEquals('Nombre Actualizado', $product['name']);
        $this->assertEquals(150.00, (float)$product['price']);
        $this->assertEquals(20, (int)$product['stock']);
    }

    public function testDeleteProduct(): void
    {
        $data = $this->createTestProduct();
        $deleted = $this->productModel->delete($data['id']);
        $this->assertTrue($deleted);

        $product = $this->productModel->findById($data['id']);
        $this->assertNull($product);
    }

    public function testUpdateStock(): void
    {
        $data = $this->createTestProduct(['stock' => 100]);

        $result = $this->productModel->updateStock($data['id'], 30);
        $this->assertTrue($result);

        $product = $this->productModel->findById($data['id']);
        $this->assertEquals(70, (int)$product['stock']);
    }

    public function testGetAllWithFilters(): void
    {
        $this->createTestProduct(['name' => 'Filtrable A', 'category' => 'CatA', 'sku' => 'FIL-A']);
        $this->createTestProduct(['name' => 'Filtrable B', 'category' => 'CatB', 'sku' => 'FIL-B']);

        $all = $this->productModel->getAll(null, null, 10, 0);
        $this->assertGreaterThanOrEqual(2, count($all));

        $filtered = $this->productModel->getAll('CatA', null, 10, 0);
        $this->assertCount(1, $filtered);
        $this->assertEquals('CatA', $filtered[0]['category']);

        $searched = $this->productModel->getAll(null, 'Filtrable', 10, 0);
        $this->assertGreaterThanOrEqual(2, count($searched));
    }

    public function testPagination(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->createTestProduct(['name' => 'Page Prod ' . $i, 'sku' => 'PAGE-' . uniqid()]);
        }

        $page1 = $this->productModel->getAll(null, null, 2, 0);
        $this->assertCount(2, $page1);

        $page2 = $this->productModel->getAll(null, null, 2, 2);
        $this->assertCount(2, $page2);

        $page3 = $this->productModel->getAll(null, null, 2, 4);
        $this->assertCount(1, $page3);
    }
}
