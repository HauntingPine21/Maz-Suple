<?php
require_once 'includes/seguridad_basica.php'; // Validar sesión
require_once 'config/db.php';

$rol = $_SESSION['user']['rol'];
$mensaje = "";
$error = "";

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $rol_input = $_POST['rol'] ?? 'operador';

    try {
        if ($action === 'crear') {
            if (!empty($nombre) && !empty($username) && !empty($password)) {
                $pass_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO usuarios (nombre_completo, username, password, rol, activo) VALUES (?, ?, ?, ?, 1)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssss", $nombre, $username, $pass_hash, $rol_input);
                $stmt->execute();
                $mensaje = "Usuario creado correctamente.";
            } else {
                $error = "Todos los campos son obligatorios.";
            }
        }
    } catch (Exception $e) {
        if ($mysqli->errno === 1062) {
            $error = "El nombre de usuario '$username' ya existe.";
        } else {
            $error = "Error al procesar la solicitud: " . $e->getMessage();
        }
    }
}

// Acción de desactivar usuario
if (isset($_GET['action']) && $_GET['action'] === 'baja' && isset($_GET['id'])) {
    $id_baja = intval($_GET['id']);
    if ($id_baja !== $_SESSION['user']['id']) {
        $sql_baja = "UPDATE usuarios SET activo = 0 WHERE id = ?";
        $stmt_baja = $mysqli->prepare($sql_baja);
        $stmt_baja->bind_param("i", $id_baja);
        $stmt_baja->execute();
        header("Location: usuarios.php");
        exit;
    } else {
        $error = "No puedes desactivar tu propia cuenta.";
    }
}

// Obtener listado de usuarios
$resultado = $mysqli->query("SELECT id, nombre_completo, username, rol, activo FROM usuarios ORDER BY id ASC");
$usuarios_db = [];
while ($row = $resultado->fetch_assoc()) {
    $usuarios_db[] = $row;
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maz-Suple | Usuarios</title>
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
            <button class="dropbtn">Gestion ▾</button>
            <div class="dropdown-content">
                <a href="productos.php">Productos</a>
                <a href="compras.php">Compras</a>
                <a href="usuarios.php">Usuarios</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">Reportes ▾</button>
            <div class="dropdown-content">
                <a href="reportes/compras.php">Reportes Compra</a>
                <a href="reportes/devoluciones.php">Reportes Devoluciones</a>
                <a href="reportes/inventario.php">Reportes Inventario</a>
                <a href="reportes/ventas_detalle.php">Reportes Detalle</a>
                <a href="reportes/ventas_encabezado.php">Reportes Encabezado</a>
            </div>
        </div>
        <?php endif; ?>

        <a href="includes/logout.php" class="btn-general">Cerrar Sesión</a>
    </div>
</div>

<div class="main-container">
    <h2>Administración de Usuarios</h2>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mb-20">
        <h3>Agregar Nuevo Usuario</h3>
        <form method="POST">
            <input type="hidden" name="action" value="crear">
            <div class="flex-column gap-10">
                <input type="text" name="nombre_completo" placeholder="Nombre completo" required>
                <input type="text" name="username" placeholder="Usuario" required>
                <input type="password" name="password" placeholder="Contraseña" required>
                <select name="rol">
                    <option value="operador">Operador</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" class="btn-general">Crear Usuario</button>
            </div>
        </form>
    </div>

    <div class="card">
        <h3>Listado de Empleados</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios_db)): ?>
                        <tr><td colspan="6" class="text-center">No hay usuarios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios_db as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo ucfirst($u['rol']); ?></td>
                            <td>
                                <?php echo $u['activo'] ? '<span class="text-success-bold">Activo</span>' : '<span class="text-gray">Inactivo</span>'; ?>
                            </td>
                            <td>
                                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                    <?php if ($u['activo']): ?>
                                        <a href="usuarios.php?action=baja&id=<?php echo $u['id']; ?>" 
                                           class="link-danger btn-confirm-action"
                                           data-confirm-message="¿Seguro que deseas desactivar este usuario?">
                                           Desactivar
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-light">Baja</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-small-muted">(Tú)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="js/main.js"></script>
<script>
    // Confirmaciones dinámicas
    document.querySelectorAll('.btn-confirm-action').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirmMessage)) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
