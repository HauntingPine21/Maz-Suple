<?php
$titulo_reporte = "REPORTE DE VENTAS POR RANGO";
ob_start();
?>

<div class="card filtros-print mb-20">
    <h3 class="mb-15">Filtros de Ventas por Período</h3>
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
                    value="<?= substr($fecha_ini, 0, 10) ?>" 
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
                    value="<?= substr($fecha_fin, 0, 10) ?>" 
                    class="filter-input">
            </div>

            <!-- Cajero dinámico -->
            <div class="filter-group-large">
                <label for="cajero">Cajero (Opcional)</label>
                <select id="cajero" name="cajero" class="filter-input">
                    <option value="0">--- Todos los Cajeros ---</option>

                    <?php foreach ($cajeros as $c): ?>
                        <option 
                            value="<?= $c['id'] ?>"
                            <?= ($filtro_cajero == $c['id']) ? "selected" : "" ?>>
                            <?= htmlspecialchars($c['nombre_completo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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


<!-- TABLA DINÁMICA REAL -->
<div class="card">
    <p class="font-bold text-sm">
        Total de Tickets Encontrados: <?= $num_tickets ?>
    </p>
    
    <table>
        <thead>
            <tr class="bg-green">
                <th class="w-100">Folio</th>
                <th class="w-150">Fecha/Hora</th>
                <th>Cajero</th>
                <th class="w-120 text-right">Subtotal</th>
                <th class="w-100 text-right">IVA</th>
                <th class="w-120 text-right">Total Venta</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach ($ventas as $v): ?>
            <tr>
                <td><?= $v['folio'] ?></td>
                <td><?= date("d/m/Y H:i:s", strtotime($v['fecha_hora'])) ?></td>
                <td><?= htmlspecialchars($v['cajero']) ?></td>

                <td class="text-right">$<?= number_format($v['subtotal'], 2) ?></td>
                <td class="text-right">$<?= number_format($v['iva'], 2) ?></td>
                <td class="text-right font-bold">$<?= number_format($v['total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>

            <?php if ($num_tickets === 0): ?>
            <tr>
                <td colspan="6" class="text-center">
                    No se encontraron ventas en este período.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="5" class="text-right font-bold bg-light-green">
                    TOTAL FACTURADO
                </td>
                <td class="text-right font-bold bg-light-green">
                    $<?= number_format($suma_total_facturado, 2) ?>
                </td>
            </tr>

            <tr>
                <td colspan="5" class="text-right font-bold bg-light-gray">
                    NÚMERO DE TICKETS
                </td>
                <td class="text-right font-bold bg-light-gray">
                    <?= $num_tickets ?>
                </td>
            </tr>

            <tr>
                <td colspan="5" class="text-right font-bold bg-gray">
                    TICKET PROMEDIO
                </td>
                <td class="text-right font-bold bg-gray">
                    $<?= number_format($ticket_promedio, 2) ?>
                </td>
            </tr>
        </tfoot>

    </table>
</div>

<?php
$contenido_reporte = ob_get_clean();
require_once 'plantilla.php';
?>
