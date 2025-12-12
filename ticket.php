<?php
// ============================================================
// TICKET 80x40mm | Ventas y Devoluciones
// ============================================================

require_once 'config/db.php';
require_once 'includes/functions.php';
session_start();

// Recibir folio y tipo
$folio = filter_input(INPUT_GET, 'folio', FILTER_SANITIZE_STRING);
$tipo  = $_GET['tipo'] ?? 'venta';
if (!in_array($tipo, ['venta','devolucion'])) $tipo = 'venta';

// Datos del negocio
$negocio = $mysqli->query("SELECT * FROM configuracion WHERE id = 1")->fetch_assoc();
$titulo_ticket = $tipo === 'devolucion' ? "COMPROBANTE DE DEVOLUCIÓN" : "TICKET DE VENTA";

// Obtener encabezado y detalles según tipo
if ($tipo === 'devolucion') {
    $encabezado = $mysqli->query("
        SELECT d.*, u.username AS cajero, d.id_venta AS folio_original
        FROM devoluciones d
        JOIN usuarios u ON d.id_usuario = u.id
        WHERE d.id = '".$mysqli->real_escape_string($folio)."'
    ")->fetch_assoc();
    if (!$encabezado) die("Devolución no encontrada");

    $detalles = $mysqli->query("
        SELECT dd.cantidad, dd.monto_reembolsado AS importe, s.nombre AS titulo, dd.monto_reembolsado/dd.cantidad AS precio_unitario
        FROM detalle_devoluciones dd
        JOIN suplementos s ON dd.id_suplemento = s.id
        WHERE dd.id_devolucion = '".$mysqli->real_escape_string($folio)."'
    ");

} else { // Venta normal
    $encabezado = $mysqli->query("
        SELECT v.*, u.username AS cajero
        FROM ventas v
        JOIN usuarios u ON v.id_usuario = u.id
        WHERE v.id = '".$mysqli->real_escape_string($folio)."'
    ")->fetch_assoc();
    if (!$encabezado) die("Venta no encontrada");

    $detalles = $mysqli->query("
        SELECT dv.*, s.nombre AS titulo, dv.precio_unitario, dv.importe
        FROM detalle_ventas dv
        JOIN suplementos s ON dv.id_suplemento = s.id
        WHERE dv.id_venta = '".$mysqli->real_escape_string($folio)."'
    ");
}

// Función para formatear líneas de productos
function imprimir_linea($nombre, $cantidad, $precio, $importe, $anchoTotal = 48) {
    $anchoPrecio = 11;
    $anchoCantidad = 5;
    $anchoNombre = $anchoTotal - $anchoPrecio - $anchoCantidad - 3;

    $cantidadStr = str_pad($cantidad, $anchoCantidad, " ", STR_PAD_LEFT);
    $importeStr  = str_pad('$'.number_format($importe,2), $anchoPrecio, " ", STR_PAD_LEFT);

    $lineasNombre = wordwrap($nombre, $anchoNombre, "\n", true);
    $lineas = explode("\n", $lineasNombre);

    $lineaPrincipal = str_pad($lineas[0], $anchoNombre) . $cantidadStr . $importeStr;

    if (count($lineas) > 1) {
        for ($i = 1; $i < count($lineas); $i++) {
            $lineaPrincipal .= "\n" . str_pad($lineas[$i], $anchoTotal);
        }
    }
    return $lineaPrincipal;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo $titulo_ticket; ?> #<?php echo htmlspecialchars($folio); ?></title>
<link rel="stylesheet" href="css/ticket.css">
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>
</head>
<body>
<div class="ticket" data-folio="<?php echo htmlspecialchars($folio); ?>">
  <div class="ticket-logo-container">
    <img src="img.php?tipo=logo" alt="Logo" class="ticket-logo">
    <h1 class="ticket-title"><?php echo htmlspecialchars($negocio['razon_social']); ?></h1>
    <p class="ticket-info">
      <?php echo htmlspecialchars($negocio['domicilio']); ?><br>
      Tel: <?php echo htmlspecialchars($negocio['telefono'] ?? ''); ?>
    </p>
    <div class="dashed-line"></div>
  </div>

  <div>
    <p class="ticket-header-text" style="font-weight:bold;"><?php echo $titulo_ticket; ?>: <?php echo htmlspecialchars($encabezado['id']); ?></p>
    <?php if ($tipo === 'devolucion'): ?>
      <p class="ticket-header-text">SOBRE VENTA ORIGINAL: #<?php echo htmlspecialchars($encabezado['folio_original']); ?></p>
    <?php endif; ?>
    <p class="ticket-header-text">FECHA: <?php echo date('d/m/Y H:i', strtotime($encabezado['fecha_hora'])); ?></p>
    <p class="ticket-subheader-text">CAJERO: <?php echo htmlspecialchars($encabezado['cajero']); ?></p>
    <div class="dashed-line"></div>
  </div>

  <div class="detalle-productos">
    <div class="ticket-table-header">
      <span>PRODUCTO</span>
      <span>CANT/TOTAL</span>
    </div>
    <pre>
<?php
while ($item = $detalles->fetch_assoc()) {
    echo imprimir_linea(
        htmlspecialchars($item['titulo']),
        $item['cantidad'],
        $item['precio_unitario'],
        $item['importe']
    )."\n";
}
?>
    </pre>
  </div>

  <div class="dashed-line"></div>

  <div class="text-right">
  <?php if ($tipo === 'venta'): ?>
    <p class="ticket-header-text">SUBTOTAL: $<?php echo number_format($encabezado['subtotal'],2); ?></p>
    <p class="ticket-header-text">IVA (16%): $<?php echo number_format($encabezado['iva'],2); ?></p>
    <h2 class="ticket-total">TOTAL: $<?php echo number_format($encabezado['total'],2); ?></h2>
  <?php else: ?>
    <h2 class="ticket-total">TOTAL REEMBOLSADO: $<?php echo number_format($encabezado['total_reembolsado'],2); ?></h2>
  <?php endif; ?>
  </div>

  <div class="ticket-center">
    <div class="dashed-line"></div>
    <p class="ticket-header-text"><?php echo htmlspecialchars($negocio['mensaje_ticket'] ?? '¡Gracias por su compra!'); ?></p>
    <p style="margin:2px 0 0 0;">(Powered by Sistema MDL)</p>
    <div style="margin-top:10px;">
      <svg id="codigoBarrasTicket"></svg>
    </div>
  </div>

  <div class="no-print ticket-center" style="margin-top:20px;">
    <button class="btn btn-close-window">Cerrar Ticket</button>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var folioTicket = document.querySelector('.ticket').dataset.folio;
    JsBarcode("#codigoBarrasTicket", folioTicket, {
        format: "CODE128",
        displayValue: true,
        height: 40,
        width: 1
    });

    document.querySelector('.btn-close-window').addEventListener('click', function() {
        window.close();
    });
});
</script>

</body>
</html>
