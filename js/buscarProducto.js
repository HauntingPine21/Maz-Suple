console.log("Sistema de Suplementos cargado");

document.addEventListener('DOMContentLoaded', function () {

    // ================================
    // MENÚ HAMBURGUESA
    // ================================
    const menuBtn = document.getElementById('mobile-menu-btn');
    const navbarMenu = document.getElementById('navbar-menu');

    if (menuBtn && navbarMenu) {
        menuBtn.addEventListener('click', e => {
            e.stopPropagation();
            navbarMenu.classList.toggle('active');
        });
    }

    // SUBMENÚS
    document.querySelectorAll('.dropbtn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                e.preventDefault();
                e.stopPropagation();
                const dropdownContent = this.nextElementSibling;
                const abierto = dropdownContent.classList.contains('show');
                document.querySelectorAll('.dropdown-content').forEach(c => c.classList.remove('show'));
                if (!abierto) dropdownContent.classList.add('show');
            }
        });
    });

    // Cerrar menú al click fuera
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (navbarMenu && !navbarMenu.contains(e.target) && e.target !== menuBtn) {
                navbarMenu.classList.remove('active');
                document.querySelectorAll('.dropdown-content').forEach(c => c.classList.remove('show'));
            }
        }
    });

    // ================================
    // COMPRAS
    // ================================
    const inputProducto = document.getElementById('input-producto-compra');
    const btnAgregar = document.getElementById('btn-agregar-item');
    const tablaDetalle = document.getElementById('tabla-detalle-compra');
    const totalDisplay = document.getElementById('total-compra-display');
    const btnGuardar = document.getElementById('btn-guardar-compra');
    const selectProveedor = document.getElementById('proveedor');

    if (!inputProducto || !btnAgregar || !tablaDetalle || !totalDisplay || !btnGuardar) return;

    let itemsCompra = {};

    // Evento agregar
    btnAgregar.addEventListener('click', buscarYAgregarProducto);
    inputProducto.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarYAgregarProducto();
        }
    });

    async function buscarYAgregarProducto() {
        const query = inputProducto.value.trim();
        if (!query) return;

        try {
            const response = await fetch(`ajax/buscar_producto.php?q=${encodeURIComponent(query)}`);
            const productos = await response.json();

            if (productos.length === 0) {
                alert("Producto no encontrado.");
                return;
            }

            const p = productos[0]; // Tomamos el primero
            if (!itemsCompra[p.id]) {
                itemsCompra[p.id] = {
                    id_producto: p.id,
                    nombre: p.nombre,
                    codigo: p.sku,
                    cantidad: 1,
                    costo: parseFloat(p.precio_venta),
                    stock: parseInt(p.stock)
                };
            }
            renderTabla();
            inputProducto.value = '';

        } catch (err) {
            console.error("Error al buscar producto:", err);
            alert("Error de conexión al buscar producto.");
        }
    }

    function renderTabla() {
        tablaDetalle.innerHTML = '';

        if (Object.keys(itemsCompra).length === 0) {
            tablaDetalle.innerHTML = `<tr>
                <td colspan="6" class="text-center text-muted">
                    Agrega suplementos para crear la orden
                </td>
            </tr>`;
            calcularTotal();
            return;
        }

        for (const id in itemsCompra) {
            const item = itemsCompra[id];
            const subtotal = (item.cantidad * item.costo).toFixed(2);

            tablaDetalle.innerHTML += `<tr data-id="${item.id_producto}">
                <td>${item.nombre}</td>
                <td>${item.codigo}</td>
                <td><input type="number" class="input-cantidad" value="${item.cantidad}" min="1" max="${item.stock}"></td>
                <td><input type="number" class="input-costo" value="${item.costo.toFixed(2)}" step="0.01"></td>
                <td class="subtotal-celda">$${subtotal}</td>
                <td class="text-center">
                    <button class="btn-remover btn-icon-remove">X</button>
                </td>
            </tr>`;
        }

        agregarEventosTabla();
        calcularTotal();
    }

    function agregarEventosTabla() {
        tablaDetalle.querySelectorAll('tr').forEach(fila => {
            const id = fila.dataset.id;

            fila.querySelector('.input-cantidad').addEventListener('change', e => {
                let val = parseInt(e.target.value) || 1;
                if (val > itemsCompra[id].stock) {
                    alert(`No hay suficiente stock. Máximo: ${itemsCompra[id].stock}`);
                    val = itemsCompra[id].stock;
                }
                itemsCompra[id].cantidad = val;
                renderTabla();
            });

            fila.querySelector('.input-costo').addEventListener('change', e => {
                itemsCompra[id].costo = parseFloat(e.target.value) || 0;
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
            total += itemsCompra[id].cantidad * itemsCompra[id].costo;
        }
        totalDisplay.textContent = `$${total.toFixed(2)}`;
    }

    btnGuardar.addEventListener('click', async () => {
        if (!selectProveedor.value) {
            alert("Debes seleccionar un proveedor.");
            return;
        }
        if (Object.keys(itemsCompra).length === 0) {
            alert("Agrega al menos un suplemento.");
            return;
        }

        if (!confirm("¿Registrar esta compra? El stock aumentará.")) return;

        const datosCompra = {
            id_proveedor: selectProveedor.value,
            items: Object.values(itemsCompra)
        };

        try {
            const response = await fetch('ajax/confirmar_compra.php', {
                method: 'POST',
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(datosCompra)
            });

            const r = await response.json();

            if (r.status === "ok") {
                alert(`Compra guardada. Folio ${r.folio}`);
                location.reload();
            } else {
                alert("Error: " + r.msg);
            }

        } catch (err) {
            console.error(err);
            alert("Error de conexión.");
        }
    });

});
