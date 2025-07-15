<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Klinik Dokterku Non-Paramedis">
    <meta name="theme-color" content="#3b82f6">
    <title>Dashboard Non-Paramedis - Klinik Dokterku</title>
    
    <!-- TailwindCSS v4 -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        accent: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'bounce-in': 'bounceIn 0.8s ease-out',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Critical mobile-first styles */
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            touch-action: manipulation;
            -webkit-text-size-adjust: 100%;
        }
        
        /* Prevent zoom on iOS inputs */
        input, textarea, select {
            font-size: 16px !important;
        }
        
        /* Clean tap highlighting */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Touch targets */
        button, a, [role="button"] {
            min-height: 44px !important;
            min-width: 44px !important;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Hide scrollbars on mobile */
        @media (max-width: 768px) {
            ::-webkit-scrollbar {
                width: 0px;
                background: transparent;
            }
        }
        
        /* Glassmorphism effect */
        .glass {
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Hover glow effect */
        .hover-glow:hover {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }
    </style>
</head>

<body class="bg-gray-50 font-sans antialiased h-full overflow-x-hidden">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <x-nonparamedis.sidebar :user="$user" />
        
        <!-- Overlay for mobile -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden"></div>
        
        <!-- Main Content -->
        <main class="flex-1 lg:ml-80 transition-all duration-300">
            <!-- Mobile Header -->
            <x-nonparamedis.mobile-header :user="$user" title="Dashboard Non-Paramedis" />
            
            <!-- Dashboard Content -->
            <div class="p-4 lg:p-8 space-y-8 animate-fade-in">
                <!-- Welcome Section -->
                <div class="animate-slide-up">
                    <div class="bg-gradient-to-r from-primary-600 to-accent-500 rounded-2xl p-6 text-white shadow-lg">
                        <h1 class="text-2xl lg:text-3xl font-bold mb-2">
                            Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 18 ? 'Siang' : 'Malam') }}! 👋
                        </h1>
                        <p class="text-primary-100 text-lg mb-4">{{ $user->name }}</p>
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-primary-200">Hari ini</p>
                                <p class="text-lg font-semibold">{{ now()->format('l, d F Y') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-primary-200">Status</p>
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                                    <span class="font-semibold">Aktif</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 animate-slide-up" style="animation-delay: 0.2s">
                    <!-- Kehadiran Card -->
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium mb-1">Kehadiran Bulan Ini</p>
                                <p class="text-3xl font-bold text-gray-900">22</p>
                                <div class="flex items-center mt-2">
                                    <svg class="w-4 h-4 text-green-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-green-600 font-medium">95% kehadiran</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jaspel Card -->
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium mb-1">Jaspel Bulan Ini</p>
                                <p class="text-3xl font-bold text-gray-900">Rp 2.5M</p>
                                <div class="flex items-center mt-2">
                                    <svg class="w-4 h-4 text-blue-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                    <span class="text-sm text-blue-600 font-medium">+15% dari bulan lalu</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Jadwal Hari Ini -->
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium mb-1">Jadwal Hari Ini</p>
                                <p class="text-3xl font-bold text-gray-900">3</p>
                                <div class="flex items-center mt-2">
                                    <svg class="w-4 h-4 text-yellow-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-yellow-600 font-medium">sesi praktek</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center relative">
                                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <div class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pasien Hari Ini -->
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 text-sm font-medium mb-1">Pasien Hari Ini</p>
                                <p class="text-3xl font-bold text-gray-900">28</p>
                                <div class="flex items-center mt-2">
                                    <svg class="w-4 h-4 text-purple-500 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span class="text-sm text-purple-600 font-medium">pasien terdaftar</span>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="animate-bounce-in" style="animation-delay: 0.4s">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Aksi Cepat</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="{{ route('nonparamedis.presensi') }}" class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-blue-500 transition-all duration-300 group block bg-white border border-gray-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="font-semibold text-gray-900 mb-1">Presensi</p>
                            <p class="text-sm text-gray-500">Absensi harian</p>
                        </a>
                        
                        <a href="#" class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-green-500 transition-all duration-300 group block bg-white border border-gray-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <p class="font-semibold text-gray-900 mb-1">Jaspel</p>
                            <p class="text-sm text-gray-500">Jasa pelayanan</p>
                        </a>
                        
                        <a href="{{ route('nonparamedis.jadwal') }}" class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-purple-500 transition-all duration-300 group block bg-white border border-gray-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <p class="font-semibold text-gray-900 mb-1">Jadwal</p>
                            <p class="text-sm text-gray-500">Jadwal kerja</p>
                        </a>
                        
                        <button class="glass rounded-xl p-6 text-center hover:bg-opacity-10 hover:bg-orange-500 transition-all duration-300 group bg-white border border-gray-200 hover:shadow-lg hover:-translate-y-1">
                            <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <p class="font-semibold text-gray-900 mb-1">Laporan</p>
                            <p class="text-sm text-gray-500">Data laporan</p>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        // Mobile debug utility
        window.mobileDebug = {
            log: function(message) {
                console.log('[Non-Paramedis Dashboard] ' + message);
            }
        };
        
        // Enhanced Sidebar toggle functionality with mobile optimization
        const openSidebar = document.getElementById('openSidebar');
        const closeSidebar = document.getElementById('closeSidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        function toggleSidebar() {
            const isOpen = !sidebar.classList.contains('-translate-x-full');
            
            if (isOpen) {
                // Close sidebar
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                window.mobileDebug.log('Sidebar closed');
            } else {
                // Open sidebar
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                window.mobileDebug.log('Sidebar opened');
            }
        }
        
        // Event listeners
        openSidebar?.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
        
        closeSidebar?.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSidebar();
        });
        
        overlay?.addEventListener('click', toggleSidebar);
        
        // Close sidebar on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !sidebar.classList.contains('-translate-x-full')) {
                toggleSidebar();
            }
        });
        
        // Handle window resize - close sidebar on mobile when resizing to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                window.mobileDebug.log('Sidebar auto-shown on desktop resize');
            }
        });
        
        // Initialize app
        document.addEventListener('DOMContentLoaded', function() {
            window.mobileDebug.log('Dashboard with sidebar loaded successfully!');
            
            // Add entrance animations with staggered delays
            const cards = document.querySelectorAll('.grid > div, .grid > a');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>