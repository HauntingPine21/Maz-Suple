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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" type="image/png" href="assets/img/logo-mazsuplementos_icon.svg">
  </head>

  <body>
    <div class="navbar">
        <div class="navbar-logo">
            <img src="assets/img/logo-mazsuplementos_v2.svg" alt="Logo Maz Suplementos">
        </div>
        
        <button class="menu-toggle" id="mobile-menu-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-menu" id="navbar-menu">
            <button onclick="sincronizarVentas()" class="btn-general">
                Sincronizar (Offline)
            </button>

            <div class="dropdown">
                <button class="dropbtn">Cajero ▾</button>
                <div class="dropdown-content">
                    <a href="dashboard.php">Inicio</a>
                    <a href="ventas.php">Punto de Venta</a>
                    <a href="devoluciones.php">Devoluciones</a>
                </div>
            </div>

            <?php if ($rol === 'admin'): ?>
                <div class="dropdown">
                    <button class="dropbtn">Gestión ▾</button>
                    <div class="dropdown-content">
                        <a href="productos.php">Productos</a>
                        <a href="compras.php">Compras</a>
                        <a href="usuarios.php">Usuarios</a>
                        <a href="proveedores.php">Proveedores</a>
                    </div>
                </div>

                <div class="dropdown">
                    <button class="dropbtn">Reportes ▾</button>
                    <div class="dropdown-content">
                        <a href="reportes/compras.php">Compras</a>
                        <a href="reportes/devoluciones.php">Devoluciones</a>
                        <a href="reportes/inventario.php">Inventario</a>
                        <a href="reportes/ventas_detalle.php">Ventas Detalle</a>
                        <a href="reportes/ventas_encabezado.php">Ventas Encabezado</a>
                    </div>
                </div>
            <?php endif; ?>

            <a href="includes/logout.php" class="btn-general">Cerrar Sesión</a>
        </div>
    </div>

    <div class="main-container">
        <h2>Punto de Venta</h2>
        <p class="text-sm text-gray">Atendido por: <strong><?php echo htmlspecialchars($cajero_nombre); ?></strong></p>

        <div class="flex-row mb-20">
            <input type="text" 
                id="codigo" 
                name="codigo"
                placeholder="Escanea código de barras o ingresa manualmente..." 
                autofocus
                class="flex-grow w-auto">
            <button id="btn-buscar" class="btn-general w-150">Buscar</button>
        </div>

        <div class="card">
            <h3>Carrito de Venta</h3>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th class="col-10">Cant.</th>
                        <th class="col-15">Precio Unit.</th>
                        <th class="col-15">Subtotal</th>
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

        <div class="text-right text-2xl font-bold text-red mt-20">
            Total: <span id="total-display">$0.00</span>
        </div>

        <div class="flex-row mt-15 flex-end-gap">
            <button id="btn-cancelar" class="btn-general">Cancelar Venta</button>
            <button id="btn-cobrar" class="btn-general">Confirmar Venta y Cobrar</button>
        </div>
    </div>

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
