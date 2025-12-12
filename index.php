<?php
// index.php
session_start(); 

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

    <link rel="stylesheet" href="css/index.css?v=<?php echo time(); ?>">

    <link rel="manifest" href="/MAZ-SUPLE/manifest.json">
    <meta name="theme-color" content="#2196f3">

    <link rel="icon" sizes="192x192" href="/MAZ-SUPLE/assets/img/icon-192.png">
    <link rel="icon" sizes="512x512" href="/MAZ-SUPLE/assets/img/icon-512.png">

</head>
<body>
    <div class="container-login">
        <div class="logo">
            <img src="assets/ImgLogo.png" alt="Logo de Maz-Suple">
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
                        class="input-padded">
                </div>

                <div class="mb-15">
                    <label for="pass">Contraseña</label><br>
                    <input type="password" 
                        id="pass" 
                        name="pass" 
                        required 
                        autocomplete="current-password"
                        placeholder="Ingresa tu contraseña"
                        class="input-padded">
                </div>
            </div>
            <button type="submit" class="btn-general w-full mt-15">
                Ingresar
            </button>
        </form>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                // SOLUCIÓN: Simplificar el registro a la ruta relativa 'sw.js' 
                // para evitar problemas de alcance (scope) en localhost.
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('Service Worker registrado con éxito. Alcance:', reg.scope))
                    .catch(err => console.error('Fallo al registrar SW:', err));
            });
        }
    </script>

    <script src="js/offline_manager.js"></script>
</body>
</html>