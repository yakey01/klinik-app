// Service Worker for Dokterku Mobile App
const CACHE_NAME = 'dokterku-mobile-v1.0.0';
const OFFLINE_URL = '/offline.html';

// Critical resources to cache
const CORE_CACHE_RESOURCES = [
  '/',
  '/offline.html',
  '/css/app.css',
  '/js/app.js',
  '/images/icons/icon-192x192.png',
  '/images/icons/icon-512x512.png'
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
  '/paramedis/web-api/dashboard-stats',
  '/paramedis/web-api/jaspel/summary',
  '/paramedis/web-api/schedules/upcoming'
];

// Network-first strategy for API calls
const NETWORK_FIRST_PATTERNS = [
  '/api/',
  '/paramedis/api/'
];

// Cache-first strategy for static assets
const CACHE_FIRST_PATTERNS = [
  '/css/',
  '/js/',
  '/images/',
  '/fonts/'
];

// Install event - cache core resources
self.addEventListener('install', (event) => {
  console.log('🔧 Service Worker installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('📦 Caching core resources');
        return cache.addAll(CORE_CACHE_RESOURCES);
      })
      .then(() => {
        console.log('✅ Core resources cached successfully');
        return self.skipWaiting();
      })
      .catch((error) => {
        console.error('❌ Failed to cache core resources:', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
  console.log('🚀 Service Worker activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_NAME) {
              console.log('🗑️ Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('✅ Service Worker activated');
        return self.clients.claim();
      })
  );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }
  
  // Skip chrome-extension and other non-http(s) requests
  if (!url.protocol.startsWith('http')) {
    return;
  }
  
  // Network-first strategy for API calls
  if (NETWORK_FIRST_PATTERNS.some(pattern => url.pathname.startsWith(pattern))) {
    event.respondWith(
      networkFirstStrategy(request)
    );
    return;
  }
  
  // Cache-first strategy for static assets
  if (CACHE_FIRST_PATTERNS.some(pattern => url.pathname.startsWith(pattern))) {
    event.respondWith(
      cacheFirstStrategy(request)
    );
    return;
  }
  
  // Stale-while-revalidate for dashboard pages
  if (url.pathname.startsWith('/paramedis') || url.pathname === '/') {
    event.respondWith(
      staleWhileRevalidateStrategy(request)
    );
    return;
  }
  
  // Default network-first for everything else
  event.respondWith(
    networkFirstStrategy(request)
  );
});

// Network-first strategy with fallback to cache
async function networkFirstStrategy(request) {
  const cache = await caches.open(CACHE_NAME);
  
  try {
    // Try network first
    const networkResponse = await fetch(request.clone());
    
    // Cache successful responses
    if (networkResponse.ok) {
      // Only cache GET requests and successful responses
      if (request.method === 'GET') {
        cache.put(request, networkResponse.clone());
      }
    }
    
    return networkResponse;
  } catch (error) {
    console.log('🌐 Network failed, trying cache for:', request.url);
    
    // Try cache as fallback
    const cachedResponse = await cache.match(request);
    if (cachedResponse) {
      console.log('📦 Served from cache:', request.url);
      return cachedResponse;
    }
    
    // If it's a navigation request and we have no cache, show offline page
    if (request.mode === 'navigate') {
      console.log('📄 Serving offline page');
      return cache.match(OFFLINE_URL);
    }
    
    // For other requests, throw the error
    throw error;
  }
}

// Cache-first strategy with network fallback
async function cacheFirstStrategy(request) {
  const cache = await caches.open(CACHE_NAME);
  
  // Try cache first
  const cachedResponse = await cache.match(request);
  if (cachedResponse) {
    console.log('📦 Served from cache:', request.url);
    return cachedResponse;
  }
  
  try {
    // Fallback to network
    const networkResponse = await fetch(request);
    
    // Cache the response if successful
    if (networkResponse.ok) {
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.error('❌ Failed to fetch from network:', request.url, error);
    throw error;
  }
}

// Stale-while-revalidate strategy
async function staleWhileRevalidateStrategy(request) {
  const cache = await caches.open(CACHE_NAME);
  
  // Get from cache immediately
  const cachedResponse = await cache.match(request);
  
  // Fetch from network in background to update cache
  const networkResponsePromise = fetch(request)
    .then((networkResponse) => {
      if (networkResponse.ok) {
        cache.put(request, networkResponse.clone());
      }
      return networkResponse;
    })
    .catch((error) => {
      console.log('🌐 Background update failed:', request.url, error);
    });
  
  // Return cached response immediately if available, otherwise wait for network
  if (cachedResponse) {
    console.log('📦 Served from cache (updating in background):', request.url);
    return cachedResponse;
  }
  
  console.log('🌐 No cache, waiting for network:', request.url);
  return networkResponsePromise;
}

// Background sync for failed API requests
self.addEventListener('sync', (event) => {
  console.log('🔄 Background sync triggered:', event.tag);
  
  if (event.tag === 'dashboard-sync') {
    event.waitUntil(syncDashboardData());
  }
});

// Sync dashboard data when connectivity is restored
async function syncDashboardData() {
  try {
    console.log('🔄 Syncing dashboard data...');
    
    // Attempt to fetch latest dashboard data
    const dashboardResponse = await fetch('/paramedis/web-api/dashboard-stats');
    if (dashboardResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put('/paramedis/web-api/dashboard-stats', dashboardResponse.clone());
      console.log('✅ Dashboard data synced successfully');
    }
  } catch (error) {
    console.error('❌ Failed to sync dashboard data:', error);
  }
}

// Push notification handling
self.addEventListener('push', (event) => {
  console.log('🔔 Push notification received');
  
  const options = {
    body: event.data ? event.data.text() : 'Ada update baru di dashboard Anda',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/icon-96x96.png',
    vibrate: [200, 100, 200],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'open-dashboard',
        title: 'Buka Dashboard',
        icon: '/images/icons/dashboard-24x24.png'
      },
      {
        action: 'dismiss',
        title: 'Tutup',
        icon: '/images/icons/close-24x24.png'
      }
    ]
  };
  
  event.waitUntil(
    self.registration.showNotification('Dokterku Mobile', options)
  );
});

// Notification click handling
self.addEventListener('notificationclick', (event) => {
  console.log('🔔 Notification clicked:', event.action);
  
  event.notification.close();
  
  if (event.action === 'open-dashboard') {
    event.waitUntil(
      clients.openWindow('/paramedis/dashboard')
    );
  }
});

// Message handling from main thread
self.addEventListener('message', (event) => {
  console.log('📧 Message received:', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'GET_VERSION') {
    event.ports[0].postMessage({ version: CACHE_NAME });
  }
});

console.log('🚀 Service Worker loaded successfully');