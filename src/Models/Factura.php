<?php
declare(strict_types=1);
namespace PIC\Models;

use PDO;

class Factura
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM facturas WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAllByMonth(int $mes, int $anio): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM facturas 
            WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ?
            ORDER BY fecha_emision DESC
        ");
        $stmt->execute([$mes, $anio]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getStatsByMonth(int $mes, int $anio): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_facturas,
                SUM(CASE WHEN estado = 'pagada' THEN total ELSE 0 END) as ventas_totales,
                SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as facturas_pagadas,
                SUM(CASE WHEN estado = 'pendiente' THEN total ELSE 0 END) as pendientes_total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as facturas_pendientes
            FROM facturas 
            WHERE MONTH(fecha_emision) = ? AND YEAR(fecha_emision) = ?
        ");
        $stmt->execute([$mes, $anio]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
