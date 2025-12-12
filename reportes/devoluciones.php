<?php
// ============================================================
// REPORTE DEVOLUCIONES
// ============================================================
require_once '../config/db.php';
require_once '../includes/security_guard.php';

$titulo_reporte = "REPORTE DE ENCABEZADOS DE DEVOLUCIONES";

// ======================
// 1. Inicializar variables y filtros
// ======================
$devoluciones = [];
$cajeros = [];
$suma_total_devuelto = 0;
$num_devoluciones = 0;
$ticket_promedio_dev = 0;

$filtro_cajero = $_GET['cajero'] ?? 0;
$fecha_ini = $_GET['inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fin'] ?? date('Y-m-d');

// ======================
// 2. Obtener lista de cajeros
// ======================
$res_cajeros = $mysqli->query("SELECT id, nombre_completo FROM usuarios WHERE rol = 'operador' AND activo = 1 ORDER BY nombre_completo");
while ($row = $res_cajeros->fetch_assoc()) {
    $cajeros[] = $row;
}

// ======================
// 3. Obtener Devoluciones según filtros
// ======================
// ASUMIMOS TABLA 'devoluciones' con campos 'total_devuelto', 'id_usuario', 'motivo'
$sql = "SELECT d.id AS folio, d.fecha_hora, d.total_reembolsado, u.nombre_completo AS cajero, d.motivo
        FROM devoluciones d
        JOIN usuarios u ON d.id_usuario = u.id
        WHERE DATE(d.fecha_hora) BETWEEN ? AND ?";

$params = [$fecha_ini, $fecha_fin];

if ($filtro_cajero != 0) {
    $sql .= " AND d.id_usuario = ?";
    $params[] = $filtro_cajero;
}

$sql .= " ORDER BY d.fecha_hora DESC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $devoluciones[] = $row;
}

// ======================
// 4. Calcular totales
// ======================
$num_devoluciones = count($devoluciones);
foreach ($devoluciones as $d) {
    $suma_total_devuelto += $d['total_reembolsado'];
}
if ($num_devoluciones > 0) {
    $ticket_promedio_dev = $suma_total_devuelto / $num_devoluciones;
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
    <link rel="stylesheet" href="../css/ventasEncabezadoReportes.css">
    <link rel="icon" type="image/png" href="../assets/SupleIcono.png"> 
</head>

<body>
    
    <div class="navbar">
        <div class="navbar-logo">
            <img src="../assets/ImgLogo.png" alt="Logo">
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
                    <a href="devoluciones.php" class="active">Reporte devoluciones</a>
                    <a href="inventario.php">Reporte inventario</a>
                    <a href="ventas_detalle.php">Reporte detalle</a>
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
            <h3 class="mb-15">Filtros de Devoluciones por Período</h3>
            <form action="" method="GET">
                <div class="filters-container">

                    <div class="filter-group">
                        <label for="inicio">Fecha Inicio</label>
                        <input type="date" id="inicio" name="inicio" required value="<?= htmlspecialchars($fecha_ini) ?>" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <label for="fin">Fecha Fin</label>
                        <input type="date" id="fin" name="fin" required value="<?= htmlspecialchars($fecha_fin) ?>" class="filter-input">
                    </div>

                    <div class="filter-group-large">
                        <label for="cajero">Cajero (Opcional)</label>
                        <select id="cajero" name="cajero" class="filter-input">
                            <option value="0">--- Todos los Cajeros ---</option>
                            <?php foreach ($cajeros as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ($filtro_cajero == $c['id']) ? "selected" : "" ?>>
                                    <?= htmlspecialchars($c['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-general w-150">Generar Reporte</button>
                    <button type="button" class="btn-general w-150 btn-print" onclick="window.print()">Imprimir / PDF</button>
                </div>
            </form>
        </div>

        <div class="card">
            <p class="font-bold text-sm">
                Total de Devoluciones Encontradas: <?= $num_devoluciones ?>
            </p>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr class="bg-green">
                            <th class="w-100">Folio Dev.</th>
                            <th class="w-150">Fecha/Hora</th>
                            <th>Cajero</th>
                            <th class="w-200">Motivo</th>
                            <th class="w-120 text-right">Total Devuelto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($num_devoluciones > 0): ?>
                            <?php foreach ($devoluciones as $d): ?>
                            <tr>
                                <td><?= $d['folio'] ?></td>
                                <td><?= date("d/m/Y H:i:s", strtotime($d['fecha_hora'])) ?></td>
                                <td><?= htmlspecialchars($d['cajero']) ?></td>
                                <td><?= htmlspecialchars($d['motivo']) ?></td>
                                <td class="text-right font-bold">$<?= number_format($d['total_reembolsado'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No se encontraron devoluciones en este período.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    
                    <?php if ($num_devoluciones > 0): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right font-bold bg-light-green">TOTAL DINERO DEVUELTO</td>
                            <td class="text-right font-bold bg-light-green">$<?= number_format($suma_total_devuelto, 2) ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right font-bold bg-light-gray">NÚMERO DE DEVOLUCIONES</td>
                            <td class="text-right font-bold bg-light-gray"><?= $num_devoluciones ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right font-bold bg-gray">PROMEDIO POR DEVOLUCIÓN</td>
                            <td class="text-right font-bold bg-gray">$<?= number_format($ticket_promedio_dev, 2) ?></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </div> 
    <script src="../js/offline_manager.js?v=<?php echo time(); ?>"></script>
</body>
</html>
