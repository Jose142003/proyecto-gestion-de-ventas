<?php

// Cargar variables de entorno desde .env si existe
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: 'carrito_db');
defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: '');
defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') ?: '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

// Configuración SMTP (desde variables de entorno)
defined('SMTP_HOST') or define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
defined('SMTP_USER') or define('SMTP_USER', getenv('SMTP_USER') ?: '');
defined('SMTP_PASS') or define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
defined('SMTP_PORT') or define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
defined('SMTP_FROM_EMAIL') or define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: SMTP_USER);
defined('SMTP_FROM_NAME') or define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'PIC - Productos Industriales');

// URL base del proyecto (para evitar rutas hardcodeadas)
defined('BASE_URL') or define('BASE_URL', getenv('APP_URL') ?: '/proyecto');
