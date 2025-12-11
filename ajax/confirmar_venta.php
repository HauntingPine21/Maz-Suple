<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Validación básica
if (empty($_SESSION['carrito']) || !isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Carrito vacío o sesión caducada']);
    exit;
}

$id_usuario = $_SESSION['user']['id'];
$carrito = $_SESSION['carrito'];

// Recalcular totales
$subtotal = 0;
foreach ($carrito as $item) {
    $subtotal += $item['costo'] * $item['cantidad']; // costo enviado desde JS
}
$iva = $subtotal * 0.16; // Ajusta si tu IVA cambia
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

    // 2. Preparar statements de detalle y stock
    $sql_detalle = "INSERT INTO detalle_ventas (id_venta, id_suplemento, cantidad, precio_unitario, importe) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmt_det = $mysqli->prepare($sql_detalle);

    $sql_stock = "UPDATE existencias 
                  SET cantidad = cantidad - ? 
                  WHERE id_suplemento = ? AND cantidad >= ?";
    $stmt_stock = $mysqli->prepare($sql_stock);

    foreach ($carrito as $item) {
        $id_suplemento = $item['id_suplemento'];
        $cantidad = $item['cantidad'];
        $precio = $item['costo'];
        $importe = $precio * $cantidad;

        // Insertar detalle de venta
        $stmt_det->bind_param("iiidd", $id_venta, $id_suplemento, $cantidad, $precio, $importe);
        $stmt_det->execute();

        // Actualizar stock
        $stmt_stock->bind_param("iii", $cantidad, $id_suplemento, $cantidad);
        $stmt_stock->execute();

        if ($mysqli->affected_rows === 0) {
            throw new Exception("Stock insuficiente para: " . $item['nombre']);
        }
    }

    // Commit final
    $mysqli->commit();
    unset($_SESSION['carrito']);

    echo json_encode(['status' => 'ok', 'folio' => $id_venta]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
