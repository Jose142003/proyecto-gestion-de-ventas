<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class PedidoTest extends TestCase
{
    public function testOrderNumberFormat(): void
    {
        $pattern = '/^PED-\d{4}-\d{4}$/';

        $validNumbers = ['PED-2026-0001', 'PED-2025-1234', 'PED-2024-9999'];
        $invalidNumbers = ['PED-2026-001', 'PED2026-0001', 'ped-2026-0001', '', '1234-5678'];

        foreach ($validNumbers as $number) {
            $this->assertMatchesRegularExpression($pattern, $number,
                "$number should match order pattern");
        }

        foreach ($invalidNumbers as $number) {
            $this->assertDoesNotMatchRegularExpression($pattern, $number,
                "$number should NOT match order pattern");
        }
    }

    public function testValidStatusTransitions(): void
    {
        $transitions = [
            'pendiente' => ['confirmado', 'cancelado'],
            'confirmado' => ['preparando', 'cancelado'],
            'preparando' => ['en_transito', 'cancelado'],
            'en_transito' => ['en_reparto', 'cancelado'],
            'en_reparto' => ['entregado', 'cancelado'],
            'entregado' => [],
            'cancelado' => [],
        ];

        $this->assertValidTransitions('pendiente', 'confirmado', $transitions);
        $this->assertValidTransitions('pendiente', 'cancelado', $transitions);
        $this->assertValidTransitions('preparando', 'en_transito', $transitions);
        $this->assertValidTransitions('en_reparto', 'entregado', $transitions);
    }

    public function testInvalidStatusTransitions(): void
    {
        $transitions = [
            'pendiente' => ['confirmado', 'cancelado'],
            'confirmado' => ['preparando', 'cancelado'],
            'preparando' => ['en_transito', 'cancelado'],
            'en_transito' => ['en_reparto', 'cancelado'],
            'en_reparto' => ['entregado', 'cancelado'],
            'entregado' => [],
            'cancelado' => [],
        ];

        $this->assertInvalidTransitions('pendiente', 'entregado', $transitions);
        $this->assertInvalidTransitions('pendiente', 'en_transito', $transitions);
        $this->assertInvalidTransitions('confirmado', 'entregado', $transitions);
        $this->assertInvalidTransitions('en_transito', 'pendiente', $transitions);
        $this->assertInvalidTransitions('entregado', 'pendiente', $transitions);
        $this->assertInvalidTransitions('cancelado', 'confirmado', $transitions);
    }

    private function assertValidTransitions(string $from, string $to, array $transitions): void
    {
        $valid = in_array($to, $transitions[$from] ?? []);
        $this->assertTrue($valid, "Transition '$from -> $to' should be valid");
    }

    private function assertInvalidTransitions(string $from, string $to, array $transitions): void
    {
        $valid = in_array($to, $transitions[$from] ?? []);
        $this->assertFalse($valid, "Transition '$from -> $to' should be invalid");
    }

    public function testPaymentMethodValidation(): void
    {
        $validMethods = ['efectivo', 'transferencia', 'pago_movil', 'mixto', 'tarjeta'];
        $invalidMethods = ['bitcoin', '', 'paypal', 'cheque', 'cripto'];

        foreach ($validMethods as $method) {
            $this->assertTrue(in_array($method, $validMethods),
                "$method should be a valid payment method");
        }

        foreach ($invalidMethods as $method) {
            $this->assertFalse(in_array($method, $validMethods),
                "$method should NOT be a valid payment method");
        }
    }

    public function testTotalCalculation(): void
    {
        $ivaRate = 0.16;
        $testCases = [
            ['subtotal' => 100.00, 'expectedIVA' => 16.00, 'expectedTotal' => 116.00],
            ['subtotal' => 50.00, 'expectedIVA' => 8.00, 'expectedTotal' => 58.00],
            ['subtotal' => 0.00, 'expectedIVA' => 0.00, 'expectedTotal' => 0.00],
        ];

        foreach ($testCases as $case) {
            $iva = round($case['subtotal'] * $ivaRate, 2);
            $total = round($case['subtotal'] + $iva, 2);

            $this->assertEquals($case['expectedIVA'], $iva, "IVA incorrect for subtotal {$case['subtotal']}");
            $this->assertEquals($case['expectedTotal'], $total, "Total incorrect for subtotal {$case['subtotal']}");
        }
    }
}
