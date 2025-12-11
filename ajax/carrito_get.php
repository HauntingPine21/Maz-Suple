<?php
// ajax/carrito_get.php
// Devuelve el estado actual del carrito

session_start();

// Si no existe, inicialízalo como array vacío
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Sanitizar claves rotas o valores corruptos (defensa básica)
$carrito = [];

foreach ($_SESSION['carrito'] as $id => $item) {
    if (!is_array($item)) continue;
    if (!isset($item['id'], $item['nombre'], $item['precio_venta'], $item['cantidad'], $item['importe'])) {
        continue; // Si el item está incompleto, lo saltamos
    }
    $carrito[$id] = $item;
}

header('Content-Type: application/json');
echo json_encode(['carrito' => $carrito]);
