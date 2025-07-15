@props(['user', 'title' => 'Dashboard Non-Paramedis'])

<!-- Mobile Header -->
<header class="lg:hidden bg-white shadow-sm border-b border-gray-200 sticky top-0 z-40">
    <div class="flex items-center justify-between h-16 px-4">
        <!-- Hamburger Menu -->
        <button id="openSidebar" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <!-- Title -->
        <h1 class="text-lg font-bold text-gray-900 text-center flex-1">{{ $title }}</h1>
        
        <!-- User Avatar & Info -->
        <div class="flex items-center space-x-2">
            <!-- Notifications -->
            <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors relative">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5-5-5h5V12h5z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 6h2a4 4 0 014 4v7"></path>
                </svg>
                <span class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white"></span>
            </button>
            
            <!-- User Avatar -->
            <div class="w-8 h-8 bg-gradient-to-r from-primary-500 to-accent-400 rounded-full flex items-center justify-center text-white font-bold text-sm">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        </div>
    </div>
    
    <!-- Quick Stats Bar (Mobile) -->
    <div class="px-4 py-2 bg-gray-50 border-t border-gray-200">
        <div class="flex justify-between items-center text-sm">
            <div class="flex items-center space-x-1">
                <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                <span class="text-gray-600 font-medium">Status: Aktif</span>
            </div>
            <div class="text-gray-500">
                <span class="font-medium">{{ now()->format('d M Y') }}</span>
            </div>
            <div class="text-primary-600 font-semibold">
                22 Hari Kerja
            </div>
        </div>
    </div>
</header>