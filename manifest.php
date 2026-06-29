<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/proyecto';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: max-age=3600, public');

echo json_encode([
    'name' => 'Proyectos Industriales Del Centro',
    'short_name' => 'PIC Industrial',
    'description' => 'Suministros industriales de alta calidad - Tienda oficial',
    'start_url' => $baseUrl . '/interfaz_usuario/pagina_modernizada.php',
    'display' => 'standalone',
    'theme_color' => '#050C18',
    'background_color' => '#F3F3F3',
    'orientation' => 'portrait-primary',
    'scope' => $baseUrl . '/',
    'icons' => [
        [
            'src' => $baseUrl . '/img/pic.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src' => $baseUrl . '/img/pic.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
    'shortcuts' => [],
    'categories' => ['shopping', 'business'],
    'lang' => 'es',
    'dir' => 'ltr',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
