// Service Worker for Dokterku PWA (All Roles)
const CACHE_NAME = 'dokterku-v2.0.0';
const STATIC_CACHE_URLS = [
    '/paramedis',
    '/nonparamedis',
    '/manifest.json',
    '/images/icon-192x192.svg',
    '/css/app.css',
    '/api/v2/system/health',
    '/api/v2/locations/work-locations'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Caching static assets');
                return cache.addAll(STATIC_CACHE_URLS);
            })
            .catch(error => {
                console.error('Failed to cache static assets:', error);
            })
    );
    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Deleting old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension and other non-http requests
    if (!event.request.url.startsWith('http')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then(cachedResponse => {
                // Return cached version if available
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Try to fetch from network
                return fetch(event.request)
                    .then(response => {
                        // Cache successful responses
                        if (response && response.status === 200) {
                            const responseToCache = response.clone();
                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseToCache);
                                });
                        }
                        return response;
                    })
                    .catch(error => {
                        console.error('Fetch failed:', error);
                        
                        // Return offline page for navigation requests
                        if (event.request.mode === 'navigate') {
                            // Return appropriate offline page based on URL
                            if (event.request.url.includes('/nonparamedis')) {
                                return caches.match('/nonparamedis');
                            }
                            return caches.match('/paramedis');
                        }
                        
                        throw error;
                    });
            })
    );
});

// Push notification event
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/images/icon-192x192.svg',
            badge: '/images/icon-192x192.svg',
            data: data.data || {},
            actions: [
                {
                    action: 'view',
                    title: 'Lihat',
                    icon: '/images/icon-192x192.svg'
                }
            ]
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Notification click event
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'view') {
        event.waitUntil(
            clients.openWindow(event.notification.data.url || '/paramedis')
        );
    }
});

// Background sync for offline attendance
self.addEventListener('sync', event => {
    if (event.tag === 'attendance-sync') {
        event.waitUntil(syncOfflineAttendance());
    }
});

// Sync offline attendance data
async function syncOfflineAttendance() {
    try {
        const db = await openDB();
        const tx = db.transaction(['pendingAttendance'], 'readonly');
        const store = tx.objectStore('pendingAttendance');
        const pendingItems = await store.getAll();
        
        for (const item of pendingItems) {
            try {
                const response = await fetch(item.url, {
                    method: item.method,
                    headers: item.headers,
                    body: JSON.stringify(item.data)
                });
                
                if (response.ok) {
                    // Remove from pending items
                    const deleteTx = db.transaction(['pendingAttendance'], 'readwrite');
                    const deleteStore = deleteTx.objectStore('pendingAttendance');
                    await deleteStore.delete(item.id);
                    console.log('Synced offline attendance:', item.id);
                }
            } catch (error) {
                console.error('Failed to sync attendance:', error);
            }
        }
    } catch (error) {
        console.error('Background sync failed:', error);
    }
}

// Open IndexedDB
function openDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('DokterKuDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
        
        request.onupgradeneeded = event => {
            const db = event.target.result;
            
            // Create object stores
            if (!db.objectStoreNames.contains('pendingAttendance')) {
                const store = db.createObjectStore('pendingAttendance', { keyPath: 'id' });
                store.createIndex('timestamp', 'timestamp');
                store.createIndex('userId', 'userId');
            }
            
            if (!db.objectStoreNames.contains('cachedData')) {
                const store = db.createObjectStore('cachedData', { keyPath: 'key' });
                store.createIndex('timestamp', 'timestamp');
            }
        };
    });
}