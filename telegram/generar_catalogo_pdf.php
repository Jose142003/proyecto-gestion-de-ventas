<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generarCatalogoPDF() {
    $dir = __DIR__ . '/../catalogo';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    $pdfPath = $dir . '/catalogo_pic.pdf';

    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );

        $productos = $pdo->query("
            SELECT name, price, stock, category, description, sku
            FROM products WHERE active = 1 AND deleted_at IS NULL
            ORDER BY category, name
        ")->fetchAll();

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #333; }
            .header { text-align: center; padding: 20px 0; border-bottom: 3px solid #1a5276; }
            .header h1 { color: #1a5276; font-size: 22px; margin: 0; }
            .header p { color: #666; font-size: 11px; margin: 5px 0 0; }
            .cat-titulo { background: #1a5276; color: #fff; padding: 6px 10px; font-size: 13px; font-weight: bold; margin: 12px 0 8px; }
            .prod { padding: 6px 8px; margin: 4px 0; border: 1px solid #e0e0e0; }
            .prod-nom { font-size: 12px; font-weight: bold; color: #1a5276; }
            .prod-info { font-size: 10px; color: #555; }
            .prod-precio { font-size: 13px; color: #27ae60; font-weight: bold; }
            .footer { text-align: center; font-size: 9px; color: #999; margin-top: 20px; padding-top: 8px; border-top: 1px solid #ddd; }
        </style>
        </head>
        <body>
            <div class="header">
                <h1>PIC - Catálogo de Productos</h1>
                <p>Proyectos Industriales del Centro | Zona Industrial, Centro Michelena</p>
                <p>Tel: +58 0424-8323902 | Email: Picca.ventas@gmail.com</p>
                <p>' . date('d/m/Y') . '</p>
            </div>';

        $catActual = '';
        foreach ($productos as $p) {
            if ($p['category'] !== $catActual) {
                $catActual = $p['category'];
                $html .= '<div class="cat-titulo">' . htmlspecialchars($catActual) . '</div>';
            }

            $desc = !empty($p['description']) ? '<div class="prod-info">' . htmlspecialchars(mb_substr(strip_tags($p['description']), 0, 200)) . '</div>' : '';
            $stock = $p['stock'] > 0 ? 'Stock: ' . $p['stock'] : 'Agotado';

            $html .= '
            <div class="prod">
                <div class="prod-nom">' . htmlspecialchars($p['name']) . '</div>
                <div class="prod-info">SKU: ' . htmlspecialchars($p['sku'] ?? 'N/A') . ' | ' . $stock . '</div>
                <div class="prod-precio">Bs. ' . number_format($p['price'], 2) . '</div>
                ' . $desc . '
            </div>';
        }

        $urlBase = getenv('APP_URL') ?: 'http://localhost/proyecto';
        $tiendaUrl = rtrim($urlBase, '/') . '/interfaz_usuario/pagina_modernizada.html';
        $html .= '<div class="footer">PIC - Productos Industriales del Centro | Tienda: ' . htmlspecialchars($tiendaUrl) . '</div></body></html>';

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        file_put_contents($pdfPath, $dompdf->output());
        return $pdfPath;
    } catch (Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        return null;
    }
}

if (PHP_SAPI === 'cli' || isset($_GET['generar'])) {
    $pdf = generarCatalogoPDF();
    if ($pdf) {
        echo "✅ Catálogo PDF generado: $pdf\n";
        echo "📦 Tamaño: " . round(filesize($pdf) / 1024, 1) . " KB\n";
    } else {
        echo "❌ Error al generar el catálogo PDF\n";
    }
}
