<?php
// ============================================================
// RESPONSABLE: Rol 2 (UX-UI Impresión)
// REQUERIMIENTO: Cabecera general del reporte
// ============================================================

// Asegurar que las variables existan para evitar warnings
$titulo_reporte    = $titulo_reporte ?? 'REPORTE';
$contenido_reporte = $contenido_reporte ?? '<p style="color:red;">⚠ No se recibió contenido del reporte.</p>';
$usuario_gen       = $usuario_gen ?? 'Administrador';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maz-Suple | Reportes</title>
    <link rel="stylesheet" href="../css/reportes.css">
</head>

<body>
    <!-- NAVBAR SUPERIOR -->
    <div class="navbar">
        <div class="navbar-logo">
            <img src="../assets/img/logo-maria-de-letras_v2.svg" alt="Logo de María de Letras">
        </div>

        <div class="navbar-menu">
            <a href="../ventas.php">Punto de ventas</a>
            <a href="../productos.php">Productos</a>
            <a href="../compras.php">Compras</a>
            <a href="../devoluciones.php">Devoluciones</a>
            <a href="../usuarios.php">Usuarios</a>

            <a href="compras.php">Reporte compras</a>
            <a href="devoluciones.php">Reporte devoluciones</a>
            <a href="inventario.php">Reporte inventario</a>
            <a href="ventas_detalle.php">Reporte detalle</a>
            <a href="ventas_encabezado.php">Reporte encabezado</a>

            <a href="../index.php">Salir</a>
        </div>
    </div>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container main-content-large">

        <!-- CABECERA DEL REPORTE -->
        <div class="report-header">
            <img src="../assets/img/logo-maria-de-letras_icon.svg" alt="Logo" class="report-logo" style="height: 50px;">
            
            <h1 class="report-title">
                <?= htmlspecialchars($titulo_reporte) ?>
            </h1>

            <div class="report-meta">
                <p class="font-bold" style="margin: 0;">Librería María de Letras</p>
                <p style="margin: 0;">
                    Fecha de Generación: <?= date('d/m/Y H:i:s') ?>
                </p>
            </div>
        </div>

        <!-- CONTENIDO DINÁMICO DEL REPORTE -->
        <?= $contenido_reporte ?>

        <!-- PIE DE PÁGINA -->
        <div class="report-footer">
            <p style="margin: 0;">Generado por: <?= htmlspecialchars($usuario_gen) ?></p>
            <p style="margin: 0;">Página 1 de 1</p>
        </div>

    </div>
</body>
</html>
