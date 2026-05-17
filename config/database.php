<?php

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'carrito_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'PIC - Proyectos Industriales Del Centro');
define('APP_URL', '/proyecto');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
