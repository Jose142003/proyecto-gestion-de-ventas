<?php
$cookie_params = session_get_cookie_params();
$cookie_path = $cookie_params["path"];
$cookie_domain = $cookie_params["domain"];
$cookie_secure = $cookie_params["secure"];
$cookie_httponly = $cookie_params["httponly"];

// Destruir sesión de cliente (CLIENTSESSID)
session_name('CLIENTSESSID');
@session_start();
$_SESSION = array();
setcookie('CLIENTSESSID', '', time() - 42000, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
@session_destroy();

// Destruir sesión de administrador (PHPSESSID - nombre por defecto)
session_name(ini_get('session.name'));
@session_start();
$_SESSION = array();
setcookie(session_name(), '', time() - 42000, $cookie_path, $cookie_domain, $cookie_secure, $cookie_httponly);
@session_destroy();

// Limpiar persist_token
setcookie('persist_token', '', time() - 42000, '/', '', false, true);

header('Location: /proyecto/interfaz_usuario/login.html');
exit;
