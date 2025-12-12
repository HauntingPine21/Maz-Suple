<?php
// compras.php

require_once 'includes/security_guard.php'; 
require_once 'config/db.php';

$rol = $_SESSION['user']['rol']; 

// BACKEND (Lógica de Proveedores)
$proveedores = [];
if (isset($mysqli)) {
    // ⚠️ Usar 'id' y no 'id_proveedor'
    $sql_prov = "SELECT id, nombre FROM proveedores WHERE estatus = 1 ORDER BY nombre";
    if ($res_prov = $mysqli->query($sql_prov)) {
        while ($row = $res_prov->fetch_assoc()) {
            $proveedores[] = $row;
        }
    }
}

// BACKEND (Lógica de suplementos para JS)
$suplementos = [];
$sql_supp = "SELECT id, codigo, nombre, precio_venta FROM suplementos WHERE estatus = 1 ORDER BY nombre";
if ($res_supp = $mysqli->query($sql_supp)) {
    while ($row = $res_supp->fetch_assoc()) {
        // Renombramos precio_venta a precio_sugerido para evitar confusión con precio de compra
        $suplementos[] = [
            'id' => intval($row['id']),
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'precio_sugerido' => floatval($row['precio_venta'])
        ];
    }
}
$suplementos_json = json_encode($suplementos);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MazSuple | Órdenes de Compra</title>
    <link rel="stylesheet" href="css/compras.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" href="assets/SupleIcono.png"> 
</head>
<body>
    
<nav class="navbar">
    <div class="navbar-logo">
        <img src="assets/ImgLogo.png" alt="Logo">
    </div>

    <button class="menu-toggle" id="mobile-menu-btn">
        <span></span><span></span><span></span>
    </button>

    <div class="navbar-menu" id="navbar-menu">
        <div class="navbar-links">
            <a href="dashboard.php" class="nav-link">Inicio</a>
            <a href="ventas.php" class="nav-link">Punto de Venta</a>
            <a href="devoluciones.php" class="nav-link">Devoluciones</a>
        </div>
        
        <?php if ($rol === 'admin'): ?>
        <hr class="nav-divider">
        <div class="dropdown">
            <button class="dropbtn active">Gestión ▾</button>
            <div class="dropdown-content show">
                <a href="productos.php">Productos</a>
                <a href="compras.php" class="active">Compras</a>
                <a href="usuarios.php">Usuarios</a>
            </div>
        </div>

        <div class="dropdown">
            <button class="dropbtn">Reportes ▾</button>
            <div class="dropdown-content">
                <a href="reportes/devoluciones.php">Devoluciones</a>
                <a href="reportes/inventario.php">Inventario</a>
                <a href="reportes/ventas_detalle.php">Ventas Detalle</a>
                <a href="reportes/ventas_encabezado.php">Ventas Encabezado</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="navbar-user-info">
            <span class="user-text">Administrador: 
                <strong><?php echo htmlspecialchars($_SESSION['user']['nombre']); ?></strong>
            </span>
            <a href="includes/logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<main class="main-content-wrapper">
    <h2>Registro de Orden de Compra</h2>
    
    <div id="compra-message" class="hidden-message"></div>

    <div class="card card-encabezado">
        <h3>Datos de la Compra</h3>
        <form id="form-compra-encabezado">
            <div class="grid-2-cols">
                <div>
                    <label for="fecha">Fecha de Pedido</label>
                    <input type="date" id="fecha" name="fecha" required 
                           value="<?php echo date('Y-m-d'); ?>" class="input-padded">
                </div>
                <div>
                    <label for="proveedor">Proveedor</label>
                    <select id="proveedor" name="proveedor" required class="input-padded select-padded">
                        <option value="">-- Seleccione un proveedor --</option>
                        <?php foreach ($proveedores as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <div class="card mt-20">
        <h3>Detalle de Suplementos a Comprar</h3>
        
        <div class="flex-row mb-15 search-row">
            <input 
                type="text" 
                id="input-producto-compra" 
                placeholder="Buscar suplemento por nombre o código (Ej: SUP001)..." 
                class="flex-grow w-auto input-padded"
                list="lista-suplementos"
            >
            <datalist id="lista-suplementos">
                </datalist>
            <button type="button" id="btn-agregar-item" class="btn-general w-150 btn-add-item">Agregar Item</button>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th class="col-35">Suplemento</th>
                        <th class="col-15">Código</th>
                        <th class="col-15 text-center">Cantidad</th>
                        <th class="col-15 text-right">Costo Unitario</th>
                        <th class="col-10 text-right">Subtotal</th>
                        <th class="col-10 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody id="tabla-detalle-compra">
                    <tr>
                        <td colspan="6" class="text-center text-muted">Agrega suplementos para comenzar la orden</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="total-summary">
            Total Compra: <span id="total-compra-display">$0.00</span>
        </div>

        <button id="btn-guardar-compra" class="btn-general mt-20 w-full btn-process">
            Guardar Orden de Compra
        </button>
    </div>
</main>

<script src="js/main.js?v=<?php echo time(); ?>"></script>
<script src="js/offline_manager.js?v=<?php echo time(); ?>"></script>
<script>
    // Inyectamos la lista de suplementos desde PHP a JS
    const PRODUCTOS_LIST = <?= $suplementos_json ?>;
    let carrito = {}; // Almacenará los items de la compra {id_suplemento: {data...}, ...}

    document.addEventListener('DOMContentLoaded', () => {
        // 1. LLENAR DATALIST
        const dataList = document.getElementById('lista-suplementos');
        const searchInput = document.getElementById('input-producto-compra');
        
        PRODUCTOS_LIST.forEach(p => {
            const option = document.createElement('option');
            // Usamos una combinación que permite buscar por nombre o código
            option.value = p.nombre + " (" + p.codigo + ")"; 
            option.setAttribute('data-id', p.id);
            dataList.appendChild(option);
        });

        // 2. BUSCAR Y AGREGAR ITEM AL CARRITO (simulado)
        document.getElementById('btn-agregar-item').addEventListener('click', () => {
            const inputValue = searchInput.value.trim();
            if (!inputValue) return alert("Ingrese un producto o código.");

            // Buscar el ID del producto basado en el valor de la entrada
            const productoEncontrado = PRODUCTOS_LIST.find(p => 
                inputValue.includes(p.nombre) && inputValue.includes(p.codigo)
            );

            if (!productoEncontrado) {
                return alert("Producto no encontrado. Asegúrese de seleccionar uno de la lista o usar el formato correcto.");
            }

            const id = productoEncontrado.id;

            if (carrito[id]) {
                carrito[id].cantidad++;
            } else {
                carrito[id] = {
                    id_suplemento: id,
                    codigo: productoEncontrado.codigo,
                    nombre: productoEncontrado.nombre,
                    // Inicializamos el costo unitario con el precio de venta sugerido, 
                    // pero el usuario puede cambiarlo en la tabla.
                    costo_unitario: productoEncontrado.precio_sugerido, 
                    cantidad: 1
                };
            }

            searchInput.value = ''; // Limpiar input
            renderCarrito();
        });

        // 3. RENDERIZAR CARRITO
        function renderCarrito() {
            const tbody = document.getElementById('tabla-detalle-compra');
            let totalCompra = 0;
            tbody.innerHTML = '';

            const items = Object.values(carrito);
            if (items.length === 0) {
                 tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Agrega suplementos para comenzar la orden</td></tr>';
                 document.getElementById('total-compra-display').textContent = `$0.00`;
                 return;
            }

            items.forEach(item => {
                const subtotal = item.cantidad * item.costo_unitario;
                totalCompra += subtotal;

                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${item.nombre}</td>
                    <td>${item.codigo}</td>
                    <td class="text-center">
                        <input type="number" data-id="${item.id_suplemento}" data-campo="cantidad"
                            value="${item.cantidad}" min="1" class="input-cant-compra input-padded" style="width: 70px;">
                    </td>
                    <td class="text-right">
                        <input type="number" data-id="${item.id_suplemento}" data-campo="costo_unitario"
                            value="${item.costo_unitario.toFixed(2)}" min="0.01" step="0.01" class="input-costo-compra input-padded text-right" style="width: 80px;">
                    </td>
                    <td class="text-right subtotal-item">$${subtotal.toFixed(2)}</td>
                    <td class="text-center">
                        <button type="button" data-id="${item.id_suplemento}" class="btn-quitar btn-desactivar">Quitar</button>
                    </td>
                `;
                tbody.appendChild(row);
            });

            document.getElementById('total-compra-display').textContent = `$${totalCompra.toFixed(2)}`;
            
            // Agregar listeners para actualizar cantidad/costo
            tbody.querySelectorAll('.input-cant-compra, .input-costo-compra').forEach(input => {
                input.addEventListener('change', updateItem);
            });
            // Agregar listeners para quitar items
            tbody.querySelectorAll('.btn-quitar').forEach(btn => {
                btn.addEventListener('click', removeItem);
            });
        }

        // 4. FUNCIÓN PARA ACTUALIZAR ITEM EN EL CARRITO
        function updateItem(event) {
            const id = parseInt(event.target.dataset.id);
            const campo = event.target.dataset.campo;
            let value = event.target.value;

            // Asegurar que la cantidad sea entera y positiva
            if (campo === 'cantidad') {
                value = Math.max(1, parseInt(value));
            } else {
                // Asegurar que el costo sea positivo
                value = Math.max(0.01, parseFloat(value));
            }

            // Aplicar el valor formateado de vuelta al input
            event.target.value = (campo === 'costo_unitario') ? value.toFixed(2) : value;

            if (carrito[id]) {
                carrito[id][campo] = value;
                renderCarrito();
            }
        }
        
        // 5. FUNCIÓN PARA QUITAR ITEM DEL CARRITO
        function removeItem(event) {
            const id = parseInt(event.target.dataset.id);
            if (confirm("¿Seguro que quieres quitar este producto de la orden?")) {
                delete carrito[id];
                renderCarrito();
            }
        }

        // 6. PROCESAR ORDEN DE COMPRA (AJAX)
        document.getElementById('btn-guardar-compra').addEventListener('click', async () => {
            const idProveedor = document.getElementById('proveedor').value;
            const fecha = document.getElementById('fecha').value;
            const items = Object.values(carrito);
            const messageDiv = document.getElementById('compra-message');

            if (!idProveedor || !fecha) {
                messageDiv.className = 'alert-custom-danger';
                messageDiv.textContent = 'Debe seleccionar un proveedor y una fecha.';
                return;
            }

            if (items.length === 0) {
                messageDiv.className = 'alert-custom-danger';
                messageDiv.textContent = 'La orden de compra no puede estar vacía.';
                return;
            }

            if (!confirm(`¿Confirmar la orden de compra al proveedor ID ${idProveedor} por ${document.getElementById('total-compra-display').textContent}? El stock será actualizado.`)) return;

            const dataToSend = {
                id_proveedor: idProveedor,
                fecha: fecha,
                items: items
            };

            const resp = await fetch("ajax/guardar_compra.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(dataToSend)
            });

            const data = await resp.json();

            if (data.status === "ok") {
                messageDiv.className = 'alert-custom-success';
                messageDiv.textContent = `¡Compra registrada con éxito! Folio: ${data.folio}.`;
                carrito = {}; // Vaciar carrito
                renderCarrito();
                // Opcional: limpiar encabezado o recargar página para nueva compra
                document.getElementById('proveedor').value = ''; 
            } else {
                messageDiv.className = 'alert-custom-danger';
                messageDiv.textContent = `Error al registrar la compra: ${data.msg || 'Error desconocido'}`;
            }
            messageDiv.classList.remove('hidden-message');
            window.scrollTo(0, 0); // Ir arriba para ver el mensaje
        });
        
        // Mobile menu is handled by offline_manager.js
    });
</script>
</body>
</html>
