<?php
session_start();

// Validar datos recibidos
if (
    !isset($_POST['id']) ||
    !isset($_POST['titulo']) ||  // ahora se llama 'titulo' para coincidir con JS
    !isset($_POST['precio_venta']) ||
    !isset($_POST['codigo'])
) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos incompletos']);
    exit;
}

$id = intval($_POST['id']);
$titulo = trim($_POST['titulo']);
$precio = floatval($_POST['precio_venta']);
$codigo = trim($_POST['codigo']);

if ($precio <= 0) {
    echo json_encode(['status' => 'error', 'msg' => 'Precio inválido']);
    exit;
}

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
        'titulo' => $titulo,
        'precio' => $precio,
        'cantidad' => 1,
        'importe' => $precio,
        'codigo' => $codigo
    ];
}

// Devolver carrito actualizado
echo json_encode([
    'status' => 'ok',
    'carrito' => $_SESSION['carrito']
]);
?>
