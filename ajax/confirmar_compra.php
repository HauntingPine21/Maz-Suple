<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['status'=>'error','msg'=>'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['proveedor']) || empty($input['items'])) {
    echo json_encode(['status'=>'error','msg'=>'Datos de compra incompletos']);
    exit;
}

$id_usuario = $_SESSION['user']['id'];
$id_proveedor = intval($input['proveedor']);
$items = $input['items'];

$total_compra = 0;
foreach ($items as $item) {
    $total_compra += $item['cantidad'] * $item['costo'];
}

$mysqli->begin_transaction();
try {
    $sql_compra = "INSERT INTO compras (id_proveedor, id_usuario, total_compra, fecha_hora) VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql_compra);
    $stmt->bind_param("iid", $id_proveedor, $id_usuario, $total_compra);
    $stmt->execute();
    $id_compra = $mysqli->insert_id;

    $sql_det = "INSERT INTO detalle_compras (id_compra, id_suplemento, cantidad, costo_unitario) VALUES (?, ?, ?, ?)";
    $sql_upd = "INSERT INTO existencias (id_suplemento, cantidad) 
                VALUES (?, ?) ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";

    $stmt_det = $mysqli->prepare($sql_det);
    $stmt_upd = $mysqli->prepare($sql_upd);

    foreach ($items as $prod) {
        $stmt_det->bind_param("iiid", $id_compra, $prod['id_suplemento'], $prod['cantidad'], $prod['costo']);
        $stmt_det->execute();

        $stmt_upd->bind_param("ii", $prod['id_suplemento'], $prod['cantidad']);
        $stmt_upd->execute();
    }

    $mysqli->commit();
    echo json_encode(['status'=>'ok','folio'=>$id_compra]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}
