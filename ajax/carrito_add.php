<?php
// Escaneo → agrega/incrementa en carrito (sesión)
session_start();

if (
    !isset($_POST['id']) ||
    !isset($_POST['nombre']) ||
    !isset($_POST['precio_venta'])
) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$nombre = $_POST['nombre'];
$precio = floatval($_POST['precio_venta']);
$cantidad = 1;

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Incrementar si ya existe
if (isset($_SESSION['carrito'][$id])) {
    $_SESSION['carrito'][$id]['cantidad']++;
    $_SESSION['carrito'][$id]['importe'] =
        $_SESSION['carrito'][$id]['cantidad'] * $precio;

} else {
    // Nuevo producto
    $_SESSION['carrito'][$id] = [
        'id' => $id,
        'nombre' => $nombre,
        'precio_venta' => $precio,
        'cantidad' => 1,
        'importe' => $precio
    ];
}

echo json_encode([
    'status' => 'ok',
    'carrito' => $_SESSION['carrito']
]);
?>
