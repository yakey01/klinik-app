<x-filament-panels::page>
    @push('styles')
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Premium Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', '-apple-system', 'BlinkMacSystemFont', 'SF Pro Display', 'Segoe UI', 'Roboto', sans-serif],
                    },
                    backdropBlur: {
                        'xs': '2px',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'float': 'float 3s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: 0, transform: 'translateY(10px)' },
                            '100%': { opacity: 1, transform: 'translateY(0)' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(100%)' },
                            '100%': { transform: 'translateY(0)' },
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-6px)' },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Hide FilamentPHP elements untuk mobile */
        @media (max-width: 1024px) {
            .fi-sidebar,
            .fi-topbar {
                display: none !important;
            }
            .fi-main {
                margin-left: 0 !important;
            }
            .fi-page {
                background: transparent !important;
            }
        }
        
        /* Premium gradient background exact seperti design */
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .fi-main-content {
            background: transparent !important;
        }
        
        /* Custom scrollbar untuk premium feel */
        ::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
        
        /* Prevent text selection untuk native app feel */
        .dashboard-container {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Premium touch feedback */
        .touch-feedback:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
        
        /* Glassmorphism effects */
        .glass-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        
        .glass-strong {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        /* Bottom navigation exact seperti design */
        .bottom-nav {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .nav-item {
            transition: all 0.2s ease;
        }
        
        .nav-item.active {
            color: #22d3ee;
        }
        
        .nav-item:not(.active) {
            color: #6b7280;
        }
        
        /* Floating animation untuk cards */
        .card-float:hover {
            animation: float 0.6s ease-in-out;
        }
    </style>
    @endpush
    
    <!-- Single Root Element for Livewire -->
    <div class="relative">
        <!-- Main Jaspel Container -->
        <div class="dashboard-container min-h-screen relative">
            <!-- Background gradient overlay -->
            <div class="fixed inset-0 bg-gradient-to-br from-indigo-500 via-purple-500 to-blue-600 -z-10"></div>
            
            <!-- Container dengan max-width sesuai mobile -->
            <div class="max-w-sm mx-auto min-h-screen px-6 py-8 relative pb-24">
            
            <!-- Header dengan Profile -->
            <div class="flex items-center justify-between mb-8 animate-fade-in">
                <!-- Back Button -->
                <a href="{{ route('filament.dokter.pages.dashboard-dokter') }}" 
                   class="p-2 rounded-lg glass-card hover:glass-strong transition-all duration-300">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                
                <!-- Title -->
                <h1 class="text-white text-xl font-medium tracking-wide">Beranda</h1>
                
                <!-- Profile Avatar -->
                <div class="w-8 h-8 rounded-full overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=100&h=100&fit=crop&crop=face" 
                         alt="Dr. SARI" class="w-full h-full object-cover">
                </div>
            </div>
            
            <!-- User Info Section -->
            <div class="mb-6 animate-slide-up" style="animation-delay: 0.1s;">
                <h2 class="text-white text-3xl font-light mb-1">dr. SARI</h2>
                <p class="text-white/80 text-base">- Dokter Umum</p>
            </div>
            
            <!-- Jaspel Bulan Ini Card - Exact Design -->
            <div class="glass-strong rounded-3xl p-6 mb-6 card-float animate-slide-up" style="animation-delay: 0.2s;">
                <div class="text-center">
                    <p class="text-gray-700 text-lg font-medium mb-2">Jaspel Bulan Ini</p>
                    <h3 class="text-black text-4xl font-bold">
                        Rp {{ number_format($jaspelBulanIni ?? 20400000, 0, ',', '.') }}
                    </h3>
                </div>
            </div>
            
            <!-- Stats Cards Grid - 2x2 Layout -->
            <div class="grid grid-cols-2 gap-4 mb-8 animate-fade-in" style="animation-delay: 0.3s;">
                
                <!-- Menit Jaga Card - Orange/Yellow Gradient -->
                <div class="rounded-3xl p-6 text-white relative overflow-hidden card-float touch-feedback"
                     style="background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);">
                    <!-- Clock Icon -->
                    <div class="w-12 h-12 bg-white bg-opacity-25 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <!-- Text Content -->
                    <h4 class="text-white text-lg font-semibold mb-1">Menit Jaga</h4>
                    <p class="text-white/90 text-sm font-medium mb-2">Bulan Ini</p>
                    <h3 class="text-white text-2xl font-bold">{{ number_format($menitJagaBulanIni ?? 920, 0, ',', '.') }} Menit</h3>
                </div>
                
                <!-- Jaspel Minggu Ini Card - Blue Gradient -->
                <div class="rounded-3xl p-6 text-white relative overflow-hidden card-float touch-feedback"
                     style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                    <!-- Dollar Icon -->
                    <div class="w-12 h-12 bg-white bg-opacity-25 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <!-- Text Content -->
                    <h4 class="text-white text-lg font-semibold mb-1">Jaspel</h4>
                    <p class="text-white/90 text-sm font-medium mb-2">Minggu Ini</p>
                    <h3 class="text-white text-xl font-bold">
                        Rp {{ number_format($jaspelMingguIni ?? 5530000, 0, ',', '.') }}
                    </h3>
                </div>
                
            </div>
            
            <!-- Quick Actions -->
            <div class="space-y-4 animate-fade-in" style="animation-delay: 0.4s;">
                <!-- View All Jaspel -->
                <a href="{{ route('filament.dokter.resources.jaspel-dokters.index') }}" 
                   class="w-full glass-card rounded-2xl p-4 block hover:glass-strong transition-all duration-300 touch-feedback">
                    <div class="flex items-center space-x-4">
                        <div class="p-3 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-white text-lg font-semibold">Lihat Semua Jaspel</div>
                            <div class="text-white/70 text-sm">Detail riwayat & status</div>
                        </div>
                        <div>
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </div>
                </a>
                
                <!-- Jaspel Pending -->
                @if($jaspelPending > 0)
                <div class="glass-card rounded-2xl p-4 border border-yellow-400/30">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-yellow-400/20 rounded-lg">
                            <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-white font-medium">{{ $jaspelPending }} Jaspel Pending</div>
                            <div class="text-white/70 text-sm">Menunggu validasi bendahara</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            
            <!-- Bottom spacing untuk navigation -->
            <div class="h-16"></div>
            
        </div>
        
        <!-- Bottom Navigation - Exact Design Match -->
        <div class="fixed bottom-0 left-0 right-0 bottom-nav">
        <div class="max-w-sm mx-auto px-6 py-3">
            <div class="flex items-center justify-around">
                <!-- Beranda -->
                <a href="{{ route('filament.dokter.pages.dashboard-dokter') }}" class="nav-item flex flex-col items-center space-y-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-xs font-medium">Beranda</span>
                </a>
                
                <!-- Riwayat -->
                <a href="{{ route('filament.dokter.resources.dokter-presensis.index') }}" class="nav-item flex flex-col items-center space-y-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-xs font-medium">Riwayat</span>
                </a>
                
                <!-- Jaspel - Active -->
                <div class="nav-item active flex flex-col items-center space-y-1">
                    <div class="w-12 h-12 bg-cyan-400 rounded-2xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium">Jaspel</span>
                </div>
                
                <!-- Presensi -->
                <a href="{{ route('filament.dokter.pages.presensi-mobile-page') }}" class="nav-item flex flex-col items-center space-y-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="text-xs font-medium">Presensi</span>
                </a>
                
                <!-- Akun -->
                <a href="#" class="nav-item flex flex-col items-center space-y-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="text-xs font-medium">Akun</span>
                </a>
            </div>
        </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Premium touch feedback untuk semua cards
            const cards = document.querySelectorAll('.touch-feedback');
            
            cards.forEach(card => {
                // Enhanced touch start
                card.addEventListener('touchstart', function(e) {
                    this.style.transform = 'scale(0.95)';
                    this.style.transition = 'transform 0.1s ease';
                    
                    // Haptic feedback jika tersedia
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }
                });
                
                // Touch end
                card.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                // Touch cancel
                card.addEventListener('touchcancel', function() {
                    this.style.transform = 'scale(1)';
                    this.style.transition = 'transform 0.2s ease';
                });
            });
            
            // Staggered animation untuk elements
            const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-slide-up');
            animatedElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
                element.style.animationFillMode = 'both';
            });
            
            // Console log untuk debugging
            console.log('🚀 Premium Jaspel Mobile Page Loaded - 100% Match Design');
        });
    </script>
    @endpush
</x-filament-panels::page>