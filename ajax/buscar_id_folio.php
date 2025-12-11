<?php
// ============================================================
// POS Offline-First: Traducción de folio offline → ID real
// Adaptado a MazSupledb (suplementos) y esquema actual
// ============================================================

require_once '../config/db.php';
header('Content-Type: application/json');

// 1. VALIDACIÓN
if (!isset($_GET['folio'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Falta parámetro folio']);
    exit;
}

// 2. DETECCIÓN DE CONEXIÓN
if (isset($mysqli)) {
    $db = $mysqli;
} elseif (isset($conexion)) {
    $db = $conexion;
} elseif (isset($conn)) {
    $db = $conn;
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Sin conexión disponible']);
    exit;
}

// 3. LIMPIEZA DE ENTRADA
$folio = $db->real_escape_string($_GET['folio']);

// 4. BÚSQUEDA DEL ID REAL
// NOTA: ventas AHORA debe incluir folio_texto
$sql = "SELECT id FROM ventas WHERE folio_texto = '$folio' LIMIT 1";
$res = $db->query($sql);

// 5. RESPUESTA
if ($res && $res->num_rows > 0) {
    $fila = $res->fetch_assoc();
    echo json_encode([
        'status' => 'ok',
        'id_real' => $fila['id']
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'msg' => 'Folio no encontrado'
    ]);
}
?>
