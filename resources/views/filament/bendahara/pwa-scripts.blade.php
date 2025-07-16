{{-- PWA Service Worker Registration and App Install Scripts --}}
<script>
/**
 * Bendahara PWA Initialization
 * Handles service worker registration, app installation, and offline functionality
 */

class BendaharaPWA {
    constructor() {
        this.deferredPrompt = null;
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches;
        this.isInstalled = this.isStandalone || window.navigator.standalone;
        
        this.init();
    }
    
    async init() {
        await this.registerServiceWorker();
        this.setupAppInstallPrompt();
        this.setupOfflineHandling();
        this.setupMobileOptimizations();
        this.setupPerformanceMonitoring();
        
        console.log('🏦 Bendahara PWA initialized successfully');
    }
    
    /**
     * Register Service Worker for offline functionality
     */
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/pwa/service-worker.js', {
                    scope: '/bendahara'
                });
                
                console.log('📱 Service Worker registered:', registration.scope);
                
                // Handle service worker updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateAvailable();
                        }
                    });
                });
                
            } catch (error) {
                console.error('❌ Service Worker registration failed:', error);
            }
        }
    }
    
    /**
     * Setup app installation prompt
     */
    setupAppInstallPrompt() {
        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallBanner();
        });
        
        // Listen for app installed event
        window.addEventListener('appinstalled', () => {
            console.log('📱 Bendahara app installed successfully');
            this.hideInstallBanner();
            this.trackEvent('pwa_installed');
        });
    }
    
    /**
     * Show app install banner
     */
    showInstallBanner() {
        if (this.isInstalled) return;
        
        const banner = this.createInstallBanner();
        document.body.appendChild(banner);
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            this.hideInstallBanner();
        }, 10000);
    }
    
    /**
     * Create install banner element
     */
    createInstallBanner() {
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'fixed bottom-4 left-4 right-4 bg-amber-500 text-white p-4 rounded-lg shadow-lg z-50 flex items-center justify-between';
        banner.innerHTML = `
            <div class="flex items-center">
                <div class="text-2xl mr-3">📱</div>
                <div>
                    <div class="font-semibold">Install Bendahara App</div>
                    <div class="text-sm opacity-90">Get faster access and offline support</div>
                </div>
            </div>
            <div class="flex space-x-2">
                <button id="pwa-install-btn" class="bg-white text-amber-600 px-4 py-2 rounded font-medium">
                    Install
                </button>
                <button id="pwa-dismiss-btn" class="text-white hover:text-amber-200">
                    ✕
                </button>
            </div>
        `;
        
        // Add event listeners
        banner.querySelector('#pwa-install-btn').addEventListener('click', () => {
            this.promptInstall();
        });
        
        banner.querySelector('#pwa-dismiss-btn').addEventListener('click', () => {
            this.hideInstallBanner();
        });
        
        return banner;
    }
    
    /**
     * Prompt app installation
     */
    async promptInstall() {
        if (!this.deferredPrompt) return;
        
        this.deferredPrompt.prompt();
        const { outcome } = await this.deferredPrompt.userChoice;
        
        console.log(`📱 User ${outcome} the install prompt`);
        this.trackEvent('pwa_install_prompt', { outcome });
        
        this.deferredPrompt = null;
        this.hideInstallBanner();
    }
    
    /**
     * Hide install banner
     */
    hideInstallBanner() {
        const banner = document.getElementById('pwa-install-banner');
        if (banner) {
            banner.remove();
        }
    }
    
    /**
     * Setup offline handling
     */
    setupOfflineHandling() {
        // Online/offline status
        window.addEventListener('online', () => {
            this.showConnectionStatus('online');
            this.syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            this.showConnectionStatus('offline');
        });
        
        // Check initial connection status
        if (!navigator.onLine) {
            this.showConnectionStatus('offline');
        }
    }
    
    /**
     * Show connection status notification
     */
    showConnectionStatus(status) {
        const isOnline = status === 'online';
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-3 rounded-lg shadow-lg z-50 transition-all duration-300 ${
            isOnline ? 'bg-green-500' : 'bg-red-500'
        } text-white`;
        notification.innerHTML = `
            <div class="flex items-center">
                <div class="mr-2">${isOnline ? '🟢' : '🔴'}</div>
                <div class="font-medium">
                    ${isOnline ? 'Back Online' : 'Working Offline'}
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    /**
     * Sync offline data when back online
     */
    async syncOfflineData() {
        try {
            // Trigger background sync if supported
            if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('background-sync');
            }
            
            // Refresh data
            if (window.Livewire) {
                window.Livewire.rescan();
            }
            
            console.log('🔄 Offline data synced');
        } catch (error) {
            console.error('❌ Failed to sync offline data:', error);
        }
    }
    
    /**
     * Setup mobile optimizations
     */
    setupMobileOptimizations() {
        // Prevent double-tap zoom on buttons
        document.addEventListener('touchend', (e) => {
            if (e.target.matches('button, .fi-btn, [role="button"]')) {
                e.preventDefault();
                e.target.click();
            }
        });
        
        // Optimize mobile scrolling
        if (this.isMobile()) {
            document.body.style.webkitOverflowScrolling = 'touch';
        }
        
        // Handle orientation changes
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                window.scrollTo(0, 0);
            }, 100);
        });
    }
    
    /**
     * Setup performance monitoring
     */
    setupPerformanceMonitoring() {
        // Monitor page load performance
        window.addEventListener('load', () => {
            if ('performance' in window) {
                const perfData = performance.getEntriesByType('navigation')[0];
                const loadTime = perfData.loadEventEnd - perfData.loadEventStart;
                
                if (loadTime > 3000) {
                    console.warn('⚠️ Slow page load detected:', loadTime + 'ms');
                }
                
                this.trackEvent('page_performance', {
                    load_time: loadTime,
                    dom_content_loaded: perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart
                });
            }
        });
    }
    
    /**
     * Show update available notification
     */
    showUpdateAvailable() {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 left-4 right-4 bg-blue-500 text-white p-4 rounded-lg shadow-lg z-50';
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-semibold">Update Available</div>
                    <div class="text-sm opacity-90">A new version of the app is ready</div>
                </div>
                <button id="pwa-update-btn" class="bg-white text-blue-600 px-4 py-2 rounded font-medium ml-4">
                    Update
                </button>
            </div>
        `;
        
        notification.querySelector('#pwa-update-btn').addEventListener('click', () => {
            window.location.reload();
        });
        
        document.body.appendChild(notification);
    }
    
    /**
     * Utility methods
     */
    isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    trackEvent(eventName, data = {}) {
        // Analytics tracking
        if (window.gtag) {
            window.gtag('event', eventName, data);
        }
        console.log('📊 Event tracked:', eventName, data);
    }
}

// Initialize PWA when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.bendaharaPWA = new BendaharaPWA();
    });
} else {
    window.bendaharaPWA = new BendaharaPWA();
}

/**
 * PWA Utility Functions for Filament Integration
 */
window.PWAUtils = {
    /**
     * Cache validation data for offline access
     */
    cacheValidationData: async function(data) {
        if ('caches' in window) {
            const cache = await caches.open('dokterku-validation-v1.0.0');
            const response = new Response(JSON.stringify(data));
            await cache.put('/validation-data', response);
        }
    },
    
    /**
     * Get cached validation data when offline
     */
    getCachedValidationData: async function() {
        if ('caches' in window) {
            const cache = await caches.open('dokterku-validation-v1.0.0');
            const response = await cache.match('/validation-data');
            return response ? await response.json() : null;
        }
        return null;
    },
    
    /**
     * Show offline notification for actions
     */
    showOfflineAction: function(action) {
        if (!navigator.onLine) {
            new FilamentNotification()
                .title('Offline Action Queued')
                .body(`${action} will be processed when connection is restored`)
                .warning()
                .send();
            return true;
        }
        return false;
    }
};

/**
 * Enhanced Mobile Experience for Filament Tables
 */
if (window.innerWidth <= 768) {
    // Add mobile-specific enhancements
    document.addEventListener('DOMContentLoaded', () => {
        // Make table actions more touch-friendly
        const actionButtons = document.querySelectorAll('.fi-ta-actions button');
        actionButtons.forEach(btn => {
            btn.style.minHeight = '44px';
            btn.style.minWidth = '44px';
        });
        
        // Improve modal interactions on mobile
        const modals = document.querySelectorAll('[role="dialog"]');
        modals.forEach(modal => {
            modal.style.maxHeight = '90vh';
            modal.style.overflowY = 'auto';
        });
    });
}
</script>

{{-- Critical CSS for PWA --}}
<style>
    /* PWA-specific critical styles */
    @media (display-mode: standalone) {
        /* Remove any margin/padding that might cause layout issues */
        html, body {
            overflow-x: hidden;
        }
        
        /* Optimize status bar area for iOS */
        @supports (padding-top: env(safe-area-inset-top)) {
            .fi-topbar {
                padding-top: env(safe-area-inset-top);
            }
        }
        
        /* Better mobile button styles */
        .fi-btn {
            touch-action: manipulation;
            user-select: none;
        }
    }
    
    /* Install banner styles */
    #pwa-install-banner {
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>