<?php
// ajax/carrito_clear.php
// Limpia por completo el carrito en sesión
session_start();

// Si quieres ser estricto: limpiar solo el carrito
if (isset($_SESSION['carrito'])) {
    unset($_SESSION['carrito']);
}

// Opcional: limpiar totales o datos temporales de venta futura
if (isset($_SESSION['venta_temp'])) {
    unset($_SESSION['venta_temp']);
}

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'msg' => 'Carrito vaciado correctamente.'
]);
