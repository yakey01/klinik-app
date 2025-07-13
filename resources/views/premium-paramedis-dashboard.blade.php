<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Premium Dashboard - {{ auth()->user()->name }}</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#007AFF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="light-content">
    <meta name="apple-mobile-web-app-title" content="Premium Dashboard">
    
    <!-- Preconnect to external resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Critical CSS -->
    <style>
        /* Critical above-the-fold styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #F0F4FF 0%, #E8F2FF 50%, #DDE7FF 100%);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        #premium-dashboard-root {
            width: 100%;
            min-height: 100vh;
            position: relative;
        }
        
        /* Initial loading state */
        .initial-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #F0F4FF 0%, #E8F2FF 50%, #DDE7FF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(0, 122, 255, 0.1);
            border-left-color: #007AFF;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Prevent zoom on iOS */
        input, textarea, select {
            font-size: 16px !important;
        }
        
        /* Safe area handling for notched devices */
        @supports (padding: max(0px)) {
            body {
                padding-left: env(safe-area-inset-left);
                padding-right: env(safe-area-inset-right);
            }
        }
        
        /* Disable selection for better mobile experience */
        * {
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Allow selection for text content */
        p, span, h1, h2, h3, h4, h5, h6, div[contenteditable] {
            -webkit-user-select: text;
            -moz-user-select: text;
            -ms-user-select: text;
            user-select: text;
        }
    </style>
    
    <!-- Preload critical assets -->
    @vite(['resources/react/premium-paramedis-dashboard/styles/PremiumParamedisDashboard.css'])
</head>
<body>
    <!-- Premium Dashboard Root -->
    <div id="premium-dashboard-root">
        <div class="initial-loading">
            <div class="loading-spinner"></div>
        </div>
    </div>

    <!-- Laravel Data Integration -->
    <script>
        // Enhanced Laravel data integration
        window.laravelData = {
            user: @json(auth()->user()),
            csrfToken: '{{ csrf_token() }}',
            apiUrl: '{{ url("/api") }}',
            baseUrl: '{{ url("/") }}',
            timestamp: {{ time() }},
            dashboardData: {
                jaspel_monthly: {{ rand(18000000, 25000000) }},
                jaspel_weekly: {{ rand(4500000, 6500000) }},
                monthly_hours: {{ rand(150, 200) }},
                monthly_target: {{ rand(24000000, 30000000) }},
                completion_rate: {{ rand(75, 95) }},
                stress_level: {{ rand(1, 3) }},
                energy_level: {{ rand(3, 5) }},
                satisfaction: {{ rand(3, 5) }},
                doctor_name: 'dr. {{ strtoupper(explode(" ", auth()->user()->name)[0]) }}',
                avatar_url: 'https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=007AFF&color=fff&size=80&rounded=true'
            },
            permissions: {
                canViewJaspel: true,
                canManageSchedule: true,
                canViewReports: true
            },
            settings: {
                theme: 'light',
                notifications: true,
                hapticFeedback: true,
                animations: true
            }
        };
        
        // Performance monitoring
        window.performanceMetrics = {
            navigationStart: performance.timing.navigationStart,
            domContentLoaded: 0,
            fullyLoaded: 0
        };
        
        // Enhanced service worker registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/premium-dashboard-sw.js', {
                    scope: '/premium-dashboard/'
                }).then(function(registration) {
                    console.log('Premium Dashboard SW registered');
                    
                    // Update handling
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                // New version available
                                if (confirm('A new version is available. Update now?')) {
                                    window.location.reload();
                                }
                            }
                        });
                    });
                }).catch(function(registrationError) {
                    console.log('Premium Dashboard SW registration failed');
                });
            });
        }
        
        // iOS specific optimizations
        if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            // Prevent bounce scrolling
            document.addEventListener('touchmove', function(e) {
                e.preventDefault();
            }, { passive: false });
            
            // Add to home screen prompt
            let deferredPrompt;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                deferredPrompt = e;
                
                // Show custom install button after some time
                setTimeout(() => {
                    if (deferredPrompt && !window.matchMedia('(display-mode: standalone)').matches) {
                        // Could show custom install prompt here
                    }
                }, 10000);
            });
        }
        
        // Android PWA optimizations
        if (/Android/.test(navigator.userAgent)) {
            // Handle back button
            window.addEventListener('popstate', function(event) {
                if (window.history.length <= 1) {
                    // Ask before closing app
                    if (confirm('Exit Premium Dashboard?')) {
                        window.close();
                    } else {
                        window.history.pushState(null, null, window.location.pathname);
                    }
                }
            });
            
            // Push initial state
            window.history.pushState(null, null, window.location.pathname);
        }
        
        // Network status monitoring
        window.addEventListener('online', function() {
            console.log('Premium Dashboard: Back online');
            // Could dispatch custom event to React app
            window.dispatchEvent(new CustomEvent('networkStatusChange', { 
                detail: { isOnline: true } 
            }));
        });
        
        window.addEventListener('offline', function() {
            console.log('Premium Dashboard: Gone offline');
            window.dispatchEvent(new CustomEvent('networkStatusChange', { 
                detail: { isOnline: false } 
            }));
        });
        
        // Memory management
        window.addEventListener('beforeunload', function() {
            // Cleanup any intervals or listeners
            window.premiumDashboardCleanup && window.premiumDashboardCleanup();
        });
        
        // Performance tracking
        window.addEventListener('DOMContentLoaded', function() {
            window.performanceMetrics.domContentLoaded = performance.now();
        });
        
        window.addEventListener('load', function() {
            window.performanceMetrics.fullyLoaded = performance.now();
            
            // Report performance metrics after everything is loaded
            setTimeout(() => {
                const metrics = {
                    domContentLoaded: window.performanceMetrics.domContentLoaded,
                    fullyLoaded: window.performanceMetrics.fullyLoaded,
                    totalLoadTime: window.performanceMetrics.fullyLoaded - window.performanceMetrics.navigationStart
                };
                
                console.log('Premium Dashboard Performance:', metrics);
                
                // Could send to analytics service
                // analytics.track('dashboard_performance', metrics);
            }, 1000);
        });
        
        // Error tracking
        window.addEventListener('error', function(e) {
            console.error('Premium Dashboard Error:', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                error: e.error
            });
            
            // Could send to error tracking service
            // errorTracker.captureException(e.error);
        });
        
        // Unhandled promise rejection tracking
        window.addEventListener('unhandledrejection', function(e) {
            console.error('Premium Dashboard Unhandled Promise Rejection:', e.reason);
            
            // Could send to error tracking service
            // errorTracker.captureException(e.reason);
        });
    </script>

    <!-- React App Bundle -->
    @vite(['resources/react/premium-paramedis-dashboard/main.jsx'])
    
    <!-- Additional Performance Optimizations -->
    <script>
        // Preload next likely routes
        setTimeout(() => {
            const routes = [
                '{{ route("filament.paramedis.resources.attendances.index") }}',
                '{{ route("jaspel.dashboard") }}'
            ];
            
            routes.forEach(route => {
                const link = document.createElement('link');
                link.rel = 'prefetch';
                link.href = route;
                document.head.appendChild(link);
            });
        }, 5000);
        
        // Resource hints for better performance
        ['dns-prefetch', 'preconnect'].forEach(rel => {
            const link = document.createElement('link');
            link.rel = rel;
            link.href = '{{ url("/") }}';
            document.head.appendChild(link);
        });
    </script>
</body>
</html>