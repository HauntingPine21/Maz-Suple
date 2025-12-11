<?php
// index.php
session_start(); // NECESARIO: Iniciar sesión para recibir mensajes de error de auth.php

// Verificar si hay algún mensaje de error guardado (ej: "Contraseña incorrecta")
$error = '';
if (isset($_SESSION['error_mensaje'])) {
    $error = $_SESSION['error_mensaje'];
    unset($_SESSION['error_mensaje']); // Borramos el mensaje para que no salga al recargar
}
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maz-Suple | Iniciar sesión</title>
    <link rel="stylesheet" href="css/styles.css"> 
    <link rel="icon" type="image/png" href="assets/img/logo-maria-de-letras_icon.svg">
    <link rel="stylesheet" href="../css/styles.css">
  </head>

  <body>
    <div class="container-login">
      <div class="logo">
        <img src="assets/img/logo-maria-de-letras_v2.svg" alt="Logo de María de Letras">
        <h2>Iniciar Sesión</h2>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert-custom-danger text-center">
            <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="includes/auth.php" novalidate>
        <div class="form-index">
          <div class="mb-15">
            <label for="user">Usuario</label><br>
            <input type="text" 
              id="user" 
              name="user" 
              required 
              autocomplete="username"
              placeholder="Ingresa tu usuario"
              class="input-padded"> </div>

          <div class="mb-15">
            <label for="pass">Contraseña</label><br>
            <input 
              type="password" 
              id="pass" 
              name="pass" 
              required 
              autocomplete="current-password"
              placeholder="Ingresa tu contraseña"
              class="input-padded">
          </div>
        </div>
        <button type="submit" class="btn-general">
          Ingresar
        </button>
      </form>
    </div>
  </body>
</html>