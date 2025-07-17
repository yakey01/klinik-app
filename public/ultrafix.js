// ULTRAFIX+: Aggressive cache clearing and Alpine.js protection
// This script will force clear all cached content AND protect against Alpine.js conflicts

(function() {
    'use strict';
    
    console.log('🧹 ULTRAFIX+: Starting aggressive cleanup with Alpine.js protection...');
    
    // CRITICAL: Block Alpine.js on dokter mobile app pages
    if (typeof window !== 'undefined' && window.location && 
        window.location.pathname.includes('/dokter/mobile-app')) {
        
        console.log('🛡️ ULTRAFIX+: Implementing Alpine.js protection for dokter mobile app...');
        
        // Set global isolation flag
        window.__DOKTERKU_ISOLATED__ = true;
        
        // Override document.body.classList globally for this page
        if (document && document.body) {
            const originalClassList = document.body.classList;
            const protectedClassList = {
                add: function() {
                    console.warn('🛡️ ULTRAFIX+: Blocked document.body.classList.add - using documentElement');
                    return document.documentElement.classList.add.apply(document.documentElement.classList, arguments);
                },
                remove: function() {
                    console.warn('🛡️ ULTRAFIX+: Blocked document.body.classList.remove - using documentElement');
                    return document.documentElement.classList.remove.apply(document.documentElement.classList, arguments);
                },
                toggle: function() {
                    console.warn('🛡️ ULTRAFIX+: Blocked document.body.classList.toggle - using documentElement');
                    return document.documentElement.classList.toggle.apply(document.documentElement.classList, arguments);
                },
                contains: function() {
                    return document.documentElement.classList.contains.apply(document.documentElement.classList, arguments);
                },
                length: originalClassList ? originalClassList.length : 0,
                item: originalClassList ? originalClassList.item.bind(originalClassList) : function() { return null; },
                toString: originalClassList ? originalClassList.toString.bind(originalClassList) : function() { return ''; }
            };
            
            // Replace classList with protected version
            Object.defineProperty(document.body, 'classList', {
                get: function() { return protectedClassList; },
                configurable: true,
                enumerable: true
            });
            
            console.log('🛡️ ULTRAFIX+: document.body.classList protection activated');
        }
        
        // Block Alpine.js events
        if (document && document.addEventListener) {
            const originalAddEventListener = document.addEventListener;
            document.addEventListener = function(type, listener, options) {
                if (type === 'alpine:init' && window.__DOKTERKU_ISOLATED__) {
                    console.log('🛡️ ULTRAFIX+: Blocked Alpine.js event listener on isolated page');
                    return;
                }
                return originalAddEventListener.call(this, type, listener, options);
            };
        }
    }
    
    async function ultraFixClearAll() {
        try {
            // 1. Unregister ALL service workers
            if ('serviceWorker' in navigator) {
                const registrations = await navigator.serviceWorker.getRegistrations();
                for (let registration of registrations) {
                    console.log('🧹 ULTRAFIX+: Unregistering service worker:', registration);
                    await registration.unregister();
                }
            }
            
            // 2. Clear ALL caches
            if ('caches' in window) {
                const cacheNames = await caches.keys();
                for (let cacheName of cacheNames) {
                    console.log('🧹 ULTRAFIX+: Deleting cache:', cacheName);
                    await caches.delete(cacheName);
                }
            }
            
            // 3. Clear localStorage (preserve theme)
            try {
                const theme = localStorage.getItem('theme');
                localStorage.clear();
                if (theme) {
                    localStorage.setItem('theme', theme);
                }
                console.log('🧹 ULTRAFIX+: localStorage cleared (theme preserved)');
            } catch (e) {
                console.log('🧹 ULTRAFIX+: localStorage clear failed (might be blocked)');
            }
            
            // 4. Clear sessionStorage
            try {
                sessionStorage.clear();
                console.log('🧹 ULTRAFIX+: sessionStorage cleared');
            } catch (e) {
                console.log('🧹 ULTRAFIX+: sessionStorage clear failed');
            }
            
            // 5. Don't auto-reload if Alpine.js protection is active
            if (window.__DOKTERKU_ISOLATED__) {
                console.log('🎯 ULTRAFIX+: All caches cleared with Alpine.js protection! Page ready.');
            } else {
                // Force reload with cache bypass for other pages
                console.log('🎯 ULTRAFIX+: All caches cleared! Forcing hard reload...');
                setTimeout(() => {
                    window.location.reload(true); // Hard reload
                }, 1000);
            }
            
        } catch (error) {
            console.error('🧹 ULTRAFIX+: Error during cache clearing:', error);
            // Still try to reload even if cache clearing failed (unless isolated)
            if (!window.__DOKTERKU_ISOLATED__) {
                setTimeout(() => {
                    window.location.reload(true);
                }, 2000);
            }
        }
    }
    
    // Execute immediately
    ultraFixClearAll();
    
})();