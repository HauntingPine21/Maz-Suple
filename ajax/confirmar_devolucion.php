<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id_venta = intval($input['id_venta']);
$items_dev = $input['items']; // {id_producto, cantidad}
$motivo = $input['motivo'] ?? 'Devolución cliente';
$id_usuario = $_SESSION['user']['id'];

$mysqli->begin_transaction();

try {
    $total_reembolso = 0;

    // Insertar encabezado
    $sql_dev = "INSERT INTO devoluciones (id_venta, id_usuario, total_reembolsado, motivo, fecha_hora)
                VALUES (?, ?, 0, ?, NOW())";
    $stmt = $mysqli->prepare($sql_dev);
    $stmt->bind_param("iis", $id_venta, $id_usuario, $motivo);
    $stmt->execute();
    $id_devolucion = $mysqli->insert_id;

    // Preparar consultas
    $sql_det = "INSERT INTO devoluciones_detalle (id_devolucion, id_producto, cantidad, monto_reembolsado)
                VALUES (?, ?, ?, ?)";

    $sql_stock = "UPDATE inventario SET cantidad = cantidad + ? WHERE id_producto = ?";

    $stmt_det = $mysqli->prepare($sql_det);
    $stmt_stk = $mysqli->prepare($sql_stock);

    foreach ($items_dev as $item) {

        // VALIDAR venta original
        $q = "SELECT cantidad, precio
              FROM ventas_detalle
              WHERE id_venta = ? AND id_producto = ?";

        $stmt_p = $mysqli->prepare($q);
        $stmt_p->bind_param("ii", $id_venta, $item['id_producto']);
        $stmt_p->execute();
        $stmt_p->bind_result($cantidad_vendida, $precio_vendido);
        $result = $stmt_p->fetch();
        $stmt_p->close();

        if (!$result) {
            throw new Exception("El producto " . $item['id_producto'] . " no está en esa venta.");
        }

        if ($item['cantidad'] > $cantidad_vendida) {
            throw new Exception("Intentas devolver {$item['cantidad']} pero solo se vendieron {$cantidad_vendida}.");
        }

        // Calcular reembolso
        $monto_linea = $item['cantidad'] * floatval($precio_vendido);
        $total_reembolso += $monto_linea;

        // Registrar detalle
        $stmt_det->bind_param(
            "iiid",
            $id_devolucion,
            $item['id_producto'],
            $item['cantidad'],
            $monto_linea
        );
        $stmt_det->execute();

        // Regresar stock
        $stmt_stk->bind_param("ii", $item['cantidad'], $item['id_producto']);
        $stmt_stk->execute();
    }

    if (empty($items_dev)) {
        throw new Exception("No seleccionaste productos para devolver.");
    }

    $mysqli->query("UPDATE devoluciones SET total_reembolsado = $total_reembolso WHERE id = $id_devolucion");

    $mysqli->commit();
    echo json_encode(['status' => 'ok', 'folio' => $id_devolucion]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
