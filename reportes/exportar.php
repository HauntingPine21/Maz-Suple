<?php
// =================================================================
// ARCHIVO: exportar.php
// OBJETIVO: Generar y forzar la descarga de archivos CSV con filtros,
//           asegurando seguridad (SQL seguro) y sincronía con vistas.
// =================================================================
require_once '../config/db.php';

if (!isset($_GET['tipo'])) {
    die("Tipo de reporte no especificado");
}

$tipo = $_GET['tipo'];
$filename = "reporte_" . $tipo . "_" . date('Ymd_Hi') . ".csv";

// Forzar descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Abrir salida estándar
$output = fopen('php://output', 'w');

// BOM UTF-8 para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// ================================================================
// SWITCH PRINCIPAL DE REPORTES
// ================================================================
switch ($tipo) {

    // ============================================================
    // INVENTARIO
    // ============================================================
    case 'inventario':

        $filtro_q = $_GET['q'] ?? "";
        $solo_activos = isset($_GET['activos']) && $_GET['activos'] == "1";

        fputcsv($output, ['Código', 'Título del Libro', 'Precio Venta', 'Stock Actual', 'Estado']);

        $sql = "SELECT 
                    l.codigo,
                    l.titulo,
                    l.precio_venta,
                    e.cantidad,
                    CASE l.estatus WHEN 1 THEN 'ACTIVO' ELSE 'INACTIVO' END AS estado
                FROM libros l
                JOIN existencias e ON l.id = e.id_libro
                WHERE 1=1";

        // Construcción segura del query
        $params = [];
        $types = "";

        if ($filtro_q !== "") {
            $sql .= " AND (l.codigo LIKE ? OR l.titulo LIKE ?)";
            $like = "%" . $filtro_q . "%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
        }

        if ($solo_activos) {
            $sql .= " AND l.estatus = 1";
        }

        $sql .= " ORDER BY l.titulo ASC";

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
    // VENTAS (ENCABEZADO SIMPLE)
    // ============================================================
    case 'ventas':

        $f1 = $_GET['inicio'] ?? date('Y-m-01');
        $f2 = $_GET['fin'] ?? date('Y-m-d');
        $cajero = isset($_GET['cajero']) ? intval($_GET['cajero']) : 0;

        $iniDB = $f1 . " 00:00:00";
        $finDB = $f2 . " 23:59:59";

        fputcsv($output, ['Folio', 'Fecha/Hora', 'Cajero', 'Subtotal', 'IVA', 'Total Venta']);

        $sql = "SELECT 
                    v.id,
                    v.fecha_hora,
                    u.nombre_completo,
                    v.subtotal,
                    v.iva,
                    v.total
                FROM ventas v
                JOIN usuarios u ON v.id_usuario = u.id
                WHERE v.fecha_hora BETWEEN ? AND ?";

        $types = "ss";
        $params = [$iniDB, $finDB];

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
            'Folio', 'Fecha/Hora', 'Código Producto',
            'Nombre Producto', 'Cant.', 'Precio Unit.', 'Subtotal Línea'
        ]);

        $sql = "SELECT 
                    v.id AS folio,
                    v.fecha_hora,
                    l.codigo,
                    l.titulo AS nombre,
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.importe
                FROM detalle_ventas dv
                JOIN ventas v ON dv.id_venta = v.id
                JOIN libros l ON dv.id_libro = l.id
                WHERE v.fecha_hora BETWEEN ? AND ?";

        $types = "ss";
        $params = [$iniDB, $finDB];

        if ($producto !== "") {
            $sql .= " AND (l.titulo LIKE ? OR l.codigo LIKE ?)";
            $like = "%".$producto."%";
            $params[] = $like;
            $params[] = $like;
            $types .= "ss";
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
            'Folio Venta', 'Fecha Devolución', 'Código Producto',
            'Nombre Producto', 'Cant. Dev.', 'Monto Devuelto', 'Motivo'
        ]);

        $sql = "SELECT 
                    d.id_venta,
                    d.fecha_hora,
                    l.codigo,
                    l.titulo,
                    dd.cantidad,
                    dd.monto_reembolsado,
                    d.motivo
                FROM devoluciones d
                JOIN detalle_devoluciones dd ON d.id = dd.id_devolucion
                JOIN libros l ON dd.id_libro = l.id
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
        fputcsv($output, ['Folio Compra', 'Fecha/Hora', 'Proveedor', 'Total Compra']);

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
