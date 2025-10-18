<?php


session_start(); // Iniciar la sesión

// 1. Destruir todas las variables de sesión
$_SESSION = array();

// 2. Eliminar la cookie de sesión (si existe)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión
session_destroy();

// 4. Redirigir al inicio de sesión
header("Location: ../index.php");
exit();
?>