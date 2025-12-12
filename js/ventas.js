document.addEventListener("DOMContentLoaded", () => {
    const inputCodigo = document.getElementById("codigo");
    const tablaCarrito = document.getElementById("tabla-carrito");
    const totalDisplay = document.getElementById("total-display");
    const btnCobrar = document.getElementById("btn-cobrar");
    const btnBuscar = document.getElementById("btn-buscar");
    const btnCancelar = document.getElementById("btn-cancelar");

    let carritoActual = {};

    // --- Renderizar carrito ---
    function renderizarCarrito() {
        tablaCarrito.innerHTML = "";
        let total = 0;
        let hayItems = false;

        Object.values(carritoActual).forEach(item => {
            const subtotal = item.cantidad * parseFloat(item.precio);
            total += subtotal;

            tablaCarrito.innerHTML += `
                <tr>
                    <td class="text-left col-0">${item.nombre}</td>
                    <td class="text-center col-10">${item.cantidad}</td>
                    <td class="text-right col-15">$${parseFloat(item.precio).toFixed(2)}</td>
                    <td class="text-right col-15">$${subtotal.toFixed(2)}</td>
                    <td class="text-center col-5">
                        <button class="btn-remover-item" data-id="${item.id}" title="Quitar del carrito" style="background:none; border:none; color:red; cursor:pointer; font-size: 1.2em;">&times;</button>
                    </td>
                </tr>
            `;
            hayItems = true;
        });

        if (!hayItems) {
            tablaCarrito.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#777;">Escanea o busca un producto para comenzar...</td></tr>';
        }

        totalDisplay.innerText = `$${total.toFixed(2)}`;
    }

    // --- Buscar producto ---
    async function buscarProducto(query) {
        if (!query) return;

        try {
            const res = await fetch(`ajax/buscar_producto.php?q=${encodeURIComponent(query)}`);
            const productos = await res.json(); // siempre un array

            if (productos.length > 0) {
                agregarAlCarrito(productos[0]);
            } else {
                alert("Producto no encontrado.");
            }
        } catch (err) {
            console.warn("Error de conexión:", err.message);
        }
    }

    // --- Agregar producto al carrito ---
    function agregarAlCarrito(producto) {
        if (!producto.stock || producto.stock <= 0) {
            alert("No hay stock disponible para: " + producto.nombre);
            inputCodigo.value = "";
            inputCodigo.focus();
            return;
        }

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
                nombre: producto.nombre,
                precio: parseFloat(producto.precio_venta),
                cantidad: 1,
                stock: producto.stock
            };
        }

        renderizarCarrito();
        inputCodigo.value = "";
        inputCodigo.focus();
    }

    // --- Remover producto ---
    function removerDelCarrito(id) {
        if (carritoActual[id]) delete carritoActual[id];
        renderizarCarrito();
        inputCodigo.focus();
    }

    // --- Cancelar venta ---
    function cancelarVenta() {
        if (!confirm("¿Cancelar venta? Se vaciará el carrito.")) return;
        carritoActual = {};
        renderizarCarrito();
        inputCodigo.focus();
    }

    // --- Confirmar venta ---
    async function confirmarVenta() {
        const itemsRaw = Object.values(carritoActual);

        if (itemsRaw.length === 0) { 
            alert("El carrito está vacío"); 
            return;
        }

        if (!confirm("¿Confirmar venta y generar ticket?")) return;

        // --- Mapear items al formato que PHP espera ---
        const items = itemsRaw.map(item => ({
            id_suplemento: item.id,
            nombre: item.nombre,
            cantidad: item.cantidad,
            precio: item.precio
        }));

        try {
            const res = await fetch("ajax/confirmar_venta.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ items })
            });
            const data = await res.json();

            if (data.status === "ok") {
                window.open(`ticket.php?folio=${data.folio}`, '_blank', 'width=400,height=600');
                carritoActual = {};
                renderizarCarrito();
            } else {
                alert("Error: " + data.msg);
            }
        } catch (err) {
            console.error("Error al confirmar venta:", err);
            alert("No se pudo procesar la venta. Intente de nuevo.");
        }
    }

    // --- Event listeners ---
    inputCodigo?.addEventListener("keypress", e => {
        if (e.key === "Enter") {
            e.preventDefault();
            buscarProducto(inputCodigo.value.trim());
        }
    });

    btnBuscar?.addEventListener("click", () => buscarProducto(inputCodigo.value.trim()));
    btnCancelar?.addEventListener("click", cancelarVenta);
    btnCobrar?.addEventListener("click", confirmarVenta);

    tablaCarrito.addEventListener("click", e => {
        if (e.target.classList.contains("btn-remover-item")) {
            const id = e.target.dataset.id;
            if (id && confirm("¿Desea quitar este producto del carrito?")) {
                removerDelCarrito(id);
            }
        }
    });

    renderizarCarrito();
});
