<?php
$pdo = new PDO("mysql:host=localhost;dbname=carrito_db;charset=utf8mb4","root","");
$stmt = $pdo->query("SELECT name FROM products WHERE active=1 ORDER BY name");
$brands = [];
foreach ($stmt as $r) {
    $name = $r["name"];
    // Look for known brand patterns in each product name
    if (preg_match('/\b(Autonics|Siemens|Schneider|UNI-T|Exceline|Omron|Mitsubishi|ABB|WEG|Delta|LS|Telemecanique|Allen[\s-]Bradley|General\s+Electric|Honeywell|Eaton|Phoenix\s+Contact|Weidmuller|Crouzet|Finder|Legrand|Panasonic|Fuji|Yaskawa|Sanyo)\b/i', $name, $m)) {
        $brands[$m[1]] = true;
    }
    echo "$name\n";
}
