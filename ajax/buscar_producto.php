<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['q'])) { echo json_encode([]); exit; }

$q = sanear($mysqli, $_GET['q']);

$sql = "
SELECT 
    s.id,
    s.codigo AS sku,
    s.nombre,
    s.marca,
    s.precio_venta,
    COALESCE(e.cantidad, 0) AS stock
FROM suplementos s
LEFT JOIN existencias e ON s.id = e.id_suplemento
LEFT JOIN suplementos_codigos sc ON s.id = sc.id_suplemento
WHERE s.estatus = 1
AND (s.codigo='$q' OR sc.codigo_barras='$q' OR s.nombre LIKE '%$q%')
GROUP BY s.id
LIMIT 10
";

$res = $mysqli->query($sql);
$productos = [];
while ($row = $res->fetch_assoc()) $productos[] = $row;

echo json_encode($productos);
