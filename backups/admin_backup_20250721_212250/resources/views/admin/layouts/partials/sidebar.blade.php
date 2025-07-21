<!-- Premium Glassmorphism Sidebar -->
<aside class="fixed inset-y-0 left-0 z-50 w-72 transform transition-all duration-500 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
       :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
       x-cloak>
    <div class="flex flex-col h-full glass-morphism-dark sidebar-glow scrollbar-premium overflow-y-auto rounded-r-3xl">
        
        <!-- Premium Logo & Branding -->
        <div class="flex items-center justify-between h-24 px-8 border-b border-white/10">
            <div class="flex items-center">
                <div class="flex-shrink-0 animate-float">
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 via-blue-500 to-emerald-500 rounded-2xl flex items-center justify-center shadow-2xl premium-glow">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-black gradient-text">Dokterku</h1>
                    <p class="text-xs text-gray-400 font-medium tracking-wider uppercase">Premium Dashboard</p>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white transition-colors duration-200 p-2 rounded-xl hover:bg-white/10">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Premium Navigation Menu -->
        <nav class="flex-1 px-6 py-8 space-y-3">
            <!-- Dashboard -->
            <div class="relative group">
                <a href="{{ route('admin.dashboard') }}" 
                   class="flex items-center px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.dashboard') ? 'glass-morphism premium-glow text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br {{ request()->routeIs('admin.dashboard') ? 'from-purple-500 to-blue-500' : 'from-gray-600 to-gray-700 group-hover:from-purple-500 group-hover:to-blue-500' }} mr-4 transition-all duration-300 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2V7zm16 0v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h14a2 2 0 012 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <span class="block text-base font-bold">Dashboard</span>
                        <span class="text-xs text-gray-400">Overview & Analytics</span>
                    </div>
                    @if(request()->routeIs('admin.dashboard'))
                        <div class="w-3 h-3 bg-gradient-to-r from-purple-500 to-blue-500 rounded-full animate-pulse shadow-lg"></div>
                    @endif
                </a>
            </div>

            <!-- Monitoring Anggaran -->
            <div class="relative group" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 text-gray-300 hover:text-white hover:bg-white/5">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 group-hover:from-emerald-500 group-hover:to-emerald-600 mr-4 transition-all duration-300 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="block text-base font-bold">Monitoring Anggaran</span>
                            <span class="text-xs text-gray-400">Budget & SPJ</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="ml-16 mt-2 space-y-2">
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Draft SPJ</span>
                        <span class="block text-xs text-gray-500">Surat Pertanggungjawaban</span>
                    </a>
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Validasi SPJ</span>
                        <span class="block text-xs text-gray-500">Review & Approval</span>
                    </a>
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Realisasi Anggaran</span>
                        <span class="block text-xs text-gray-500">Budget Realization</span>
                    </a>
                </div>
            </div>

            <!-- Data Keuangan -->
            <div class="relative group" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 text-gray-300 hover:text-white hover:bg-white/5">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 group-hover:from-blue-500 group-hover:to-blue-600 mr-4 transition-all duration-300 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="block text-base font-bold">Data Keuangan</span>
                            <span class="text-xs text-gray-400">Financial Management</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="ml-16 mt-2 space-y-2">
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Pendapatan</span>
                        <span class="block text-xs text-gray-500">Revenue Management</span>
                    </a>
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Pengeluaran</span>
                        <span class="block text-xs text-gray-500">Expense Tracking</span>
                    </a>
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Laporan Keuangan</span>
                        <span class="block text-xs text-gray-500">Financial Reports</span>
                    </a>
                </div>
            </div>

            <!-- Data Medis -->
            <div class="relative group" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 text-gray-300 hover:text-white hover:bg-white/5">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 group-hover:from-red-500 group-hover:to-pink-500 mr-4 transition-all duration-300 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="block text-base font-bold">Data Medis</span>
                            <span class="text-xs text-gray-400">Medical Records</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="ml-16 mt-2 space-y-2">
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Data Pasien</span>
                        <span class="block text-xs text-gray-500">Patient Records</span>
                    </a>
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Tindakan Medis</span>
                        <span class="block text-xs text-gray-500">Medical Procedures</span>
                    </a>
                    <a href="#" class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 text-gray-400 hover:text-white hover:bg-white/5">
                        <span class="font-medium">Jenis Tindakan</span>
                        <span class="block text-xs text-gray-500">Procedure Types</span>
                    </a>
                </div>
            </div>

            <!-- User Management -->
            <div class="relative group" x-data="{ open: {{ request()->routeIs('admin.users.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" 
                        class="flex items-center justify-between w-full px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 {{ request()->routeIs('admin.users.*') ? 'glass-morphism premium-glow text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br {{ request()->routeIs('admin.users.*') ? 'from-purple-500 to-blue-500' : 'from-gray-600 to-gray-700 group-hover:from-purple-500 group-hover:to-blue-500' }} mr-4 transition-all duration-300 shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <span class="block text-base font-bold">User Management</span>
                            <span class="text-xs text-gray-400">Staff & Roles</span>
                        </div>
                    </div>
                    <svg class="w-5 h-5 transition-transform duration-300" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div x-show="open" x-transition class="ml-16 mt-2 space-y-2">
                    <a href="{{ route('admin.users.index') }}" 
                       class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('admin.users.index') ? 'bg-purple-500/20 text-purple-300' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <span class="font-medium">Daftar User</span>
                        <span class="block text-xs text-gray-500">User List</span>
                    </a>
                    <a href="{{ route('admin.users.create') }}" 
                       class="block px-4 py-3 text-sm rounded-xl transition-all duration-200 {{ request()->routeIs('admin.users.create') ? 'bg-purple-500/20 text-purple-300' : 'text-gray-400 hover:text-white hover:bg-white/5' }}">
                        <span class="font-medium">Tambah User</span>
                        <span class="block text-xs text-gray-500">Add New User</span>
                    </a>
                </div>
            </div>

            <div class="border-t border-white/10 my-6"></div>

            <!-- Reports -->
            <div class="relative group">
                <a href="#" 
                   class="flex items-center px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 text-gray-300 hover:text-white hover:bg-white/5">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 group-hover:from-indigo-500 group-hover:to-indigo-600 mr-4 transition-all duration-300 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <span class="block text-base font-bold">Reports</span>
                        <span class="text-xs text-gray-400">Analytics & Export</span>
                    </div>
                </a>
            </div>

            <!-- Settings -->
            <div class="relative group">
                <a href="#" 
                   class="flex items-center px-6 py-4 text-sm font-semibold rounded-2xl transition-all duration-300 text-gray-300 hover:text-white hover:bg-white/5">
                    <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-gray-600 to-gray-700 group-hover:from-yellow-500 group-hover:to-orange-500 mr-4 transition-all duration-300 shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <span class="block text-base font-bold">Settings</span>
                        <span class="text-xs text-gray-400">System Config</span>
                    </div>
                </a>
            </div>
        </nav>

        <!-- Premium User Profile -->
        <div class="border-t border-white/10 p-6">
            <div class="glass-morphism rounded-2xl p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-14 h-14 bg-gradient-to-br from-purple-500 via-blue-500 to-emerald-500 rounded-2xl flex items-center justify-center text-white font-bold shadow-2xl premium-glow animate-float">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-bold text-white">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-400">{{ auth()->user()->role->name ?? 'Administrator' }}</p>
                        <div class="flex items-center mt-2">
                            <div class="w-2 h-2 bg-emerald-400 rounded-full mr-2 animate-pulse shadow-lg"></div>
                            <span class="text-xs text-emerald-400 font-medium">Online</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white transition-colors duration-200 p-3 rounded-xl hover:bg-white/10 premium-glow">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>