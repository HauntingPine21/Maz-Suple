<?php
require_once 'config/db.php';
require_once 'includes/security_guard.php';

$rol = $_SESSION['user']['rol'];
$mensaje = "";

// ============================================================
// PROCESAR FORMULARIO DE ALTA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {

    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $marca = trim($_POST['marca']);
    $precio = $_POST['precio'];
    $descripcion = trim($_POST['descripcion']);

    if (!is_numeric($precio)) {
        $mensaje = "El precio no es válido.";
    } else {
        $imagen_binaria = null;
        $tipo_mime = 'image/jpeg';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {
            if ($_FILES['imagen']['size'] > 2 * 1024 * 1024) {
                $mensaje = "La imagen excede los 2MB permitidos.";
            } else {
                $tipo_mime = $_FILES['imagen']['type'];
                $imagen_binaria = file_get_contents($_FILES['imagen']['tmp_name']);
            }
        }

        if ($mensaje === "") {
            $mysqli->begin_transaction();

            try {
                // Insertar suplemento
                $sql = "INSERT INTO suplementos (codigo, nombre, marca, descripcion, precio_venta, estatus)
                        VALUES (?, ?, ?, ?, ?, 1)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssssd", $codigo, $nombre, $marca, $descripcion, $precio);
                $stmt->execute();
                $id_suplemento = $mysqli->insert_id;

                // Insertar imagen (si existe)
                if ($imagen_binaria) {
                    $sql_img = "INSERT INTO imagenes_suplemento (id_suplemento, contenido, tipo_mime, es_principal)
                                VALUES (?, ?, ?, 1)";
                    $stmt_img = $mysqli->prepare($sql_img);
                    $null = NULL;
                    $stmt_img->bind_param("iss", $id_suplemento, $null, $tipo_mime);
                    $stmt_img->send_long_data(1, $imagen_binaria);
                    $stmt_img->execute();
                }

                // Existencia inicial
                $mysqli->query("INSERT INTO existencias (id_suplemento, cantidad) VALUES ($id_suplemento, 0)");

                $mysqli->commit();
                $mensaje = "Suplemento creado correctamente.";

                $stmt->close();
                if (isset($stmt_img)) $stmt_img->close();

            } catch (mysqli_sql_exception $e) {
                $mysqli->rollback();
                $mensaje = str_contains($e->getMessage(), 'Duplicate') ? 
                            "Error: El código '$codigo' ya existe." : 
                            "Error: " . $e->getMessage();
            }
        }
    }
}

// ============================================================
// ACTIVAR / DESACTIVAR SUPLEMENTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $accion = $_GET['action'];
    $id = intval($_GET['id']);
    if ($id <= 0) die("ID inválido");
    if ($rol !== 'admin') die("Acceso denegado");

    try {
        if ($accion === 'desactivar') {
            $sql = "UPDATE suplementos SET estatus = 0 WHERE id = ?";
        } elseif ($accion === 'activar') {
            $sql = "UPDATE suplementos SET estatus = 1 WHERE id = ?";
        } else {
            die("Acción inválida");
        }

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header("Location: productos.php");
        exit;

    } catch (Exception $e) {
        $mensaje = "Error al actualizar el suplemento: " . $e->getMessage();
    }
}

// ============================================================
// LISTAR SUPLEMENTOS
// ============================================================
$sql_suplementos = "
    SELECT s.*, COALESCE(e.cantidad, 0) AS cantidad
    FROM suplementos s
    LEFT JOIN existencias e ON s.id = e.id_suplemento
    ORDER BY s.estatus DESC, s.nombre ASC
";

$suplementos = $mysqli->query($sql_suplementos);
?>
