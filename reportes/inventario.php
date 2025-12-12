<?php
// ============================================================
// REPORTE DE INVENTARIO PARA SUPLEMENTOS
// ============================================================
require_once '../config/db.php';
require_once '../includes/security_guard.php';

// ------------------------
// 1. Recibir filtros
// ------------------------
$filtro_q = isset($_GET['q']) ? $mysqli->real_escape_string($_GET['q']) : '';
$solo_activos = isset($_GET['activos']);
$filtro_stock = $_GET['stock'] ?? 'todos';

// ------------------------
// 2. Construir query
// ------------------------
$sql = "
    SELECT 
        s.codigo,
        s.nombre,
        s.precio_venta AS precio,
        e.cantidad AS existencia,
        s.estatus
    FROM suplementos s
    JOIN existencias e ON s.id = e.id_suplemento
    WHERE 1=1
";

if ($filtro_q !== '') {
    $sql .= " AND (s.codigo LIKE '%$filtro_q%' OR s.nombre LIKE '%$filtro_q%')";
}

if ($solo_activos) {
    $sql .= " AND s.estatus = 1";
}

$sql .= " ORDER BY s.nombre";

// ------------------------
// 3. Ejecutar query
// ------------------------
$resultado = $mysqli->query($sql);
$productos = [];
$total_existencias = 0;
$valor_total_inventario = 0;

while ($row = $resultado->fetch_assoc()) {
    $row['estado_str'] = ($row['estatus'] == 1) ? 'ACTIVO' : 'INACTIVO';
    $row['valor_linea'] = $row['existencia'] * $row['precio'];

    $total_existencias += $row['existencia'];
    $valor_total_inventario += $row['valor_linea'];

    $productos[] = $row;
}

$total_items = count($productos);

// ==========================================================
// INICIO DEL HTML (Estructura de Plantilla con Sidebar)
// ==========================================================
$titulo_reporte = "REPORTE DE INVENTARIO ACTUAL";
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maz-Suple | <?php echo $titulo_reporte; ?></title>
    <link rel="stylesheet" href="../css/inventarioReportes.css?v=<?php echo time(); ?>"> 
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
                    <a href="devoluciones.php">Reporte devoluciones</a>
                    <a href="inventario.php" class="active">Reporte inventario</a>
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
            <h3 class="mb-15">Filtros de Inventario</h3>

            <form method="GET">

                <div class="filters-container">

                    <div class="filter-group-large">
                        <label for="q">Buscar Suplemento</label>
                        <input 
                            type="text"
                            id="q" 
                            name="q" 
                            placeholder="Código o Nombre..." 
                            class="filter-input"
                            value="<?php echo htmlspecialchars($filtro_q); ?>"
                        >
                    </div>

                    <div class="filter-group">
                        <label for="stock">Estado de Stock (Solo visual)</label>
                        <select id="stock" name="stock" class="filter-input">
                            <option value="todos"   <?php if ($filtro_stock === 'todos')   echo 'selected'; ?>>Todos</option>
                            <option value="bajo"    <?php if ($filtro_stock === 'bajo')    echo 'selected'; ?>>Stock Bajo</option>
                            <option value="agotado" <?php if ($filtro_stock === 'agotado') echo 'selected'; ?>>Agotado</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-general w-150">Filtrar</button>

                    <?php 
                        $csv_url = "../reportes/exportar.php?tipo=inventario"
                                 . "&q=" . urlencode($filtro_q)
                                 . "&activos=" . ($solo_activos ? "1" : "0");
                    ?>
                    <a href="<?php echo $csv_url; ?>" class="btn-general w-150">
                        Exportar CSV
                    </a>
                </div>
            </form>
        </div>

        <div class="card">
            <p class="font-bold text-sm">
                Total de Suplementos: <?php echo $total_items; ?>
            </p>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr class="bg-green">
                            <th class="w-150">Código</th>
                            <th>Nombre del Suplemento</th>
                            <th class="w-120 text-right">Precio Venta</th>
                            <th class="w-100 text-center">Stock Actual</th>
                            <th class="w-150 text-right">Valor Inventario</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($total_items > 0): ?>
                            <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>

                                <td class="text-right">
                                    $<?php echo number_format($producto['precio'], 2); ?>
                                </td>

                                <td class="text-center">
                                    <?php echo number_format($producto['existencia'], 0); ?>

                                    <?php if ($producto['existencia'] <= 5 && $producto['existencia'] > 0): ?>
                                        <span class="font-bold text-danger">(Bajo)</span>
                                    <?php elseif ($producto['existencia'] == 0): ?>
                                        <span class="font-bold text-danger">(Agotado)</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-right">
                                    $<?php echo number_format($producto['valor_linea'], 2); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    No se encontraron suplementos con los filtros aplicados.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right font-bold bg-light-green">
                                VALOR TOTAL DEL INVENTARIO
                            </td>
                            <td class="text-right font-bold bg-light-green">
                                $<?php echo number_format($valor_total_inventario, 2); ?>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" class="text-right font-bold bg-light-gray">
                                TOTAL UNIDADES EN STOCK
                            </td>
                            <td class="text-right font-bold bg-light-gray">
                                <?php echo number_format($total_existencias, 0); ?>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div> </body>
</html>