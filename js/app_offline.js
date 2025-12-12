// ==========================
// IndexedDB para ventas offline
// ==========================
let db;
const request = indexedDB.open("maz_suple_db", 1);

request.onupgradeneeded = (e) => {
    db = e.target.result;
    db.createObjectStore("ventas_pendientes", { autoIncrement: true });
};

request.onsuccess = (e) => {
    db = e.target.result;
};

// ==========================
// Guardar venta local
// ==========================
function guardarVentaOffline(venta) {
    const tx = db.transaction("ventas_pendientes", "readwrite");
    tx.objectStore("ventas_pendientes").add(venta);
}

// ==========================
// Enviar ventas pendientes
// ==========================
async function sincronizarVentas() {
    const tx = db.transaction("ventas_pendientes", "readonly");
    const store = tx.objectStore("ventas_pendientes");

    const ventas = await store.getAll();

    if (ventas.length === 0) {
        alert("No hay ventas pendientes.");
        return;
    }

    let ok = await fetch("/ajax/sync_ventas.php", {
        method: "POST",
        body: JSON.stringify(ventas),
        headers: { "Content-Type": "application/json" }
    });

    if (ok.status === 200) {
        // limpiar cola
        const del = db.transaction("ventas_pendientes", "readwrite");
        del.objectStore("ventas_pendientes").clear();
        alert("Ventas sincronizadas");
    }
}
