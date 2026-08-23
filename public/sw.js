/* Bump this on every release that changes cached assets. The activate handler
   deletes every cache whose name does not match, so raising the number is what
   forces an installed phone to let go of the previous build. */
var CACHE_NAME = 'carpoolhub-v4';

var STATIC_ASSETS = [
    '/offline.html',
    '/assets/branding/icon-192.png',
    '/assets/branding/icon.png',
];

/* Install — pre-cache static assets */
self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

/* Activate — remove old caches */
self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

/* Fetch — network first, fall back to offline page for navigation */
self.addEventListener('fetch', function (event) {
    var request = event.request;

    /* Only handle GET requests */
    if (request.method !== 'GET') return;

    /* Static assets (CSS, JS, fonts, images) — cache first */
    if (
        request.destination === 'style' ||
        request.destination === 'script' ||
        request.destination === 'font' ||
        request.destination === 'image'
    ) {
        event.respondWith(
            caches.match(request).then(function (cached) {
                if (cached) return cached;
                return fetch(request).then(function (response) {
                    if (response.ok) {
                        var clone = response.clone();
                        caches.open(CACHE_NAME).then(function (cache) {
                            cache.put(request, clone);
                        });
                    }
                    return response;
                });
            })
        );
        return;
    }

    /* Navigation (HTML pages) — network first, offline fallback */
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(function () {
                return caches.match('/offline.html');
            })
        );
    }
});

/* ── Web Push: receive push and show notification ── */
self.addEventListener('push', function (event) {
    if (!event.data) return;

    var data = {};
    try { data = event.data.json(); } catch (e) { return; }

    var iconMap = {
        trip:       '🚗',
        payment:    '💰',
        connection: '👥',
        system:     '⚙️',
        route:      '🗺️',
    };

    event.waitUntil(
        self.registration.showNotification(data.title || 'CarpoolHub', {
            body:    data.message || '',
            icon:    '/assets/branding/icon-192.png',
            badge:   '/assets/branding/icon-192.png',
            tag:     'carpoolhub-' + (data.type || 'system'),
            renotify: true,
            data:    { url: data.url || '/' },
            vibrate: [200, 100, 200],
        })
    );
});

/* ── Web Push: handle notification click ── */
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/home';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            /* If app is already open, focus it and navigate */
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if ('focus' in client) {
                    client.focus();
                    client.navigate(targetUrl);
                    return;
                }
            }
            /* Otherwise open a new window */
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
