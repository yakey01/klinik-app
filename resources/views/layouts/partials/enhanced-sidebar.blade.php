<!-- Enhanced Sidebar for Petugas Panel -->
<div class="flex flex-col h-full">
    <!-- Logo and Brand -->
    <div class="flex items-center flex-shrink-0 px-4 py-6 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-3">
            <div class="h-10 w-10 bg-medical-500 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Enhanced</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Petugas Dashboard</p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
        <!-- Dashboard -->
        <a href="/petugas/enhanced-dashboard" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced-dashboard*') ? 'bg-medical-50 text-medical-700 dark:bg-medical-900/50 dark:text-medical-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
            <svg class="mr-3 h-5 w-5 {{ request()->is('petugas/enhanced-dashboard*') ? 'text-medical-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5zM8 15h8"></path>
            </svg>
            Dashboard
        </a>

        <!-- Patient Management -->
        <div x-data="{ open: {{ request()->is('petugas/enhanced/pasien*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/pasien*') ? 'bg-medical-50 text-medical-700 dark:bg-medical-900/50 dark:text-medical-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 {{ request()->is('petugas/enhanced/pasien*') ? 'text-medical-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    Manajemen Pasien
                </div>
                <svg class="h-4 w-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open" class="mt-2 space-y-1 pl-8" style="display: none;">
                <a href="/petugas/enhanced/pasien" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/pasien') && !request()->is('petugas/enhanced/pasien/create') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Daftar Pasien
                </a>
                <a href="/petugas/enhanced/pasien/create" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/pasien/create') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Pasien
                </a>
            </div>
        </div>

        <!-- Medical Procedures -->
        <div x-data="{ open: {{ request()->is('petugas/enhanced/tindakan*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/tindakan*') ? 'bg-medical-50 text-medical-700 dark:bg-medical-900/50 dark:text-medical-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 {{ request()->is('petugas/enhanced/tindakan*') ? 'text-medical-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                    </svg>
                    Tindakan Medis
                </div>
                <svg class="h-4 w-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open" class="mt-2 space-y-1 pl-8" style="display: none;">
                <a href="/petugas/enhanced/tindakan" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/tindakan') && !request()->is('petugas/enhanced/tindakan/create') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Timeline Tindakan
                </a>
                <a href="/petugas/enhanced/tindakan/create" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/tindakan/create') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Input Tindakan
                </a>
            </div>
        </div>

        <!-- Financial Management -->
        <div x-data="{ open: {{ request()->is('petugas/enhanced/pendapatan*') || request()->is('petugas/enhanced/pengeluaran*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/pendapatan*') || request()->is('petugas/enhanced/pengeluaran*') ? 'bg-medical-50 text-medical-700 dark:bg-medical-900/50 dark:text-medical-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 {{ request()->is('petugas/enhanced/pendapatan*') || request()->is('petugas/enhanced/pengeluaran*') ? 'text-medical-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Keuangan
                </div>
                <svg class="h-4 w-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open" class="mt-2 space-y-1 pl-8" style="display: none;">
                <a href="/petugas/enhanced/pendapatan" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/pendapatan*') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                    </svg>
                    Pendapatan
                </a>
                <a href="/petugas/enhanced/pengeluaran" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/pengeluaran*') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                    </svg>
                    Pengeluaran
                </a>
            </div>
        </div>

        <!-- Reports & Analytics -->
        <div x-data="{ open: {{ request()->is('petugas/enhanced/jumlah-pasien*') || request()->is('petugas/enhanced/analytics*') ? 'true' : 'false' }} }">
            <button @click="open = !open" class="w-full group flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/jumlah-pasien*') || request()->is('petugas/enhanced/analytics*') ? 'bg-medical-50 text-medical-700 dark:bg-medical-900/50 dark:text-medical-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                <div class="flex items-center">
                    <svg class="mr-3 h-5 w-5 {{ request()->is('petugas/enhanced/jumlah-pasien*') || request()->is('petugas/enhanced/analytics*') ? 'text-medical-500' : 'text-gray-400 group-hover:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    Laporan & Analytics
                </div>
                <svg class="h-4 w-4 transform transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open" class="mt-2 space-y-1 pl-8" style="display: none;">
                <a href="/petugas/enhanced/jumlah-pasien" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/jumlah-pasien*') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Kalender Pasien
                </a>
                <a href="/petugas/enhanced/analytics" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg {{ request()->is('petugas/enhanced/analytics*') ? 'bg-petugas-50 text-petugas-700 dark:bg-petugas-900/30 dark:text-petugas-300' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700' }} transition-colors">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    ML Insights
                </a>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

        <!-- Quick Actions -->
        <div class="space-y-2">
            <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Quick Actions
            </h3>
            
            <a href="/petugas/enhanced/pasien/create" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-colors">
                <svg class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                </svg>
                Daftar Pasien Baru
            </a>
            
            <a href="/petugas/enhanced/tindakan/create" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-colors">
                <svg class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Input Tindakan
            </a>
        </div>

        <!-- Divider -->
        <div class="border-t border-gray-200 dark:border-gray-700 my-4"></div>

        <!-- System Links -->
        <div class="space-y-2">
            <h3 class="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                System
            </h3>
            
            <a href="/petugas" class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-colors">
                <svg class="mr-3 h-4 w-4 text-gray-400 group-hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v4H8V5z"></path>
                </svg>
                Standard Dashboard
            </a>
        </div>
    </nav>

    <!-- Footer with status -->
    <div class="flex-shrink-0 px-4 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center space-x-2 text-xs text-gray-500 dark:text-gray-400">
            <div class="h-2 w-2 bg-green-400 rounded-full"></div>
            <span>Enhanced Mode Active</span>
        </div>
        <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">
            v2.0 • {{ date('Y') }} Dokterku
        </div>
    </div>
</div>