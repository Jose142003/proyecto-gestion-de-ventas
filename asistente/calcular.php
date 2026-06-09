<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: http://localhost');
require_once __DIR__ . '/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_GET;

$tipo = $input['tipo'] ?? '';

try {
    $db = obtenerDb();

    if ($tipo === 'motor_trifasico') {
        $hp = floatval($input['hp'] ?? 0);
        $voltaje = floatval($input['voltaje'] ?? 220);
        $distancia = floatval($input['distancia'] ?? 50);
        $fp = floatval($input['factor_potencia'] ?? 0.85);
        $eficiencia = floatval($input['eficiencia'] ?? 0.90);
        $fases = 3;

        if ($hp <= 0) responder(['error' => 'Ingrese un HP válido'], 400);

        $corrienteNominal = ($hp * 746) / ($voltaje * 1.732 * $fp * $eficiencia);
        $corrienteArranque = $corrienteNominal * 6;
        $breaker = $corrienteNominal * 1.25;
        $contactorAC3 = $corrienteNominal * 1.5;
        $releMin = $corrienteNominal * 0.95;
        $releMax = $corrienteNominal * 1.15;

        $cable50m = calcularCable($breaker, min($distancia, 50));
        $cable100m = calcularCable($breaker, min($distancia, 100));
        $cableReal = calcularCable($breaker, $distancia);

        $stmtContactores = $db->prepare("SELECT id, name, price, stock FROM products 
            WHERE category = 'Contactores' 
            AND CAST(SUBSTRING_INDEX(name, 'amp', 1) AS UNSIGNED) >= ?
            AND stock > 0 ORDER BY CAST(SUBSTRING_INDEX(name, 'amp', 1) AS UNSIGNED) ASC LIMIT 3");
        $stmtContactores->execute([ceil($contactorAC3)]);
        $contactores = $stmtContactores->fetchAll(PDO::FETCH_ASSOC);

        $stmtRele = $db->prepare("SELECT id, name, price, stock FROM products 
            WHERE category IN ('Relés', 'Protecciones') AND (name LIKE ? OR name LIKE ?)
            AND stock > 0 LIMIT 3");
        $stmtRele->execute(['%termico%', '%guardamotor%']);
        $reles = $stmtRele->fetchAll(PDO::FETCH_ASSOC);

        $stmtBreakers = $db->prepare("SELECT id, name, price, stock FROM products 
            WHERE category = 'Protecciones' AND stock > 0 LIMIT 3");
        $stmtBreakers->execute();
        $breakers = $stmtBreakers->fetchAll(PDO::FETCH_ASSOC);

        responder([
            'success' => true,
            'tipo' => 'Motor Trifásico',
            'parametros' => compact('hp', 'voltaje', 'distancia', 'fp', 'eficiencia'),
            'resultados' => [
                'corriente_nominal' => round($corrienteNominal, 1),
                'corriente_arranque' => round($corrienteArranque, 1),
                'breaker_recomendado' => round($breaker, 0) . 'A',
                'breaker_tipo' => 'Curva D (motores)',
                'contactor_ac3' => round($contactorAC3, 0) . 'A',
                'rele_termico' => round($releMin, 0) . 'A - ' . round($releMax, 0) . 'A',
                'cable_recomendado' => $cableReal['suficiente']
                    ? 'Cable AWG #' . $cableReal['awg'] . ' (' . $cableReal['mm2'] . ' mm²)'
                    : 'Se requiere cable de mayor calibre. Consulte tabla AWG.',
                'potencia_kw' => round($hp * 0.746, 2),
            ],
            'productos_sugeridos' => [
                'contactores' => $contactores,
                'proteccion_termica' => $reles,
                'breakers' => $breakers,
                'cable' => $cableReal['suficiente'] 
                    ? 'Cable AWG #' . $cableReal['awg'] . ' (' . $cableReal['mm2'] . ' mm²)'
                    : 'Se requiere cable de mayor calibre. Consulte tabla AWG.'
            ]
        ]);
    }

    elseif ($tipo === 'motor_monofasico') {
        $hp = floatval($input['hp'] ?? 0);
        $voltaje = floatval($input['voltaje'] ?? 115);
        $distancia = floatval($input['distancia'] ?? 30);
        $fp = floatval($input['factor_potencia'] ?? 0.80);
        $eficiencia = floatval($input['eficiencia'] ?? 0.80);

        if ($hp <= 0) responder(['error' => 'Ingrese un HP válido'], 400);

        $corrienteNominal = ($hp * 746) / ($voltaje * $fp * $eficiencia);
        $corrienteArranque = $corrienteNominal * 5;
        $breaker = $corrienteNominal * 1.25;
        $cable = calcularCable($breaker, $distancia);

        responder([
            'success' => true,
            'tipo' => 'Motor Monofásico',
            'parametros' => compact('hp', 'voltaje', 'distancia', 'fp', 'eficiencia'),
            'resultados' => [
                'corriente_nominal' => round($corrienteNominal, 1) . 'A',
                'corriente_arranque' => round($corrienteArranque, 1) . 'A',
                'breaker_recomendado' => round($breaker, 0) . 'A (Curva C)',
                'cable_recomendado' => $cable['suficiente']
                    ? 'Cable AWG #' . $cable['awg'] . ' (' . $cable['mm2'] . ' mm²)'
                    : 'Se requiere cable de mayor calibre. Consulte tabla AWG.',
            ]
        ]);
    }

    elseif ($tipo === 'carga_resistiva') {
        $potencia = floatval($input['potencia_w'] ?? 0);
        $voltaje = floatval($input['voltaje'] ?? 220);
        $fases = intval($input['fases'] ?? 1);
        $distancia = floatval($input['distancia'] ?? 30);

        if ($potencia <= 0) responder(['error' => 'Ingrese una potencia válida'], 400);

        $corriente = $potencia / ($voltaje * ($fases === 3 ? 1.732 : 1));
        $breaker = $corriente * 1.20;
        $cable = calcularCable($breaker, $distancia);

        responder([
            'success' => true,
            'tipo' => 'Carga Resistiva',
            'parametros' => compact('potencia', 'voltaje', 'fases', 'distancia'),
            'resultados' => [
                'corriente_nominal' => round($corriente, 1) . 'A',
                'breaker_recomendado' => round($breaker, 0) . 'A (Curva C)',
                'cable_recomendado' => $cable['suficiente']
                    ? 'Cable AWG #' . $cable['awg'] . ' (' . $cable['mm2'] . ' mm²)'
                    : 'Se requiere cable de mayor calibre. Consulte tabla AWG.',
                'potencia_kw' => round($potencia / 1000, 2) . ' kW',
            ]
        ]);
    }

    elseif ($tipo === 'variador_vfd') {
        $hp = floatval($input['hp'] ?? 0);
        $voltaje = floatval($input['voltaje'] ?? 220);
        $fases = intval($input['fases'] ?? 3);

        if ($hp <= 0) responder(['error' => 'Ingrese un HP válido'], 400);

        $corriente = ($hp * 746) / ($voltaje * ($fases === 3 ? 1.732 : 1) * 0.85 * 0.9);
        $vfdCorriente = $corriente * 1.25;
        $breaker = $corriente * 1.25;

        $stmtVfd = $db->prepare("SELECT id, name, price, stock FROM products 
            WHERE category = 'Variadores' AND stock > 0 LIMIT 3");
        $stmtVfd->execute();
        $vfdList = $stmtVfd->fetchAll(PDO::FETCH_ASSOC);

        responder([
            'success' => true,
            'tipo' => 'Variador de Frecuencia (VFD)',
            'parametros' => compact('hp', 'voltaje', 'fases'),
            'resultados' => [
                'corriente_motor' => round($corriente, 1) . 'A',
                'vfd_corriente_min' => round($vfdCorriente, 0) . 'A',
                'breaker_recomendado' => round($breaker, 0) . 'A',
                'recomendaciones' => [
                    'Sobredimensionar VFD al 125% de la corriente del motor',
                    'Usar reactor de línea en VFD para reducir armónicos',
                    'Cable apantallado entre VFD y motor si distancia > 50m'
                ]
            ],
            'productos_sugeridos' => $vfdList
        ]);
    }

    else {
        $stmt = $db->query("SELECT tipo_equipo, parametros_entrada, notas FROM formulas_tecnicas");
        $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        responder([
            'success' => true,
            'tipos_disponibles' => array_map(function($t) {
                return [
                    'tipo' => $t['tipo_equipo'],
                    'parametros' => json_decode($t['parametros_entrada'], true),
                    'notas' => $t['notas']
                ];
            }, $tipos)
        ]);
    }

} catch (Exception $e) {
    responder(['error' => 'Error interno: ' . $e->getMessage()], 500);
}
