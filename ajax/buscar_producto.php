<?php
header('Content-Type: application/json');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'MazSupledb';
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $db, $port);
$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die(json_encode([]));
}

if (!isset($_GET['q'])) {
    echo json_encode([]);
    exit;
}

$q = $mysqli->real_escape_string($_GET['q']);

// Buscamos por código interno, código de barras o nombre (LIKE para coincidencias parciales)
$sql = "SELECT s.id, s.codigo, s.nombre, s.precio_venta, IFNULL(e.cantidad, 0) AS stock
        FROM suplementos s
        LEFT JOIN existencias e ON s.id = e.id_suplemento
        LEFT JOIN suplementos_codigos sc ON s.id = sc.id_suplemento
        WHERE s.estatus = 1 
          AND (
              s.codigo = '$q' 
              OR sc.codigo_barras = '$q' 
              OR s.nombre LIKE '%$q%'
          )
        LIMIT 10";

$result = $mysqli->query($sql);

$productos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
}

echo json_encode($productos);
$mysqli->close();
?>
