<?php
// ajax/confirmar_compra.php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

// Solo método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['status' => 'error', 'msg' => 'Método no permitido'], 405);
}

// Leer JSON del body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['id_proveedor'], $input['items'])) {
    json_response(['status' => 'error', 'msg' => 'Datos incompletos'], 400);
}

$id_proveedor = intval($input['id_proveedor']);
$items = $input['items'];
$usuario_id = $_SESSION['user']['id'] ?? 0;

// Validar proveedor
$res = $mysqli->query("SELECT id_proveedor FROM proveedores WHERE id_proveedor = $id_proveedor AND estatus = 1");
if (!$res || $res->num_rows === 0) {
    json_response(['status' => 'error', 'msg' => 'Proveedor no válido']);
}

// Validar items
if (count($items) === 0) {
    json_response(['status' => 'error', 'msg' => 'No hay suplementos para registrar']);
}

foreach ($items as $i => $item) {
    if (!isset($item['id_producto'], $item['cantidad'], $item['costo'])) {
        json_response(['status' => 'error', 'msg' => "Item $i incompleto"]);
    }
    if ($item['cantidad'] <= 0 || $item['costo'] < 0) {
        json_response(['status' => 'error', 'msg' => "Item $i con valores inválidos"]);
    }
}

// Comenzar transacción
$mysqli->begin_transaction();
try {
    // Insertar encabezado de compra
    $fecha = date('Y-m-d H:i:s');
    $total = 0;
    foreach ($items as $item) {
        $total += $item['cantidad'] * $item['costo'];
    }

    $stmt = $mysqli->prepare("INSERT INTO compras (id_proveedor, id_usuario, fecha, total) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisd", $id_proveedor, $usuario_id, $fecha, $total);
    $stmt->execute();
    $id_compra = $mysqli->insert_id;

    // Insertar detalle y actualizar stock
    $stmt_detalle = $mysqli->prepare("INSERT INTO detalle_compras (id_compra, id_suplemento, cantidad, costo, subtotal) VALUES (?, ?, ?, ?, ?)");
    $stmt_stock = $mysqli->prepare("UPDATE existencias SET cantidad = cantidad + ? WHERE id_suplemento = ?");

    foreach ($items as $item) {
        $subtotal = $item['cantidad'] * $item['costo'];

        $stmt_detalle->bind_param("iiidd", $id_compra, $item['id_producto'], $item['cantidad'], $item['costo'], $subtotal);
        $stmt_detalle->execute();

        $stmt_stock->bind_param("ii", $item['cantidad'], $item['id_producto']);
        $stmt_stock->execute();
    }

    $mysqli->commit();
    json_response(['status' => 'ok', 'folio' => $id_compra]);

} catch (Exception $e) {
    $mysqli->rollback();
    json_response(['status' => 'error', 'msg' => $e->getMessage()]);
}

// Función auxiliar para JSON
function json_response($arr, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}
