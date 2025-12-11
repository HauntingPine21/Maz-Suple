/*document.addEventListener('DOMContentLoaded', function () {
    const inputSuplemento = document.getElementById('input-producto-compra');
    const btnAgregar = document.getElementById('btn-agregar-item');
    const tablaDetalle = document.getElementById('tabla-detalle-compra');
    const totalDisplay = document.getElementById('total-compra-display');
    const btnGuardar = document.getElementById('btn-guardar-compra');
    const selectProveedor = document.getElementById('proveedor');

    let itemsCompra = {};

    btnAgregar.addEventListener('click', buscarYAgregarSuplemento);
    inputSuplemento.addEventListener('keypress', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscarYAgregarSuplemento();
        }
    });

    async function buscarYAgregarSuplemento() {
        const query = inputSuplemento.value.trim();
        if (!query) return;

        try {
            const res = await fetch(`ajax/buscar_producto.php?q=${encodeURIComponent(query)}`);
            const suplementos = await res.json();

            if (suplementos.length > 0) {
                const s = suplementos[0];
                const id = s.id.toString();
                if (!itemsCompra[id]) {
                    itemsCompra[id] = {
                        id_suplemento: parseInt(s.id),
                        nombre: s.nombre || "Sin nombre",
                        codigo: s.codigo || "",
                        cantidad: 1,
                        costo: parseFloat(s.precio_venta) || 0
                    };
                } else {
                    itemsCompra[id].cantidad += 1;
                }
                inputSuplemento.value = '';
                renderTabla();
            } else {
                alert('Suplemento no encontrado.');
            }
        } catch (err) {
            console.error('Error al buscar suplemento:', err);
            alert('Error al buscar suplemento.');
        }
    }

    function renderTabla() {
        tablaDetalle.innerHTML = '';
        const keys = Object.keys(itemsCompra);
        if (keys.length === 0) {
            tablaDetalle.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Agrega suplementos para crear la orden</td></tr>';
            calcularTotal();
            return;
        }

        keys.forEach(id => {
            const item = itemsCompra[id];
            const subtotal = (item.cantidad * item.costo).toFixed(2);
            tablaDetalle.innerHTML += `
                <tr data-id="${item.id_suplemento}">
                    <td>${item.nombre}</td>
                    <td>${item.codigo}</td>
                    <td><input type="number" class="input-cantidad" value="${item.cantidad}" min="1"></td>
                    <td><input type="number" class="input-costo" value="${item.costo.toFixed(2)}" step="0.01" min="0"></td>
                    <td class="subtotal-celda">$${subtotal}</td>
                    <td class="text-center"><button type="button" class="btn-remover btn-icon-remove">X</button></td>
                </tr>
            `;
        });
        agregarEventosTabla();
        calcularTotal();
    }

    function agregarEventosTabla() {
        tablaDetalle.querySelectorAll('tr').forEach(fila => {
            const id = fila.dataset.id;
            const inputCant = fila.querySelector('.input-cantidad');
            const inputCosto = fila.querySelector('.input-costo');
            const btnRemove = fila.querySelector('.btn-remover');

            if (inputCant) {
                inputCant.addEventListener('change', e => {
                    let val = parseInt(e.target.value);
                    if (isNaN(val) || val <= 0) val = 1;
                    itemsCompra[id].cantidad = val;
                    renderTabla();
                });
            }

            if (inputCosto) {
                inputCosto.addEventListener('change', e => {
                    let val = parseFloat(e.target.value);
                    if (isNaN(val) || val < 0) val = 0;
                    itemsCompra[id].costo = val;
                    renderTabla();
                });
            }

            if (btnRemove) {
                btnRemove.addEventListener('click', () => {
                    delete itemsCompra[id];
                    renderTabla();
                });
            }
        });
    }

    function calcularTotal() {
        let total = 0;
        Object.values(itemsCompra).forEach(item => {
            total += item.cantidad * item.costo;
        });
        totalDisplay.textContent = `$${total.toFixed(2)}`;
    }

    btnGuardar.addEventListener('click', async function () {
        if (!selectProveedor.value) return alert('Debes seleccionar un proveedor.');
        if (Object.keys(itemsCompra).length === 0) return alert('Agrega al menos un suplemento.');
        if (!confirm('¿Registrar esta compra? El stock aumentará.')) return;

        const datosCompra = {
            id_proveedor: parseInt(selectProveedor.value),
            items: Object.values(itemsCompra).map(item => ({
                id_suplemento: parseInt(item.id_suplemento),
                cantidad: parseInt(item.cantidad) || 1,
                costo: parseFloat(item.costo) || 0
            }))
        };

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
        } catch (err) {
            console.error(err);
            alert('Error de conexión.');
        }
    });
});*/
