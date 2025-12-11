<?php
require_once 'config/db.php';
require_once 'includes/security_guard.php';

$rol = $_SESSION['user']['rol'];
$mensaje = "";
$producto = null;

// 1. Obtener ID desde URL
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_producto <= 0) {
    header("Location: productos.php");
    exit;
}

// 2. Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $marca = trim($_POST['marca']);
    $precio = floatval($_POST['precio']);

    $mysqli->begin_transaction();
    try {
        // Actualizar datos principales
        $sql = "UPDATE suplementos SET codigo = ?, nombre = ?, marca = ?, precio_venta = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sssdi", $codigo, $nombre, $marca, $precio, $id_producto);
        $stmt->execute();

        // Si hay nueva imagen, actualizar BLOB
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            $imagen_binaria = file_get_contents($_FILES['imagen']['tmp_name']);
            $mime = $_FILES['imagen']['type'];

            // Verificar si ya existe imagen principal
            $sql_check = "SELECT id FROM imagenes_suplemento WHERE id_suplemento = ? AND es_principal = 1 LIMIT 1";
            $stmt_check = $mysqli->prepare($sql_check);
            $stmt_check->bind_param("i", $id_producto);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // Actualizar imagen existente
                $sql_img = "UPDATE imagenes_suplemento SET contenido = ?, tipo_mime = ? WHERE id_suplemento = ? AND es_principal = 1";
                $stmt_img = $mysqli->prepare($sql_img);
                $null = NULL;
                $stmt_img->bind_param("bsi", $null, $mime, $id_producto);
                $stmt_img->send_long_data(0, $imagen_binaria);
                $stmt_img->execute();
            } else {
                // Insertar nueva imagen
                $sql_img = "INSERT INTO imagenes_suplemento (id_suplemento, contenido, tipo_mime, es_principal) VALUES (?, ?, ?, 1)";
                $stmt_img = $mysqli->prepare($sql_img);
                $null = NULL;
                $stmt_img->bind_param("ibs", $id_producto, $null, $mime);
                $stmt_img->send_long_data(1, $imagen_binaria);
                $stmt_img->execute();
            }
        }

        $mysqli->commit();
        $mensaje = "Producto actualizado correctamente.";
    } catch (Exception $e) {
        $mysqli->rollback();
        $mensaje = "Error al actualizar: " . $e->getMessage();
    }
}

// 3. Obtener datos actuales del producto
$sql_producto = "SELECT * FROM suplementos WHERE id = ?";
$stmt = $mysqli->prepare($sql_producto);
$stmt->bind_param("i", $id_producto);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado && $resultado->num_rows > 0) {
    $producto = $resultado->fetch_assoc();
} else {
    header("Location: productos.php");
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Editar Suplemento | <?= htmlspecialchars($producto['nombre']) ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <div class="main-container">
        <div class="flex-between mb-15">
            <h2>Editando Suplemento: "<?= htmlspecialchars($producto['nombre']) ?>"</h2>
            <a href="productos.php" class="btn-general">Volver al Listado</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="<?= strpos($mensaje, 'Error') !== false ? 'alert-custom-danger' : 'alert-custom-success' ?> text-center">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="card mb-30">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="editar">
                <div class="grid-2-cols">
                    <div>
                        <label>Código</label>
                        <input type="text" name="codigo" required value="<?= htmlspecialchars($producto['codigo']) ?>" class="input-padded">

                        <label>Nombre</label>
                        <input type="text" name="nombre" required value="<?= htmlspecialchars($producto['nombre']) ?>" class="input-padded">

                        <label>Marca</label>
                        <input type="text" name="marca" required value="<?= htmlspecialchars($producto['marca']) ?>" class="input-padded">
                    </div>
                    <div>
                        <label>Precio</label>
                        <input type="number" name="precio" required step="0.01" min="0" value="<?= htmlspecialchars($producto['precio_venta']) ?>" class="input-padded">

                        <label>Cambiar Imagen (Opcional)</label>
                        <input type="file" name="imagen" accept="image/*" class="file-input-padded">

                        <p class="text-gray" style="margin-top:10px;font-size:12px;">Imagen actual:</p>
                        <img src="img.php?tipo=suplemento&id=<?= $id_producto ?>" alt="Imagen actual" class="img-product-small">
                    </div>
                </div>

                <button type="submit" class="btn-general mt-15">Guardar Cambios</button>
            </form>
        </div>
    </div>
</body>
</html>
