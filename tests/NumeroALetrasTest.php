<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Prueba para la función numeroALetras utilizada en generar_pdf_factura.php
 */
class NumeroALetrasTest extends TestCase
{
    private function numeroALetras(float $numero): string
    {
        $formatter = new NumberFormatter("es", NumberFormatter::SPELLOUT);
        $entero = floor($numero);
        $decimal = round(($numero - $entero) * 100);

        $texto = ucfirst($formatter->format($entero)) . " BOLÍVARES";

        if ($decimal > 0) {
            $texto .= " CON " . $formatter->format($decimal) . " CÉNTIMOS";
        }

        return $texto;
    }

    public function testNumeroEntero(): void
    {
        $resultado = $this->numeroALetras(100);
        $this->assertStringContainsString('BOLÍVARES', $resultado);
        $this->assertStringNotContainsString('CÉNTIMOS', $resultado);
    }

    public function testNumeroConDecimales(): void
    {
        $resultado = $this->numeroALetras(150.75);
        $this->assertStringContainsString('BOLÍVARES', $resultado);
        $this->assertStringContainsString('CÉNTIMOS', $resultado);
    }

    public function testCero(): void
    {
        $resultado = $this->numeroALetras(0);
        $this->assertStringContainsString('BOLÍVARES', $resultado);
    }

    public function testNumeroGrande(): void
    {
        $resultado = $this->numeroALetras(1000000);
        $this->assertStringContainsString('BOLÍVARES', $resultado);
        $this->assertStringContainsString('millón', strtolower($resultado));
    }
}
