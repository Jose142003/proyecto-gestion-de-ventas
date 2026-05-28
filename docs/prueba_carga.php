<?php
/**
 * Script de prueba de carga — peticiones concurrentes con curl_multi_*
 *
 * Uso:
 *   php docs/prueba_carga.php [url_base] [num_peticiones] [concurrentes]
 *
 * Ejemplo:
 *   php docs/prueba_carga.php http://localhost/proyecto 50 5
 */

$urlBase = $argv[1] ?? 'http://localhost/proyecto';
$numPeticiones = (int)($argv[2] ?? 50);
$concurrentes = (int)($argv[3] ?? 5);

$endpoints = [
    "$urlBase/producto/obtener_productos.php",
    "$urlBase/admin/obtener_dashboard.php",
    "$urlBase/reportes/obtener_auditoria.php?pagina=1&limite=10",
];

echo "============================================\n";
echo "  PRUEBA DE CARGA\n";
echo "============================================\n";
echo "URL Base:       $urlBase\n";
echo "Peticiones:     $numPeticiones\n";
echo "Concurrentes:   $concurrentes\n";
echo "Endpoints:      " . count($endpoints) . "\n";
echo "============================================\n\n";

$resultados = [];

foreach ($endpoints as $endpoint) {
    $nombre = basename(parse_url($endpoint, PHP_URL_PATH));
    echo "Probando: $nombre\n";
    echo "  URL: $endpoint\n";

    $tiempos = [];
    $exitosas = 0;
    $fallidas = 0;

    $inicioGlobal = microtime(true);

    $pendientes = range(0, $numPeticiones - 1);
    $activas = [];

    while (!empty($pendientes) || !empty($activas)) {
        while (count($activas) < $concurrentes && !empty($pendientes)) {
            $idx = array_shift($pendientes);
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $activas[(int)$ch] = ['handle' => $ch, 'index' => $idx, 'start' => microtime(true)];
        }

        $mh = curl_multi_init();
        foreach ($activas as $info) {
            curl_multi_add_handle($mh, $info['handle']);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh, 1);
        } while ($running > 0);

        foreach ($activas as $key => $info) {
            $httpCode = curl_getinfo($info['handle'], CURLINFO_HTTP_CODE);
            $error = curl_error($info['handle']);
            $tiempo = microtime(true) - $info['start'];
            $tiempos[] = $tiempo;

            if ($httpCode >= 200 && $httpCode < 400 && empty($error)) {
                $exitosas++;
            } else {
                $fallidas++;
            }

            curl_multi_remove_handle($mh, $info['handle']);
        }

        curl_multi_close($mh);
        $activas = [];
    }

    $tiempoTotal = microtime(true) - $inicioGlobal;
    $promedio = count($tiempos) > 0 ? array_sum($tiempos) / count($tiempos) : 0;
    $minimo = count($tiempos) > 0 ? min($tiempos) : 0;
    $maximo = count($tiempos) > 0 ? max($tiempos) : 0;
    $throughput = $tiempoTotal > 0 ? $numPeticiones / $tiempoTotal : 0;

    $resultados[$nombre] = [
        'min' => number_format($minimo, 4),
        'max' => number_format($maximo, 4),
        'avg' => number_format($promedio, 4),
        'exitosas' => $exitosas,
        'fallidas' => $fallidas,
        'throughput' => number_format($throughput, 2),
        'total' => number_format($tiempoTotal, 4),
    ];

    echo "  Mínimo: {$resultados[$nombre]['min']}s\n";
    echo "  Máximo: {$resultados[$nombre]['max']}s\n";
    echo "  Promedio: {$resultados[$nombre]['avg']}s\n";
    echo "  Exitosas: {$resultados[$nombre]['exitosas']}\n";
    echo "  Fallidas: {$resultados[$nombre]['fallidas']}\n";
    echo "  Throughput: {$resultados[$nombre]['throughput']} peticiones/s\n";
    echo "  Tiempo total: {$resultados[$nombre]['total']}s\n\n";
}

echo "============================================\n";
echo "  RESUMEN FINAL (formato texto)\n";
echo "============================================\n";
echo str_pad("Endpoint", 30) . str_pad("Min(s)", 12) . str_pad("Max(s)", 12) . str_pad("Avg(s)", 12) . str_pad("OK", 8) . str_pad("FAIL", 8) . str_pad("Req/s", 10) . "\n";
echo str_repeat("-", 92) . "\n";
foreach ($resultados as $nombre => $r) {
    echo str_pad($nombre, 30) . str_pad($r['min'], 12) . str_pad($r['max'], 12) . str_pad($r['avg'], 12) . str_pad($r['exitosas'], 8) . str_pad($r['fallidas'], 8) . str_pad($r['throughput'], 10) . "\n";
}
echo str_repeat("=", 92) . "\n\n";

// También mostrar en formato HTML si la salida es por navegador
if (php_sapi_name() !== 'cli') {
    echo "<h2>Resultados</h2>";
    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse;font-family:sans-serif;'>";
    echo "<tr style='background:#1e3c72;color:white;'>";
    echo "<th>Endpoint</th><th>Min (s)</th><th>Max (s)</th><th>Avg (s)</th><th>OK</th><th>FAIL</th><th>Req/s</th>";
    echo "</tr>";
    foreach ($resultados as $nombre => $r) {
        echo "<tr>";
        echo "<td>$nombre</td>";
        echo "<td>{$r['min']}</td><td>{$r['max']}</td><td>{$r['avg']}</td>";
        echo "<td style='color:green;font-weight:bold;'>{$r['exitosas']}</td>";
        echo "<td style='color:red;font-weight:bold;'>{$r['fallidas']}</td>";
        echo "<td>{$r['throughput']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
