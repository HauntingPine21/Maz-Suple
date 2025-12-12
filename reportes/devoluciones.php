<?php
// devoluciones.php

require_once '../includes/seguridad_basica.php';
require_once '../config/db.php';

$rol = $_SESSION['user']['rol'];
$venta_encontrada = null;
$detalles_venta = [];
$mensaje_error = "";
$mensaje_exito = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['folio_input'])) {

    $folio_busqueda = $mysqli->real_escape_string($_POST['folio_input']);
    $folio_id = intval($folio_busqueda);

    // ============================
    // 1. BUSCAR ENCABEZADO DE VENTA
    // ============================
    $sql_v = "SELECT v.id, v.fecha_hora, v.total, u.nombre AS cajero
              FROM ventas v
              JOIN usuarios u ON v.id_usuario = u.id
              WHERE v.id = '$folio_id'";

    $res_v = $mysqli->query($sql_v);

    if ($res_v && $res_v->num_rows > 0) {

        $venta_encontrada = $res_v->fetch_assoc();
        $id_venta_encontrada = intval($venta_encontrada['id']);

        // ======================================
        // 2. BUSCAR DETALLES + LIBROS + CODIGOS
        // ======================================
        $sql_d = "SELECT dv.id_libro, dv.cantidad, dv.precio_unitario, dv.importe,
                         l.titulo, l.codigo
                  FROM detalle_ventas dv
                  JOIN libros l ON dv.id_libro = l.id
                  WHERE dv.id_venta = $id_venta_encontrada";

        $res_d = $mysqli->query($sql_d);


        while ($row = $res_d->fetch_assoc()) {
            $detalles_venta[] = $row;
        }

    } else {
        $mensaje_error = "Folio de venta '$folio_busqueda' no encontrado.";
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ma<-Suple | Devoluciones</title>
    <link rel="stylesheet" href="../css/devolucionesReportes.css?v=<?php echo time(); ?>">
    
</head>

<body>

<div class="navbar">
    <div class="navbar-logo">
        <img src="../assets/ImgLogo.png" alt="Logo">
    </div>

    <button class="menu-toggle" id="mobile-menu-btn">
        <span></span><span></span><span></span>
    </button>

    <div class="navbar-menu" id="navbar-menu">
        <div class="dropdown">
            <button class="dropbtn">Cajero ▾</button>
            <div class="dropdown-content">
                <a href="../dashboard.php">Inicio</a>
                <a href="../ventas.php">Punto de Venta</a>
                <a href="../devoluciones.php">Devoluciones</a>
            </div>
        </div>

        <?php if ($rol === 'admin'): ?>
        <div class="dropdown">
            <button class="dropbtn">Gestión ▾</button>
            <div class="dropdown-content">
                <a href="../productos.php">Productos</a>
                <a href="../compras.php">Compras</a>
                <a href="../usuarios.php">Usuarios</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">Reportes ▾</button>
            <div class="dropdown-content">
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
    <h2>Gestión de Devoluciones</h2>

    <?php if (!empty($mensaje_error)): ?>
        <div class="alert-custom-danger">
            <?= htmlspecialchars($mensaje_error) ?>
        </div>
    <?php endif; ?>

    <div class="card mb-20">
        <h3>Buscar Venta por Folio</h3>

        <form method="POST">
            <div class="flex-row">
                <input type="text" name="folio_input"
                       placeholder="Ingresa Folio (Ej: 1001)"
                       required
                       value="<?= isset($_POST['folio_input']) ? htmlspecialchars($_POST['folio_input']) : '' ?>"
                       class="flex-grow w-auto">

                <button class="btn-general w-150">Buscar Venta</button>
            </div>
        </form>
    </div>

<?php if ($venta_encontrada): ?>

<div class="card mt-20">
    <h3>Venta Encontrada (#<?= $venta_encontrada['id'] ?>)</h3>

    <p>
        Fecha: <strong><?= date('d/m/Y H:i', strtotime($venta_encontrada['fecha_hora'])) ?></strong> |
        Total Venta: <strong>$<?= number_format($venta_encontrada['total'], 2) ?></strong> |
        Cajero: <strong><?= htmlspecialchars($venta_encontrada['cajero']) ?></strong>
    </p>

    <hr>

    <form id="form-devolucion">
        <input type="hidden" id="venta_id_origen" value="<?= $venta_encontrada['id'] ?>">

        <table>
            <thead>
                <tr>
                    <th>Devolver</th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Cant. Vendida</th>
                    <th>Cant. a Devolver</th>
                    <th>Precio Unitario</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($detalles_venta as $item): ?>
                <tr>
                    <td class="text-center">
                        <input type="checkbox" class="check-devolucion"
                               data-id="<?= $item['id_libro'] ?>">
                    </td>

                    <td><?= htmlspecialchars($item['titulo']) ?></td>
                    <td><?= htmlspecialchars($item['codigo']) ?></td>

                    <td class="text-center"><?= $item['cantidad'] ?></td>

                    <td class="text-center">
                        <input type="number"
                               id="cant_<?= $item['id_libro'] ?>"
                               min="1"
                               max="<?= $item['cantidad'] ?>"
                               value="1"
                               disabled>
                    </td>

                    <td class="text-right">$<?= number_format($item['precio_unitario'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-15">
            <label for="motivo_devolucion">Motivo de la Devolución</label>
            <input type="text" id="motivo_devolucion"
                   placeholder="Ej: Defecto de fabricación..."
                   class="w-full">
        </div>

        <div class="text-right">
            <button type="button" id="btn-procesar-devolucion"
                    class="btn-general mt-15">
                Procesar Devolución Seleccionada
            </button>
        </div>
    </form>
</div>

<?php endif; ?>
</div>

<script src="js/main.js"></script>

<script>
// Habilitar input si el checkbox está marcado
document.querySelectorAll('.check-devolucion').forEach(ch => {
    ch.addEventListener('change', function () {
        const id = this.dataset.id;
        const inp = document.getElementById('cant_' + id);
        inp.disabled = !this.checked;
        if (!this.checked) inp.value = 1;
    });
});

// PROCESAR DEVOLUCIÓN
const btn = document.getElementById('btn-procesar-devolucion');

if (btn) {
    btn.addEventListener('click', async () => {

        const items = [];
        document.querySelectorAll('.check-devolucion:checked').forEach(ch => {
            const id = ch.dataset.id;
            const q = document.getElementById('cant_' + id).value;
            items.push({ id_libro: parseInt(id), cantidad: parseInt(q) });
        });

        if (items.length === 0) {
            alert("Seleccione al menos un producto.");
            return;
        }

        const motivo = document.getElementById("motivo_devolucion").value;
        const idVenta = parseInt(document.getElementById("venta_id_origen").value);

        if (!confirm("¿Procesar devolución? El stock será restaurado.")) return;

        const resp = await fetch("ajax/confirmar_devolucion.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id_venta: idVenta, items, motivo })
        });

        const data = await resp.json();

        if (data.status === "ok") {
            alert("Devolución registrada. Folio: " + data.folio);
            window.open(`ticket.php?folio=${data.folio}&tipo=devolucion`, "_blank");
            window.location.href = "devoluciones.php";
        } else {
            alert("Error: " + data.msg);
        }
    });
}

// MANEJO DE FOLIOS OFFLINE
document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector("form");
    const input = document.querySelector('input[name="folio_input"]');

    form.addEventListener("submit", async (e) => {

        const folio = input.value.trim();

        if (folio.toUpperCase().includes("OFF-") || isNaN(folio)) {

            e.preventDefault();

            const res = await fetch(`ajax/buscar_id_folio.php?folio=${folio}`);
            const data = await res.json();

            if (data.status === "ok") {
                input.value = data.id_real;
                form.submit();
            } else {
                alert("Ese Folio Offline no existe o no se ha sincronizado.");
            }
        }
    });
});
</script>

</body>
</html>
