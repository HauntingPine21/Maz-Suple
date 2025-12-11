<?php
session_start();

header('Content-Type: application/json');

// Validación estricta
if (!isset($_POST['id'])) {
    echo json_encode([
        'status' => 'error',
        'msg' => 'ID no recibido'
    ]);
    exit;
}

$id = intval($_POST['id']); // Garantiza que no entren valores raros

// Validación del carrito
if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Eliminar solo si existe
if (isset($_SESSION['carrito'][$id])) {
    unset($_SESSION['carrito'][$id]);
}

// Regresar el carrito actualizado
echo json_encode([
    'status' => 'ok',
    'carrito' => $_SESSION['carrito']
]);
?>
