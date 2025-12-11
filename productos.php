<?php
// ============================================================
// RESPONSABLE: Rol 4 (CRUD) y Rol 2 (UI)
// REQUERIMIENTO: "CRUD productos con imagen BLOB"
// ============================================================
require_once 'config/db.php';
require_once 'includes/security_guard.php';

// Variables para la vista
$rol = $_SESSION['user']['rol'];
$mensaje = "";

// ============================================================
// PROCESAR FORMULARIO DE ALTA
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear') {
    
    // Sanitización básica
    $codigo = trim($_POST['codigo']);
    $titulo = trim($_POST['titulo']);
    $precio = $_POST['precio'];

    if (!is_numeric($precio)) {
        $mensaje = "El precio no es válido.";
    } else {

        // Manejo de IMAGEN BLOB
        $imagen_binaria = null;
        $tipo_mime = 'image/jpeg';

        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === 0) {

            // 🔧 FIX — límite real de 2MB
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
                // 1. Insertar Libro
                $sql = "INSERT INTO libros (codigo, titulo, precio_venta, estatus) VALUES (?, ?, ?, 1)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssd", $codigo, $titulo, $precio);
                $stmt->execute();
                $id_libro = $mysqli->insert_id;

                // 2. Insertar Imagen (si existe)
                if ($imagen_binaria) {
                    $sql_img = "INSERT INTO imagenes_libro (id_libro, contenido, tipo_mime, es_principal) 
                                VALUES (?, ?, ?, 1)";
                    $stmt_img = $mysqli->prepare($sql_img);

                    // 🔧 FIX — cambio de "ibs" → "iss"
                    $null = NULL;
                    $stmt_img->bind_param("iss", $id_libro, $null, $tipo_mime);

                    // 🔧 FIX — mandar BLOB correctamente
                    $stmt_img->send_long_data(1, $imagen_binaria);

                    $stmt_img->execute();
                }

                // 3. Existencia inicial
                $mysqli->query("INSERT INTO existencias (id_libro, cantidad) VALUES ($id_libro, 0)");

                $mysqli->commit();
                $mensaje = "Producto creado correctamente.";

                // Limpieza
                $stmt->close();
                if (isset($stmt_img)) $stmt_img->close();

            } catch (mysqli_sql_exception $e) {
                $mysqli->rollback();

                // 🔧 FIX — detección real de duplicado
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $mensaje = "Error: El código '$codigo' ya existe.";
                } else {
                    $mensaje = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// ============================================================
// DESACTIVAR PRODUCTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'desactivar') {

    if ($rol !== 'admin') die("Acceso denegado"); // 🔧 FIX — seguridad

    $id_desactivar = intval($_GET['id']);
    if ($id_desactivar > 0) {
        try {
            $sql = "UPDATE libros SET estatus = 0 WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $id_desactivar);
            $stmt->execute();
            header("Location: productos.php");
            exit;
        } catch (Exception $e) {
            $mensaje = "Error al desactivar el producto: " . $e->getMessage();
        }
    }
}

// ============================================================
// ACTIVAR PRODUCTO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'activar') {

    if ($rol !== 'admin') die("Acceso denegado"); // 🔧 FIX — seguridad

    $id_activar = intval($_GET['id']);
    if ($id_activar > 0) {
        try {
            $sql = "UPDATE libros SET estatus = 1 WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $id_activar);
            $stmt->execute();
            header("Location: productos.php");
            exit;
        } catch (Exception $e) {
            $mensaje = "Error al activar el producto: " . $e->getMessage();
        }
    }
}

// ============================================================
// LISTAR PRODUCTOS (todos, incluso inactivos)
// ============================================================
$sql_productos = "
    SELECT l.*, 
           COALESCE(e.cantidad, 0) AS cantidad
    FROM libros l
    LEFT JOIN existencias e ON l.id = e.id_libro
    ORDER BY l.estatus DESC, l.titulo ASC  /* 🔧 FIX — activos primero */
";

$productos = $mysqli->query($sql_productos);
?>
