<?php
if (!isset($db)) {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                putenv(trim($key) . '=' . trim($value));
            }
        }
    }
    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'carrito_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $db = new PDO(
        "mysql:host=$host;dbname=$name;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}
$stmt = $db->query("SELECT id, token FROM api_tokens WHERE LENGTH(token) < 64");
$fixed = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $hash = hash('sha256', $row['token']);
    $update = $db->prepare("UPDATE api_tokens SET token = ? WHERE id = ?");
    $update->execute([$hash, $row['id']]);
    $fixed++;
}
echo "[OK] $fixed token(s) re-hashed to SHA256\n";
