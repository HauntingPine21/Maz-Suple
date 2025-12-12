<?php


session_start();

require_once '../config/db.php'; 
require_once '../includes/seguridad_basica.php'; 

header('Content-Type: application/json');


$data = json_decode(file_get_contents('php://input'), true);


$id_usuario = $_SESSION['user']['id'] ?? 1; 

$items = $data['items'] ?? [];

$offline = isset($data["offline"]) ? true : false; 


if (!isset($_SESSION['user'])) {
    echo json_encode(['status'=>'error','msg'=>'Sesión caducada. Por favor, reinicie la aplicación.']);
    exit;
}
if (!is_array($items) || count($items) === 0) {
    echo json_encode(['status'=>'error','msg'=>'Carrito vacío. No hay productos para registrar.']);
    exit;
}


$mysqli->begin_transaction(); 

try {
    $stmt = $mysqli->prepare("INSERT INTO ventas (id_usuario, subtotal, iva, total, fecha_hora, estado) VALUES (?,0,0,0,NOW(),'completada')");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();

    $id_venta = $mysqli->insert_id;
    $subtotal_total = 0.00;

    $stmt_det = $mysqli->prepare("INSERT INTO detalle_ventas (id_venta, id_suplemento, cantidad, precio_unitario, importe) VALUES (?,?,?,?,?)");
    
    $stmt_stock = $mysqli->prepare("UPDATE existencias SET cantidad = cantidad - ? WHERE id_suplemento=? AND cantidad>=?");

    foreach ($items as $item) {
        
        $id_suplemento = intval($item['id_suplemento'] ?? $item['id_producto'] ?? 0); 
        $cantidad = intval($item['cantidad'] ?? 0);
        $precio = floatval($item['precio'] ?? $item['precio_unitario'] ?? 0);
        $importe = $cantidad * $precio;

        if ($id_suplemento <= 0 || $cantidad <= 0 || $precio <= 0) {
            throw new Exception("Datos inválidos en el producto ID: {$id_suplemento}.");
        }

        $stmt_det->bind_param("iiidd", $id_venta, $id_suplemento, $cantidad, $precio, $importe);
        $stmt_det->execute();
        
        $stmt_stock->bind_param("iii", $cantidad, $id_suplemento, $cantidad);
        $stmt_stock->execute();
        
        if ($stmt_stock->affected_rows === 0) {
             throw new Exception("Stock insuficiente para el producto ID: {$id_suplemento}");
        }

        $subtotal_total += $importe;
    }

    $iva = round($subtotal_total * 0.16, 2); 
    $total = round($subtotal_total + $iva, 2);

    $stmt_update = $mysqli->prepare("UPDATE ventas SET subtotal=?, iva=?, total=? WHERE id=?");
    $stmt_update->bind_param("dddi", $subtotal_total, $iva, $total, $id_venta);
    $stmt_update->execute();

    $mysqli->commit();

    if (!$offline) {
         unset($_SESSION['carrito']); 
    }

    echo json_encode([
        "status" => "ok",
        "folio" => $id_venta,
        "subtotal" => $subtotal_total,
        "iva" => $iva,
        "total" => $total,
        "offline_sync" => $offline 
    ]);


} catch (Exception $e) {
    $mysqli->rollback();
    error_log("Error en confirmar_venta.php: " . $e->getMessage()); 
    echo json_encode([
        'status' => 'error',
        'msg' => 'Error en la transacción. Detalle: ' . $e->getMessage()
    ]);
}