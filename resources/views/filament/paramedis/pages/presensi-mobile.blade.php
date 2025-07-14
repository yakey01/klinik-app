<x-filament-panels::page>
    @push('styles')
    <!-- Tailwind CSS CDN for instant styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts - Professional Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    },
                    colors: {
                        'green-custom': {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.4s ease-out',
                        'pulse-gentle': 'pulseGentle 2s ease-in-out infinite',
                        'bounce-soft': 'bounceSoft 0.6s ease-out',
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
                        pulseGentle: {
                            '0%, 100%': { opacity: '1', transform: 'scale(1)' },
                            '50%': { opacity: '0.95', transform: 'scale(1.02)' }
                        },
                        bounceSoft: {
                            '0%, 20%, 50%, 80%, 100%': { transform: 'translateY(0)' },
                            '40%': { transform: 'translateY(-4px)' },
                            '60%': { transform: 'translateY(-2px)' }
                        }
                    },
                    boxShadow: {
                        'soft': '0 2px 15px -3px rgba(0, 0, 0, 0.07), 0 10px 20px -2px rgba(0, 0, 0, 0.04)',
                        'medium': '0 4px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'large': '0 10px 50px -12px rgba(0, 0, 0, 0.25)',
                        'button': '0 4px 20px -2px rgba(34, 197, 94, 0.3)',
                        'card': '0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06)',
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Hide FilamentPHP elements for clean mobile experience */
        @media (max-width: 1024px) {
            .fi-sidebar,
            .fi-topbar {
                display: none !important;
            }
            .fi-main {
                margin-left: 0 !important;
            }
            .fi-page {
                background: #f8fafc !important;
            }
        }
        
        /* Clean white background */
        body {
            background: #f8fafc !important;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        .fi-main-content {
            background: transparent !important;
        }
        
        /* Hide scrollbar */
        ::-webkit-scrollbar {
            width: 0px;
            background: transparent;
        }
        
        /* Prevent text selection for native app feel */
        .attendance-container {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Premium touch feedback */
        .touch-active:active {
            transform: scale(0.98);
            transition: transform 0.1s ease;
        }
        
        /* Button hover effects */
        .check-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px -8px rgba(34, 197, 94, 0.4);
        }
        
        .check-button:active {
            transform: translateY(0);
            box-shadow: 0 4px 20px -2px rgba(34, 197, 94, 0.3);
        }
    </style>
    @endpush
    
    <!-- Main Attendance Container -->
    <div class="attendance-container min-h-screen bg-slate-50 relative">
        <!-- Container with mobile-first max-width -->
        <div class="max-w-sm mx-auto min-h-screen px-6 py-8 relative">
        
        <!-- Header Section with Personal Greeting -->
        <header class="mb-8 animate-fade-in">
            <div class="flex items-center justify-between mb-6">
                <!-- Personal Greeting -->
                <div class="flex-1">
                    <h1 class="text-gray-900 text-2xl font-semibold mb-1">
                        Hey {{ explode(' ', $user->name ?? 'Paramedis')[0] }}!
                    </h1>
                    <p class="text-gray-600 text-base">
                        @php
                            $hour = now('Asia/Jakarta')->hour;
                            $greeting = $hour < 12 ? 'Good morning!' : ($hour < 17 ? 'Good afternoon!' : 'Good evening!');
                        @endphp
                        {{ $greeting }} Mark your attendance
                    </p>
                </div>
                
                <!-- User Avatar -->
                <div class="w-12 h-12 bg-green-custom-500 rounded-full flex items-center justify-center text-white font-semibold text-lg shadow-soft">
                    {{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}
                </div>
            </div>
        </header>
        
        <!-- Central Time Display -->
        <section class="text-center mb-12 animate-slide-up" style="animation-delay: 0.1s;">
            <!-- Current Time -->
            <div id="current-time" class="text-gray-900 text-6xl font-light mb-2 tracking-tight">
                09:00 AM
            </div>
            
            <!-- Current Date -->
            <div id="current-date" class="text-gray-500 text-lg font-medium">
                Oct 26, 2022 - Wednesday
            </div>
            
            <!-- GPS Status Indicator -->
            <div id="gps-status" class="mt-4 flex items-center justify-center space-x-2 text-sm">
                <div id="gps-icon" class="w-4 h-4 bg-yellow-400 rounded-full animate-pulse"></div>
                <span id="gps-text" class="text-gray-600">Detecting location...</span>
                <button id="refresh-gps" onclick="refreshGPSLocation()" 
                        class="ml-2 text-blue-500 hover:text-blue-700 text-xs underline" 
                        style="display:none;">🔄 Refresh</button>
            </div>
        </section>
        
        <!-- Central Check-in/Check-out Button -->
        <section class="flex justify-center mb-12 animate-slide-up" style="animation-delay: 0.2s;">
            @if($canCheckin)
                <button 
                    wire:click="checkinWithLocation"
                    wire:loading.attr="disabled"
                    class="check-button relative w-32 h-32 bg-white rounded-full shadow-large hover:shadow-button transition-all duration-300 transform hover:-translate-y-1 touch-active"
                >
                    <!-- Outer Ring -->
                    <div class="absolute inset-2 border-2 border-gray-200 rounded-full"></div>
                    
                    <!-- Inner Circle -->
                    <div class="absolute inset-4 bg-green-custom-500 rounded-full flex items-center justify-center shadow-medium">
                        <div wire:loading.remove wire:target="checkinWithLocation">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                      d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                            </svg>
                        </div>
                        <div wire:loading wire:target="checkinWithLocation">
                            <div class="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </div>
                    </div>
                </button>
            @endif
            
            @if($canCheckout)
                <button 
                    wire:click="checkoutWithLocation"
                    wire:loading.attr="disabled"
                    class="check-button relative w-32 h-32 bg-white rounded-full shadow-large hover:shadow-button transition-all duration-300 transform hover:-translate-y-1 touch-active"
                >
                    <!-- Outer Ring -->
                    <div class="absolute inset-2 border-2 border-gray-200 rounded-full"></div>
                    
                    <!-- Inner Circle -->
                    <div class="absolute inset-4 bg-red-500 rounded-full flex items-center justify-center shadow-medium">
                        <div wire:loading.remove wire:target="checkoutWithLocation">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" 
                                      d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                            </svg>
                        </div>
                        <div wire:loading wire:target="checkoutWithLocation">
                            <div class="w-6 h-6 border-2 border-white/30 border-t-white rounded-full animate-spin"></div>
                        </div>
                    </div>
                </button>
            @endif
        </section>
        
        <!-- Action Button Text -->
        <div class="text-center mb-12 animate-slide-up" style="animation-delay: 0.3s;">
            @if($canCheckin)
                <p class="text-gray-900 text-lg font-medium">Check In</p>
            @elseif($canCheckout)
                <p class="text-gray-900 text-lg font-medium">Check Out</p>
            @else
                <p class="text-gray-500 text-lg font-medium">Attendance Complete</p>
            @endif
        </div>
        
        <!-- Status Cards Row -->
        <section class="grid grid-cols-3 gap-4 mb-8 animate-slide-up" style="animation-delay: 0.4s;">
            <!-- Check In Card -->
            <div class="bg-white rounded-2xl p-4 shadow-card text-center">
                <div class="w-10 h-10 bg-green-custom-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-green-custom-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <p class="text-gray-900 text-lg font-semibold mb-1">
                    @if($todayAttendance && $todayAttendance->time_in)
                        {{ Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i') }}
                    @else
                        --:--
                    @endif
                </p>
                <p class="text-gray-500 text-sm font-medium">Check In</p>
            </div>
            
            <!-- Check Out Card -->
            <div class="bg-white rounded-2xl p-4 shadow-card text-center">
                <div class="w-10 h-10 bg-green-custom-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-green-custom-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <p class="text-gray-900 text-lg font-semibold mb-1">
                    @if($todayAttendance && $todayAttendance->time_out)
                        {{ Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i') }}
                    @else
                        --:--
                    @endif
                </p>
                <p class="text-gray-500 text-sm font-medium">Check Out</p>
            </div>
            
            <!-- Total Hours Card -->
            <div class="bg-white rounded-2xl p-4 shadow-card text-center">
                <div class="w-10 h-10 bg-green-custom-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-5 h-5 text-green-custom-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-gray-900 text-lg font-semibold mb-1">
                    @if($todayAttendance && $todayAttendance->time_in && $todayAttendance->time_out)
                        @php
                            $start = Carbon\Carbon::parse($todayAttendance->time_in);
                            $end = Carbon\Carbon::parse($todayAttendance->time_out);
                            $diff = $end->diff($start);
                            $hours = $diff->h + ($diff->days * 24);
                            $minutes = $diff->i;
                        @endphp
                        {{ sprintf('%02d:%02d', $hours, $minutes) }}
                    @else
                        --:--
                    @endif
                </p>
                <p class="text-gray-500 text-sm font-medium">Total Hrs</p>
            </div>
        </section>
        
        <!-- Completion Status -->
        @if($todayAttendance && $todayAttendance->time_out)
            <div class="bg-green-custom-50 border border-green-custom-200 rounded-2xl p-4 mb-8 animate-bounce-soft">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-custom-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-green-custom-900 font-semibold">Attendance Complete!</p>
                        <p class="text-green-custom-700 text-sm">
                            Great work today! Have a safe trip home.
                        </p>
                    </div>
                </div>
            </div>
        @endif
        
    </div>
    
    <!-- Bottom Navigation -->
    <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 animate-slide-up" style="animation-delay: 0.5s;">
        <div class="bg-green-custom-600 rounded-full px-8 py-4 shadow-large flex items-center space-x-6">
            <!-- Home Button (Active) -->
            <a href="/paramedis" 
               class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-full font-medium text-sm transition-all duration-300 hover:bg-yellow-300">
                🏠 Home
            </a>
            
            <!-- Calendar Icon -->
            <a href="/paramedis/attendances" 
               class="text-white hover:text-green-custom-100 transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </a>
            
            <!-- Profile Icon -->
            <a href="/paramedis" class="text-white hover:text-green-custom-100 transition-colors duration-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </a>
        </div>
    </div>
    
</div>

    @push('scripts')
    <script>
        // 🕐 Premium Attendance System with Real-time Clock
        let currentUserLocation = null;
        let timeUpdateInterval = null;
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeAttendanceSystem();
            startRealTimeClock();
            addTouchFeedback();
            startLocationTracking();
        });
        
        // Real-time clock exactly like the reference design
        function startRealTimeClock() {
            function updateDateTime() {
                const now = new Date();
                
                // Format time exactly like reference: "09:00 AM"
                const timeOptions = {
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'Asia/Jakarta',
                    hour12: true
                };
                const timeStr = now.toLocaleTimeString('en-US', timeOptions);
                
                // Format date exactly like reference: "Oct 26, 2022 - Wednesday"
                const dateOptions = {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    weekday: 'long',
                    timeZone: 'Asia/Jakarta'
                };
                const dateStr = now.toLocaleDateString('en-US', dateOptions);
                const formattedDate = dateStr.replace(/(\w+), (\w+ \d+, \d+)/, '$2 - $1');
                
                // Update DOM elements
                document.getElementById('current-time').textContent = timeStr;
                document.getElementById('current-date').textContent = formattedDate;
            }
            
            updateDateTime(); // Initial update
            timeUpdateInterval = setInterval(updateDateTime, 1000); // Update every second
        }
        
        // Initialize the attendance system
        function initializeAttendanceSystem() {
            console.log('🚀 Premium Attendance System Initialized');
            
            // Setup staggered animations
            const animatedElements = document.querySelectorAll('.animate-fade-in, .animate-slide-up');
            animatedElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
                element.style.animationFillMode = 'both';
            });
        }
        
        // Add premium touch feedback for mobile
        function addTouchFeedback() {
            const touchElements = document.querySelectorAll('.touch-active, .check-button');
            
            touchElements.forEach(element => {
                // Touch start
                element.addEventListener('touchstart', function(e) {
                    this.style.transform = this.style.transform.replace('scale(0.98)', '') + ' scale(0.98)';
                    this.style.transition = 'transform 0.1s ease';
                    
                    // Haptic feedback
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }
                }, { passive: true });
                
                // Touch end
                element.addEventListener('touchend', function() {
                    this.style.transform = this.style.transform.replace(' scale(0.98)', '');
                    this.style.transition = 'transform 0.2s ease';
                }, { passive: true });
                
                // Touch cancel
                element.addEventListener('touchcancel', function() {
                    this.style.transform = this.style.transform.replace(' scale(0.98)', '');
                    this.style.transition = 'transform 0.2s ease';
                }, { passive: true });
            });
        }
        
        // Simple location tracking for attendance validation
        function startLocationTracking() {
            if (!navigator.geolocation) {
                console.log('⚠️ Geolocation not available');
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            };

            // Get current position for attendance validation
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    currentUserLocation = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy
                    };
                    
                    console.log('📍 Location detected for attendance validation');
                    updateGPSStatus('success', `GPS ready (${Math.round(position.coords.accuracy)}m accuracy)`);
                    enableAttendanceButtons();
                },
                function(error) {
                    console.log('⚠️ Location error:', error.message);
                    updateGPSStatus('error', 'GPS unavailable - manual mode');
                    // Buttons remain enabled for testing
                    enableAttendanceButtons();
                },
                options
            );
        }
        
        // Enable attendance buttons after location detection
        function enableAttendanceButtons() {
            const buttons = document.querySelectorAll('button[wire\\:click*="checkin"], button[wire\\:click*="checkout"]');
            buttons.forEach(button => {
                button.disabled = false;
                button.addEventListener('click', function() {
                    if (currentUserLocation) {
                        // Set location data for Livewire before making the call
                        @this.set('userLatitude', currentUserLocation.latitude);
                        @this.set('userLongitude', currentUserLocation.longitude);
                        @this.set('userAccuracy', currentUserLocation.accuracy);
                        
                        console.log('📍 GPS Data sent:', {
                            lat: currentUserLocation.latitude,
                            lng: currentUserLocation.longitude,
                            accuracy: currentUserLocation.accuracy
                        });
                    } else {
                        console.log('⚠️ No GPS location available');
                    }
                });
            });
        }
        
        // Update GPS status indicator
        function updateGPSStatus(status, message) {
            const gpsIcon = document.getElementById('gps-icon');
            const gpsText = document.getElementById('gps-text');
            const refreshBtn = document.getElementById('refresh-gps');
            
            if (!gpsIcon || !gpsText) return;
            
            // Remove existing status classes
            gpsIcon.className = 'w-4 h-4 rounded-full';
            
            switch(status) {
                case 'detecting':
                    gpsIcon.className += ' bg-yellow-400 animate-pulse';
                    gpsText.textContent = message || 'Detecting location...';
                    gpsText.className = 'text-gray-600';
                    if (refreshBtn) refreshBtn.style.display = 'none';
                    break;
                case 'success':
                    gpsIcon.className += ' bg-green-500';
                    gpsText.textContent = message || 'GPS ready';
                    gpsText.className = 'text-green-600';
                    if (refreshBtn) refreshBtn.style.display = 'inline';
                    break;
                case 'error':
                    gpsIcon.className += ' bg-red-500';
                    gpsText.textContent = message || 'GPS unavailable';
                    gpsText.className = 'text-red-600';
                    if (refreshBtn) refreshBtn.style.display = 'inline';
                    break;
            }
        }
        
        // Refresh GPS location manually
        function refreshGPSLocation() {
            updateGPSStatus('detecting', 'Refreshing location...');
            startLocationTracking();
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
            }
        });
        
        console.log('✅ Premium Attendance System Ready - World Class Design');
    </script>
    @endpush
</x-filament-panels::page>