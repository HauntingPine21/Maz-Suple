document.addEventListener("DOMContentLoaded", () => {
    const inputCodigo = document.getElementById("codigo");
    const tablaCarrito = document.getElementById("tabla-carrito");
    const totalDisplay = document.getElementById("total-display");
    const btnCobrar = document.getElementById("btn-cobrar");
    const btnBuscar = document.getElementById("btn-buscar");
    const btnCancelar = document.getElementById("btn-cancelar");

    let carritoActual = {};

    inputCodigo.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            const codigo = inputCodigo.value.trim();
            if (codigo) buscarProducto(codigo);
        }
    });

    if (btnBuscar) {
        btnBuscar.addEventListener('click', () => {
            const codigo = inputCodigo.value.trim();
            if (codigo) buscarProducto(codigo);
            else inputCodigo.focus();
        });
    }

    tablaCarrito.addEventListener('click', (e) => {
        if (e.target.classList.contains('btn-remover-item')) {
            e.preventDefault();
            const id = e.target.dataset.id;
            if (id && confirm('¿Desea quitar este producto del carrito?')) {
                removerDelCarrito(id);
            }
        }
    });

    if (btnCancelar) {
        btnCancelar.addEventListener('click', () => {
            cancelarVenta();
        });
    }

    async function cargarCarritoInicial() {
        if (!navigator.onLine) {
            carritoActual = {};
            renderizarCarrito();
            return;
        }
        try {
            const res = await fetch("ajax/carrito_get.php");
            if (!res.ok) return;
            const data = await res.json();
            carritoActual = data.carrito || {};
            renderizarCarrito();
        } catch (error) {
            console.error("Error al cargar carrito inicial:", error);
        }
    }

    async function buscarProducto(codigo) {
        try {
            const respuesta = await fetch(`ajax/buscar_producto.php?q=${codigo}`);
            if (!respuesta.ok) throw new Error("Error servidor");
            const productos = await respuesta.json();
            if (productos.length > 0) {
                agregarAlCarrito(productos[0]);
            } else {
                alert("Producto no encontrado.");
                inputCodigo.value = "";
                inputCodigo.focus();
            }
        } catch (error) {
            console.warn("Sin conexión: " + error.message);
            if (typeof buscarProductoOffline === 'function') {
                try {
                    const productoLocal = await buscarProductoOffline(codigo);
                    if (productoLocal) agregarAlCarrito({
                        id: productoLocal.id,
                        titulo: productoLocal.titulo,
                        precio_venta: productoLocal.precio_venta,
                        codigo: productoLocal.codigo,
                        stock: productoLocal.stock || 0
                    });
                    else alert("Producto no encontrado offline.");
                } catch (err) {
                    console.error("Error offline:", err);
                }
            } else {
                console.error("Falta función buscarProductoOffline");
            }
        }
    }

    async function agregarAlCarrito(producto) {
        // Validación de stock
        if (producto.stock <= 0) {
            alert("No hay stock disponible para " + producto.titulo);
            inputCodigo.value = "";
            inputCodigo.focus();
            return;
        }

        const guardarEnLocal = () => {
            const id = producto.id;
            if (carritoActual[id]) {
                if (carritoActual[id].cantidad + 1 > producto.stock) {
                    alert("No puedes agregar más. Stock máximo: " + producto.stock);
                    return;
                }
                carritoActual[id].cantidad++;
            } else {
                carritoActual[id] = {
                    id: producto.id,
                    titulo: producto.titulo,
                    precio: producto.precio_venta,
                    cantidad: 1,
                    codigo: producto.codigo,
                    stock: producto.stock
                };
            }
            renderizarCarrito();
            inputCodigo.value = "";
            inputCodigo.focus();
        };

        if (!navigator.onLine) { guardarEnLocal(); return; }

        const formData = new FormData();
        formData.append("id", producto.id);
        formData.append("titulo", producto.titulo);
        formData.append("precio", producto.precio_venta);

        try {
            const res = await fetch("ajax/carrito_add.php", { method: "POST", body: formData });
            if (!res.ok) throw new Error("Fallo servidor");
            const data = await res.json();
            if (data.status === "ok") {
                carritoActual = data.carrito;
                renderizarCarrito();
                inputCodigo.value = "";
                inputCodigo.focus();
            }
        } catch (error) {
            console.warn("Fallo conexión PHP. Guardando en local.");
            guardarEnLocal();
        }
    }

    async function removerDelCarrito(id) {
        const borrarLocal = () => {
            if (carritoActual[id]) delete carritoActual[id];
            renderizarCarrito();
            inputCodigo.focus();
        };
        if (!navigator.onLine) { borrarLocal(); return; }

        const formData = new FormData();
        formData.append("id", id);
        try {
            const res = await fetch("ajax/carrito_remove.php", { method: "POST", body: formData });
            if (!res.ok) throw new Error("Fallo servidor");
            const data = await res.json();
            if (data.status === "ok") {
                carritoActual = data.carrito;
                renderizarCarrito();
                inputCodigo.focus();
            }
        } catch (error) { borrarLocal(); }
    }

    async function cancelarVenta() {
        if (!confirm('¿Cancelar venta? Se vaciará el carrito.')) return;

        const borrarLocal = () => {
            carritoActual = {};
            renderizarCarrito();
            inputCodigo.focus();
        };
        if (!navigator.onLine) { borrarLocal(); return; }

        try {
            const res = await fetch("ajax/carrito_clear.php", { method: "POST" });
            if (!res.ok) throw new Error("Fallo servidor");
            const data = await res.json();
            if (data.status === 'ok') { carritoActual = {}; renderizarCarrito(); inputCodigo.focus(); }
        } catch (error) { borrarLocal(); }
    }

    function renderizarCarrito() {
        tablaCarrito.innerHTML = "";
        let total = 0;
        let hayItems = false;

        Object.values(carritoActual).forEach(item => {
            const subtotal = item.cantidad * parseFloat(item.precio);
            total += subtotal;

            const row = `
                <tr>
                    <td>${item.titulo}</td>
                    <td class="text-center col-10">${item.cantidad}</td>
                    <td class="text-right col-15">$${parseFloat(item.precio).toFixed(2)}</td>
                    <td class="text-right col-15">$${subtotal.toFixed(2)}</td>
                    <td class="text-center col-5">
                        <button class="btn-remover-item" data-id="${item.id}" title="Quitar del carrito" style="background:none; border:none; color:red; cursor:pointer; font-size: 1.2em;">&times;</button>
                    </td>
                </tr>
            `;
            tablaCarrito.innerHTML += row;
            hayItems = true;
        });

        if (!hayItems) tablaCarrito.innerHTML = '<tr><td colspan="5" style="text-align: center; color: #777;">Escanea un producto para comenzar...</td></tr>';

        if (totalDisplay) totalDisplay.innerText = `$${total.toFixed(2)}`;
    }

    if (btnCobrar) {
        btnCobrar.addEventListener("click", async () => {
            if (Object.keys(carritoActual).length === 0) { alert("El carrito está vacío"); return; }
            if (!confirm("¿Confirmar venta y generar ticket?")) return;

            const procesarVentaOffline = () => {
                const productosArray = Object.values(carritoActual);
                const totalVenta = productosArray.reduce((acc, item) => acc + (item.cantidad * parseFloat(item.precio)), 0);
                const folioUnico = "OFF-" + Date.now().toString().slice(-9);
                const datosVenta = { total: totalVenta.toFixed(2), productos: productosArray, folio: folioUnico };
                if (typeof guardarVentaOffline === 'function') { guardarVentaOffline(datosVenta); carritoActual = {}; renderizarCarrito(); }
                else alert("Error crítico: No se encontró la función offline.");
            };

            if (!navigator.onLine) { procesarVentaOffline(); return; }

            try {
                const res = await fetch("ajax/confirmar_venta.php", { method: "POST" });
                if (!res.ok) throw new Error("Fallo servidor");
                const data = await res.json();
                if (data.status === "ok") {
                    window.open(`ticket.php?folio=${data.folio}`, '_blank', 'width=400,height=600');
                    window.location.reload();
                } else { alert("Error del sistema: " + data.msg); }
            } catch (error) { console.warn("Fallo conexión con servidor (" + error.message + "). Guardando offline."); procesarVentaOffline(); }
        });
    }

    cargarCarritoInicial();
});
