<?php
// ============================================================
// Descarga de catálogo completo para modo offline (IndexedDB)
// ============================================================

require_once '../config/db.php';
header('Content-Type: application/json');

// Solo productos activos y vendibles
$sql = "SELECT 
            s.id,
            s.codigo,
            s.nombre,
            s.marca,
            s.precio_venta,
            e.cantidad AS stock
        FROM suplementos s
        LEFT JOIN existencias e ON s.id = e.id_suplemento
        WHERE s.estatus = 1";

$result = $mysqli->query($sql);

$productos = [];

while ($row = $result->fetch_assoc()) {
    // Asegurar stock mínimo de 0 si viene NULL
    $row['stock'] = $row['stock'] ?? 0;
    $productos[] = $row;
}

echo json_encode($productos);
