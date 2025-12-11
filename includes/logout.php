<?php
// includes/logout.php
session_start();

// 1. Vaciar todas las variables de sesión
$_SESSION = [];

// 2. Destruir la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión
session_destroy();

// 4. Redirigir al Login (ajustar ruta saliendo de includes)
header("Location: ../index.php");
exit();
?>