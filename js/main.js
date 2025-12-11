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
    const btnAgregar = document.getElementById('btn-agregar-item');
    const tablaDetalle = document.getElementById('tabla-detalle-compra');
    const totalDisplay = document.getElementById('total-compra-display');
    const btnGuardar = document.getElementById('btn-guardar-compra');
    const selectProveedor = document.getElementById('id_proveedor');

    let itemsCompra = {};

    btnAgregar.addEventListener('click', buscarYAgregarProducto);
    inputProducto.addEventListener('keypress', e => { if (e.key === 'Enter') { e.preventDefault(); buscarYAgregarProducto(); } });

    async function buscarYAgregarProducto() {
        const query = inputProducto.value.trim();
        if (!query) return;

        try {
            const res = await fetch(`ajax/buscar_producto.php?q=${encodeURIComponent(query)}`);
            const productos = await res.json();

            if (productos.length > 0) {
                const p = productos[0];
                if (!itemsCompra[p.id]) {
                    itemsCompra[p.id] = {
                        id_producto: p.id,
                        nombre: p.nombre,
                        codigo: p.sku || p.codigo,
                        cantidad: 1,
                        costo: parseFloat(p.precio_venta) || 0
                    };
                }
                renderTabla();
                inputProducto.value = '';
            } else {
                alert('Producto no encontrado.');
            }
        } catch (err) {
            console.error(err);
            alert('Error al buscar producto.');
        }
    }

    function renderTabla() {
        tablaDetalle.innerHTML = '';
        if (Object.keys(itemsCompra).length === 0) {
            tablaDetalle.innerHTML = `<tr><td colspan="6" class="text-center text-muted">Agrega suplementos para crear la orden</td></tr>`;
            calcularTotal();
            return;
        }

        for (const id in itemsCompra) {
            const item = itemsCompra[id];
            const subtotal = (item.cantidad * item.costo).toFixed(2);
            tablaDetalle.innerHTML += `
                <tr data-id="${item.id_producto}">
                    <td>${item.nombre}</td>
                    <td>${item.codigo}</td>
                    <td><input type="number" class="input-cantidad" value="${item.cantidad}" min="1"></td>
                    <td><input type="number" class="input-costo" value="${item.costo.toFixed(2)}" step="0.01"></td>
                    <td class="subtotal-celda">$${subtotal}</td>
                    <td class="text-center"><button class="btn-remover btn-icon-remove">X</button></td>
                </tr>`;
        }

        agregarEventosTabla();
        calcularTotal();
    }

    function agregarEventosTabla() {
        tablaDetalle.querySelectorAll('tr').forEach(fila => {
            const id = fila.dataset.id;
            fila.querySelector('.input-cantidad').addEventListener('change', e => { itemsCompra[id].cantidad = parseInt(e.target.value) || 1; renderTabla(); });
            fila.querySelector('.input-costo').addEventListener('change', e => { itemsCompra[id].costo = parseFloat(e.target.value) || 0; renderTabla(); });
            fila.querySelector('.btn-remover').addEventListener('click', () => { delete itemsCompra[id]; renderTabla(); });
        });
    }

    function calcularTotal() {
        let total = 0;
        for (const id in itemsCompra) total += itemsCompra[id].cantidad * itemsCompra[id].costo;
        totalDisplay.textContent = `$${total.toFixed(2)}`;
    }

    btnGuardar.addEventListener('click', async function () {
        if (!selectProveedor.value) return alert('Debes seleccionar un proveedor.');
        if (Object.keys(itemsCompra).length === 0) return alert('Agrega al menos un suplemento.');
        if (!confirm('¿Registrar esta compra? El stock aumentará.')) return;

        const datosCompra = { id_proveedor: selectProveedor.value, items: Object.values(itemsCompra) };
        try {
            const res = await fetch('ajax/confirmar_compra.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datosCompra)
            });
            const r = await res.json();
            if (r.status === 'ok') {
                alert(`Compra guardada. Folio ${r.folio}`);
                location.reload();
            } else {
                alert('Error: ' + r.msg);
            }
        } catch (err) { console.error(err); alert('Error de conexión.'); }
    });
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
