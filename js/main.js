// RESPONSABLE: Rol 2 (Front)
// Sistema Front adaptado para Suplementos
console.log("Sistema de Suplementos cargado correctamente");

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

            if (window.innerWidth <= 768) {
                e.preventDefault();
                e.stopPropagation();

                const dropdownContent = this.nextElementSibling;

                const yaEstabaAbierto = dropdownContent.classList.contains('show');

                document.querySelectorAll('.dropdown-content').forEach(content => {
                    content.classList.remove('show');
                });

                if (!yaEstabaAbierto) {
                    dropdownContent.classList.add('show');
                }
            }
        });
    });

    // ==========================================
    // CERRAR AL HACER CLIC AFUERA
    // ==========================================
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 768) {

            if (navbarMenu && !navbarMenu.contains(e.target) && e.target !== menuBtn) {

                navbarMenu.classList.remove('active');

                document.querySelectorAll('.dropdown-content').forEach(c => c.classList.remove('show'));
            }
        }
    });

    // ==========================================
    // CONFIRM ACTIONS
    // ==========================================
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-confirm-action');
        if (btn) {
            const msg = btn.dataset.confirmMessage || '¿Estás seguro?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        }
    });

});


// ==========================================
// LÓGICA DE COMPRAS (compras.php)
// ==========================================
document.addEventListener('DOMContentLoaded', function () {

    const inputProducto = document.getElementById('input-producto-compra');

    if (inputProducto) {

        const btnAgregar = document.getElementById('btn-agregar-item');
        const tablaDetalle = document.getElementById('tabla-detalle-compra');
        const totalDisplay = document.getElementById('total-compra-display');
        const btnGuardar = document.getElementById('btn-guardar-compra');
        const selectProveedor = document.getElementById('proveedor');

        let itemsCompra = {};

        btnAgregar.addEventListener('click', buscarYAgregarSuplemento);

        inputProducto.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarYAgregarSuplemento();
            }
        });

        async function buscarYAgregarSuplemento() {
            const query = inputProducto.value.trim();
            if (!query) return;

            try {
                const response = await fetch(`ajax/buscar_suplemento.php?q=${query}`);
                const suplementos = await response.json();

                if (suplementos.length > 0) {

                    const suplemento = suplementos[0];

                    if (!itemsCompra[suplemento.id_suplemento]) {

                        itemsCompra[suplemento.id_suplemento] = {
                            id_suplemento: suplemento.id_suplemento,
                            nombre: suplemento.nombre,
                            codigo_barras: suplemento.codigo_barras,
                            cantidad: 1,
                            costo: 0.00
                        };

                        renderizarTabla();
                    }

                    inputProducto.value = '';
                } else {
                    alert("Suplemento no encontrado.");
                }

            } catch (error) {
                console.error("Error al buscar suplemento:", error);
            }
        }


        function renderizarTabla() {

            tablaDetalle.innerHTML = '';

            if (Object.keys(itemsCompra).length === 0) {
                tablaDetalle.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted">
                            Agrega suplementos para comenzar la compra
                        </td>
                    </tr>`;
                calcularTotal();
                return;
            }

            for (const id in itemsCompra) {
                const item = itemsCompra[id];
                const subtotal = (item.cantidad * item.costo).toFixed(2);

                const fila = `
                    <tr data-id="${item.id_suplemento}">
                        <td>${item.nombre}</td>
                        <td>${item.codigo_barras}</td>
                        <td>
                            <input type="number" class="input-cantidad input-qty-small" value="${item.cantidad}" min="1">
                        </td>
                        <td>
                            <input type="number" class="input-costo input-cost-small" value="${item.costo.toFixed(2)}" step="0.01" min="0">
                        </td>
                        <td class="text-right">$${subtotal}</td>
                        <td class="text-center">
                            <button type="button" class="btn-remover btn-icon-remove">X</button>
                        </td>
                    </tr>
                `;
                tablaDetalle.innerHTML += fila;
            }

            calcularTotal();
            agregarListenersInputs();
        }

        function agregarListenersInputs() {
            tablaDetalle.querySelectorAll('tr').forEach(fila => {

                const id = fila.dataset.id;

                fila.querySelector('.input-cantidad').addEventListener('change', (e) => {
                    itemsCompra[id].cantidad = parseInt(e.target.value) || 1;
                    renderizarTabla();
                });

                fila.querySelector('.input-costo').addEventListener('change', (e) => {
                    itemsCompra[id].costo = parseFloat(e.target.value.replace(',', '.')) || 0.00;
                    renderizarTabla();
                });

                fila.querySelector('.btn-remover').addEventListener('click', () => {
                    delete itemsCompra[id];
                    renderizarTabla();
                });

            });
        }

        function calcularTotal() {
            let total = 0;
            for (const id in itemsCompra) {
                total += itemsCompra[id].cantidad * itemsCompra[id].costo;
            }
            totalDisplay.textContent = `$${total.toFixed(2)}`;
        }

        // GUARDAR COMPRA
        btnGuardar.addEventListener('click', async function () {

            if (!selectProveedor.value) {
                alert("Selecciona un proveedor.");
                return;
            }

            if (Object.keys(itemsCompra).length === 0) {
                alert("Debe agregar al menos un suplemento.");
                return;
            }

            if (confirm("¿Confirmas la compra? El stock aumentará.")) {

                const datos = {
                    proveedor: selectProveedor.value,
                    items: Object.values(itemsCompra)
                };

                try {
                    const response = await fetch("ajax/confirmar_compra.php", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify(datos)
                    });

                    const resultado = await response.json();

                    if (resultado.status === 'ok') {
                        alert(`Compra registrada. Folio: ${resultado.folio}`);
                        window.location.reload();
                    } else {
                        alert("Error: " + resultado.msg);
                    }

                } catch (error) {
                    alert("Error al guardar la compra.");
                }
            }

        });

    }

});


// ==========================================
// LÓGICA DE TICKET (ticket.php)
// ==========================================
document.addEventListener('DOMContentLoaded', function () {

    if (document.getElementById("codigoBarrasTicket") && window.JsBarcode) {

        const ticketContainer = document.querySelector(".ticket");
        const folio = ticketContainer ? ticketContainer.dataset.folio : "00000000";

        JsBarcode("#codigoBarrasTicket", folio.padStart(8, '0'), {
            format: "CODE128",
            lineColor: "#000",
            width: 2,
            height: 40,
            displayValue: true,
            fontSize: 14,
            margin: 5
        });

        setTimeout(() => window.print(), 500);
    }

    const btnClose = document.querySelector('.btn-close-window');
    if (btnClose) btnClose.addEventListener('click', () => window.close());

    document.querySelectorAll('.btn-print').forEach(btn => {
        btn.addEventListener('click', () => window.print());
    });

});
