<?php
// includes/security_guard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Si no hay sesión, al Login (ruta corregida)
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php'); 
    exit;
}

// 2. Si no es Admin, error 403
if ($_SESSION['user']['rol'] !== 'admin') {
    http_response_code(403);
    echo "<div style='text-align:center; margin-top:50px; font-family: sans-serif;'>";
    echo "<h1 style='color:red;'>403 Acceso Prohibido</h1>";
    echo "<p>Tu rol de <strong>{$_SESSION['user']['rol']}</strong> no tiene permisos para ver esta sección.</p>";

    // Ruta corregida al dashboard
    echo "<a href='../dashboard.php' style='padding:10px; background:#333; color:white; text-decoration:none; border-radius:5px;'>Volver al Dashboard</a>";
    echo "</div>";
    exit; 
}
?>
