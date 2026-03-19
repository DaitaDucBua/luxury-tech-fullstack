/**
 * Service Worker for LuxuryTech PWA
 */

const CACHE_NAME = 'luxurytech-v1';
const RUNTIME_CACHE = 'luxurytech-runtime';

// Files to cache on install
const STATIC_CACHE_URLS = [
    '/LUXURYTECH/',
    '/LUXURYTECH/index.php',
    '/LUXURYTECH/products.php',
    '/LUXURYTECH/cart.php',
    '/LUXURYTECH/assets/css/style.css',
    '/LUXURYTECH/assets/js/main.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'https://code.jquery.com/jquery-3.7.0.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    console.log('[Service Worker] Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('[Service Worker] Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .then(() => self.skipWaiting())
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('[Service Worker] Activating...');
    
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE) {
                        console.log('[Service Worker] Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Skip cross-origin requests
    if (url.origin !== location.origin && !url.href.includes('cdn.jsdelivr.net') && !url.href.includes('cdnjs.cloudflare.com')) {
        return;
    }
    
    // Network first for API calls and admin pages (no cache)
    if (request.url.includes('/ajax/') || request.url.includes('/admin/') || request.method !== 'GET') {
        event.respondWith(networkFirst(request));
        return;
    }
    
    // Cache first for static assets
    event.respondWith(cacheFirst(request));
});

// Cache first strategy
async function cacheFirst(request) {
    const cache = await caches.open(CACHE_NAME);
    const cached = await cache.match(request);
    
    if (cached) {
        return cached;
    }
    
    try {
        const response = await fetch(request);
        
        if (response.ok) {
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.error('[Service Worker] Fetch failed:', error);
        
        // Return offline page if available
        return cache.match('/LUXURYTECH/offline.html');
    }
}

// Network first strategy
async function networkFirst(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    
    try {
        const response = await fetch(request);
        
        // Only cache GET requests
        if (response.ok && request.method === 'GET') {
            cache.put(request, response.clone());
        }
        
        return response;
    } catch (error) {
        console.error('[Service Worker] Network request failed:', error);
        
        const cached = await cache.match(request);
        
        if (cached) {
            return cached;
        }
        
        throw error;
    }
}

// Push notification event
self.addEventListener('push', event => {
    console.log('[Service Worker] Push received');
    
    let data = {
        title: 'LuxuryTech',
        body: 'Bạn có thông báo mới!',
        icon: '/LUXURYTECH/assets/images/icons/icon-192x192.png',
        badge: '/LUXURYTECH/assets/images/icons/icon-72x72.png'
    };
    
    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }
    
    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        vibrate: [200, 100, 200],
        data: {
            url: data.url || '/LUXURYTECH/'
        }
    };
    
    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', event => {
    console.log('[Service Worker] Notification clicked');
    
    event.notification.close();
    
    const url = event.notification.data.url || '/LUXURYTECH/';
    
    event.waitUntil(
        clients.openWindow(url)
    );
});

