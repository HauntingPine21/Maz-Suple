<?php

$host = 'localhost';
$user = 'root';        
$pass = '';            
$db   = 'MazSupledb';  
$port = 3306;

$mysqli = new mysqli($host, $user, $pass, $db, $port);

$mysqli->set_charset("utf8mb4");

if ($mysqli->connect_error) {
    die("Error crítico de conexión: " . $mysqli->connect_error);
}
?>
