<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['q'])) {
    json_response(['error' => 'Falta parámetro q'], 400);
}

$q = sanear($mysqli, $_GET['q']);

/*
    ADAPTADO A MazSupledb:

    - Buscar por:
        * suplementos.codigo (código interno)
        * suplementos_codigos.codigo_barras (código alterno)
        * suplementos.nombre LIKE '%q%'

    - Obtener:
        * id
        * codigo
        * nombre
        * marca
        * precio_venta
        * stock desde existencias
*/

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
AND (
        s.codigo = '$q'           -- coincidencia exacta del SKU
     OR sc.codigo_barras = '$q'   -- coincidencia exacta del código alterno
     OR s.nombre LIKE '%$q%'      -- búsqueda LIKE por nombre
)
GROUP BY s.id
LIMIT 10
";

$res = $mysqli->query($sql);
$productos = [];

while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);
?>
