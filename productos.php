<?php
require_once 'config/db.php';
require_once 'includes/security_guard.php';

$rol = $_SESSION['user']['rol'];
$mensaje = "";

// ================================================
// PROCESAR FORMULARIO DE ALTA
// ================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $marca = trim($_POST['marca']);
    $precio = $_POST['precio'];
    $stock_inicial = intval($_POST['stock'] ?? 0);
    if ($stock_inicial < 0) $stock_inicial = 0;
    
    $imagen_binaria = null;
    $tipo_mime = 'image/jpeg';
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
        $tipo_mime = $_FILES['imagen']['type'];
        $imagen_binaria = file_get_contents($_FILES['imagen']['tmp_name']);
    }

    if (!is_numeric($precio)) {
        $mensaje = "El precio no es válido.";
    } else {
        $mysqli->begin_transaction();
        try {
            // Insertar suplemento
            $sql = "INSERT INTO suplementos (codigo, nombre, marca, precio_venta, estatus)
                     VALUES (?, ?, ?, ?, 1)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssd", $codigo, $nombre, $marca, $precio);
            $stmt->execute();
            $id_suplemento = $mysqli->insert_id;
            // Insertar código de barras si fue capturado
                $codigo_barras = trim($_POST['codigo_barras'] ?? '');

                if ($codigo_barras !== '') {
                    $sql_cb = "INSERT INTO suplementos_codigos (id_suplemento, codigo_barras)
                            VALUES (?, ?)";
                    $stmt_cb = $mysqli->prepare($sql_cb);
                    $stmt_cb->bind_param("is", $id_suplemento, $codigo_barras);
                    $stmt_cb->execute();
                }

            // Insertar imagen BLOB si existe
            if ($imagen_binaria) {
                $sql_img = "INSERT INTO imagenes_suplemento (id_suplemento, contenido, tipo_mime, es_principal)
                             VALUES (?, ?, ?, 1)";
                $stmt_img = $mysqli->prepare($sql_img);
                $null = NULL;
                $stmt_img->bind_param("ibs", $id_suplemento, $null, $tipo_mime);
                $stmt_img->send_long_data(1, $imagen_binaria);
                $stmt_img->execute();
            }

            // Existencia inicial con la cantidad que ingrese el usuario
            $mysqli->query("INSERT INTO existencias (id_suplemento, cantidad) VALUES ($id_suplemento, $stock_inicial)");

            $mysqli->commit();
            $mensaje = "Suplemento creado correctamente con stock inicial de $stock_inicial.";
        } catch (Exception $e) {
            $mysqli->rollback();
            $mensaje = str_contains($e->getMessage(), 'Duplicate') ?
                         "Error: El código '$codigo' ya existe." :
                         "Error: " . $e->getMessage();
        }
    }
}

// ================================
// ACTIVAR / DESACTIVAR
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'], $_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id <= 0) die("ID inválido");

    $sql = "";
    if ($_GET['action'] === 'desactivar') $sql = "UPDATE suplementos SET estatus = 0 WHERE id = ?";
    if ($_GET['action'] === 'activar') $sql = "UPDATE suplementos SET estatus = 1 WHERE id = ?";

    if ($sql) {
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: productos.php");
        exit;
    }
}

// ================================
// LISTAR SUPLEMENTOS
// ================================
$sql_suplementos = "
    SELECT s.*, COALESCE(e.cantidad,0) as cantidad
    FROM suplementos s
    LEFT JOIN existencias e ON s.id = e.id_suplemento
    ORDER BY s.estatus DESC, s.nombre ASC
";
$productos = $mysqli->query($sql_suplementos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Maz Suple | Gestión de Productos</title>
    <link rel="stylesheet" href="css/productos.css?v=<?php echo time(); ?>">
</head>
<body>
    
<nav class="navbar">
    <div class="navbar-logo">
        <img src="assets/ImgLogo.png" alt="Logo">
    </div>
    <button class="menu-toggle" id="mobile-menu-btn">
        <span></span><span></span><span></span>
    </button>
    <div class="navbar-menu" id="navbar-menu">
        <div class="navbar-links">
            <a href="dashboard.php" class="nav-link">Inicio</a>
            <a href="ventas.php" class="nav-link">Punto de Venta</a>
            <a href="devoluciones.php" class="nav-link">Devoluciones</a>
        </div>
        <?php if ($rol === 'admin'): ?>
        <hr class="nav-divider">
        <div class="dropdown">
            <button class="dropbtn active">Gestión ▾</button>
            <div class="dropdown-content show">
                <a href="productos.php" class="active">Productos</a>
                <a href="compras.php">Compras</a>
                <a href="usuarios.php">Usuarios</a>
            </div>
        </div>
        <div class="dropdown">
            <button class="dropbtn">Reportes ▾</button>
            <div class="dropdown-content">
                <a href="reportes/devoluciones.php">Devoluciones</a>
                <a href="reportes/inventario.php">Inventario</a>
                <a href="reportes/ventas_detalle.php">Ventas Detalle</a>
                <a href="reportes/ventas_encabezado.php">Ventas Encabezado</a>
            </div>
        </div>
        <?php endif; ?>
        <div class="navbar-user-info">
            <span class="user-text">Cajero: 
                <strong><?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></strong>
            </span>
            <a href="includes/logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<main class="main-content-wrapper">
    <h2>Gestión de Productos</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="<?= strpos($mensaje,'Error')!==false?'alert-custom-danger':'alert-custom-success' ?> text-center">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-30 card-form">
        <h3>Agregar Nuevo Suplemento</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="crear">
            <div class="grid-2-cols">
                <div>
                    <label for="codigo">Código</label>
                    <input type="text" id="codigo" name="codigo" required placeholder="Ej: SUP001" class="input-padded">
                    
                    <label for="codigo_barras">Código de Barras</label>
                    <input type="text" id="codigo_barras" name="codigo_barras" placeholder="Escanéalo aquí" class="input-padded">

                    
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Proteína Whey" class="input-padded">
                    
                    <label for="marca">Marca</label>
                    <input type="text" id="marca" name="marca" required placeholder="Ej: NutriPower" class="input-padded">
                    
                    <label for="precio">Precio de Venta ($)</label>
                    <input type="number" id="precio" name="precio" required step="0.01" min="0" placeholder="Ej: 250.00" class="input-padded">

                    <label for="stock">Stock Inicial</label>
                    <input type="number" id="stock" name="stock" min="0" value="0" placeholder="Ej: 10" class="input-padded">
                </div>
                <div>
                    <label for="imagen">Imagen (Máx. 2MB)</label>
                    <input type="file" id="imagen" name="imagen" accept="image/*" class="file-input-padded">
                </div>
            </div>
            <button type="submit" class="btn-general mt-15 w-full">Guardar Suplemento</button>
        </form>
    </div>

    <div class="card">
        <h3>Listado de Suplementos</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="col-img">Imagen</th>
                        <th>Código</th>
                        <th>Nombre</th>
                        <th>Marca</th>
                        <th class="col-price">Precio</th>
                        <th class="col-stock">Stock</th>
                        <th class="col-status">Estatus</th>
                        <th class="col-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($productos && $productos->num_rows > 0): ?>
                        <?php while($p = $productos->fetch_assoc()): ?>
                            <tr class="<?= $p['estatus'] ? '' : 'row-inactive' ?>">
                                <td><img src="img.php?tipo=suplemento&id=<?= $p['id'] ?>" class="img-product-small" alt="Imagen del producto"></td>
                                <td><?= htmlspecialchars($p['codigo']) ?></td>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td><?= htmlspecialchars($p['marca']) ?></td>
                                <td class="text-right">$<?= number_format($p['precio_venta'],2) ?></td>
                                <td class="text-center stock-<?= $p['cantidad'] > 0 ? 'good' : 'low' ?>"><?= $p['cantidad'] ?></td>
                                <td><?= $p['estatus'] ? '<span class="text-success-bold">ACTIVO</span>' : '<span class="text-danger-simple">INACTIVO</span>' ?></td>
                                <td class="text-center text-nowrap action-col">
                                    <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn-editar">✏️ Editar</a>
                                    <?php if ($p['estatus']): ?>
                                        <a href="productos.php?action=desactivar&id=<?= $p['id'] ?>" 
                                        class="btn-desactivar btn-confirm-action"
                                        data-confirm-message="¿Seguro quieres desactivar este producto?">
                                        ❌ Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="productos.php?action=activar&id=<?= $p['id'] ?>" 
                                        class="btn-general btn-confirm-action btn-activate"
                                        data-confirm-message="¿Seguro quieres activar este producto?">
                                        ✅ Activar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No hay suplementos registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script src="js/main.js"></script>
<script>
    document.querySelectorAll('.btn-confirm-action').forEach(btn => {
        btn.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm-message');
            if (!confirm(message)) event.preventDefault();
        });
    });

    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('navbar-menu').classList.toggle('active');
    });
</script>
</body>
</html>
