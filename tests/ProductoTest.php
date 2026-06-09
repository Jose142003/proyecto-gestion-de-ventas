<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;
use PIC\Models\Product;

class ProductoTest extends TestCase
{
    public function testProductModelClassExists(): void
    {
        $this->assertTrue(class_exists(Product::class), 'Product class should exist');
    }

    public function testProductModelUsesPdo(): void
    {
        $reflection = new \ReflectionClass(Product::class);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('PDO', $params[0]->getType()->getName());
    }

    public function testProductModelHasRequiredMethods(): void
    {
        $methods = ['findById', 'getAll', 'create', 'update', 'delete', 'updateStock'];
        $reflection = new \ReflectionClass(Product::class);
        foreach ($methods as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Product should have method $method");
        }
    }

    public function testProductModelMethodsReturnExpectedTypes(): void
    {
        $reflection = new \ReflectionClass(Product::class);

        $findById = $reflection->getMethod('findById');
        $returnType = $findById->getReturnType();
        $this->assertNotNull($returnType, 'findById should have a return type');

        $getAll = $reflection->getMethod('getAll');
        $this->assertEquals('array', $getAll->getReturnType()->getName());

        $create = $reflection->getMethod('create');
        $this->assertEquals('int', $create->getReturnType()->getName());

        $update = $reflection->getMethod('update');
        $this->assertEquals('bool', $update->getReturnType()->getName());

        $delete = $reflection->getMethod('delete');
        $this->assertEquals('bool', $delete->getReturnType()->getName());

        $updateStock = $reflection->getMethod('updateStock');
        $this->assertEquals('bool', $updateStock->getReturnType()->getName());
    }
}
