<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
session_start();

// Recibir folio y tipo
$folio = filter_input(INPUT_GET, 'folio', FILTER_SANITIZE_STRING);
$tipo  = $_GET['tipo'] ?? 'venta';
if (!in_array($tipo, ['venta','devolucion'])) $tipo = 'venta';

// Datos negocio
$negocio = $mysqli->query("
    SELECT * FROM configuracion WHERE id = 1
")->fetch_assoc();

$titulo_ticket = $tipo === 'devolucion' ? "COMPROBANTE DE DEVOLUCIÓN" : "TICKET DE VENTA";

// Obtener encabezado y detalles
if ($tipo === 'devolucion') {
    $encabezado = $mysqli->query("
        SELECT d.*, u.username AS cajero, d.id_venta AS folio_original
        FROM devoluciones d
        JOIN usuarios u ON d.id_usuario = u.id
        WHERE d.id = '".$mysqli->real_escape_string($folio)."'
    ")->fetch_assoc();
    if (!$encabezado) die("Devolución no encontrada");

    $detalles = $mysqli->query("
        SELECT dd.cantidad, dd.monto_reembolsado AS importe, s.nombre AS titulo,
               dd.monto_reembolsado/dd.cantidad AS precio_unitario
        FROM detalle_devoluciones dd
        JOIN suplementos s ON dd.id_suplemento = s.id
        WHERE dd.id_devolucion = '".$mysqli->real_escape_string($folio)."'
    ");
} else {
    $encabezado = $mysqli->query("
        SELECT v.*, u.username AS cajero
        FROM ventas v
        JOIN usuarios u ON v.id_usuario = u.id
        WHERE v.id = '".$mysqli->real_escape_string($folio)."'
    ")->fetch_assoc();
    if (!$encabezado) die("Venta no encontrada");

    $detalles = $mysqli->query("
        SELECT dv.*, s.nombre AS titulo
        FROM detalle_ventas dv
        JOIN suplementos s ON dv.id_suplemento = s.id
        WHERE dv.id_venta = '".$mysqli->real_escape_string($folio)."'
    ");
}

// Formato línea
function imprimir_linea($nombre, $cantidad, $precio, $importe, $anchoTotal = 40) {
    $anchoPrecio = 9;
    $anchoCantidad = 4;
    $anchoNombre = $anchoTotal - $anchoPrecio - $anchoCantidad - 3;

    $cantidadStr = str_pad($cantidad, $anchoCantidad, " ", STR_PAD_LEFT);
    $importeStr  = str_pad(number_format($importe,2), $anchoPrecio, " ", STR_PAD_LEFT);

    $nombreL = wordwrap($nombre, $anchoNombre, "\n", true);
    $lineas = explode("\n", $nombreL);

    $out = str_pad($lineas[0], $anchoNombre) . " " . $cantidadStr . " " . $importeStr;
    for ($i=1; $i<count($lineas); $i++) {
        $out .= "\n" . str_pad($lineas[$i], $anchoTotal);
    }
    return $out;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">

<title><?php echo $titulo_ticket; ?> #<?php echo $folio; ?></title>

<link rel="stylesheet" href="css/ticket.css">

<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/JsBarcode.all.min.js"></script>

</head>
<body>

<div class="ticket" data-folio="<?php echo $folio; ?>">

    <div class="center">
        <img src="img.php?tipo=logo" class="logo">
        <div class="title"><?php echo $negocio['razon_social']; ?></div>
        <div class="info"><?php echo $negocio['domicilio']; ?><br>Tel: <?php echo $negocio['telefono']; ?></div>
        <div class="line"></div>
    </div>

    <div class="content">
        <strong><?php echo $titulo_ticket; ?> #<?php echo $encabezado['id']; ?></strong><br>
        <?php if ($tipo === "devolucion"): ?>
            <small>Sobre venta: #<?php echo $encabezado['folio_original']; ?></small><br>
        <?php endif; ?>
        Fecha: <?php echo date('d/m/Y H:i', strtotime($encabezado['fecha_hora'])); ?><br>
        Cajero: <?php echo $encabezado['cajero']; ?>
        <div class="line"></div>
    </div>

    <pre class="productos">
<?php
while ($i = $detalles->fetch_assoc()) {
    echo imprimir_linea($i['titulo'], $i['cantidad'], $i['precio_unitario'], $i['importe']) . "\n";
}
?>
    </pre>

    <div class="line"></div>

    <div class="totales">
    <?php if ($tipo === 'venta'): ?>
        SUBTOTAL: $<?php echo number_format($encabezado['subtotal'],2); ?><br>
        IVA: $<?php echo number_format($encabezado['iva'],2); ?><br>
        <strong>TOTAL: $<?php echo number_format($encabezado['total'],2); ?></strong>
    <?php else: ?>
        <strong>REEMBOLSADO: $<?php echo number_format($encabezado['total_reembolsado'],2); ?></strong>
    <?php endif; ?>
    </div>

    <div class="center">
        <div class="line"></div>
        <?php echo $negocio['mensaje_ticket']; ?><br>
        <small>(Maz-Suple)</small>

        <svg id="codigoBarrasTicket"></svg>
    </div>

    <div class="no-print center" style="margin-top:15px;">
        <button onclick="window.close()">Cerrar</button>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    JsBarcode("#codigoBarrasTicket",
        document.querySelector(".ticket").dataset.folio,
        { format:"CODE128", width:1, height:40, displayValue:true }
    );
});
</script>

</body>
</html>
