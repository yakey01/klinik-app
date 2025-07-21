<!-- Premium Glassmorphism Header -->
<header class="glass-morphism border-b border-white/10 shadow-2xl premium-glow">
    <div class="flex items-center justify-between h-20 px-8">
        <!-- Mobile Menu Button -->
        <button @click="sidebarOpen = !sidebarOpen" 
                class="lg:hidden text-gray-400 hover:text-white transition-all duration-200 p-3 rounded-xl hover:bg-white/10 premium-glow">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <!-- Page Title & Breadcrumb -->
        <div class="hidden lg:block">
            <div class="flex items-center space-x-3 text-sm">
                <div class="flex items-center glass-morphism px-4 py-2 rounded-xl">
                    <svg class="w-4 h-4 mr-2 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2V7zm16 0v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2z"></path>
                    </svg>
                    <span class="text-gray-400">Admin</span>
                </div>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <div class="glass-morphism px-4 py-2 rounded-xl">
                    <span class="text-white font-bold gradient-text">@yield('page-title', 'Dashboard')</span>
                </div>
            </div>
        </div>

        <!-- Search Bar Premium -->
        <div class="flex-1 max-w-2xl mx-8 hidden md:block">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input type="text" 
                       class="block w-full pl-12 pr-4 py-3 border-0 rounded-2xl leading-5 glass-morphism placeholder-gray-400 text-white focus:outline-none focus:ring-2 focus:ring-purple-500/50 transition-all duration-200 premium-glow"
                       placeholder="Cari pasien, transaksi, anggaran, atau user..."
                       x-data="{ search: '' }"
                       x-model="search"
                       @keydown.enter="console.log('Search:', search)">
            </div>
        </div>

        <!-- Right Side Premium Actions -->
        <div class="flex items-center space-x-4">
            <!-- Quick Stats Premium -->
            <div class="hidden xl:flex items-center space-x-6 text-sm glass-morphism px-6 py-3 rounded-2xl">
                <div class="text-center">
                    <p class="text-gray-400 text-xs font-medium">Pendapatan Hari Ini</p>
                    <p class="text-emerald-400 font-bold text-lg stats-number">Rp 8.5M</p>
                </div>
                <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/20 to-transparent"></div>
                <div class="text-center">
                    <p class="text-gray-400 text-xs font-medium">Transaksi Aktif</p>
                    <p class="text-blue-400 font-bold text-lg stats-number">47</p>
                </div>
                <div class="w-px h-8 bg-gradient-to-b from-transparent via-white/20 to-transparent"></div>
                <div class="text-center">
                    <p class="text-gray-400 text-xs font-medium">SPJ Pending</p>
                    <p class="text-amber-400 font-bold text-lg stats-number">12</p>
                </div>
            </div>

            <!-- Notifications Premium -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="relative p-3 text-gray-400 hover:text-white rounded-2xl glass-morphism transition-all duration-200 premium-glow">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <!-- Premium Notification badge -->
                    <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-gradient-to-r from-red-500 to-pink-500 rounded-full animate-pulse shadow-lg">{{ $stats['pending_approvals'] ?? 12 }}</span>
                </button>
                
                <!-- Premium Notifications Dropdown -->
                <div x-show="open" 
                     x-transition
                     @click.away="open = false"
                     class="absolute right-0 mt-3 w-96 glass-morphism-dark rounded-3xl shadow-2xl border border-white/10 z-50 premium-glow">
                    <div class="p-6 border-b border-white/10">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xl font-bold text-white gradient-text">Notifikasi</h3>
                            <span class="glass-morphism px-3 py-1 rounded-full text-xs font-bold text-purple-400">{{ $stats['pending_approvals'] ?? 12 }} baru</span>
                        </div>
                    </div>
                    <div class="max-h-80 overflow-y-auto scrollbar-premium">
                        <div class="p-4 hover:bg-white/5 border-b border-white/5 transition-colors">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl flex items-center justify-center shadow-lg premium-glow">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-bold text-white">
                                        Validasi SPJ Menunggu
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        {{ $stats['pending_approvals'] ?? 12 }} dokumen SPJ menunggu persetujuan Anda
                                    </p>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                                        <div class="w-2 h-2 bg-amber-400 rounded-full mr-2 animate-pulse"></div>
                                        3 menit yang lalu
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 hover:bg-white/5 border-b border-white/5 transition-colors">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-green-500 rounded-2xl flex items-center justify-center shadow-lg premium-glow">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-bold text-white">
                                        Target Anggaran Tercapai
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        Realisasi anggaran Q4 mencapai 94.2% dari target
                                    </p>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                                        <div class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse"></div>
                                        1 jam yang lalu
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 hover:bg-white/5 transition-colors">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center shadow-lg premium-glow">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <p class="text-sm font-bold text-white">
                                        Laporan Bulanan Siap
                                    </p>
                                    <p class="text-sm text-gray-400">
                                        Laporan keuangan November 2024 telah selesai dibuat
                                    </p>
                                    <p class="text-xs text-gray-500 mt-2 flex items-center">
                                        <div class="w-2 h-2 bg-blue-400 rounded-full mr-2 animate-pulse"></div>
                                        2 jam yang lalu
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-white/10">
                        <button class="w-full text-center text-sm text-purple-400 hover:text-purple-300 font-bold transition-colors glass-morphism px-4 py-3 rounded-2xl">
                            Lihat Semua Notifikasi
                        </button>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown Premium -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center space-x-3 p-3 text-gray-400 hover:text-white rounded-2xl glass-morphism transition-all duration-200 premium-glow">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 via-blue-500 to-emerald-500 rounded-2xl flex items-center justify-center text-white font-bold shadow-lg premium-glow animate-float">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="hidden md:block text-left">
                        <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400">{{ auth()->user()->role->name ?? 'Administrator' }}</p>
                    </div>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                <!-- Premium Profile Dropdown Menu -->
                <div x-show="open" 
                     x-transition
                     @click.away="open = false"
                     class="absolute right-0 mt-3 w-80 glass-morphism-dark rounded-3xl shadow-2xl border border-white/10 z-50 premium-glow">
                    <div class="p-6 border-b border-white/10">
                        <div class="flex items-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 via-blue-500 to-emerald-500 rounded-3xl flex items-center justify-center text-white font-bold shadow-2xl premium-glow">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="ml-4">
                                <p class="text-lg font-bold text-white">{{ auth()->user()->name }}</p>
                                <p class="text-sm text-gray-400">{{ auth()->user()->email }}</p>
                                <div class="flex items-center mt-2">
                                    <span class="glass-morphism px-3 py-1 rounded-full text-xs font-bold gradient-text">
                                        {{ auth()->user()->role->name ?? 'Administrator' }}
                                    </span>
                                    <div class="ml-3 flex items-center">
                                        <div class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse shadow-lg"></div>
                                        <span class="text-xs text-emerald-400 font-medium">Online</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="py-2">
                        <a href="{{ route('profile.edit') }}" 
                           class="flex items-center px-6 py-4 text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium">Profil Saya</span>
                                <span class="block text-xs text-gray-500">Edit informasi akun</span>
                            </div>
                        </a>
                        <a href="#" 
                           class="flex items-center px-6 py-4 text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="font-medium">Pengaturan</span>
                                <span class="block text-xs text-gray-500">Konfigurasi sistem</span>
                            </div>
                        </a>
                        <div class="border-t border-white/10 my-2"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" 
                                    class="flex items-center w-full px-6 py-4 text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-colors">
                                <div class="w-10 h-10 bg-gradient-to-br from-red-500 to-pink-500 rounded-xl flex items-center justify-center mr-4 shadow-lg">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-medium">Keluar</span>
                                    <span class="block text-xs text-gray-500">Logout dari sistem</span>
                                </div>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>