<?php
require_once 'includes/security_guard.php'; // Usaremos security_guard.php para consistencia
require_once 'config/db.php';

$rol = $_SESSION['user']['rol'];
$mensaje = "";
$error = "";

// Solo administradores pueden acceder y gestionar usuarios
if ($rol !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

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
            $error = "Error: El nombre de usuario '$username' ya existe.";
        } else {
            $error = "Error al procesar la solicitud: " . $e->getMessage();
        }
    }
}

// Acción de activar/desactivar usuario
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id_user = intval($_GET['id']);
    $action_type = $_GET['action'];

    if ($id_user <= 0) die("ID inválido");

    if ($id_user === $_SESSION['user']['id']) {
        $error = "No puedes cambiar el estatus de tu propia cuenta.";
    } else {
        $nuevo_estatus = ($action_type === 'baja') ? 0 : 1;
        $sql = "UPDATE usuarios SET activo = ? WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $nuevo_estatus, $id_user);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
             header("Location: usuarios.php");
             exit;
        } else {
             $error = "No se pudo actualizar el estatus del usuario.";
        }
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
    <title>MazSuple | Administración de Usuarios</title>
    <link rel="stylesheet" href="css/usuarios.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="assets/img/logo-mazsuplementos_icon.svg">
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
                <a href="productos.php">Productos</a>
                <a href="compras.php">Compras</a>
                <a href="usuarios.php" class="active">Usuarios</a>
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
            <span class="user-text">Administrador: 
                <strong><?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></strong>
            </span>
            <a href="includes/logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<main class="main-content-wrapper">
    <h2>Administración de Usuarios</h2>

    <?php if ($mensaje): ?>
        <div class="alert-custom-success text-center"><?php echo htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-custom-danger text-center"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mb-30 card-form">
        <h3>Agregar Nuevo Usuario</h3>
        <form method="POST">
            <input type="hidden" name="action" value="crear">
            <div class="grid-2-cols">
                <div>
                    <label for="nombre_completo">Nombre Completo</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Nombre completo" required class="input-padded">
                    
                    <label for="username">Usuario (Login)</label>
                    <input type="text" id="username" name="username" placeholder="Usuario" required class="input-padded">
                </div>
                <div>
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Contraseña" required class="input-padded">
                    
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" class="input-padded select-padded">
                        <option value="operador">Operador</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-general mt-15 w-full">Crear Usuario</button>
        </form>
    </div>

    <div class="card">
        <h3>Listado de Empleados</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="col-small">ID</th>
                        <th>Nombre Completo</th>
                        <th class="col-medium">Usuario</th>
                        <th class="col-small">Rol</th>
                        <th class="col-status">Estatus</th>
                        <th class="col-actions-sm">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios_db)): ?>
                        <tr><td colspan="6" class="text-center text-muted">No hay usuarios registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios_db as $u): ?>
                        <tr class="<?= $u['activo'] ? '' : 'row-inactive' ?>">
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo ucfirst($u['rol']); ?></td>
                            <td>
                                <?php echo $u['activo'] ? '<span class="text-success-bold">ACTIVO</span>' : '<span class="text-danger-simple">INACTIVO</span>'; ?>
                            </td>
                            <td class="text-center text-nowrap action-col">
                                <?php if ($u['id'] != $_SESSION['user']['id']): ?>
                                    <?php if ($u['activo']): ?>
                                        <a href="usuarios.php?action=baja&id=<?php echo $u['id']; ?>" 
                                           class="btn-desactivar btn-confirm-action"
                                           data-confirm-message="¿Seguro que deseas desactivar al usuario '<?php echo $u['username']; ?>'?">
                                           ❌ Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="usuarios.php?action=activar&id=<?php echo $u['id']; ?>" 
                                           class="btn-general btn-confirm-action btn-activate"
                                           data-confirm-message="¿Seguro que deseas reactivar al usuario '<?php echo $u['username']; ?>'?">
                                           ✅ Activar
                                        </a>
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
</main>

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
    // Script para la barra de navegación móvil (se puede mover a main.js)
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('navbar-menu').classList.toggle('active');
    });
</script>
</body>
</html>