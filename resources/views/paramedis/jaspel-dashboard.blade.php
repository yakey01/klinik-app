<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Jaspel Dashboard - Dokterku Paramedis</title>
    
    <!-- Pass Laravel data to React -->
    <script>
        window.laravelJaspelData = @json($laravelJaspelData);
        console.log('Laravel Jaspel Data loaded:', window.laravelJaspelData);
    </script>
    
    <!-- React Build Assets -->
    @vite([
        'resources/react/paramedis-jaspel/main.jsx',
        'resources/react/paramedis-jaspel/styles/ParamedisJaspelDashboard.css'
    ])
</head>
<body class="bg-gray-50 font-sans antialiased h-full">
    <!-- React Root Container -->
    <div id="paramedis-jaspel-root" class="h-full"></div>
    
    <!-- Loading Fallback -->
    <div id="loading-fallback" style="display: none;" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Loading Jaspel Dashboard...</h2>
            <p class="text-gray-500">Memuat data jaspel Anda</p>
        </div>
    </div>
    
    <!-- Error Fallback -->
    <div id="error-fallback" style="display: none;" class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-pink-100">
        <div class="text-center max-w-md mx-auto p-6">
            <div class="text-red-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-700 mb-2">Error Loading Dashboard</h2>
            <p class="text-gray-500 mb-4">Terjadi kesalahan saat memuat dashboard jaspel.</p>
            <button onclick="window.location.reload()" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition-colors">
                Muat Ulang
            </button>
        </div>
    </div>
    
    <script>
        // Show loading fallback if React doesn't load within 5 seconds
        setTimeout(function() {
            const reactRoot = document.getElementById('paramedis-jaspel-root');
            const loadingFallback = document.getElementById('loading-fallback');
            
            if (reactRoot && reactRoot.children.length === 0) {
                loadingFallback.style.display = 'flex';
                console.warn('React component did not load, showing fallback');
            }
        }, 5000);
        
        // Handle React errors
        window.addEventListener('error', function(e) {
            if (e.error && e.error.name === 'ChunkLoadError') {
                const errorFallback = document.getElementById('error-fallback');
                const loadingFallback = document.getElementById('loading-fallback');
                
                loadingFallback.style.display = 'none';
                errorFallback.style.display = 'flex';
                
                console.error('React chunk load error:', e.error);
            }
        });
        
        // Debug information
        console.log('Jaspel Dashboard View loaded');
        console.log('User:', @json(auth()->user()->name ?? 'Unknown'));
        console.log('Available data:', {
            hasLaravelData: !!window.laravelJaspelData,
            dataKeys: window.laravelJaspelData ? Object.keys(window.laravelJaspelData) : [],
            jaspelData: window.laravelJaspelData?.jaspel || null
        });
    </script>
</body>
</html>