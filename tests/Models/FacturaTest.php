<?php
declare(strict_types=1);
namespace PIC\Tests\Models;

use PIC\Tests\DatabaseTestCase;
use PIC\Models\Factura;

class FacturaTest extends DatabaseTestCase
{
    private Factura $facturaModel;

    protected function setUp(): void
    {
        parent::setUp();
        if ($this->getConnection() === null) return;
        $this->facturaModel = new Factura($this->getConnection());
    }

    private function insertFactura(string $estado, float $total): int
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO facturas (numero_factura, total, estado, metodo_pago, fecha_emision)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $num = 'FAC-' . uniqid();
        $stmt->execute([$num, $total, $estado, 'transferencia']);
        return (int) $pdo->lastInsertId();
    }

    public function testFindFacturaById(): void
    {
        $id = $this->insertFactura('pagada', 250.00);

        $factura = $this->facturaModel->findById($id);
        $this->assertNotNull($factura);
        $this->assertEquals(250.00, (float)$factura['total']);
        $this->assertEquals('pagada', $factura['estado']);
    }

    public function testFindFacturaReturnsNullWhenNotFound(): void
    {
        $result = $this->facturaModel->findById(99999);
        $this->assertNull($result);
    }

    public function testGetAllByMonth(): void
    {
        $this->insertFactura('pagada', 100.00);
        $this->insertFactura('pendiente', 200.00);

        $mes = (int)date('m');
        $anio = (int)date('Y');

        $facturas = $this->facturaModel->getAllByMonth($mes, $anio);
        $this->assertGreaterThanOrEqual(2, count($facturas));
    }

    public function testGetStatsByMonth(): void
    {
        $this->insertFactura('pagada', 500.00);
        $this->insertFactura('pagada', 300.00);
        $this->insertFactura('pendiente', 150.00);

        $mes = (int)date('m');
        $anio = (int)date('Y');

        $stats = $this->facturaModel->getStatsByMonth($mes, $anio);
        $this->assertGreaterThanOrEqual(3, (int)$stats['total_facturas']);
        $this->assertGreaterThanOrEqual(2, (int)$stats['facturas_pagadas']);
        $this->assertGreaterThanOrEqual(800.00, (float)$stats['ventas_totales']);
    }
}
