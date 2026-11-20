/**
 * Citadel Todo App - Service Worker [E05: The Offline Abyss]
 * 
 * Features:
 * 1. App Shell Pre-caching & Offline Fallback (offline.html)
 * 2. Granular Caching Strategies per Resource:
 *    - HTML / Navigation: Network-First with Cache Fallback -> Offline Page Fallback
 *    - Static App Shell (CSS, JS, Manifest, Icons): Cache-First with Network Fallback
 *    - Fonts & Third-Party CDNs: Stale-While-Revalidate
 *    - Form Actions & Mutations (POST): Network-Only
 * 3. Notification Handling & Background Communication
 */

const CACHE_VERSION = 'v1';
const STATIC_CACHE = `citadel-static-${CACHE_VERSION}`;
const PAGES_CACHE = `citadel-pages-${CACHE_VERSION}`;
const RUNTIME_CACHE = `citadel-runtime-${CACHE_VERSION}`;

// Daftar App Shell & aset penting yang di-precache saat instalasi
const PRECACHE_ASSETS = [
    './',
    'index.php',
    'index.php?view=personal',
    'index.php?view=shared',
    'style.css',
    'script.js',
    'manifest.json',
    'offline.html',
    'icons/icon-192x192.png',
    'icons/icon-512x512.png',
    'icons/icon-maskable-192x192.png',
    'icons/icon-maskable-512x512.png',
    'icons/icon.svg'
];

// =========================================================================
// 1. INSTALL EVENT: Pre-cache App Shell & Offline Page
// =========================================================================
self.addEventListener('install', (event) => {
    console.log('[Service Worker] Memulai instalasi & pre-caching App Shell...');
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[Service Worker] Menyimpan App Shell ke Cache:', STATIC_CACHE);
                // Menggunakan addAll untuk precache aset inti
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                console.log('[Service Worker] Semua aset inti berhasil di-cache.');
                return self.skipWaiting();
            })
            .catch((err) => {
                console.warn('[Service Worker] Peringatan saat pre-caching (akan dilanjutkan):', err);
                return self.skipWaiting();
            })
    );
});

// =========================================================================
// 2. ACTIVATE EVENT: Pembersihan Cache Lama & Claim Clients
// =========================================================================
self.addEventListener('activate', (event) => {
    console.log('[Service Worker] Diaktifkan.');
    const activeCaches = [STATIC_CACHE, PAGES_CACHE, RUNTIME_CACHE];

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName.startsWith('citadel-') && !activeCaches.includes(cacheName)) {
                        console.log('[Service Worker] Menghapus cache usang:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// =========================================================================
// 3. FETCH EVENT: Strategi Caching Per Resource
// =========================================================================
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    // Strategi 0: Mutasi / Form Submit / POST (actions.php, export.php) -> Network-Only
    if (request.method !== 'GET') {
        event.respondWith(
            fetch(request).catch(() => {
                return new Response(
                    JSON.stringify({
                        error: true,
                        message: 'Anda sedang offline. Perubahan data memerlukan koneksi jaringan aktif.'
                    }),
                    {
                        status: 503,
                        statusText: 'Service Unavailable (Offline)',
                        headers: { 'Content-Type': 'application/json' }
                    }
                );
            })
        );
        return;
    }

    // Strategi 1: Navigasi Halaman HTML (mode: 'navigate' atau Accept: text/html)
    // -> Network-First dengan Cache Fallback, dan Fallback Akhir ke offline.html
    const isHtmlNavigation = request.mode === 'navigate' || 
        (request.method === 'GET' && request.headers.get('accept') && request.headers.get('accept').includes('text/html'));

    if (isHtmlNavigation) {
        event.respondWith(
            fetch(request)
                .then((networkResponse) => {
                    // Jika jaringan berhasil, simpan salinan halaman ke PAGES_CACHE
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(PAGES_CACHE).then((cache) => cache.put(request, responseClone));
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    console.log('[Service Worker] Jaringan terputus, mencari di Cache untuk:', request.url);
                    // Coba cari halaman yang diminta dari cache
                    const cachedResponse = await caches.match(request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    // Coba match dengan URL normalisasi (tanpa query string atau index.php)
                    const fallbackIndex = await caches.match('index.php');
                    if (fallbackIndex) {
                        return fallbackIndex;
                    }
                    // Jika halaman spesifik tidak ada, sajikan offline.html
                    console.log('[Service Worker] Menyajikan offline.html sebagai fallback');
                    const offlinePage = await caches.match('offline.html');
                    return offlinePage || new Response('Aplikasi sedang offline.', {
                        headers: { 'Content-Type': 'text/html; charset=utf-8' }
                    });
                })
        );
        return;
    }

    // Strategi 2: External CDNs & Google Fonts -> Stale-While-Revalidate
    if (url.origin.includes('fonts.googleapis.com') || 
        url.origin.includes('fonts.gstatic.com') || 
        url.origin.includes('cdn-icons-png.flaticon.com')) {
        event.respondWith(
            caches.open(RUNTIME_CACHE).then(async (cache) => {
                const cachedResponse = await cache.match(request);
                const fetchPromise = fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        cache.put(request, networkResponse.clone());
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Strategi 3: Static App Shell (CSS, JS, Icons, Images, Manifest) -> Cache-First dengan Network Fallback
    const isStaticAsset = url.pathname.endsWith('.css') || 
                          url.pathname.endsWith('.js') || 
                          url.pathname.endsWith('.json') || 
                          url.pathname.endsWith('.png') || 
                          url.pathname.endsWith('.svg') || 
                          url.pathname.endsWith('.ico') ||
                          url.pathname.endsWith('.woff2');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                // Jika belum ada di cache, ambil dari network dan simpan di cache
                return fetch(request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(STATIC_CACHE).then((cache) => cache.put(request, responseClone));
                    }
                    return networkResponse;
                }).catch((err) => {
                    console.warn('[Service Worker] Gagal mengambil aset statis offline:', request.url, err);
                });
            })
        );
        return;
    }

    // Default Fallback: Network dengan Cache Fallback
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});

// =========================================================================
// 4. NOTIFICATION & MESSAGE EVENTS
// =========================================================================
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            if (windowClients.length > 0) {
                windowClients[0].focus();
            } else {
                clients.openWindow('./index.php');
            }
        })
    );
});

// Pesan komunikasi dua arah dari script.js jika diperlukan (misal skipWaiting)
self.addEventListener('message', (event) => {
    if (event.data && event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
});
