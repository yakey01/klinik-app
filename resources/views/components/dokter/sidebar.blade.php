<!-- Dark Elegant Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-80 bg-gray-900 text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between h-20 px-6 border-b border-gray-800">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                <i data-lucide="heart-pulse" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-xl font-bold gradient-text">Dokterku</h1>
                <p class="text-sm text-gray-400">Dashboard Dokter</p>
            </div>
        </div>
        <button id="closeSidebar" class="lg:hidden p-2 rounded-md hover:bg-gray-800 transition-colors">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    
    <!-- User Profile -->
    <div class="p-6 border-b border-gray-800">
        <div class="flex items-center space-x-3">
            <div class="w-12 h-12 bg-gradient-to-r from-blue-400 to-purple-500 rounded-full flex items-center justify-center font-bold text-lg">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div>
                <h3 class="font-semibold text-white">{{ auth()->user()->name }}</h3>
                <p class="text-sm text-gray-400">Dokter</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="p-4 space-y-2">
        <a href="/dokter" class="flex items-center space-x-3 px-4 py-3 rounded-md {{ request()->is('dokter') ? 'bg-blue-600 text-white hover-glow' : 'hover:bg-gray-800' }} transition-all duration-200">
            <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            <span class="font-medium">Dashboard</span>
        </a>
        
        <a href="/dokter/dokter-presensis" class="flex items-center space-x-3 px-4 py-3 rounded-md {{ request()->is('dokter/dokter-presensis*') ? 'bg-blue-600 text-white hover-glow' : 'hover:bg-gray-800' }} transition-all duration-200 group">
            <i data-lucide="clock" class="w-5 h-5 group-hover:text-blue-400"></i>
            <span class="font-medium group-hover:text-blue-400">Presensi</span>
        </a>
        
        <a href="/dokter/jaspel-dokters" class="flex items-center space-x-3 px-4 py-3 rounded-md {{ request()->is('dokter/jaspel-dokters*') ? 'bg-blue-600 text-white hover-glow' : 'hover:bg-gray-800' }} transition-all duration-200 group">
            <i data-lucide="banknote" class="w-5 h-5 group-hover:text-green-400"></i>
            <span class="font-medium group-hover:text-green-400">Jaspel</span>
        </a>
        
        <a href="/dokter/tindakan-dokters" class="flex items-center space-x-3 px-4 py-3 rounded-md {{ request()->is('dokter/tindakan-dokters*') ? 'bg-blue-600 text-white hover-glow' : 'hover:bg-gray-800' }} transition-all duration-200 group">
            <i data-lucide="stethoscope" class="w-5 h-5 group-hover:text-purple-400"></i>
            <span class="font-medium group-hover:text-purple-400">Tindakan Medis</span>
        </a>
        
        <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-md hover:bg-gray-800 transition-all duration-200 group">
            <i data-lucide="settings" class="w-5 h-5 group-hover:text-gray-400"></i>
            <span class="font-medium group-hover:text-gray-400">Pengaturan</span>
        </a>
    </nav>
    
    <!-- Footer -->
    <div class="absolute bottom-4 left-4 right-4">
        <div class="glass rounded-lg p-4">
            <div class="flex items-center space-x-2 text-sm text-gray-300">
                <i data-lucide="stethoscope" class="w-4 h-4 text-blue-400"></i>
                <span>Dashboard Dokter</span>
            </div>
        </div>
    </div>
</aside>