<?php
require_once __DIR__ . '/../conexion/conexion.php';

function responder($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function obtenerDb() {
    return Database::getConnection();
}

function calcularCable($corriente, $distancia, $caidaMax = 3) {
    $tablaAWG = [
        [14, 2.08, 1.6], [12, 3.31, 2.0], [10, 5.26, 2.6],
        [8, 8.37, 3.3], [6, 13.3, 4.1], [4, 21.2, 5.2],
        [2, 33.6, 6.6], [1, 42.4, 7.4], [0, 53.5, 8.4],
        [00, 67.4, 9.5], [000, 85.0, 10.7], [0000, 107.0, 12.0],
    ];

    foreach ($tablaAWG as $row) {
        $awg = $row[0]; $mm2 = $row[1]; $resistencia = $row[2];
        $caida = 2 * $distancia * ($resistencia / 1000) * $corriente;
        $caidaPorc = ($caida / 220) * 100;
        if ($caidaPorc <= $caidaMax) {
            return [
                'awg' => $awg,
                'mm2' => round($mm2, 1),
                'caida_voltaje' => round($caida, 2),
                'caida_porcentaje' => round($caidaPorc, 1),
                'suficiente' => true
            ];
        }
    }
    return ['awg' => 0, 'mm2' => 0, 'caida_voltaje' => 0, 'caida_porcentaje' => 0, 'suficiente' => false];
}
