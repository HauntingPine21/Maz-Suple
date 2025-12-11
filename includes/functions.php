<?php
// ===============================================
// functions.php (Versión adaptada a MazSupleDB)
// Compatibilidad con MySQLi y PDO
// ===============================================

// -------------------------------
// Formato monetario estándar
// -------------------------------
function formato_moneda($cantidad) {
    return number_format(floatval($cantidad), 2, '.', ',');
}

// -------------------------------
// Sanitización universal
// -------------------------------
// Detecta si recibes $mysqli (MySQLi) o $pdo (PDO)
// y sanea correctamente.
// -------------------------------
function sanear($db, $string) {

    $string = trim($string);

    // Si es MySQLi
    if ($db instanceof mysqli) {
        return $db->real_escape_string($string);
    }

    // Si es PDO
    if ($db instanceof PDO) {
        // Reemplaza caracteres peligrosos sin romper consultas preparadas
        return str_replace(
            ["'", '"', ";", "--"],
            ["´", "¨", "", ""],
            $string
        );
    }

    // Si no reconoce el tipo, regresa limpio básico
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// -------------------------------
// Respuesta JSON estándar
// -------------------------------
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
