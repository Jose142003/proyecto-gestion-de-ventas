<?php
// /proyecto/usuarios/cerrar_sesion.php
session_start();

// Solo destruir la sesión actual
$_SESSION = array();

// Destruir la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir según el origen (opcional)
header('Location: /proyecto/usuario/login.html');
exit;
?>