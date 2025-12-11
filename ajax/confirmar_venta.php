<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (empty($_SESSION['carrito']) || !isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Carrito vacío o sesión caducada']);
    exit;
}

$id_usuario = $_SESSION['user']['id'];
$carrito = $_SESSION['carrito'];

// Backend recalcula todo
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['precio'] * $item['cantidad'];
}

$iva = $subtotal * 0.16;
$total = $subtotal + $iva;

$mysqli->begin_transaction();

try {
    // 1. Insertar encabezado de venta
    $sql_venta = "INSERT INTO ventas (id_usuario, subtotal, iva, total, fecha_hora)
                  VALUES (?, ?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql_venta);
    $stmt->bind_param("iddd", $id_usuario, $subtotal, $iva, $total);
    $stmt->execute();
    $id_venta = $mysqli->insert_id;

    // 2. Preparar sentencias
    $sql_det = "INSERT INTO ventas_detalle (id_venta, id_producto, cantidad, precio, importe)
                VALUES (?, ?, ?, ?, ?)";

    $sql_stock = "UPDATE inventario
                  SET cantidad = cantidad - ?
                  WHERE id_producto = ? AND cantidad >= ?";

    $stmt_det = $mysqli->prepare($sql_det);
    $stmt_stk = $mysqli->prepare($sql_stock);

    foreach ($carrito as $p) {

        $importe = $p['precio'] * $p['cantidad'];

        // Insertar detalle
        $stmt_det->bind_param(
            "iiidd",
            $id_venta,
            $p['id'],        // id_producto
            $p['cantidad'],
            $p['precio'],
            $importe
        );
        $stmt_det->execute();

        // Descontar stock (validación cantidad >= ?)
        $stmt_stk->bind_param(
            "iii",
            $p['cantidad'],  // -X
            $p['id'],        // id_producto
            $p['cantidad']   // validar suficiente stock
        );
        $stmt_stk->execute();

        if ($mysqli->affected_rows === 0) {
            throw new Exception("Stock insuficiente para: " . $p['titulo']);
        }
    }

    // 3. Finalizar
    $mysqli->commit();
    unset($_SESSION['carrito']);

    echo json_encode(['status' => 'ok', 'folio' => $id_venta]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
