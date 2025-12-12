<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

// 🔐 Validación de sesión
if (!isset($_SESSION['user'])) {
    echo json_encode(['status'=>'error','msg'=>'No autorizado']);
    exit;
}

// 📥 Leer el JSON recibido
$input = json_decode(file_get_contents('php://input'), true);

// 📌 Validaciones básicas
if (!$input || !isset($input['proveedor']) || !isset($input['items'])) {
    echo json_encode(['status'=>'error','msg'=>'Datos de compra incompletos']);
    exit;
}

$id_usuario   = intval($_SESSION['user']['id']);
$id_proveedor = intval($input['proveedor']);
$items        = $input['items'];

// 🔍 Validar items (evita "Datos inválidos en uno de los productos")
foreach ($items as $item) {
    if (
        empty($item['id_suplemento']) ||
        empty($item['cantidad']) ||
        empty($item['costo'])
    ) {
        echo json_encode(['status'=>'error','msg'=>'Datos inválidos en uno de los productos']);
        exit;
    }
}

// 💰 Calcular total de compra
$total_compra = 0;
foreach ($items as $item) {
    $total_compra += floatval($item['cantidad']) * floatval($item['costo']);
}

$mysqli->begin_transaction();

try {

    // 🧾 Insertar encabezado de compra
    $sql_compra = "INSERT INTO compras (id_proveedor, id_usuario, total_compra, fecha_hora)
                   VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql_compra);
    $stmt->bind_param("iid", $id_proveedor, $id_usuario, $total_compra);
    $stmt->execute();
    $id_compra = $mysqli->insert_id;

    // 🧩 Detalle compra
    $sql_det = "INSERT INTO detalle_compras (id_compra, id_suplemento, cantidad, costo_unitario)
                VALUES (?, ?, ?, ?)";

    // 📦 Actualizar existencias
    $sql_upd = "INSERT INTO existencias (id_suplemento, cantidad)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE cantidad = cantidad + VALUES(cantidad)";

    $stmt_det = $mysqli->prepare($sql_det);
    $stmt_upd = $mysqli->prepare($sql_upd);

    foreach ($items as $prod) {
        $id_supp   = intval($prod['id_suplemento']);
        $cantidad  = intval($prod['cantidad']);
        $costo     = floatval($prod['costo']);

        // Insert detalle
        $stmt_det->bind_param("iiid", $id_compra, $id_supp, $cantidad, $costo);
        $stmt_det->execute();

        // Actualizar existencias
        $stmt_upd->bind_param("ii", $id_supp, $cantidad);
        $stmt_upd->execute();
    }

    $mysqli->commit();

    echo json_encode([
        'status' => 'ok',
        'folio'  => $id_compra
    ]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}
