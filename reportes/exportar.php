<?php
require_once '../config/db.php';

if (!isset($_GET['tipo'])) {
    die("Tipo de reporte no especificado");
}

$tipo = $_GET['tipo'];
$filename = "reporte_" . $tipo . "_" . date('Ymd_Hi') . ".csv";

// Headers CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// BOM UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

switch ($tipo) {

    // ============================================================
    // INVENTARIO
    // ============================================================
    case 'inventario':

        $filtro_q = $_GET['q'] ?? "";
        $solo_activos = isset($_GET['activos']) && $_GET['activos'] == "1";

        fputcsv($output, ['Código', 'Nombre', 'Marca', 'Precio Venta', 'Stock Actual', 'Estado']);

        $sql = "SELECT 
                    s.codigo,
                    s.nombre,
                    s.marca,
                    s.precio_venta,
                    e.cantidad,
                    CASE s.estatus WHEN 1 THEN 'ACTIVO' ELSE 'INACTIVO' END AS estado
                FROM suplementos s
                LEFT JOIN existencias e ON s.id = e.id_suplemento
                WHERE 1=1";

        $params = [];
        $types = "";

        if ($filtro_q !== "") {
            $sql .= " AND (s.codigo LIKE ? OR s.nombre LIKE ? OR s.marca LIKE ?)";
            $like = "%" . $filtro_q . "%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= "sss";
        }

        if ($solo_activos) {
            $sql .= " AND s.estatus = 1";
        }

        $sql .= " ORDER BY s.nombre ASC";

        $stmt = $mysqli->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }

        break;

    // ============================================================
    // VENTAS (ENCABEZADO)
    // ============================================================
    case 'ventas':

        $f1 = $_GET['inicio'] ?? date('Y-m-01');
        $f2 = $_GET['fin'] ?? date('Y-m-d');
        $cajero = isset($_GET['cajero']) ? intval($_GET['cajero']) : 0;

        $iniDB = $f1 . " 00:00:00";
        $finDB = $f2 . " 23:59:59";

        fputcsv($output, ['Folio', 'Fecha', 'Cajero', 'Subtotal', 'IVA', 'Total', 'Estado']);

        $sql = "SELECT 
                    v.id,
                    v.fecha_hora,
                    u.nombre_completo,
                    v.subtotal,
                    v.iva,
                    v.total,
                    v.estado
                FROM ventas v
                JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.fecha_hora BETWEEN ? AND ?";

        $params = [$iniDB, $finDB];
        $types = "ss";

        if ($cajero > 0) {
            $sql .= " AND v.id_usuario = ?";
            $types .= "i";
            $params[] = $cajero;
        }

        $sql .= " ORDER BY v.fecha_hora DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }

        break;

    // ============================================================
    // DETALLE DE VENTAS
    // ============================================================
    case 'detalle_ventas':

        $f1 = $_GET['inicio'] ?? date('Y-m-01');
        $f2 = $_GET['fin'] ?? date('Y-m-d');
        $producto = $_GET['producto'] ?? "";

        $iniDB = $f1 . " 00:00:00";
        $finDB = $f2 . " 23:59:59";

        fputcsv($output, [
            'Folio Venta', 'Fecha', 'Código', 'Nombre',
            'Cant.', 'Precio Unit.', 'Subtotal Línea'
        ]);

        $sql = "SELECT 
                    v.id AS folio,
                    v.fecha_hora,
                    s.codigo,
                    s.nombre,
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.importe
                FROM detalle_ventas dv
                JOIN ventas v ON dv.id_venta = v.id
                JOIN suplementos s ON dv.id_suplemento = s.id
                WHERE v.fecha_hora BETWEEN ? AND ?";

        $params = [$iniDB, $finDB];
        $types = "ss";

        if ($producto !== "") {
            $sql .= " AND (s.nombre LIKE ? OR s.codigo LIKE ? OR s.marca LIKE ?)";
            $like = "%".$producto."%";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= "sss";
        }

        $sql .= " ORDER BY v.fecha_hora DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }

        break;

    // ============================================================
    // DEVOLUCIONES
    // ============================================================
    case 'devoluciones':

        $f1 = $_GET['inicio'] ?? date('Y-m-01');
        $f2 = $_GET['fin'] ?? date('Y-m-d');

        $iniDB = $f1 . " 00:00:00";
        $finDB = $f2 . " 23:59:59";

        fputcsv($output, [
            'Folio Venta', 'Fecha Devolución', 'Código', 'Nombre',
            'Cant. Dev.', 'Monto Dev.', 'Motivo'
        ]);

        $sql = "SELECT 
                    d.id_venta,
                    d.fecha_hora,
                    s.codigo,
                    s.nombre,
                    dd.cantidad,
                    dd.monto_reembolsado,
                    d.motivo
                FROM devoluciones d
                JOIN detalle_devoluciones dd ON d.id = dd.id_devolucion
                JOIN suplementos s ON dd.id_suplemento = s.id
                WHERE d.fecha_hora BETWEEN ? AND ?
                ORDER BY d.fecha_hora DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ss", $iniDB, $finDB);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    // ============================================================
    // COMPRAS
    // ============================================================
    case 'compras':

        fputcsv($output, ['Folio', 'Fecha', 'Proveedor', 'Total Compra']);

        $sql = "SELECT 
                    c.id,
                    c.fecha_hora,
                    p.nombre,
                    c.total_compra
                FROM compras c
                JOIN proveedores p ON c.id_proveedor = p.id
                ORDER BY c.fecha_hora DESC";

        $res = $mysqli->query($sql);

        while ($row = $res->fetch_assoc()) {
            fputcsv($output, $row);
        }
        break;

    default:
        die("Tipo de reporte no válido");
}

fclose($output);
exit;
?>
