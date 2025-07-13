<x-filament-panels::page>
    @push('styles')
    <!-- Tailwind CSS 4.0 CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Premium Professional Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=SF+Pro+Display:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sf': ['SF Pro Display', 'Inter', '-apple-system', 'BlinkMacSystemFont', 'system-ui', 'sans-serif'],
                        'inter': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        // Professional Fintech Color Palette
                        'primary': {
                            50: '#f0f4ff',
                            100: '#e0e8ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        'purple-custom': {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7c3aed',
                            800: '#6b21a8',
                            900: '#581c87',
                        },
                        'success': '#10b981',
                        'warning': '#f59e0b',
                        'danger': '#ef4444',
                        'info': '#06b6d4',
                        'gray-custom': {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827',
                        }
                    },
                    spacing: {
                        '18': '4.5rem',
                        '88': '22rem',
                        '128': '32rem',
                    },
                    borderRadius: {
                        '4xl': '2rem',
                        '5xl': '2.5rem',
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'medium': '0 4px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'large': '0 10px 50px -12px rgba(0, 0, 0, 0.25)',
                        'glow': '0 0 20px rgba(99, 102, 241, 0.3)',
                        'glow-purple': '0 0 30px rgba(168, 85, 247, 0.4)',
                        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                        'floating': '0 20px 40px -15px rgba(0, 0, 0, 0.2)',
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1)',
                        'slide-down': 'slideDown 0.5s ease-out',
                        'scale-in': 'scaleIn 0.4s cubic-bezier(0.16, 1, 0.3, 1)',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
                        'shimmer': 'shimmer 2s linear infinite',
                        'bounce-soft': 'bounceSoft 1s ease-in-out',
                        'gradient-x': 'gradientX 3s ease infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0', transform: 'translateY(10px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideUp: {
                            '0%': { opacity: '0', transform: 'translateY(30px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        slideDown: {
                            '0%': { opacity: '0', transform: 'translateY(-20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' }
                        },
                        scaleIn: {
                            '0%': { opacity: '0', transform: 'scale(0.9)' },
                            '100%': { opacity: '1', transform: 'scale(1)' }
                        },
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' }
                        },
                        pulseSoft: {
                            '0%, 100%': { opacity: '1' },
                            '50%': { opacity: '0.8' }
                        },
                        shimmer: {
                            '0%': { transform: 'translateX(-100%)' },
                            '100%': { transform: 'translateX(100%)' }
                        },
                        bounceSoft: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-5px)' }
                        },
                        gradientX: {
                            '0%, 100%': { 'background-position': '0% 50%' },
                            '50%': { 'background-position': '100% 50%' }
                        }
                    },
                    backdropBlur: {
                        'xs': '2px',
                        '4xl': '72px',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Hide FilamentPHP elements for mobile */
        @media (max-width: 1024px) {
            .fi-sidebar, .fi-topbar { display: none !important; }
            .fi-main { margin-left: 0 !important; }
            .fi-page { background: transparent !important; }
        }
        
        /* Professional Background */
        body {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            min-height: 100vh;
        }
        
        /* Advanced Glassmorphism */
        .glass-effect {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .glass-dark {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* Premium Purple Gradient */
        .gradient-purple {
            background: linear-gradient(135deg, #7c3aed 0%, #a855f7 25%, #c084fc 75%, #d8b4fe 100%);
            background-size: 400% 400%;
            animation: gradientX 8s ease infinite;
        }
        
        /* Professional Card Effects */
        .card-professional {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }
        
        .card-professional:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.15);
        }
        
        .card-professional:active {
            transform: translateY(-2px) scale(0.98);
        }
        
        /* Feature Icons Background */
        .feature-bg-yellow { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); }
        .feature-bg-purple { background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%); }
        .feature-bg-blue { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); }
        .feature-bg-pink { background: linear-gradient(135deg, #fce7f3 0%, #fbcfe8 100%); }
        
        /* Activity Status */
        .status-confirmed { color: #10b981; background-color: #d1fae5; }
        .status-pending { color: #f59e0b; background-color: #fef3c7; }
        
        /* Professional Typography */
        .text-display {
            font-weight: 700;
            letter-spacing: -0.025em;
            line-height: 1.1;
        }
        
        .text-balance {
            font-weight: 800;
            font-size: 2.5rem;
            letter-spacing: -0.02em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        /* Bottom Navigation */
        .nav-floating {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .nav-home-button {
            background: linear-gradient(135deg, #4338ca 0%, #6366f1 100%);
            box-shadow: 0 8px 25px rgba(67, 56, 202, 0.4);
        }
        
        /* Professional Animations */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        
        .animate-on-scroll.in-view {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Touch Feedback */
        .touch-feedback:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 0px; background: transparent; }
        
        /* Performance Optimizations */
        * {
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            -webkit-tap-highlight-color: transparent;
        }
    </style>
    @endpush
    
    <!-- Main Container -->
    <div class="min-h-screen bg-gray-50">
        
        <!-- Professional Mobile Container -->
        <div class="max-w-sm mx-auto min-h-screen bg-white shadow-large relative">
            
            <!-- Purple Header Section -->
            <div class="gradient-purple rounded-b-5xl px-6 pt-12 pb-8 relative overflow-hidden">
                <!-- Background Pattern -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-10 left-10 w-32 h-32 bg-white rounded-full animate-float"></div>
                    <div class="absolute top-20 right-8 w-20 h-20 bg-white rounded-full animate-float" style="animation-delay: 1s;"></div>
                </div>
                
                <!-- Header Content -->
                <div class="relative z-10">
                    <!-- Top Bar -->
                    <div class="flex items-center justify-between mb-8 animate-fade-in">
                        <!-- User Profile -->
                        <div class="flex items-center space-x-3">
                            <div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center">
                                <img src="https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=100&h=100&fit=crop&crop=face" 
                                     alt="Dr. Profile" 
                                     class="w-10 h-10 rounded-full object-cover">
                            </div>
                            <div>
                                <p class="text-white/80 text-sm font-medium font-sf">Welcome back</p>
                                <h1 class="text-white text-lg font-bold font-sf">Hello, {{ explode(' ', $user->name ?? 'Dr. Alexandre')[0] }}!</h1>
                            </div>
                        </div>
                        
                        <!-- Notification Button -->
                        <button class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center touch-feedback">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Balance Card -->
                    <div class="glass-dark rounded-3xl p-6 text-center animate-slide-up" style="animation-delay: 0.2s;">
                        <p class="text-white/70 text-sm font-medium font-sf mb-2">Total Jaspel</p>
                        <h2 class="text-balance text-white font-sf">
                            Rp{{ number_format($monthlyJaspel ?? 3756000, 0, ',', '.') }}
                        </h2>
                        <p class="text-white/60 text-xs font-sf mt-2">Bulan {{ \Carbon\Carbon::now()->format('F Y') }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="px-6 -mt-6 relative z-20">
                <div class="grid grid-cols-2 gap-4 animate-slide-up" style="animation-delay: 0.3s;">
                    <!-- Presensi Button -->
                    <a href="{{ route('filament.dokter.pages.presensi-mobile-page') }}" 
                       class="bg-gradient-to-r from-cyan-500 to-blue-500 rounded-2xl p-4 flex items-center justify-center space-x-2 shadow-medium card-professional touch-feedback">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-white font-semibold font-sf">Presensi</span>
                    </a>
                    
                    <!-- Jadwal Button -->
                    <button class="bg-gradient-to-r from-orange-500 to-red-500 rounded-2xl p-4 flex items-center justify-center space-x-2 shadow-medium card-professional touch-feedback">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-white font-semibold font-sf">Jadwal</span>
                    </button>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="px-6 mt-8 pb-32">
                
                <!-- Just for You Section -->
                <div class="animate-slide-up" style="animation-delay: 0.4s;">
                    <h3 class="text-gray-900 text-lg font-bold font-sf mb-6">Just for you</h3>
                    
                    <!-- Feature Grid -->
                    <div class="grid grid-cols-4 gap-4 mb-8">
                        <!-- Jaspel Detail -->
                        <a href="{{ route('filament.dokter.pages.jaspel-mobile-page') }}" class="text-center card-professional touch-feedback">
                            <div class="w-14 h-14 feature-bg-yellow rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-soft">
                                <svg class="w-7 h-7 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                </svg>
                            </div>
                            <p class="text-gray-700 text-xs font-medium font-sf">Jaspel</p>
                        </a>
                        
                        <!-- Riwayat -->
                        <div class="text-center card-professional touch-feedback">
                            <div class="w-14 h-14 feature-bg-purple rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-soft">
                                <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <p class="text-gray-700 text-xs font-medium font-sf">Riwayat</p>
                        </div>
                        
                        <!-- Tindakan -->
                        <div class="text-center card-professional touch-feedback">
                            <div class="w-14 h-14 feature-bg-blue rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-soft">
                                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <p class="text-gray-700 text-xs font-medium font-sf">Tindakan</p>
                        </div>
                        
                        <!-- Profile -->
                        <div class="text-center card-professional touch-feedback">
                            <div class="w-14 h-14 feature-bg-pink rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-soft">
                                <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <p class="text-gray-700 text-xs font-medium font-sf">Profile</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="animate-slide-up" style="animation-delay: 0.5s;">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-gray-900 text-lg font-bold font-sf">Recent Activity</h3>
                        <button class="text-purple-600 text-sm font-medium font-sf">View all</button>
                    </div>
                    
                    <!-- Activity Cards -->
                    <div class="space-y-4">
                        <!-- Presensi Activity -->
                        <div class="bg-white rounded-2xl p-4 shadow-soft border border-gray-100 card-professional">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-gray-900 font-semibold font-sf">Presensi</h4>
                                    <p class="text-gray-500 text-sm font-sf">Check-in hari ini</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-900 font-bold font-sf">08:00</p>
                                    <span class="status-confirmed text-xs font-medium px-2 py-1 rounded-full">Confirmed</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Jaspel Activity -->
                        <div class="bg-white rounded-2xl p-4 shadow-soft border border-gray-100 card-professional">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-gray-900 font-semibold font-sf">Jaspel</h4>
                                    <p class="text-gray-500 text-sm font-sf">Tindakan pemeriksaan</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-gray-900 font-bold font-sf">Rp125.000</p>
                                    <span class="status-pending text-xs font-medium px-2 py-1 rounded-full">Pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
        <!-- Floating Bottom Navigation -->
        <div class="fixed bottom-0 left-0 right-0 nav-floating">
            <div class="max-w-sm mx-auto px-6 py-4">
                <div class="flex items-center justify-around relative">
                    <!-- Wallet -->
                    <button class="flex flex-col items-center space-y-1 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        <span class="text-xs font-medium font-sf">Wallet</span>
                    </button>
                    
                    <!-- Market -->
                    <button class="flex flex-col items-center space-y-1 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span class="text-xs font-medium font-sf">Market</span>
                    </button>
                    
                    <!-- Home (Active) -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 -top-6">
                        <button class="nav-home-button w-14 h-14 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Notifications -->
                    <button class="flex flex-col items-center space-y-1 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="text-xs font-medium font-sf">Notifications</span>
                    </button>
                    
                    <!-- Settings -->
                    <button class="flex flex-col items-center space-y-1 text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="text-xs font-medium font-sf">Settings</span>
                    </button>
                </div>
            </div>
        </div>
        
    </div>
    
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Professional Fintech-Style Dashboard Loaded');
            
            // Professional Touch Feedback System
            const touchElements = document.querySelectorAll('.touch-feedback');
            
            touchElements.forEach(element => {
                let touchTimeout;
                
                element.addEventListener('touchstart', function(e) {
                    clearTimeout(touchTimeout);
                    this.style.transform = 'scale(0.96)';
                    this.style.transition = 'transform 0.15s cubic-bezier(0.4, 0, 0.2, 1)';
                    
                    // Professional haptic feedback
                    if (navigator.vibrate) {
                        navigator.vibrate(8);
                    }
                });
                
                element.addEventListener('touchend', function() {
                    touchTimeout = setTimeout(() => {
                        this.style.transform = 'scale(1)';
                        this.style.transition = 'transform 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
                    }, 50);
                });
                
                element.addEventListener('touchcancel', function() {
                    this.style.transform = 'scale(1)';
                    this.style.transition = 'transform 0.25s cubic-bezier(0.4, 0, 0.2, 1)';
                });
            });
            
            // Professional Card Hover Effects
            const cards = document.querySelectorAll('.card-professional');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.01)';
                    this.style.boxShadow = '0 20px 40px -15px rgba(0, 0, 0, 0.15)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '';
                });
            });
            
            // Professional Scroll Animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                    }
                });
            }, observerOptions);
            
            // Observe animated elements
            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
            
            // Professional Balance Counter Animation
            const balanceElement = document.querySelector('.text-balance');
            if (balanceElement) {
                const finalValue = balanceElement.textContent;
                const numberValue = parseInt(finalValue.replace(/[^\d]/g, ''));
                
                if (!isNaN(numberValue)) {
                    let currentValue = 0;
                    const increment = numberValue / 50;
                    const duration = 2000;
                    const stepTime = duration / 50;
                    
                    const counter = setInterval(() => {
                        currentValue += increment;
                        if (currentValue >= numberValue) {
                            balanceElement.textContent = finalValue;
                            clearInterval(counter);
                        } else {
                            const formattedValue = 'Rp' + Math.floor(currentValue).toLocaleString('id-ID');
                            balanceElement.textContent = formattedValue;
                        }
                    }, stepTime);
                }
            }
            
            // Professional Gesture Recognition
            let startY, startX, startTime;
            
            document.addEventListener('touchstart', function(e) {
                startY = e.touches[0].clientY;
                startX = e.touches[0].clientX;
                startTime = Date.now();
            });
            
            document.addEventListener('touchmove', function(e) {
                if (!startY || !startX) return;
                
                const currentY = e.touches[0].clientY;
                const currentX = e.touches[0].clientX;
                const diffY = startY - currentY;
                const diffX = startX - currentX;
                const timeDiff = Date.now() - startTime;
                
                // Pull-to-refresh gesture
                if (diffY < -100 && Math.abs(diffX) < 50 && window.scrollY === 0 && timeDiff > 200) {
                    // Show professional refresh indicator
                    const indicator = document.createElement('div');
                    indicator.className = 'fixed top-20 left-1/2 transform -translate-x-1/2 bg-white rounded-full p-3 shadow-large z-50 animate-bounce-soft';
                    indicator.innerHTML = '<svg class="w-6 h-6 text-purple-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>';
                    document.body.appendChild(indicator);
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1200);
                }
            });
            
            // Professional Performance Monitoring
            if ('performance' in window) {
                window.addEventListener('load', function() {
                    setTimeout(() => {
                        const perfData = performance.getEntriesByType('navigation')[0];
                        const loadTime = perfData.loadEventEnd - perfData.fetchStart;
                        console.log('📊 Professional Dashboard Performance:', {
                            loadTime: `${loadTime}ms`,
                            status: loadTime < 2000 ? '✅ Excellent' : loadTime < 3000 ? '⚠️ Good' : '❌ Needs optimization'
                        });
                    }, 100);
                });
            }
            
            // Memory Management
            window.addEventListener('beforeunload', function() {
                observer.disconnect();
            });
        });
    </script>
    @endpush
</x-filament-panels::page>