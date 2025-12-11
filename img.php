<?php
require_once 'config/db.php';

$tipo = $_GET['tipo'] ?? 'suplemento'; // 'suplemento' o 'logo'
$id   = isset($_GET['id']) ? intval($_GET['id']) : 0;

// PNG transparente 1x1 como fallback
$png_transparente = base64_decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
);

if ($tipo === 'logo') {
    // ==========================
    // Logo de la empresa desde configuracion
    // ==========================
    $sql = "SELECT logo_empresa FROM configuracion WHERE id = 1 LIMIT 1";
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
    // Imagen de suplemento
    // ==========================
    if ($id <= 0) {
        header("Content-type: image/png");
        echo $png_transparente;
        exit;
    }

    $sql = "SELECT contenido, tipo_mime 
            FROM imagenes_suplemento 
            WHERE id_suplemento = ? AND es_principal = 1 
            LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($contenido, $mime);

    if ($stmt->fetch() && $contenido) {
        header("Content-type: " . ($mime ?: "image/png"));
        echo $contenido;
    } else {
        // Si no hay imagen registrada, fallback
        header("Content-type: image/png");
        echo $png_transparente;
    }
}
?>
