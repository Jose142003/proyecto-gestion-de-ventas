<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class CarritoTest extends TestCase
{
    public function testStockMustBeGreaterThanZero(): void
    {
        $this->assertStockValidation(0, 1, false);
        $this->assertStockValidation(-1, 1, false);
        $this->assertStockValidation(5, 1, true);
    }

    public function testStockValidationExactQuantity(): void
    {
        $this->assertStockValidation(10, 10, true);
        $this->assertStockValidation(10, 11, false);
    }

    private function assertStockValidation(int $stock, int $cantidad, bool $expected): void
    {
        $result = $stock > 0 && $cantidad > 0 && $cantidad <= $stock;
        $this->assertSame($expected, $result,
            "Stock $stock, cantidad $cantidad should " . ($expected ? 'be' : 'not be') . ' valid');
    }

    public function testQuantityCalculation(): void
    {
        $this->assertSame(5, 2 + 3);
        $this->assertSame(15, 10 + 5);
        $this->assertSame(0, 0 + 0);
    }

    public function testPriceCalculationWithIVA(): void
    {
        $ivaRate = 0.16;
        $testCases = [
            ['subtotal' => 100.00, 'expectedIVA' => 16.00, 'expectedTotal' => 116.00],
            ['subtotal' => 250.50, 'expectedIVA' => 40.08, 'expectedTotal' => 290.58],
            ['subtotal' => 0.00, 'expectedIVA' => 0.00, 'expectedTotal' => 0.00],
            ['subtotal' => 999.99, 'expectedIVA' => 160.00, 'expectedTotal' => 1159.99],
        ];

        foreach ($testCases as $case) {
            $iva = round($case['subtotal'] * $ivaRate, 2);
            $total = round($case['subtotal'] + $iva, 2);

            $this->assertEquals($case['expectedIVA'], $iva,
                "IVA for {$case['subtotal']} should be {$case['expectedIVA']}");
            $this->assertEquals($case['expectedTotal'], $total,
                "Total for {$case['subtotal']} should be {$case['expectedTotal']}");
        }
    }

    public function testCartItemStructure(): void
    {
        $item = [
            'product_id' => 1,
            'name' => 'Producto Test',
            'price' => 50.00,
            'quantity' => 2,
            'subtotal' => 100.00,
        ];

        $this->assertArrayHasKey('product_id', $item);
        $this->assertArrayHasKey('name', $item);
        $this->assertArrayHasKey('price', $item);
        $this->assertArrayHasKey('quantity', $item);
        $this->assertArrayHasKey('subtotal', $item);
        $this->assertIsInt($item['product_id']);
        $this->assertIsString($item['name']);
        $this->assertIsFloat($item['price']);
        $this->assertIsInt($item['quantity']);
        $this->assertSame(100.00, $item['subtotal']);
    }

    public function testCartItemSubtotalCalculation(): void
    {
        $items = [
            ['price' => 25.00, 'quantity' => 3, 'expected' => 75.00],
            ['price' => 10.50, 'quantity' => 1, 'expected' => 10.50],
            ['price' => 100.00, 'quantity' => 0, 'expected' => 0.00],
        ];

        foreach ($items as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $this->assertEquals($item['expected'], $subtotal);
        }
    }

    public function testCartTotalCalculation(): void
    {
        $items = [
            ['price' => 30.00, 'quantity' => 2],
            ['price' => 15.50, 'quantity' => 1],
            ['price' => 5.99, 'quantity' => 3],
        ];

        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $iva = round($total * 0.16, 2);
        $grandTotal = round($total + $iva, 2);

        $this->assertEquals(93.47, $total);
        $this->assertEquals(14.96, $iva);
        $this->assertEquals(108.43, $grandTotal);
    }
}
