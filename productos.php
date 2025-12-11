<?php
require_once 'config/db.php';
require_once 'includes/security_guard.php';

$rol = $_SESSION['user']['rol'];
$mensaje = "";

// ================================
// PROCESAR FORMULARIO DE ALTA
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $marca = trim($_POST['marca']);
    $precio = $_POST['precio'];
    
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
            $sql = "INSERT INTO suplementos (codigo, nombre, marca, precio_venta, estatus)
                    VALUES (?, ?, ?, ?, 1)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssd", $codigo, $nombre, $marca, $precio);
            $stmt->execute();
            $id_suplemento = $mysqli->insert_id;

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

            // Existencia inicial
            $mysqli->query("INSERT INTO existencias (id_suplemento, cantidad) VALUES ($id_suplemento, 0)");

            $mysqli->commit();
            $mensaje = "Suplemento creado correctamente.";
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
    <title>Maz Suple | Productos</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="main-container">
        <h2>Gestión de Productos</h2>

        <?php if ($mensaje !== ""): ?>
            <div class="<?= strpos($mensaje,'Error')!==false?'alert-custom-danger':'alert-custom-success' ?> text-center">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- FORMULARIO DE ALTA -->
        <div class="card mb-30">
            <h3>Agregar Nuevo Suplemento</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="crear">
                <div class="grid-2-cols">
                    <div>
                        <label>Código</label><br>
                        <input type="text" name="codigo" required placeholder="Ej: SUP001" class="input-padded">
                        <br><br>
                        <label>Nombre</label><br>
                        <input type="text" name="nombre" required placeholder="Ej: Proteína Whey" class="input-padded">
                        <br><br>
                        <label>Marca</label><br>
                        <input type="text" name="marca" required placeholder="Ej: NutriPower" class="input-padded">
                    </div>
                    <div>
                        <label>Precio</label><br>
                        <input type="number" name="precio" required step="0.01" min="0" placeholder="Ej: 250.00" class="input-padded">
                        <br><br>
                        <label>Imagen (Máx. 2MB)</label><br>
                        <input type="file" name="imagen" accept="image/*" class="file-input-padded">
                    </div>
                </div>
                <button type="submit" class="btn-general mt-15">Guardar Suplemento</button>
            </form>
        </div>

        <!-- LISTADO DE PRODUCTOS -->
        <div class="card">
            <h3>Listado de Suplementos</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estatus</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productos && $productos->num_rows > 0): ?>
                            <?php while($p = $productos->fetch_assoc()): ?>
                                <tr>
                                    <td><img src="img.php?tipo=suplemento&id=<?= $p['id'] ?>" class="img-product-small" alt="Imagen"></td>
                                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                                    <td><?= htmlspecialchars($p['nombre']) ?></td>
                                    <td><?= htmlspecialchars($p['marca']) ?></td>
                                    <td>$<?= number_format($p['precio_venta'],2) ?></td>
                                    <td><?= $p['cantidad'] ?></td>
                                    <td><?= $p['estatus'] ? '<span class="text-success-bold">ACTIVO</span>' : '<span class="text-danger-simple">INACTIVO</span>' ?></td>
                                    <td class="text-center text-nowrap">
                                        <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn-editar">Editar</a>
                                        
                                        <?php if ($p['estatus']): ?>
                                            <a href="productos.php?action=desactivar&id=<?= $p['id'] ?>" 
                                            class="btn-desactivar btn-confirm-action"
                                            data-confirm-message="¿Seguro quieres desactivar este producto?">
                                            Desactivar
                                            </a>
                                        <?php else: ?>
                                            <a href="productos.php?action=activar&id=<?= $p['id'] ?>" 
                                            class="btn-general btn-confirm-action"
                                            data-confirm-message="¿Seguro quieres activar este producto?">
                                            Activar
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
    </div>

    <script src="js/main.js"></script>
</body>
</html>
