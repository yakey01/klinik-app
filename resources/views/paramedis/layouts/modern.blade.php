<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#3b82f6">
    
    <title>@yield('title', 'Dashboard Paramedis - Dokterku')</title>
    
    <!-- SEO Meta -->
    <meta name="description" content="Dashboard modern untuk paramedis Klinik Dokterku - Kelola jadwal, presensi, dan jaspel dengan mudah">
    <meta name="keywords" content="paramedis, dashboard, klinik, dokterku, jadwal, presensi, jaspel">
    <meta name="author" content="Klinik Dokterku">
    <meta name="robots" content="noindex, nofollow"> <!-- Internal app -->
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Dashboard Paramedis - Dokterku')">
    <meta property="og:description" content="Dashboard modern untuk paramedis Klinik Dokterku">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Klinik Dokterku">
    
    <!-- Additional meta from sections -->
    @yield('meta')
    
    <!-- Icons -->
    <link rel="icon" type="image/svg+xml" href="/images/icon-192x192.svg">
    <link rel="apple-touch-icon" href="/images/icon-192x192.svg">
    <link rel="manifest" href="/pwa/paramedis-manifest.json">
    
    <!-- Styles -->
    @vite(['resources/css/filament/paramedis-mobile.css'])
    
    <!-- Critical CSS -->
    <style>
        /* Critical above-the-fold styles */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-radius: 50%;
            border-top: 3px solid #3b82f6;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Prevent flash of unstyled content */
        .paramedis-layout {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .paramedis-layout.loaded {
            opacity: 1;
        }
        
        /* Mobile-first responsive */
        .container {
            width: 100%;
            max-width: 480px;
            margin: 0 auto;
            padding: 0 16px;
        }
        
        @media (min-width: 640px) {
            .container {
                max-width: 640px;
            }
        }
        
        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Focus management */
        .focus\:outline-none:focus {
            outline: 2px solid transparent;
            outline-offset: 2px;
        }
        
        .focus\:ring-2:focus {
            --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
            --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
            box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
        }
        
        .focus\:ring-blue-500:focus {
            --tw-ring-opacity: 1;
            --tw-ring-color: rgb(59 130 246 / var(--tw-ring-opacity));
        }
    </style>
    
    @stack('styles')
</head>
<body class="paramedis-layout" data-panel="paramedis">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-blue-600 text-white px-4 py-2 rounded-md z-50">
        Skip to main content
    </a>
    
    <!-- App Loading Overlay -->
    <div id="app-loading" class="fixed inset-0 bg-white z-50 flex items-center justify-center">
        <div class="text-center">
            <div class="loading-spinner mb-4"></div>
            <p class="text-gray-600">Memuat aplikasi...</p>
        </div>
    </div>
    
    <!-- Main Application -->
    <div id="app" class="min-h-screen">
        <!-- Main Content -->
        <main id="main-content" role="main" class="focus:outline-none">
            @yield('content')
        </main>
        
        <!-- Error Boundary -->
        <div id="error-boundary" class="hidden fixed inset-0 bg-red-50 z-40 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Terjadi Kesalahan</h3>
                </div>
                <p class="text-gray-600 mb-4">Maaf, terjadi kesalahan saat memuat dashboard. Silakan coba refresh halaman.</p>
                <div class="flex gap-3">
                    <button onclick="window.location.reload()" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        Refresh Halaman
                    </button>
                    <button onclick="window.location.href='/paramedis'" class="flex-1 bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors">
                        Dashboard Klasik
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50 space-y-2"></div>
    
    <!-- Offline Indicator -->
    <div id="offline-indicator" class="hidden fixed bottom-4 left-4 bg-orange-500 text-white px-4 py-2 rounded-md shadow-lg">
        <span class="flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2v4m0 12v4m10-10h-4M6 12H2"></path>
            </svg>
            Offline Mode
        </span>
    </div>
    
    <!-- Scripts -->
    <script>
        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading overlay
            const loadingOverlay = document.getElementById('app-loading');
            const layout = document.querySelector('.paramedis-layout');
            
            setTimeout(() => {
                if (loadingOverlay) loadingOverlay.style.display = 'none';
                if (layout) layout.classList.add('loaded');
            }, 500);
            
            // Error boundary handler
            window.addEventListener('error', function(event) {
                console.error('Global error:', event.error);
                showErrorBoundary();
            });
            
            // Unhandled promise rejection handler
            window.addEventListener('unhandledrejection', function(event) {
                console.error('Unhandled promise rejection:', event.reason);
                showErrorBoundary();
            });
            
            // Network status monitoring
            window.addEventListener('online', function() {
                hideOfflineIndicator();
                showNotification('Koneksi internet tersambung kembali', 'success');
            });
            
            window.addEventListener('offline', function() {
                showOfflineIndicator();
                showNotification('Koneksi internet terputus', 'warning');
            });
            
            // Performance monitoring
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    setTimeout(() => {
                        const perfData = performance.getEntriesByType('navigation')[0];
                        if (perfData) {
                            console.log('Page load time:', perfData.loadEventEnd - perfData.loadEventStart, 'ms');
                        }
                    }, 0);
                });
            }
        });
        
        // Utility functions
        function showErrorBoundary() {
            const errorBoundary = document.getElementById('error-boundary');
            if (errorBoundary) {
                errorBoundary.classList.remove('hidden');
            }
        }
        
        function hideErrorBoundary() {
            const errorBoundary = document.getElementById('error-boundary');
            if (errorBoundary) {
                errorBoundary.classList.add('hidden');
            }
        }
        
        function showOfflineIndicator() {
            const indicator = document.getElementById('offline-indicator');
            if (indicator) {
                indicator.classList.remove('hidden');
            }
        }
        
        function hideOfflineIndicator() {
            const indicator = document.getElementById('offline-indicator');
            if (indicator) {
                indicator.classList.add('hidden');
            }
        }
        
        function showNotification(message, type = 'info', duration = 3000) {
            const container = document.getElementById('notification-container');
            if (!container) return;
            
            const notification = document.createElement('div');
            notification.className = `notification p-4 rounded-md shadow-lg max-w-sm transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500 text-white' :
                type === 'warning' ? 'bg-orange-500 text-white' :
                type === 'error' ? 'bg-red-500 text-white' :
                'bg-blue-500 text-white'
            }`;
            notification.textContent = message;
            
            container.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 10);
            
            // Auto remove
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, duration);
        }
        
        // Expose global utilities
        window.ParamedisUtils = {
            showNotification,
            showErrorBoundary,
            hideErrorBoundary
        };
    </script>
    
    @stack('scripts')
    
    <!-- React Hot Reload for development -->
    @if(app()->environment('local'))
    <script>
        if (typeof module !== 'undefined' && module.hot) {
            module.hot.accept();
        }
    </script>
    @endif
</body>
</html>