<?php
// ============================================================
// ADAPTADO A LA NUEVA BASE DE DATOS
// Tabla: items
// Campos: id_item, codigo, nombre, precio, imagen (BLOB)
// ============================================================
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

// 2. Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'editar') {
    $codigo = $_POST['codigo'];
    $nombre = $_POST['nombre'];
    $precio = floatval($_POST['precio']);

    $mysqli->begin_transaction();
    try {

        // ACTUALIZAR DATOS PRINCIPALES
        $sql_update = "UPDATE items SET codigo = ?, nombre = ?, precio = ? WHERE id_item = ?";
        $stmt = $mysqli->prepare($sql_update);
        $stmt->bind_param("ssdi", $codigo, $nombre, $precio, $id_producto);
        $stmt->execute();


        // SI HAY IMAGEN NUEVA (SE REEMPLAZA EL BLOB)
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {

            $imagen_binaria = file_get_contents($_FILES['imagen']['tmp_name']);
            $mime = $_FILES['imagen']['type'];

            $sql_img = "UPDATE items SET imagen = ?, imagen_tipo = ? WHERE id_item = ?";
            $stmt_img = $mysqli->prepare($sql_img);
            $stmt_img->bind_param("bsi", $null, $mime, $id_producto);

            $null = NULL; 
            $stmt_img->send_long_data(0, $imagen_binaria);
            $stmt_img->execute();
        }

        $mysqli->commit();
        $mensaje = "Producto actualizado correctamente.";

    } catch (Exception $e) {
        $mysqli->rollback();
        $mensaje = "Error al actualizar: " . $e->getMessage();
    }
}

// 3. Obtener datos actuales del producto
$sql_producto = "SELECT * FROM items WHERE id_item = $id_producto";
$resultado = $mysqli->query($sql_producto);

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Producto | <?php echo htmlspecialchars($producto['nombre']); ?></title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" type="image/png" href="assets/img/logo-maria-de-letras_icon.svg">
  </head>

  <body>

    <div class="main-container">

        <div class="flex-between mb-15">
            <h2>Editando Producto: "<?php echo htmlspecialchars($producto['nombre']); ?>"</h2>
            <a href="productos.php" class="btn-general">Volver al Listado</a>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="<?php echo strpos($mensaje, 'Error') !== false ? 'alert-custom-danger' : 'alert-custom-success'; ?> text-center">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="card mb-30">
            <form method="POST" action="editar_producto.php?id=<?php echo $id_producto; ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="editar">

                <div class="grid-2-cols">

                    <div>
                        <label for="codigo">Código (SKU)</label><br>
                        <input type="text" id="codigo" name="codigo" required value="<?php echo htmlspecialchars($producto['codigo']); ?>" class="input-padded">

                        <br><br>
                        <label for="nombre">Nombre</label><br>
                        <input type="text" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($producto['nombre']); ?>" class="input-padded">
                    </div>

                    <div>
                        <label for="precio">Precio de Venta</label><br>
                        <input type="number" id="precio" name="precio" required step="0.01" min="0" value="<?php echo htmlspecialchars($producto['precio']); ?>" class="input-padded">

                        <br><br>
                        <label for="imagen">Cambiar Imagen (Opcional)</label><br>
                        <input type="file" id="imagen" name="imagen" accept="image/*" class="w-full file-input-padded">
                    </div>

                </div>

                <div style="display: flex; align-items: center; gap: 20px; margin-top: 15px;">
                    <button type="submit" class="btn-general">Guardar Cambios</button>

                    <div>
                        <p class="text-gray" style="margin: 0; font-size: 12px;">Imagen actual:</p>
                        <img src="img.php?tipo=item&id=<?php echo $id_producto; ?>" alt="Imagen actual" class="img-product-small">
                    </div>
                </div>

            </form>
        </div>
    </div>

  </body>
</html>
