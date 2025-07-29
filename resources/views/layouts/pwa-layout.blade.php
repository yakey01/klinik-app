<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-token" content="{{ auth()->user()?->createToken('mobile-app')->plainTextToken ?? '' }}">
    
    <!-- PWA Meta Tags -->
    <meta name="application-name" content="Dokterku Mobile">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Dokterku Mobile">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#3b82f6">
    <meta name="theme-color" content="#3b82f6">
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/favicon-16x16.png">
    <link rel="mask-icon" href="/images/icons/safari-pinned-tab.svg" color="#3b82f6">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/js/manifest.json">
    
    <title>{{ $title ?? 'Dashboard' }} - Dokterku Mobile</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?families=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/css/mobile-dashboard.css'])
    
    <!-- Additional Mobile Optimizations -->
    <style>
        /* PWA-specific styles */
        body {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            overscroll-behavior-y: contain;
        }
        
        /* iOS Safari specific */
        @supports (-webkit-touch-callout: none) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }
        
        /* PWA splash screen styles */
        .splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease-out;
        }
        
        .splash-screen.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .splash-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        /* Install prompt styles */
        .install-prompt {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 16px;
            display: none;
            z-index: 1000;
            animation: slideUp 0.3s ease-out;
        }
        
        .install-prompt.show {
            display: block;
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Network status indicator */
        .network-status {
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            z-index: 1000;
            display: none;
            animation: slideDown 0.3s ease-out;
        }
        
        .network-status.online {
            background: #10b981;
        }
        
        .network-status.show {
            display: block;
        }
        
        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Splash Screen -->
    <div id="splash-screen" class="splash-screen">
        <div class="splash-logo">
            <svg class="w-10 h-10 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19.5 3.5L18 2l-1.5 1.5L15 2l-1.5 1.5L12 2l-1.5 1.5L9 2L7.5 3.5L6 2v14H3v3c0 1.66 1.34 3 3 3h12c1.66 0 3-1.34 3-3V2l-1.5 1.5zM19 19c0 .55-.45 1-1 1H6c-.55 0-1-.45-1-1v-1h14v2zm0-5H8V5.5l4-4 4 4V14z"/>
            </svg>
        </div>
    </div>
    
    <!-- Network Status Indicator -->
    <div id="network-status" class="network-status">
        <span id="network-message">Tidak ada koneksi internet</span>
    </div>
    
    <!-- Install Prompt -->
    <div id="install-prompt" class="install-prompt">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900">Install Dokterku Mobile</h3>
                <p class="text-sm text-gray-600">Akses lebih cepat dari home screen</p>
            </div>
        </div>
        <div class="flex gap-2">
            <button id="install-button" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg font-medium">
                Install
            </button>
            <button id="dismiss-install" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700">
                Nanti
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="min-h-screen">
        {{ $slot }}
    </main>
    
    <!-- PWA Update Available Toast -->
    <div id="update-toast" class="fixed bottom-20 left-4 right-4 bg-white rounded-lg shadow-lg border p-4 hidden z-50">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17l-4.17-4.17-1.42 1.41 5.59 5.59 12-12-1.41-1.41z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="font-medium text-gray-900">Update Tersedia</h4>
                <p class="text-sm text-gray-600">Versi terbaru siap diinstall</p>
            </div>
            <button id="update-button" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
                Update
            </button>
        </div>
    </div>
    
    <!-- Scripts -->
    @vite(['resources/js/app.js'])
    
    <!-- PWA Registration Script -->
    <script>
        // PWA Installation and Service Worker Management
        let deferredPrompt;
        let swRegistration;
        
        // Hide splash screen after load
        window.addEventListener('load', () => {
            setTimeout(() => {
                const splash = document.getElementById('splash-screen');
                splash.classList.add('hidden');
                setTimeout(() => splash.remove(), 300);
            }, 1500);
        });
        
        // Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    swRegistration = await navigator.serviceWorker.register('/js/service-worker.js');
                    console.log('✅ Service Worker registered successfully');
                    
                    // Check for updates
                    swRegistration.addEventListener('updatefound', () => {
                        const newWorker = swRegistration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                showUpdateToast();
                            }
                        });
                    });
                    
                } catch (error) {
                    console.error('❌ Service Worker registration failed:', error);
                }
            });
        }
        
        // PWA Install Prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install prompt after a delay
            setTimeout(() => {
                if (!localStorage.getItem('install-prompt-dismissed')) {
                    showInstallPrompt();
                }
            }, 5000);
        });
        
        function showInstallPrompt() {
            const installPrompt = document.getElementById('install-prompt');
            installPrompt.classList.add('show');
            
            document.getElementById('install-button').addEventListener('click', async () => {
                if (deferredPrompt) {
                    deferredPrompt.prompt();
                    const result = await deferredPrompt.userChoice;
                    console.log('PWA install result:', result);
                    deferredPrompt = null;
                }
                hideInstallPrompt();
            });
            
            document.getElementById('dismiss-install').addEventListener('click', () => {
                localStorage.setItem('install-prompt-dismissed', 'true');
                hideInstallPrompt();
            });
        }
        
        function hideInstallPrompt() {
            const installPrompt = document.getElementById('install-prompt');
            installPrompt.classList.remove('show');
        }
        
        // Network Status Management
        function updateNetworkStatus() {
            const networkStatus = document.getElementById('network-status');
            const networkMessage = document.getElementById('network-message');
            
            if (navigator.onLine) {
                networkStatus.classList.add('online');
                networkMessage.textContent = 'Koneksi tersambung kembali';
                networkStatus.classList.add('show');
                setTimeout(() => {
                    networkStatus.classList.remove('show');
                }, 3000);
            } else {
                networkStatus.classList.remove('online');
                networkMessage.textContent = 'Tidak ada koneksi internet';
                networkStatus.classList.add('show');
            }
        }
        
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        
        // Update Toast Management
        function showUpdateToast() {
            const updateToast = document.getElementById('update-toast');
            updateToast.classList.remove('hidden');
            
            document.getElementById('update-button').addEventListener('click', () => {
                if (swRegistration && swRegistration.waiting) {
                    swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
                    window.location.reload();
                }
            });
        }
        
        // Handle app install
        window.addEventListener('appinstalled', (e) => {
            console.log('✅ PWA installed successfully');
            localStorage.setItem('app-installed', 'true');
        });
        
        // Prevent zoom on double tap (iOS Safari)
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (event) => {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Handle iOS status bar
        if (window.navigator.standalone) {
            document.body.style.paddingTop = '20px';
        }
        
        console.log('🚀 PWA scripts loaded successfully');
    </script>
    
    @stack('scripts')
</body>
</html>