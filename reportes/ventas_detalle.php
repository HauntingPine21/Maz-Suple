<?php
// ============================================================
// REPORTE DETALLADO DE VENTAS DE SUPLEMENTOS
// ============================================================
require_once '../config/db.php';
// Nota: Puedes necesitar el security_guard si aplica a esta ruta.

$titulo_reporte = "REPORTE DETALLADO DE VENTAS DE SUPLEMENTOS";

// Asegúrate de recibir las fechas desde GET
$fecha_ini = $_GET['inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fin'] ?? date('Y-m-t');
$producto_filter = $_GET['producto'] ?? '';

// ===============================
// CONSULTA DETALLE VENTAS
// ===============================
$sql = "
SELECT 
    v.id AS folio,
    v.fecha_hora,
    s.nombre,
    dv.cantidad,
    dv.precio_unitario,
    (dv.cantidad * dv.precio_unitario) AS importe_linea
FROM detalle_ventas dv
INNER JOIN ventas v ON dv.id_venta = v.id
INNER JOIN suplementos s ON dv.id_suplemento = s.id
WHERE v.fecha_hora BETWEEN ? AND ?
";

$params = [$fecha_ini . ' 00:00:00', $fecha_fin . ' 23:59:59'];

if (!empty($producto_filter)) {
    $sql .= " AND s.nombre LIKE ?";
    $params[] = "%" . $producto_filter . "%";
}

$sql .= " ORDER BY v.fecha_hora ASC";

$stmt = $mysqli->prepare($sql);

// Bind dinámico según cantidad de parámetros
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    // Asumiendo PHP 5.6+ para el unpacking con ...$params
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();

$detalles = [];
$suma_importe = 0;
$suma_unidades = 0;

while ($row = $res->fetch_assoc()) {
    $detalles[] = $row;
    $suma_importe += $row['importe_linea'];
    $suma_unidades += $row['cantidad'];
}

// ==========================================================
// INICIO DEL HTML (Estructura de Plantilla con Sidebar)
// ==========================================================
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maz-Suple | <?php echo $titulo_reporte; ?></title>
    <link rel="stylesheet" href="../css/ventasDetalleReportes.css"> 
</head>

<body>
    
    <div class="navbar">
        <div class="navbar-logo">
            <img src="../assets/img/logo-maria-de-letras_v2.svg" alt="Logo de María de Letras">
        </div>
        
        <div class="navbar-menu">
            <a href="../ventas.php" class="nav-link">Punto de ventas</a>
            <a href="../productos.php" class="nav-link">Productos</a>
            <a href="../compras.php" class="nav-link">Compras</a>
            <a href="../devoluciones.php" class="nav-link">Devoluciones</a>
            <a href="../usuarios.php" class="nav-link">Usuarios</a>

            <div class="nav-divider"></div>
            
            <div class="dropdown">
                <button class="dropbtn active">Reportes</button>
                <div class="dropdown-content show">
                    <a href="compras.php">Reporte compras</a>
                    <a href="devoluciones.php">Reporte devoluciones</a>
                    <a href="inventario.php">Reporte inventario</a>
                    <a href="ventas_detalle.php" class="active">Reporte detalle</a>
                    <a href="ventas_encabezado.php">Reporte encabezado</a>
                </div>
            </div>

            <div class="navbar-user-info">
                <span class="user-text">Usuario: Administrador</span>
                <a href="../index.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </div>
    
    <div class="main-container"> 
        
        <div class="report-header">
             <h1 class="report-title"><?php echo $titulo_reporte; ?></h1>
        </div>

        <div class="card filtros-print mb-20">
            <h3 class="mb-15">Filtros de Detalle</h3>
            <form action="" method="GET">
                <div class="filters-container">
                    
                    <div class="filter-group">
                        <label for="inicio">Fecha Inicio</label>
                        <input 
                            type="date" 
                            id="inicio" 
                            name="inicio" 
                            required
                            value="<?= htmlspecialchars(substr($fecha_ini,0,10)) ?>" 
                            class="filter-input">
                    </div>
                    
                    <div class="filter-group">
                        <label for="fin">Fecha Fin</label>
                        <input 
                            type="date" 
                            id="fin" 
                            name="fin" 
                            required
                            value="<?= htmlspecialchars(substr($fecha_fin,0,10)) ?>" 
                            class="filter-input">
                    </div>

                    <div class="filter-group-large">
                        <label for="producto">Suplemento (Opcional)</label>
                        <input 
                            type="text" 
                            id="producto" 
                            name="producto" 
                            placeholder="Nombre del suplemento..." 
                            value="<?= htmlspecialchars($producto_filter) ?>"
                            class="filter-input">
                    </div>
                    
                    <button type="submit" class="btn-general w-150">
                        Generar Reporte
                    </button>

                    <button type="button" class="btn-general w-150 btn-print" onclick="window.print()">
                        Imprimir / PDF
                    </button>
                </div>
            </form>
        </div>

        <div class="card">
            <p class="font-bold text-sm">
                Mostrando resultados del <?= date("d/m/Y", strtotime($fecha_ini)) ?> 
                al <?= date("d/m/Y", strtotime($fecha_fin)) ?>
            </p>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr class="bg-green"> 
                            <th class="w-100">Folio</th>
                            <th class="w-150">Fecha/Hora</th>
                            <th>Suplemento</th>
                            <th class="w-100 text-center">Cant.</th>
                            <th class="w-120 text-right">Precio Unit.</th>
                            <th class="w-120 text-right">Importe</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($detalles) > 0): ?>
                        <?php foreach ($detalles as $d): ?>
                        <tr>
                            <td><?= $d['folio'] ?></td>
                            <td><?= date("d/m/Y H:i:s", strtotime($d['fecha_hora'])) ?></td>
                            <td><?= htmlspecialchars($d['nombre']) ?></td>
                            <td class="text-center"><?= $d['cantidad'] ?></td>
                            <td class="text-right">$<?= number_format($d['precio_unitario'], 2) ?></td>
                            <td class="text-right">$<?= number_format($d['importe_linea'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No se encontraron ventas para este rango.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>

                    <?php if (count($detalles) > 0): ?>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right font-bold bg-light-green">
                                IMPORTE TOTAL
                            </td>
                            <td class="text-right font-bold bg-light-green">
                                $<?= number_format($suma_importe, 2) ?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="5" class="text-right font-bold bg-light-gray">
                                TOTAL UNIDADES VENDIDAS
                            </td>
                            <td class="text-right font-bold bg-light-gray">
                                <?= $suma_unidades ?>
                            </td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div> </body>
</html>