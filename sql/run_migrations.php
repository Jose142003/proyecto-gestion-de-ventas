<?php

$config = require __DIR__ . '/../app/config/database.php';
$db = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$db->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $db->query("SELECT migration FROM migrations ORDER BY migration");
$executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

$files = glob(__DIR__ . '/*.sql');
$phpFiles = glob(__DIR__ . '/migracion_*.php');
$allFiles = array_merge($files, $phpFiles);
sort($allFiles);

$exitCode = 0;

foreach ($allFiles as $file) {
    $filename = basename($file);

    if (in_array($filename, $executed)) {
        echo "[SKIP] $filename already executed\n";
        continue;
    }

    try {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $sql = file_get_contents($file);
            $db->exec($sql);
        } else {
            require_once $file;
        }

        $stmt = $db->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$filename]);

        echo "[OK] $filename executed successfully\n";
    } catch (Exception $e) {
        echo "[FAIL] $filename error: {$e->getMessage()}\n";
        $exitCode = 1;
    }
}

if ($exitCode === 0) {
    echo "\nAll migrations completed successfully.\n";
}

exit($exitCode);
