<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Petugas - Dokterku</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'medical': {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            --medical-50: #f0fdf4;
            --medical-100: #dcfce7;
            --medical-200: #bbf7d0;
            --medical-300: #86efac;
            --medical-400: #4ade80;
            --medical-500: #22c55e;
            --medical-600: #16a34a;
            --medical-700: #15803d;
            --medical-800: #166534;
            --medical-900: #14532d;
        }
        
        .petugas-sidebar {
            width: 280px;
            min-width: 280px;
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
        }
        
        .gradient-medical {
            background: linear-gradient(135deg, var(--medical-600) 0%, var(--medical-700) 100%);
        }
        
        /* Custom isolated styles for non-Filament dashboard */
        .petugas-isolated {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .dark .glass-card {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }
        
        .gradient-medical {
            background: linear-gradient(135deg, #10b981 0%, #047857 100%);
        }
        
        .hover-lift {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse-green {
            animation: pulseGreen 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulseGreen {
            0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
        }
        
        /* Sidebar Styles - Isolated */
        .petugas-sidebar {
            width: 256px; /* 16rem - same as Filament default */
            transition: all 0.3s ease;
        }
        
        .petugas-sidebar.collapsed {
            width: 64px; /* 4rem - collapsed width */
        }
        
        .sidebar-nav-group {
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .sidebar-nav-group-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #e2e8f0;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 0.5rem;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav-group-header:hover {
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(2px);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .sidebar-nav-group-header.collapsed {
            background-color: rgba(255, 255, 255, 0.05);
            color: #94a3b8;
        }
        
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            margin: 0.125rem 0.5rem;
            font-size: 0.875rem;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid transparent;
        }
        
        .sidebar-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
            border-color: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        
        .sidebar-nav-item.active {
            background-color: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            font-weight: 600;
            border-color: rgba(34, 197, 94, 0.3);
        }
        
        .sidebar-nav-item svg {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }
        
        .sidebar-nav-group-content {
            overflow: hidden;
            transition: all 0.3s ease;
            max-height: 1000px; /* Default expanded state */
            opacity: 1;
            display: block; /* Ensure content is visible */
        }
        
        .sidebar-nav-group.collapsed .sidebar-nav-group-content {
            max-height: 0;
            opacity: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-top: 0;
            margin-bottom: 0;
        }
        
        .sidebar-nav-group:not(.collapsed) .sidebar-nav-group-content {
            max-height: 1000px;
            opacity: 1;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            margin-top: 0.25rem;
            margin-bottom: 0.25rem;
        }
        
        .sidebar-toggle-icon {
            transition: transform 0.2s ease;
        }
        
        .sidebar-nav-group.collapsed .sidebar-toggle-icon {
            transform: rotate(-90deg);
        }
        
        /* Mobile sidebar */
        @media (max-width: 768px) {
            .petugas-sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                z-index: 50;
                transform: translateX(-100%);
            }
            
            .petugas-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 40;
            }
        }

        /* Force all navigation groups to be expanded by default */
        .sidebar-nav-group {
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        /* Override any collapsed state */
        .sidebar-nav-group,
        .sidebar-nav-group:not(.collapsed) {
            /* Force expanded state */
        }
        
        .sidebar-nav-group-content {
            overflow: hidden;
            transition: all 0.3s ease;
            max-height: 1000px !important; /* Force expanded */
            opacity: 1 !important; /* Force visible */
            display: block !important; /* Force display */
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
            margin-top: 0.25rem !important;
            margin-bottom: 0.25rem !important;
        }
        
        /* Only collapse when explicitly set */
        .sidebar-nav-group.collapsed .sidebar-nav-group-content {
            max-height: 0 !important;
            opacity: 0 !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
    </style>
</head>
<body class="petugas-isolated bg-gradient-to-br from-medical-50 to-blue-50 dark:from-gray-900 dark:to-gray-800 min-h-screen flex">
    
    <!-- Sidebar -->
    <aside id="petugas-sidebar" class="petugas-sidebar bg-white dark:bg-gray-900 shadow-lg border-r border-medical-200 dark:border-gray-700 h-screen overflow-y-auto">
        <!-- Sidebar Header -->
        <div class="gradient-medical p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-medical-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-2m-2 0H5m14 0v-4a2 2 0 00-2-2h-2a2 2 0 00-2 2v4"></path>
                    </svg>
                </div>
                <div class="text-white">
                    <h2 class="font-bold text-sm">🏥 Dokterku</h2>
                    <p class="text-xs text-medical-100">Petugas</p>
                </div>
            </div>
            <button onclick="toggleSidebar()" class="text-white hover:bg-medical-700 p-1 rounded lg:hidden">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <nav class="p-4 space-y-2">
            <!-- Dashboard Group -->
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-group-header" onclick="toggleNavGroup(this)">
                    <span>📊 Dashboard</span>
                    <svg class="sidebar-toggle-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <div class="sidebar-nav-group-content">
                    <a href="#" class="sidebar-nav-item active">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                </div>
            </div>

            <!-- Manajemen Pasien Group -->
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-group-header" onclick="toggleNavGroup(this)">
                    <span>🏥 Manajemen Pasien</span>
                    <svg class="sidebar-toggle-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <div class="sidebar-nav-group-content">
                    <a href="/petugas/pasiens" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Input Pasien
                    </a>
                    <a href="/petugas/tindakans" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3"></path>
                        </svg>
                        Input Tindakan
                    </a>
                </div>
            </div>

            <!-- Input Data Harian Group -->
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-group-header" onclick="toggleNavGroup(this)">
                    <span>📊 Input Data Harian</span>
                    <svg class="sidebar-toggle-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <div class="sidebar-nav-group-content">
                    <a href="/petugas/pendapatan-harians" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        Input Pendapatan
                    </a>
                    <a href="/petugas/pengeluaran-harians" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                        Input Pengeluaran
                    </a>
                    <a href="/petugas/jumlah-pasien-harians" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Input Jumlah Pasien
                    </a>
                </div>
            </div>

            <!-- Transaksi Group -->
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-group-header" onclick="toggleNavGroup(this)">
                    <span>💰 Transaksi</span>
                    <svg class="sidebar-toggle-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <div class="sidebar-nav-group-content">
                    <a href="/petugas/pendapatans" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        Pendapatan Lainnya
                    </a>
                </div>
            </div>

            <!-- Enhanced Management Group -->
            <div class="sidebar-nav-group">
                <div class="sidebar-nav-group-header" onclick="toggleNavGroup(this)">
                    <span>✨ Enhanced Management</span>
                    <svg class="sidebar-toggle-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
                <div class="sidebar-nav-group-content">
                    <a href="{{ route('petugas.enhanced.pasien.index') }}" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Enhanced Pasien
                    </a>
                    <a href="{{ route('petugas.enhanced.tindakan.index') }}" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Enhanced Tindakan
                    </a>
                    <a href="{{ route('petugas.enhanced.pendapatan.index') }}" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        Enhanced Pendapatan
                    </a>
                    <a href="{{ route('petugas.enhanced.pengeluaran.index') }}" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                        </svg>
                        Enhanced Pengeluaran
                    </a>
                    <a href="{{ route('petugas.enhanced.jumlah-pasien.index') }}" class="sidebar-nav-item">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Laporan Jumlah Pasien
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" class="sidebar-backdrop hidden lg:hidden" onclick="toggleSidebar()"></div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-900 shadow-lg border-b border-medical-200 dark:border-gray-700">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <div class="flex items-center space-x-4">
                        <!-- Mobile Menu Button -->
                        <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 gradient-medical rounded-xl flex items-center justify-center pulse-green">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Dashboard Petugas</h1>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Enhanced UI/UX</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors">
                            <svg class="w-5 h-5 hidden dark:block" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                            </svg>
                            <svg class="w-5 h-5 dark:hidden" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                            </svg>
                        </button>
                        
                        <!-- Refresh Button -->
                        <button onclick="refreshData()" class="inline-flex items-center px-4 py-2 bg-medical-600 hover:bg-medical-700 text-white font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-auto">
        
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Pasien Hari Ini -->
            <div class="glass-card rounded-xl p-6 hover-lift animate-fade-in shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Pasien Hari Ini</p>
                        <p class="text-3xl font-bold text-medical-600 dark:text-medical-400 mt-2" id="pasien-count">24</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +12% dari kemarin
                            </span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pendapatan Hari Ini -->
            <div class="glass-card rounded-xl p-6 hover-lift animate-fade-in shadow-lg" style="animation-delay: 0.1s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Pendapatan</p>
                        <p class="text-3xl font-bold text-medical-600 dark:text-medical-400 mt-2" id="pendapatan-count">Rp 2.4M</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +8% dari kemarin
                            </span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tindakan Selesai -->
            <div class="glass-card rounded-xl p-6 hover-lift animate-fade-in shadow-lg" style="animation-delay: 0.2s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Tindakan</p>
                        <p class="text-3xl font-bold text-medical-600 dark:text-medical-400 mt-2" id="tindakan-count">18</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +5% dari kemarin
                            </span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Efisiensi -->
            <div class="glass-card rounded-xl p-6 hover-lift animate-fade-in shadow-lg" style="animation-delay: 0.3s">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400 uppercase tracking-wide">Efisiensi</p>
                        <p class="text-3xl font-bold text-medical-600 dark:text-medical-400 mt-2" id="efisiensi-count">94%</p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            <span class="inline-flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                +2% dari kemarin
                            </span>
                        </p>
                    </div>
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Revenue Chart -->
            <div class="glass-card rounded-xl p-6 shadow-lg hover-lift animate-fade-in" style="animation-delay: 0.4s">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pendapatan Mingguan</h3>
                    <div class="flex space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                            Trending Up
                        </span>
                    </div>
                </div>
                <div id="revenue-chart" class="h-64"></div>
            </div>

            <!-- Patient Distribution Chart -->
            <div class="glass-card rounded-xl p-6 shadow-lg hover-lift animate-fade-in" style="animation-delay: 0.5s">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Distribusi Tindakan</h3>
                    <div class="flex space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            Hari Ini
                        </span>
                    </div>
                </div>
                <div id="donut-chart" class="h-64"></div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="glass-card rounded-xl p-6 shadow-lg hover-lift animate-fade-in" style="animation-delay: 0.6s">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Aksi Cepat</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <button class="flex items-center p-4 bg-medical-50 dark:bg-medical-900 rounded-lg hover:bg-medical-100 dark:hover:bg-medical-800 transition-colors group">
                    <div class="w-10 h-10 bg-medical-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900 dark:text-white">Tambah Pasien</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Registrasi pasien baru</p>
                    </div>
                </button>

                <button class="flex items-center p-4 bg-blue-50 dark:bg-blue-900 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-800 transition-colors group">
                    <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900 dark:text-white">Input Tindakan</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Catat tindakan medis</p>
                    </div>
                </button>

                <button class="flex items-center p-4 bg-green-50 dark:bg-green-900 rounded-lg hover:bg-green-100 dark:hover:bg-green-800 transition-colors group">
                    <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900 dark:text-white">Laporan Harian</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Lihat laporan hari ini</p>
                    </div>
                </button>

                <button class="flex items-center p-4 bg-purple-50 dark:bg-purple-900 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-800 transition-colors group">
                    <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center mr-3 group-hover:scale-110 transition-transform">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900 dark:text-white">Pengaturan</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Konfigurasi sistem</p>
                    </div>
                </button>
            </div>
        </div>
        
        </main>
    </div>

    <script src="/js/petugas-chart.js"></script>
    <script>
        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('petugas-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (window.innerWidth < 1024) { // Mobile
                sidebar.classList.toggle('mobile-open');
                backdrop.classList.toggle('hidden');
            } else { // Desktop
                sidebar.classList.toggle('collapsed');
            }
        }

        function toggleNavGroup(header) {
            const group = header.closest('.sidebar-nav-group');
            const icon = header.querySelector('.sidebar-toggle-icon');
            const content = group.querySelector('.sidebar-nav-group-content');
            
            console.log('Toggle clicked for:', header.querySelector('span').textContent);
            console.log('Current collapsed state:', group.classList.contains('collapsed'));
            
            // Toggle collapsed state
            group.classList.toggle('collapsed');
            
            // Force reflow to ensure transition works
            content.offsetHeight;
            
            // Add visual feedback
            if (group.classList.contains('collapsed')) {
                // Group is now collapsed
                header.style.backgroundColor = 'rgba(255, 255, 255, 0.05)';
                header.style.color = '#94a3b8';
                console.log('Group collapsed:', header.querySelector('span').textContent);
            } else {
                // Group is now expanded
                header.style.backgroundColor = 'rgba(255, 255, 255, 0.15)';
                header.style.color = '#f1f5f9';
                console.log('Group expanded:', header.querySelector('span').textContent);
            }
            
            // Log final state
            console.log('Final collapsed state:', group.classList.contains('collapsed'));
        }

        // Dark mode toggle
        function toggleDarkMode() {
            document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', document.documentElement.classList.contains('dark'));
        }

        // Initialize dark mode from localStorage
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }

        // Refresh data function
        function refreshData() {
            // Add loading state
            const button = event.target.closest('button');
            const originalText = button.innerHTML;
            button.innerHTML = '<svg class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Memuat...';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                // Simulate data refresh
                updateStats();
                updateCharts();
            }, 1500);
        }

        function updateStats() {
            // Simulate real-time data updates
            document.getElementById('pasien-count').textContent = Math.floor(Math.random() * 50) + 20;
            document.getElementById('pendapatan-count').textContent = 'Rp ' + (Math.random() * 5 + 1).toFixed(1) + 'M';
            document.getElementById('tindakan-count').textContent = Math.floor(Math.random() * 30) + 15;
            document.getElementById('efisiensi-count').textContent = Math.floor(Math.random() * 10 + 90) + '%';
        }

        function updateCharts() {
            // This will be called by the chart script
            if (window.petugasCharts) {
                window.petugasCharts.updateCharts();
            }
        }

        // Initialize sidebar navigation groups
        function initializeSidebarGroups() {
            console.log('🚀 INITIALIZING SIDEBAR NAVIGATION GROUPS...');
            
            const navGroups = document.querySelectorAll('.sidebar-nav-group');
            console.log('📊 Found', navGroups.length, 'navigation groups');
            
            navGroups.forEach((group, index) => {
                const header = group.querySelector('.sidebar-nav-group-header');
                const content = group.querySelector('.sidebar-nav-group-content');
                const groupName = header.querySelector('span').textContent;
                
                console.log(`🔧 Processing group ${index + 1}:`, groupName);
                
                // Force remove collapsed class
                group.classList.remove('collapsed');
                
                // Force content to be visible with inline styles
                content.style.setProperty('display', 'block', 'important');
                content.style.setProperty('max-height', '1000px', 'important');
                content.style.setProperty('opacity', '1', 'important');
                content.style.setProperty('padding-top', '0.5rem', 'important');
                content.style.setProperty('padding-bottom', '0.5rem', 'important');
                content.style.setProperty('margin-top', '0.25rem', 'important');
                content.style.setProperty('margin-bottom', '0.25rem', 'important');
                
                // Set proper visual state
                header.style.setProperty('background-color', 'rgba(255, 255, 255, 0.15)', 'important');
                header.style.setProperty('color', '#f1f5f9', 'important');
                
                console.log(`✅ Group "${groupName}" FORCED EXPANDED`);
            });
            
            console.log('🎉 SIDEBAR INITIALIZATION COMPLETE - ALL GROUPS SHOULD BE EXPANDED');
        }
        
        // Force immediate initialization
        console.log('⚡ IMMEDIATE SIDEBAR INITIALIZATION STARTING...');
        setTimeout(() => {
            initializeSidebarGroups();
        }, 100);
        
        // Also initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🌐 DOM Content Loaded - Starting initialization...');
            
            if (window.petugasCharts) {
                console.log('📈 Initializing petugas charts...');
                window.petugasCharts.init();
            } else {
                console.log('⚠️ Petugas charts not available');
            }
            
            // Initialize sidebar navigation groups
            console.log('🔄 Starting sidebar initialization...');
            initializeSidebarGroups();
            
            console.log('✅ Page initialization complete');
        });

        // Auto-refresh every 30 seconds
        setInterval(updateStats, 30000);

        // Handle window resize for sidebar
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('petugas-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('mobile-open');
                backdrop.classList.add('hidden');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('petugas-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (window.innerWidth < 1024 && 
                !sidebar.contains(event.target) && 
                !event.target.closest('[onclick="toggleSidebar()"]')) {
                sidebar.classList.remove('mobile-open');
                backdrop.classList.add('hidden');
            }
        });
    </script>
</body>
</html>