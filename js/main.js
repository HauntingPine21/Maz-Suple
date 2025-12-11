document.addEventListener('DOMContentLoaded', function () {
    const inputProducto = document.getElementById('input-producto-compra');
    const btnAgregar = document.getElementById('btn-agregar-item');
    const tablaDetalle = document.getElementById('tabla-detalle-compra');
    const totalDisplay = document.getElementById('total-compra-display');
    const btnGuardar = document.getElementById('btn-guardar-compra');
    const selectProveedor = document.getElementById('proveedor');

    let itemsCompra = {};

    // --- Buscar y agregar producto ---
    btnAgregar.addEventListener('click', buscarYAgregarProducto);
    inputProducto.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); buscarYAgregarProducto(); }
    });

    async function buscarYAgregarProducto() {
        const query = inputProducto.value.trim();
        if (!query) return;

        try {
            const res = await fetch(`ajax/buscar_producto.php?q=${query}`);
            const productos = await res.json();

            if (productos.length > 0) {
                const producto = productos[0];
                if (!itemsCompra[producto.id]) {
                    itemsCompra[producto.id] = {
                        id_suplemento: producto.id,
                        nombre: producto.nombre || producto.titulo,
                        cantidad: 1,
                        costo: parseFloat(producto.precio_venta) || 0
                    };
                    renderizarTabla();
                }
                inputProducto.value = '';
            } else {
                alert('Producto no encontrado.');
            }
        } catch (err) {
            console.error(err);
            alert('Error al buscar producto');
        }
    }

    // --- Renderizar tabla ---
    function renderizarTabla() {
        tablaDetalle.innerHTML = '';
        if (Object.keys(itemsCompra).length === 0) {
            tablaDetalle.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Agrega productos para comenzar la orden</td></tr>';
            calcularTotal();
            return;
        }

        for (const id in itemsCompra) {
            const item = itemsCompra[id];
            const subtotal = (item.cantidad * item.costo).toFixed(2);
            tablaDetalle.innerHTML += `
                <tr data-id="${item.id_suplemento}">
                    <td>${item.nombre}</td>
                    <td>${item.id_suplemento}</td>
                    <td><input type="number" class="input-cantidad" value="${item.cantidad}" min="1"></td>
                    <td><input type="number" class="input-costo" value="${item.costo.toFixed(2)}" min="0" step="0.01"></td>
                    <td class="text-right subtotal-celda">$${subtotal}</td>
                    <td class="text-center"><button type="button" class="btn-remover">X</button></td>
                </tr>
            `;
        }
        calcularTotal();
        agregarListenersInputs();
    }

    function agregarListenersInputs() {
        tablaDetalle.querySelectorAll('tr').forEach(fila => {
            const id = fila.dataset.id;
            fila.querySelector('.input-cantidad').addEventListener('change', e => {
                itemsCompra[id].cantidad = parseInt(e.target.value) || 1;
                renderizarTabla();
            });
            fila.querySelector('.input-costo').addEventListener('change', e => {
                itemsCompra[id].costo = parseFloat(e.target.value.replace(',', '.')) || 0;
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

    // --- Guardar compra ---
    btnGuardar.addEventListener('click', async () => {
        if (!selectProveedor.value) { alert('Seleccione un proveedor'); return; }
        if (Object.keys(itemsCompra).length === 0) { alert('Agregue al menos un producto'); return; }

        if (!confirm('Confirma la creación de la orden de compra?')) return;

        const datosCompra = {
            proveedor: selectProveedor.value,
            items: Object.values(itemsCompra)
        };

        try {
            const res = await fetch('ajax/confirmar_compra.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datosCompra)
            });
            const resultado = await res.json();
            if (resultado.status === 'ok') {
                alert(`Compra registrada. Folio: ${resultado.folio}`);
                window.location.reload();
            } else {
                alert('Error: ' + resultado.msg);
            }
        } catch (err) {
            console.error(err);
            alert('Error de conexión');
        }
    });
});
                    