<?php
$titulo_reporte = "REPORTE DETALLADO DE VENTAS DE SUPLEMENTOS";

// Asegúrate de recibir las fechas desde GET
$fecha_ini = $_GET['inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fin'] ?? date('Y-m-t');
$producto_filter = $_GET['producto'] ?? '';

require_once '../config/db.php';

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

ob_start();
?>

<div class="card filtros-print mb-20">
    <h3 class="mb-15">Filtros de Detalle</h3>
    <form action="" method="GET">
        <div class="filters-container">
            
            <!-- Fecha Inicio -->
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
            
            <!-- Fecha Fin -->
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

            <!-- Filtro por producto opcional -->
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
            
            <button type="submit" class="btn w-150">
                Generar Reporte
            </button>

            <button type="button" class="btn-secondary w-150" onclick="window.print()">
                Imprimir / PDF
            </button>
        </div>
    </form>
</div>

<!-- TABLA REAL DE DETALLE -->
<div class="card">
    <p class="font-bold text-sm">
        Mostrando resultados del <?= date("d/m/Y", strtotime($fecha_ini)) ?> 
        al <?= date("d/m/Y", strtotime($fecha_fin)) ?>
    </p>
    
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

<?php
$contenido_reporte = ob_get_clean();
require_once 'plantilla.php';
?>
