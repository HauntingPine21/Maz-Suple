<?php
// ======================================================
// ADAPTADO A LA NUEVA BASE DE DATOS
//  - Logo desde tabla configuracion (logo_empresa)
//  - Imágenes desde tabla items (imagen, imagen_tipo)
// ======================================================

require_once 'config/db.php';

$tipo = $_GET['tipo'] ?? 'item'; // 'item' o 'logo'
$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;

// PNG transparente 1x1 (fallback)
$png_transparente = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
);

if ($tipo === 'logo') {

    // ==========================
    // LOGO DE LA EMPRESA
    // ==========================
    $sql = "SELECT logo_empresa FROM configuracion WHERE id = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($contenido);
    $stmt->fetch();

    if ($contenido) {
        header("Content-type: image/png"); 
        echo $contenido;
    } else {
        header("Content-type: image/png");
        echo $png_transparente;
    }

} else {

    // ==========================
    // IMAGEN DE ITEM
    // ==========================
    if ($id <= 0) {
        header("Content-type: image/png");
        echo $png_transparente;
        exit;
    }

    $sql = "SELECT imagen, imagen_tipo FROM items WHERE id_item = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($contenido, $mime);

    if ($stmt->fetch() && $contenido) {
        header("Content-type: " . ($mime ?: "image/png"));
        echo $contenido;
    } else {
        header("Content-type: image/png");
        echo $png_transparente;
    }
}
?>
