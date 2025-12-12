<?php
// dashboard.php
// 1. Incluimos el guard básico que verifica que exista sesión
require_once 'includes/seguridad_basica.php';

// 2. Extraemos datos para usar en el HTML
$usuario = $_SESSION['user'];
$nombre_usuario = $usuario['nombre'];
$rol = $usuario['rol'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MazSuple | Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="assets/SupleIcono.png"> 
</head>

<body>
    
    <nav class="navbar"> 
        <div class="navbar-logo">
            <img src="assets/ImgLogo.png" alt="Logo">
        </div>
        
        <div class="navbar-menu">
            <a href="dashboard.php" class="nav-link active">Inicio</a>
            <a href="ventas.php">Punto de Venta</a>
            <a href="devoluciones.php">Devoluciones</a>
            
            <?php if ($rol === 'admin'): ?>
                <hr class="nav-divider"> <div class="dropdown">
                    <a href="#" class="dropbtn">Administración</a>
                    <div class="dropdown-content">
                        <a href="usuarios.php">Gestionar Usuarios</a>
                        <a href="productos.php">Catálogo de Suplementos</a>
                        <a href="compras.php">Registrar Compras</a>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#" class="dropbtn">Reportes</a>
                    <div class="dropdown-content">
                        <a href="reportes/inventario.php">Reporte Inventario</a>
                        <a href="reportes/ventas_encabezado.php">Reporte Ventas</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="navbar-user-info">
            <span class="user-text">Hola, 
                <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> 
                (<?php echo ucfirst($rol); ?>)
            </span>
            <a href="includes/logout.php" class="btn-general">Cerrar Sesión</a>
        </div>
        
        <button class="menu-toggle">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <main class="main-content-wrapper">
        
        <div class="welcome-banner">
            <h2>Panel de Control</h2>
            <p>Bienvenido al sistema de gestión MazSuple. Tu panel rápido de acceso.</p>
        </div>

        <div class="dashboard-grid">
            
            <div class="card card-action">
                <h3>Punto de Venta</h3>
                <p>Realizar ventas rápidas, cobrar y emitir tickets.</p>
                <a href="ventas.php" class="btn-general">Ir a Caja</a>
            </div>

            <div class="card card-action">
                <h3>Devoluciones</h3>
                <p>Gestionar devoluciones de suplementos.</p>
                <a href="devoluciones.php" class="btn-general">Ir a Devoluciones</a>
            </div>
        </div>

        <?php if ($rol === 'admin'): ?>
                
            <div class="card admin-panel-container mt-30">
                <div class="admin-header">
                    <h3>Administración Global</h3>
                    <p>Zona restringida para control del negocio (Admin).</p>
                </div>
                
                <hr class="divider"> 
                
                <div class="admin-grid-actions">
                    <a href="usuarios.php" class="admin-btn-card">
                        <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <span>Gestionar Usuarios</span>
                    </a>

                    <a href="productos.php" class="admin-btn-card">
                        <svg viewBox="0 0 24 24"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/></svg>
                        <span>Catálogo de Suplementos</span>
                    </a>

                    <a href="compras.php" class="admin-btn-card">
                        <svg viewBox="0 0 24 24"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        <span>Registrar Compras</span>
                    </a>

                    <a href="reportes/inventario.php" class="admin-btn-card">
                        <svg viewBox="0 0 24 24"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                        <span>Reporte Inventario</span>
                    </a>

                    <a href="reportes/ventas_encabezado.php" class="admin-btn-card">
                        <svg viewBox="0 0 24 24"><path d="M3.5 18.49l6-6.01 4 4L22 6.92l-1.41-1.41-7.09 7.97-4-4L2 16.99z"/></svg>
                        <span>Reporte Ventas</span>
                    </a>
                    
                    <div class="empty-admin-card"></div>
                </div>
            </div>

        <?php endif; ?>
        
    </main>
    <script src="js/main.js"></script>
<script src="js/offline_manager.js"></script>

<script>
    // ... Código de Service Worker ...
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('SW registrado. Listo para offline.', reg.scope))
        .catch(err => console.error('SW falló:', err));
        });
    }
</script>

</body>
</html>