<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-authenticated" content="{{ auth()->check() ? 'true' : 'false' }}">
    <meta name="user-data" content="{{ auth()->check() ? json_encode($userData ?? []) : '{}' }}">
    <meta name="api-token" content="{{ $token ?? '' }}">
    <title>KLINIK DOKTERKU - {{ auth()->user()->name ?? 'Dokter' }}</title>
    
    <!-- FORCE NEW BUNDLE -->
    <script>
        // Force remove old script
        document.querySelectorAll('script[src*="BecotfJC"]').forEach(s => s.remove());
        
        // Force load new bundle
        var script = document.createElement('script');
        script.type = 'module';
        script.src = '/build/assets/dokter-mobile-app-DXixV-6x.js?_=' + Date.now();
        script.onload = function() {
            console.log('✅ NEW BUNDLE LOADED: DXixV-6x');
        };
        document.head.appendChild(script);
        
        // Load CSS
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = '/build/assets/css/dokter-mobile-app-CmQfbHE1.css?_=' + Date.now();
        document.head.appendChild(link);
    </script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
        }
        
        #dokter-app {
            min-height: 100vh;
            width: 100%;
        }
        
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    </style>
</head>
<body>
    <div id="loading" class="loading">
        <div class="loading-spinner"></div>
    </div>
    
    <div id="dokter-app"></div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const loading = document.getElementById('loading');
                if (loading) {
                    loading.style.display = 'none';
                }
            }, 1000);
        });
    </script>
</body>
</html>