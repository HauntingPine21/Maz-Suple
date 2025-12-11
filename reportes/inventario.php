<?php
// ============================================================
// RESPONSABLE: Vista de Reporte de Inventario con filtros.
// REQUERIMIENTO: "Reporte 3.1 Inventario actual... Filtros: q, solo activos"
// ============================================================
require_once '../config/db.php';
require_once '../includes/security_guardr.php';

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
        l.codigo,
        l.titulo AS nombre,
        l.precio_venta AS precio,
        e.cantidad AS existencia,
        l.estatus
    FROM libros l
    JOIN existencias e ON l.id = e.id_libro
    WHERE 1=1
";

if ($filtro_q !== '') {
    $sql .= " AND (l.codigo LIKE '%$filtro_q%' OR l.titulo LIKE '%$filtro_q%')";
}
if ($solo_activos) {
    $sql .= " AND l.estatus = 1";
}

$sql .= " ORDER BY l.titulo";

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

// ===============================
// CONTENIDO DEL REPORTE
// ===============================
$titulo_reporte = "REPORTE DE INVENTARIO ACTUAL";
ob_start();
?>

<div class="card filtros-print mb-20">
    <h3 class="mb-15">Filtros de Inventario</h3>

    <form method="GET">

        <div class="filters-container">

            <!-- Buscar -->
            <div class="filter-group-large">
                <label for="q">Buscar Producto</label>
                <input 
                    type="text"
                    id="q" 
                    name="q" 
                    placeholder="Código o Título..." 
                    class="filter-input"
                    value="<?php echo htmlspecialchars($filtro_q); ?>"
                >
            </div>

            <!-- Estado del stock -->
            <div class="filter-group">
                <label for="stock">Estado de Stock (Solo visual)</label>
                <select id="stock" name="stock" class="filter-input">
                    <option value="todos"   <?php if ($filtro_stock === 'todos')   echo 'selected'; ?>>Todos</option>
                    <option value="bajo"    <?php if ($filtro_stock === 'bajo')    echo 'selected'; ?>>Stock Bajo</option>
                    <option value="agotado" <?php if ($filtro_stock === 'agotado') echo 'selected'; ?>>Agotado</option>
                </select>
            </div>

            <!-- Botones -->
            <button type="submit" class="btn-general w-150">Filtrar</button>

            <button type="button" class="btn-general w-150 btn-print">
                Imprimir / PDF
            </button>

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

<!-- Tabla -->
<div class="card">
    <p class="font-bold text-sm">
        Total de Productos: <?php echo $total_items; ?>
    </p>

    <div class="table-responsive">
        <table>
            <thead>
                <tr class="bg-green">
                    <th class="w-150">Código</th>
                    <th>Título del Libro</th>
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
                            No se encontraron productos con los filtros aplicados.
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

<?php
$contenido_reporte = ob_get_clean();
require_once 'plantilla.php';
?>
