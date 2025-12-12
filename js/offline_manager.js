// RESPONSABLE: Rol 2 (Front)
// Validaciones generales, manejo de modales, toggles de menú.
console.log("Sistema de Suplementos cargado");

document.addEventListener('DOMContentLoaded', function () {

    // ==========================================
    // MENÚ HAMBURGUESA
    // ==========================================
    const menuBtn = document.getElementById('mobile-menu-btn');
    const navbarMenu = document.getElementById('navbar-menu');

    if (menuBtn && navbarMenu) {
        menuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            navbarMenu.classList.toggle('active');
        });
    }

    // ==========================================
    // SUBMENÚS
    // ==========================================
    const dropdownBtns = document.querySelectorAll('.dropbtn');

    dropdownBtns.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation(); // Evita que el clic se propague al documento

            const dropdownContent = this.nextElementSibling;
            
            // Cerrar otros menús abiertos
            document.querySelectorAll('.dropdown-content.show').forEach(openDropdown => {
                if (openDropdown !== dropdownContent) {
                    openDropdown.classList.remove('show');
                }
            });

            // Alternar el menú actual
            dropdownContent.classList.toggle('show');
        });
    });

    // ==========================================
    // CERRAR MENÚ AL HACER CLIC FUERA
    // ==========================================
    document.addEventListener('click', function (e) {
        // Cierra todos los dropdowns si el clic no es en un botón de dropdown
        if (!e.target.matches('.dropbtn')) {
            document.querySelectorAll('.dropdown-content.show').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
        
        // Lógica del menú hamburguesa para móviles
        const menuBtn = document.getElementById('mobile-menu-btn');
        const navbarMenu = document.getElementById('navbar-menu');

        if (window.innerWidth <= 768) {
            if (navbarMenu && !navbarMenu.contains(e.target) && e.target !== menuBtn) {
                navbarMenu.classList.remove('active');
            }
        }
    });

    // ==========================================
    // CONFIRMACIONES
    // ==========================================
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-confirm-action');
        if (btn) {
            const msg = btn.dataset.confirmMessage || '¿Estás seguro?';
            if (!confirm(msg)) e.preventDefault();
        }
    });

});


// ======================================================
// LÓGICA DE COMPRAS (compras.php) - SUPLEMENTOS
// ======================================================
document.addEventListener('DOMContentLoaded', function () {
    const inputProducto = document.getElementById('input-producto-compra');

    if (inputProducto) {

        const btnAgregar = document.getElementById('btn-agregar-item');
        const tablaDetalle = document.getElementById('tabla-detalle-compra');
        const totalDisplay = document.getElementById('total-compra-display');
        const btnGuardar = document.getElementById('btn-guardar-compra');
        const selectProveedor = document.getElementById('id_proveedor');

        let itemsCompra = {};

        btnAgregar.addEventListener('click', buscarYAgregar);
        inputProducto.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarYAgregar();
            }
        });

        async function buscarYAgregar() {
            const query = inputProducto.value.trim();
            if (!query) return;

            try {
                const response = await fetch(`ajax/buscar_producto.php?q=${query}`);
                const productos = await response.json();

                if (productos.length > 0) {

                    const p = productos[0];

                    if (!itemsCompra[p.id_producto]) {
                        itemsCompra[p.id_producto] = {
                            id_producto: p.id_producto,
                            nombre: p.nombre,
                            codigo: p.codigo,
                            cantidad: 1,
                            costo_compra: 0.00
                        };
                    }

                    renderTabla();
                    inputProducto.value = '';

                } else {
                    alert('Producto no encontrado.');
                }

            } catch (e) {
                console.error('Error buscando producto:', e);
            }
        }

        function renderTabla() {
            tablaDetalle.innerHTML = '';

            if (Object.keys(itemsCompra).length === 0) {
                tablaDetalle.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        Agrega suplementos para crear la orden
                    </td>
                </tr>`;
                calcularTotal();
                return;
            }

            for (const id in itemsCompra) {
                const item = itemsCompra[id];
                const subtotal = (item.cantidad * item.costo_compra).toFixed(2);

                tablaDetalle.innerHTML += `
                    <tr data-id="${item.id_producto}">
                        <td>${item.nombre}</td>
                        <td>${item.codigo}</td>
                        <td><input type="number" class="input-cantidad" value="${item.cantidad}" min="1"></td>
                        <td><input type="number" class="input-costo" value="${item.costo_compra.toFixed(2)}" step="0.01"></td>
                        <td class="subtotal-celda">$${subtotal}</td>
                        <td><button class="btn-remover">X</button></td>
                    </tr>
                `;
            }

            agregarEventos();
            calcularTotal();
        }

        function agregarEventos() {
            tablaDetalle.querySelectorAll('tr').forEach(fila => {
                const id = fila.dataset.id;

                fila.querySelector('.input-cantidad').addEventListener('change', (e) => {
                    itemsCompra[id].cantidad = parseInt(e.target.value) || 1;
                    renderTabla();
                });

                fila.querySelector('.input-costo').addEventListener('change', (e) => {
                    itemsCompra[id].costo_compra = parseFloat(e.target.value) || 0;
                    renderTabla();
                });

                fila.querySelector('.btn-remover').addEventListener('click', () => {
                    delete itemsCompra[id];
                    renderTabla();
                });
            });
        }

        function calcularTotal() {
            let total = 0;
            for (const id in itemsCompra) {
                total += itemsCompra[id].cantidad * itemsCompra[id].costo_compra;
            }
            totalDisplay.textContent = `$${total.toFixed(2)}`;
        }

        btnGuardar.addEventListener('click', async function () {

            if (!selectProveedor.value) {
                alert("Selecciona un proveedor.");
                return;
            }

            if (Object.keys(itemsCompra).length === 0) {
                alert("Debe agregar suplementos.");
                return;
            }

            if (confirm("¿Confirmar orden de compra?")) {

                const datos = {
                    id_proveedor: selectProveedor.value,
                    items: Object.values(itemsCompra)
                };

                try {
                    const response = await fetch('ajax/confirmar_compra.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(datos)
                    });

                    const r = await response.json();

                    if (r.status === 'ok') {
                        alert(`Compra registrada. Folio: ${r.folio}`);
                        location.reload();
                    } else {
                        alert("Error: " + r.msg);
                    }

                } catch (e) {
                    alert("Error de conexión.");
                }
            }

        });

    }
});


// ======================================================
// DEVOLUCIONES SUPLEMENTOS
// ======================================================
document.addEventListener('DOMContentLoaded', function () {

    const checks = document.querySelectorAll('.check-devolucion');

    if (checks.length > 0) {
        checks.forEach(c => {
            c.addEventListener('change', function () {
                const id = this.dataset.id;
                const input = document.getElementById('cant_' + id);
                input.disabled = !this.checked;
                if (!this.checked) input.value = 1;
            });
        });
    }

    const btnProcesar = document.getElementById('btn-procesar-devolucion');

    if (btnProcesar) {
        btnProcesar.addEventListener('click', async function () {

            const items = [];

            document.querySelectorAll('.check-devolucion:checked').forEach(c => {
                const id = c.dataset.id;
                const cant = parseInt(document.getElementById('cant_' + id).value);
                items.push({ id_producto: parseInt(id), cantidad: cant });
            });

            if (items.length === 0) {
                alert("Selecciona productos.");
                return;
            }

            const idVenta = document.getElementById('venta_id_origen').value;
            const motivo = document.getElementById('motivo_devolucion').value;

            if (confirm("¿Procesar devolución?")) {

                try {
                    const res = await fetch('ajax/confirmar_devolucion.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            id_venta: parseInt(idVenta),
                            items: items,
                            motivo: motivo
                        })
                    });

                    const r = await res.json();

                    if (r.status === 'ok') {
                        alert(`Folio de devolución: ${r.folio}`);
                        window.open(`ticket.php?folio=${r.folio}&tipo=devolucion`, '_blank');
                        location.href = 'devoluciones.php';
                    } else {
                        alert(r.msg);
                    }

                } catch (e) {
                    alert("Error de conexión.");
                }
            }

        });
    }

});


// ======================================================
// TICKET
// ======================================================
document.addEventListener('DOMContentLoaded', function () {

    if (document.getElementById("codigoBarrasTicket") && window.JsBarcode) {

        const ticketContainer = document.querySelector('.ticket');
        const folio = ticketContainer ? ticketContainer.dataset.folio : '00000000';

        JsBarcode("#codigoBarrasTicket", folio.padStart(8, '0'), {
            format: "CODE128",
            width: 2,
            height: 40,
            displayValue: true
        });

        setTimeout(() => window.print(), 500);
    }

    document.querySelectorAll('.btn-print').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

});
