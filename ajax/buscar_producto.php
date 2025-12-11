<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['q'])) {
    json_response(['error' => 'Falta parámetro q'], 400);
}

$q = sanear($mysqli, $_GET['q']);

// Buscar por código exacto o por nombre (LIKE)
$sql = "SELECT s.id, s.codigo, s.nombre, s.precio_venta, e.cantidad as stock 
        FROM suplementos s
        LEFT JOIN existencias e ON s.id = e.id_suplemento
        WHERE s.estatus = 1 AND (s.codigo = '$q' OR s.nombre LIKE '%$q%')
        LIMIT 10";

$res = $mysqli->query($sql);
$productos = [];

while ($row = $res->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);
?>
