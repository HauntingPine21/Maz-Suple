<?php
// includes/seguridad_basica.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión activa, mandar al Login (ruta corregida)
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit;
}
?>
