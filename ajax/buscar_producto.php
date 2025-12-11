<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Verifica que venga el parámetro de búsqueda
if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo json_encode([]);
    exit;
}

// Sanitiza la entrada
$q = sanear($mysqli, $_GET['q']);

// Consulta a la base de datos
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
        s.codigo = '$q'           -- coincidencia exacta SKU
     OR sc.codigo_barras = '$q'   -- coincidencia exacta código alterno
     OR s.nombre LIKE '%$q%'      -- búsqueda por nombre
)
GROUP BY s.id
LIMIT 10
";

// Ejecuta la consulta y arma el array de resultados
$productos = [];
if ($res = $mysqli->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $productos[] = $row;
    }
}

// Devuelve JSON
header('Content-Type: application/json');
echo json_encode($productos);
