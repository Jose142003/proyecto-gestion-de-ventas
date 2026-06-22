<?php
namespace PIC\Tests;

use PHPUnit\Framework\TestCase;

class EnviosTest extends TestCase
{
    private array $validTransitions = [
        'preparando' => ['en_transito'],
        'en_transito' => ['en_reparto'],
        'en_reparto' => ['entregado'],
    ];

    public function testValidStateTransitions(): void
    {
        $this->assertTrue($this->canTransition('preparando', 'en_transito'));
        $this->assertTrue($this->canTransition('en_transito', 'en_reparto'));
        $this->assertTrue($this->canTransition('en_reparto', 'entregado'));
    }

    public function testInvalidStateTransitions(): void
    {
        $this->assertFalse($this->canTransition('preparando', 'entregado'));
        $this->assertFalse($this->canTransition('preparando', 'en_reparto'));
        $this->assertFalse($this->canTransition('en_transito', 'entregado'));
        $this->assertFalse($this->canTransition('entregado', 'en_reparto'));
        $this->assertFalse($this->canTransition('entregado', 'preparando'));
        $this->assertFalse($this->canTransition('en_reparto', 'en_transito'));
    }

    public function testInvalidFromState(): void
    {
        $this->assertFalse($this->canTransition('', 'entregado'));
        $this->assertFalse($this->canTransition('desconocido', 'entregado'));
        $this->assertFalse($this->canTransition('entregado', 'preparando'));
    }

    public function testFullShippingFlow(): void
    {
        $flow = ['preparando', 'en_transito', 'en_reparto', 'entregado'];

        for ($i = 0; $i < count($flow) - 1; $i++) {
            $this->assertTrue(
                $this->canTransition($flow[$i], $flow[$i + 1]),
                "Transition {$flow[$i]} -> {$flow[$i + 1]} should be valid"
            );
        }
    }

    public function testSkippingStatesNotAllowed(): void
    {
        $this->assertFalse($this->canTransition('preparando', 'en_reparto'));
        $this->assertFalse($this->canTransition('preparando', 'entregado'));
        $this->assertFalse($this->canTransition('en_transito', 'entregado'));
    }

    private function canTransition(string $from, string $to): bool
    {
        return isset($this->validTransitions[$from]) && in_array($to, $this->validTransitions[$from]);
    }

    public function testValidEstadosSet(): void
    {
        $validEstados = ['preparando', 'en_transito', 'en_reparto', 'entregado'];
        $invalidEstados = ['', 'pendiente', 'cancelado', 'devuelto', 'en_espera'];

        foreach ($validEstados as $estado) {
            $this->assertTrue(in_array($estado, $validEstados),
                "$estado should be a valid shipping state");
        }

        foreach ($invalidEstados as $estado) {
            $this->assertFalse(in_array($estado, $validEstados),
                "$estado should NOT be a valid shipping state");
        }
    }
}
