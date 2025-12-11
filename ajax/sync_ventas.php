<?php
// POS Offline-First (PWA) - Sincronización Diferida de Ventas
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
session_start();

if (!file_exists('../config/db.php')) {
    echo json_encode(['status' => 'error', 'msg' => 'Falta config/db.php']);
    exit;
}
require_once '../config/db.php';

if (isset($mysqli)) { $db = $mysqli; } 
elseif (isset($conexion)) { $db = $conexion; } 
elseif (isset($conn)) { $db = $conn; } 
else { echo json_encode(['status'=>'error','msg'=>'Error: Variable de conexión no encontrada']); exit; }

if ($db->connect_error) {
    echo json_encode(['status'=>'error','msg'=>'Error DB: '.$db->connect_error]);
    exit;
}

$input = file_get_contents("php://input");
$ventasPendientes = json_decode($input, true);

if (!$ventasPendientes) {
    echo json_encode(['status'=>'error','msg'=>'No se recibieron datos']);
    exit;
}

$id_usuario = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 1;

try {
    $db->begin_transaction();

    // Preparar SQL adaptado
    $sql_venta = "INSERT INTO ventas (id_usuario, subtotal, iva, total, fecha_hora) VALUES (?, ?, ?, ?, ?)";
    $stmt_venta = $db->prepare($sql_venta);

    $sql_detalle = "INSERT INTO detalle_ventas (id_venta, id_suplemento, cantidad, precio_unitario, importe) VALUES (?, ?, ?, ?, ?)";
    $stmt_det = $db->prepare($sql_detalle);

    $sql_stock = "UPDATE existencias SET cantidad = cantidad - ? WHERE id_suplemento = ?";
    $stmt_stk = $db->prepare($sql_stock);

    if (!$stmt_venta || !$stmt_det || !$stmt_stk) throw new Exception("Error preparando SQL: " . $db->error);

    foreach ($ventasPendientes as $venta) {
        $subtotal = 0;
        foreach ($venta['productos'] as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
        }
        $iva = $subtotal * 0.16;
        $total = $subtotal + $iva;

        $fecha = isset($venta['fecha_local']) ? date('Y-m-d H:i:s', strtotime($venta['fecha_local'])) : date('Y-m-d H:i:s');

        $stmt_venta->bind_param("iddds", $id_usuario, $subtotal, $iva, $total, $fecha);
        if (!$stmt_venta->execute()) throw new Exception("Error al guardar venta: " . $stmt_venta->error);
        $id_nuevo = $db->insert_id;

        foreach ($venta['productos'] as $prod) {
            $importe = $prod['precio'] * $prod['cantidad'];

            $stmt_det->bind_param("iiidd", $id_nuevo, $prod['id'], $prod['cantidad'], $prod['precio'], $importe);
            $stmt_det->execute();

            $stmt_stk->bind_param("ii", $prod['cantidad'], $prod['id']);
            $stmt_stk->execute();
        }
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'msg' => 'Sincronización correcta']);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
?>
