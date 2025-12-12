<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$id_venta = intval($input['id_venta']);
$items_dev = $input['items']; // {id_suplemento, cantidad}
$motivo = $input['motivo'] ?? 'Devolución cliente';
$id_usuario = $_SESSION['user']['id'];

$mysqli->begin_transaction();

try {
    if (empty($items_dev)) {
        throw new Exception("No seleccionaste productos para devolver.");
    }

    $total_reembolso = 0;

    // Insertar encabezado de devolución
    $sql_dev = "INSERT INTO devoluciones (id_venta, id_usuario, total_reembolsado, motivo, fecha_hora)
                VALUES (?, ?, 0, ?, NOW())";
    $stmt = $mysqli->prepare($sql_dev);
    $stmt->bind_param("iis", $id_venta, $id_usuario, $motivo);
    $stmt->execute();
    $id_devolucion = $mysqli->insert_id;

    // Preparar consultas de detalle y stock
    $sql_det = "INSERT INTO detalle_devoluciones (id_devolucion, id_suplemento, cantidad, monto_reembolsado)
                VALUES (?, ?, ?, ?)";
    $sql_stock = "UPDATE existencias SET cantidad = cantidad + ? WHERE id_suplemento = ?";

    $stmt_det = $mysqli->prepare($sql_det);
    $stmt_stk = $mysqli->prepare($sql_stock);

    foreach ($items_dev as $item) {
        $id_supl = intval($item['id_suplemento']);
        $cant_dev = intval($item['cantidad']);

        // Validar venta original
        $q = "SELECT cantidad, precio_unitario
              FROM detalle_ventas
              WHERE id_venta = ? AND id_suplemento = ?";
        $stmt_p = $mysqli->prepare($q);
        $stmt_p->bind_param("ii", $id_venta, $id_supl);
        $stmt_p->execute();
        $stmt_p->bind_result($cantidad_vendida, $precio_unitario);
        $encontrado = $stmt_p->fetch();
        $stmt_p->close();

        if (!$encontrado) {
            throw new Exception("El suplemento ID $id_supl no está en esa venta.");
        }

        if ($cant_dev > $cantidad_vendida) {
            throw new Exception("Intentas devolver $cant_dev pero solo se vendieron $cantidad_vendida.");
        }

        // Calcular reembolso
        $monto_linea = $cant_dev * floatval($precio_unitario);
        $total_reembolso += $monto_linea;

        // Insertar detalle
        $stmt_det->bind_param("iiid", $id_devolucion, $id_supl, $cant_dev, $monto_linea);
        $stmt_det->execute();

        // Restaurar stock
        $stmt_stk->bind_param("ii", $cant_dev, $id_supl);
        $stmt_stk->execute();
    }

    // Actualizar total en encabezado
    $stmt_total = $mysqli->prepare("UPDATE devoluciones SET total_reembolsado = ? WHERE id = ?");
    $stmt_total->bind_param("di", $total_reembolso, $id_devolucion);
    $stmt_total->execute();

    $mysqli->commit();
    echo json_encode(['status' => 'ok', 'folio' => $id_devolucion]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
