<?php
session_name('CLIENTSESSID');
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    if (isset($_COOKIE['persist_token'])) {
        setcookie('persist_token', '', time() - 3600, '/');
        header('Location: /proyecto/interfaz_usuario/login.html');
        exit;
    }
}

session_write_close();

readfile(__DIR__ . '/pagina_modernizada.html');
