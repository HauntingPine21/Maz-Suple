<?php
session_start();
require_once '../config/db.php';
require_once '../includes/seguridad_basica.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validaciones básicas
if (!isset($_SESSION['user'])) {
    echo json_encode(['status'=>'error','msg'=>'Sesión caducada']);
    exit;
}

if (!isset($data['items']) || !is_array($data['items']) || count($data['items']) === 0) {
    echo json_encode(['status'=>'error','msg'=>'Carrito vacío']);
    exit;
}

$id_usuario = $_SESSION['user']['id'];
$items = $data['items'];

$mysqli->begin_transaction();

try {
    // Insertar encabezado de venta
    $stmt = $mysqli->prepare("INSERT INTO ventas (id_usuario, subtotal, iva, total, fecha_hora) VALUES (?,0,0,0,NOW())");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $id_venta = $mysqli->insert_id;

    $subtotal_total = 0;

    // Preparar statements para detalle y stock
    $stmt_det = $mysqli->prepare("INSERT INTO detalle_ventas (id_venta, id_suplemento, cantidad, precio_unitario, importe) VALUES (?,?,?,?,?)");
    $stmt_stock = $mysqli->prepare("UPDATE existencias SET cantidad = cantidad - ? WHERE id_suplemento=? AND cantidad>=?");

    foreach ($items as $item) {
        // Asegúrate de que cada item tenga los campos correctos
        $id_suplemento = intval($item['id_suplemento'] ?? 0);
        $cantidad = intval($item['cantidad'] ?? 0);
        $precio = floatval($item['precio'] ?? $item['precio_venta'] ?? 0);
        $importe = $cantidad * $precio;

        if ($id_suplemento <= 0 || $cantidad <= 0 || $precio <= 0) {
            throw new Exception("Datos inválidos en uno de los productos");
        }

        // Insertar detalle
        $stmt_det->bind_param("iiidd", $id_venta, $id_suplemento, $cantidad, $precio, $importe);
        $stmt_det->execute();

        // Actualizar stock
        $stmt_stock->bind_param("iii", $cantidad, $id_suplemento, $cantidad);
        $stmt_stock->execute();

        if ($stmt_stock->affected_rows === 0) {
            throw new Exception("Stock insuficiente para: " . ($item['nombre'] ?? 'Producto'));
        }

        $subtotal_total += $importe;
    }

    $iva = round($subtotal_total * 0.16, 2);
    $total = round($subtotal_total + $iva, 2);

    $stmt_update = $mysqli->prepare("UPDATE ventas SET subtotal=?, iva=?, total=? WHERE id=?");
    $stmt_update->bind_param("dddi", $subtotal_total, $iva, $total, $id_venta);
    $stmt_update->execute();

    $mysqli->commit();

    // Limpiar carrito de sesión
    unset($_SESSION['carrito']);

    echo json_encode(['status'=>'ok','folio'=>$id_venta]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}
