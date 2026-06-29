<?php
$base = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '/proyecto';
header('Location: ' . $base . '/interfaz_usuario/index.html');
exit;
