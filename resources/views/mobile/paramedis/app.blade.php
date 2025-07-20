<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">
    <meta name="user-data" content="{{ auth()->check() ? json_encode($userData ?? []) : '{}' }}">
    <meta name="api-token" content="{{ $token ?? '' }}">
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Paramedis' }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Preload critical resources -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Removed external debug scripts that cause DOM access errors -->
    
    <!-- Vite Assets -->
    @vite(['resources/js/paramedis-mobile-app.tsx'])
    
    <style>
        /* Dark mode initialization script */
        :root {
            color-scheme: light dark;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            transition: background-color 0.3s ease, color 0.3s ease;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .dark body {
            background: #0f172a;
            color: #f1f5f9;
        }
        
        #paramedis-app {
            min-height: 100vh;
            width: 100%;
        }
        
        /* Loading state */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* PWA styles */
        @media screen and (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 0 16px;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
                color: #f1f5f9;
            }
        }
        
        /* Safe area support for iOS */
        @supports (padding-top: env(safe-area-inset-top)) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }
    </style>
    
    <!-- Simple theme initialization -->
    <script>
        (function() {
            'use strict';
            
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initTheme);
            } else {
                initTheme();
            }
            
            function initTheme() {
                try {
                    let theme = 'light';
                    
                    // Get saved theme
                    try {
                        const savedTheme = localStorage.getItem('theme');
                        if (savedTheme === 'dark' || savedTheme === 'light') {
                            theme = savedTheme;
                        }
                    } catch (e) {
                        // localStorage not available
                    }
                    
                    // Check system preference if no saved theme
                    if (theme === 'light') {
                        try {
                            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                                theme = 'dark';
                            }
                        } catch (e) {
                            // matchMedia not available
                        }
                    }
                    
                    // Apply theme
                    if (theme === 'dark' && document.documentElement) {
                        document.documentElement.classList.add('dark');
                    }
                    
                    // Store for React
                    window.__PARAMEDIS_THEME__ = theme;
                    
                } catch (e) {
                    // Fallback
                    window.__PARAMEDIS_THEME__ = 'light';
                    console.warn('Theme initialization failed:', e);
                }
            }
        })();
    </script>
    
</head>
<body>
    <!-- Loading State -->
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- React App Container -->
    <div id="paramedis-app"></div>
    
    <!-- Fallback for non-JS users -->
    <noscript>
        <div style="padding: 20px; text-align: center; background: #fee2e2; color: #991b1b; margin: 20px; border-radius: 8px;">
            <h3>JavaScript Required</h3>
            <p>This application requires JavaScript to run properly. Please enable JavaScript in your browser settings.</p>
        </div>
    </noscript>
    
    <!-- Simple loading script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide loading after a short delay
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'none';
                }
            }, 500);
        });
    </script>
</body>
</html>