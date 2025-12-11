<?php
// ventas.php

require_once 'includes/seguridad_basica.php';

$rol = $_SESSION['user']['rol'];
$cajero_nombre = $_SESSION['user']['nombre'];
$cajero_id = $_SESSION['user']['id'];

if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}
?>

<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Suplementos Deportivos MX | Punto de Venta</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" type="image/png" href="assets/img/logo_icon.png">
  </head>

  <body>
    <div class="navbar">
      <div class="navbar-logo">
        <img src="assets/img/logo.svg" alt="Logo">
      </div>
      
      <button class="menu-toggle" id="mobile-menu-btn">
        <span></span><span></span><span></span>
      </button>

      <div class="navbar-menu" id="navbar-menu">
        <button onclick="sincronizarVentas()" class="btn btn-warning">
          Sincronizar (Offline)
        </button>

        <div class="dropdown">
          <button class="dropbtn">Cajero ▾</button>
          <div class="dropdown-content">
            <a href="dashboard.php">Inicio</a>
            <a href="ventas.php">Punto de Venta</a>
            <a href="devoluciones.php">Devoluciones</a>
          </div>
        </div>

        <?php if ($rol === 'admin'): ?>
          <div class="dropdown">
            <button class="dropbtn">Gestión ▾</button>
            <div class="dropdown-content">
              <a href="productos.php">Productos</a>
              <a href="compras.php">Compras</a>
              <a href="usuarios.php">Usuarios</a>
            </div>
          </div>

          <div class="dropdown">
            <button class="dropbtn">Reportes ▾</button>
            <div class="dropdown-content">
              <a href="reportes/compras.php">Reportes Compra</a>
              <a href="reportes/devoluciones.php">Reportes Devoluciones</a>
              <a href="reportes/inv
