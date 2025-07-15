@props(['user'])

<!-- Dark Elegant Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-80 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 shadow-2xl">
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between h-20 px-6 border-b border-gray-700/50 bg-gradient-to-r from-primary-900/20 to-accent-500/20">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-gradient-to-r from-primary-500 to-accent-400 rounded-xl flex items-center justify-center shadow-lg">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold bg-gradient-to-r from-primary-400 to-accent-400 bg-clip-text text-transparent">Dokterku</h1>
                <p class="text-sm text-gray-400 font-medium">Non-Paramedis Panel</p>
            </div>
        </div>
        <button id="closeSidebar" class="lg:hidden p-2 rounded-md hover:bg-gray-800 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <!-- User Profile -->
    <div class="p-6 border-b border-gray-700/50">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 bg-gradient-to-r from-accent-400 to-primary-500 rounded-full flex items-center justify-center font-bold text-lg shadow-lg">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <h3 class="font-semibold text-white text-lg">{{ $user->name }}</h3>
                <p class="text-sm text-accent-400 font-medium">Non-Paramedis</p>
                <p class="text-xs text-gray-400">{{ $user->email }}</p>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="mt-4 grid grid-cols-2 gap-3">
            <div class="bg-gradient-to-r from-primary-900/30 to-primary-800/30 rounded-lg p-3 border border-primary-700/30">
                <p class="text-xs text-primary-300 font-medium">Bulan Ini</p>
                <p class="text-sm font-bold text-white">22 Hari</p>
            </div>
            <div class="bg-gradient-to-r from-accent-900/30 to-accent-800/30 rounded-lg p-3 border border-accent-700/30">
                <p class="text-xs text-accent-300 font-medium">Status</p>
                <p class="text-sm font-bold text-white">Aktif</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="p-4 space-y-2 flex-1 overflow-y-auto">
        <!-- Dashboard -->
        <a href="{{ route('nonparamedis.dashboard') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('nonparamedis.dashboard') ? 'bg-gradient-to-r from-primary-600 to-accent-500 text-white shadow-lg' : 'hover:bg-gray-800/50' }} transition-all duration-200 group">
            <div class="w-10 h-10 {{ request()->routeIs('nonparamedis.dashboard') ? 'bg-white/20' : 'bg-gray-700' }} rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v0a2 2 0 01-2 2H10a2 2 0 01-2-2v0z"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Dashboard</span>
                <p class="text-xs opacity-75">Beranda utama</p>
            </div>
        </a>
        
        <!-- Presensi -->
        <a href="{{ route('nonparamedis.presensi') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('nonparamedis.presensi') ? 'bg-gradient-to-r from-primary-600 to-accent-500 text-white shadow-lg' : 'hover:bg-gray-800/50' }} transition-all duration-200 group">
            <div class="w-10 h-10 {{ request()->routeIs('nonparamedis.presensi') ? 'bg-white/20' : 'bg-gray-700' }} rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Presensi</span>
                <p class="text-xs opacity-75">Absensi harian</p>
            </div>
            <div class="ml-auto">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            </div>
        </a>
        
        <!-- Jaspel -->
        <a href="#" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800/50 transition-all duration-200 group">
            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Jaspel</span>
                <p class="text-xs opacity-75">Jasa pelayanan</p>
            </div>
            <div class="ml-auto">
                <span class="text-xs bg-accent-500 text-white px-2 py-1 rounded-full font-medium">Rp 2.5M</span>
            </div>
        </a>
        
        <!-- Jadwal -->
        <a href="{{ route('nonparamedis.jadwal') }}" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg {{ request()->routeIs('nonparamedis.jadwal') ? 'bg-gradient-to-r from-primary-600 to-accent-500 text-white shadow-lg' : 'hover:bg-gray-800/50' }} transition-all duration-200 group">
            <div class="w-10 h-10 {{ request()->routeIs('nonparamedis.jadwal') ? 'bg-white/20' : 'bg-gray-700' }} rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Jadwal</span>
                <p class="text-xs opacity-75">Jadwal kerja</p>
            </div>
            <div class="ml-auto">
                <span class="w-2 h-2 bg-yellow-400 rounded-full"></span>
            </div>
        </a>
        
        <!-- Pasien -->
        <a href="#" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800/50 transition-all duration-200 group">
            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Pasien</span>
                <p class="text-xs opacity-75">Data pasien</p>
            </div>
            <div class="ml-auto">
                <span class="text-xs bg-gray-600 text-white px-2 py-1 rounded-full font-medium">156</span>
            </div>
        </a>
        
        <!-- Divider -->
        <div class="my-4 border-t border-gray-700/50"></div>
        
        <!-- Laporan -->
        <a href="#" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800/50 transition-all duration-200 group">
            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Laporan</span>
                <p class="text-xs opacity-75">Laporan bulanan</p>
            </div>
        </a>
        
        <!-- Pengaturan -->
        <a href="#" class="nav-item flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800/50 transition-all duration-200 group">
            <div class="w-10 h-10 bg-gray-700 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </div>
            <div>
                <span class="font-semibold">Pengaturan</span>
                <p class="text-xs opacity-75">Konfigurasi</p>
            </div>
        </a>
    </nav>
    
    <!-- Footer -->
    <div class="p-4 border-t border-gray-700/50">
        <div class="bg-gradient-to-r from-primary-900/30 to-accent-900/30 rounded-lg p-4 border border-primary-700/30">
            <div class="flex items-center space-x-2 text-sm">
                <svg class="w-4 h-4 text-accent-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="text-gray-300 font-medium">Non-Paramedis Dashboard</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Sistem Manajemen Klinik</p>
        </div>
        
        <!-- Logout Button -->
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center space-x-2 px-4 py-3 bg-red-600/20 hover:bg-red-600/30 border border-red-500/30 rounded-lg transition-all duration-200 group">
                <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="text-red-400 font-medium">Keluar</span>
            </button>
        </form>
    </div>
</aside>