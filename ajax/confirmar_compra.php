<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Validar usuario logueado
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'msg' => 'No autorizado']);
    exit;
}

// Recibir JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['proveedor']) || empty($input['items'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Datos de compra incompletos']);
    exit;
}

$id_usuario   = $_SESSION['user']['id'];
$id_proveedor = intval($input['proveedor']);
$items        = $input['items']; // Array de {id_suplemento, cantidad, costo}

$total_compra = 0;
foreach ($items as $item) {
    if (!isset($item['id_suplemento'], $item['cantidad'], $item['costo'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Item incompleto en la compra']);
        exit;
    }
    $total_compra += $item['cantidad'] * $item['costo'];
}

$mysqli->begin_transaction();

try {

    // 1. Insertar compra
    $sql_compra = "INSERT INTO compras (id_proveedor, id_usuario, total_compra, fecha_hora)
                   VALUES (?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql_compra);
    $stmt->bind_param("iid", $id_proveedor, $id_usuario, $total_compra);
    $stmt->execute();
    $id_compra = $mysqli->insert_id;

    // 2. Insertar detalle y actualizar existencias
    $sql_det = "INSERT INTO detalle_compras (id_compra, id_suplemento, cantidad, costo_unitario)
                VALUES (?, ?, ?, ?)";
    $stmt_det = $mysqli->prepare($sql_det);

    // Stock
    $sql_upd = "UPDATE existencias SET cantidad = cantidad + ? WHERE id_suplemento = ?";
    $stmt_upd = $mysqli->prepare($sql_upd);

    // Crear existencias si no existen
    $sql_check_stock = "SELECT id FROM existencias WHERE id_suplemento = ?";
    $stmt_check = $mysqli->prepare($sql_check_stock);

    $sql_insert_stock = "INSERT INTO existencias (id_suplemento, cantidad) VALUES (?, ?)";
    $stmt_insert_stock = $mysqli->prepare($sql_insert_stock);

    foreach ($items as $prod) {
        $id_sup   = intval($prod['id_suplemento']);
        $cant     = intval($prod['cantidad']);
        $costo    = floatval($prod['costo']);

        // Insertar detalle
        $stmt_det->bind_param("iiid", $id_compra, $id_sup, $cant, $costo);
        $stmt_det->execute();

        // Verificar si existen existencias
        $stmt_check->bind_param("i", $id_sup);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows == 0) {
            // Crear registro de stock
            $stmt_insert_stock->bind_param("ii", $id_sup, $cant);
            $stmt_insert_stock->execute();
        } else {
            // Actualizar stock existente
            $stmt_upd->bind_param("ii", $cant, $id_sup);
            $stmt_upd->execute();
        }
    }

    $mysqli->commit();
    echo json_encode(['status' => 'ok', 'msg' => 'Compra registrada', 'folio' => $id_compra]);

} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['status' => 'error', 'msg' => 'Error en compra: ' . $e->getMessage()]);
}
?>
