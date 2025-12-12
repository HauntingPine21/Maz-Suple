<?php
require_once '../includes/security_guard.php'; 
require_once '../config/db.php';

$rol = $_SESSION['user']['rol']; 

// Proveedores activos
$proveedores = [];
$sql_prov = "SELECT id, nombre FROM proveedores WHERE estatus = 1 ORDER BY nombre";
if ($res_prov = $mysqli->query($sql_prov)) {
    while ($row = $res_prov->fetch_assoc()) {
        $proveedores[] = $row;
    }
}

// Suplementos activos
$suplementos = [];
$sql_supp = "SELECT id, codigo, nombre, precio_venta FROM suplementos WHERE estatus = 1 ORDER BY nombre";
if ($res_supp = $mysqli->query($sql_supp)) {
    while ($row = $res_supp->fetch_assoc()) {
        $suplementos[] = $row;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MazSuple | Órdenes de Compra</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" type="image/png" href="assets/img/logo-maria-de-letras_icon.svg">
</head>
<body>
<div class="navbar">
    <div class="navbar-logo">
        <img src="assets/img/logo-maria-de-letras_v2.svg" alt="Logo">
    </div>
    <button class="menu-toggle" id="mobile-menu-btn">
        <span></span><span></span><span></span>
    </button>
    <div class="navbar-menu" id="navbar-menu">
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
                    <a href="productos.php">Suplementos</a>
                    <a href="compras.php">Compras</a>
                    <a href="usuarios.php">Usuarios</a>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropbtn">Reportes ▾</button>
                <div class="dropdown-content">
                    <a href="../reportes/compras.php">Reportes Compra</a>
                    <a href="../reportes/devoluciones.php">Reportes Devoluciones</a>
                    <a href="../reportes/inventario.php">Reportes Inventario</a>
                    <a href="../reportes/ventas_detalle.php">Reportes Detalle</a>
                    <a href="../reportes/ventas_encabezado.php">Reportes Encabezado</a>
                </div>  
            </div>
        <?php endif; ?>
        <a href="includes/logout.php" class="btn-general">Cerrar Sesión</a>
    </div>
</div>

<div class="main-container">
    <h2>Registro de Orden de Compra</h2>

    <div class="card">
        <h3>Datos de la Compra</h3>
        <form id="form-compra-encabezado">
            <div class="grid-2">
                <div>
                    <label for="fecha">Fecha de Pedido</label>
                    <input type="date" id="fecha" name="fecha" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label for="proveedor">Proveedor</label>
                    <select id="proveedor" name="proveedor" required>
                        <option value="">-- Seleccione un proveedor --</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="card mt-20">
        <h3>Detalle de Suplementos a Comprar</h3>
        
        <div class="flex-row mb-15">
            <input 
                type="text" 
                id="input-producto-compra" 
                placeholder="Buscar suplemento por nombre o código..." 
                class="flex-grow w-auto"
            >
            <button type="button" id="btn-agregar-item" class="btn-general w-150">Agregar Item</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-35">Suplemento</th>
                    <th class="col-15">Código</th>
                    <th class="col-15">Cantidad</th>
                    <th class="col-15">Costo Unitario</th>
                    <th class="col-10">Subtotal</th>
                    <th class="col-10"></th>
                </tr>
            </thead>
            <tbody id="tabla-detalle-compra">
                <tr>
                    <td colspan="6" class="text-center text-muted">Agrega suplementos para comenzar la orden</td>
                </tr>
            </tbody>
        </table>
        
        <div class="text-right text-xl font-bold mt-15">
            Total Compra: <span id="total-compra-display">$0.00</span>
        </div>

        <button id="btn-guardar-compra" class="btn-general mt-20">
            Guardar Orden de Compra
        </button>
    </div>
</div>

<script src="js/compras.js"></script>
</body>
</html>
