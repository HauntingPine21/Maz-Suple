<?php
// ventas.php - Punto de venta tienda Maz Suplementos

require_once 'includes/seguridad_basica.php'; // valida sesión

$rol = $_SESSION['user']['rol'];
$cajero_nombre = $_SESSION['user']['nombre'];
$cajero_id = $_SESSION['user']['id'];

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maz Suplementos | Punto de Venta</title>
    <link rel="stylesheet" href="css/ventas.css">
    <link rel="icon" type="image/png" href="assets/img/logo-mazsuplementos_icon.svg">
  </head>

  <body>
    <nav class="navbar">
        <div class="navbar-logo">
            <img src="assets/img/logo-mazsuplementos_v2.svg" alt="Logo Maz Suplementos">
        </div>
        
        <button class="menu-toggle" id="mobile-menu-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-menu" id="navbar-menu">
            <button onclick="sincronizarVentas()" class="btn-general w-full sync-btn">
                Sincronizar (Offline)
            </button>
            
            <div class="navbar-links">
                <a href="dashboard.php" class="nav-link">🏠 Inicio</a>
                <a href="ventas.php" class="nav-link active">🛒 Punto de Venta</a>
                <a href="devoluciones.php" class="nav-link">↩️ Devoluciones</a>
            </div>

            <?php if ($rol === 'admin'): ?>
                <hr class="nav-divider">
                <div class="dropdown">
                    <button class="dropbtn">⚙️ Gestión ▾</button>
                    <div class="dropdown-content">
                        <a href="productos.php">Productos</a>
                        <a href="compras.php">Compras</a>
                        <a href="usuarios.php">Usuarios</a>
                        <a href="proveedores.php">Proveedores</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn">📈 Reportes ▾</button>
                    <div class="dropdown-content">
                        <a href="reportes/compras.php">Compras</a>
                        <a href="reportes/devoluciones.php">Devoluciones</a>
                        <a href="reportes/inventario.php">Inventario</a>
                        <a href="reportes/ventas_detalle.php">Ventas Detalle</a>
                        <a href="reportes/ventas_encabezado.php">Ventas Encabezado</a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="navbar-user-info">
                <span class="user-text">Cajero: 
                    <strong><?php echo htmlspecialchars($cajero_nombre); ?></strong>
                </span>
                <a href="includes/logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <main class="main-content-wrapper">
        <h2>Punto de Venta</h2>
        <p class="text-sm text-gray">Atendido por: <strong><?php echo htmlspecialchars($cajero_nombre); ?></strong></p>

        <div class="search-bar mb-20">
            <input type="text" 
                id="codigo" 
                name="codigo"
                placeholder="Escanea código de barras o ingresa manualmente..." 
                autofocus
                class="flex-grow w-auto">
            <button id="btn-buscar" class="btn-general w-150">Buscar</button>
        </div>

        <div class="card card-carrito">
            <h3>Carrito de Venta</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="col-0">Producto</th>
                            <th class="col-10">Cant.</th>
                            <th class="col-15 text-right">Precio Unit.</th>
                            <th class="col-15 text-right">Subtotal</th>
                            <th class="col-5"></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-carrito">
                        <tr>
                            <td colspan="5" class="text-center-muted">Escanea un suplemento para comenzar...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="totals-area">
            <div class="total-display">
                Total: <span id="total-display">$0.00</span>
            </div>
            <div class="action-buttons">
                <button id="btn-cancelar" class="btn-secundario">Cancelar Venta</button>
                <button id="btn-cobrar" class="btn-general">Confirmar Venta y Cobrar</button>
            </div>
        </div>
    </main>

    <script src="js/main.js"></script>
    <script src="js/ventas.js"></script>
    <script src="js/offline_manager.js"></script>

    <script>
        // Registro del Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw.js')
                    .then(reg => console.log('SW registrado: ', reg.scope))
                    .catch(err => console.log('SW fallo: ', err));
            });
        }
    </script>
  </body>
</html>
