// ============================================================
// SERVICE WORKER | POS Offline-First (PWA)
// ============================================================

const CACHE_NAME = 'pos-libreria-v2';
const ASSETS_TO_CACHE = [
  './',
  'index.php',
  'dashboard.php',
  'ventas.php',
  'css/styles.css',
  'css/ticket.css',
  'js/main.js',
  'js/ventas.js',
  'js/offline_manager.js',
  'assets/img/logo-maria-de-letras_v2.svg',
  'assets/img/logo-maria-de-letras-ticket.png'
];

// 1. INSTALL: Pre-cache de assets esenciales
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('[SW] Pre-cacheando assets...');
        return cache.addAll(ASSETS_TO_CACHE);
      })
      .then(() => self.skipWaiting())
  );
});

// 2. ACTIVATE: Limpieza de caches antiguos
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => 
      Promise.all(
        keys.filter(key => key !== CACHE_NAME)
            .map(key => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

// 3. FETCH: Intercepta peticiones GET
self.addEventListener('fetch', event => {
  // Ignora todo lo que no sea GET
  if (event.request.method !== 'GET') return;

  // Ignora AJAX y endpoints de API (ajustar según tu proyecto)
  if (event.request.url.includes('ajax/') || event.request.url.includes('api/')) return;

  event.respondWith(
    fetch(event.request)
      .then(response => {
        // Si hay respuesta válida, actualizar cache
        if (response && response.status === 200) {
          const responseClone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, responseClone));
        }
        return response;
      })
      .catch(() => {
        // Fallback: buscar en cache
        return caches.match(event.request).then(cachedResp => {
          if (cachedResp) return cachedResp;
          // Si no hay cache, mostrar fallback básico (opcional)
          if (event.request.destination === 'document') return caches.match('./');
        });
      })
  );
});
