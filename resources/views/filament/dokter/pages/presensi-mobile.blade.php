<x-filament-panels::page>
    @push('styles')
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                    },
                    backdropBlur: {
                        xs: '2px',
                        '4xl': '72px',
                        '5xl': '96px',
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-soft': 'pulse-soft 2s ease-in-out infinite',
                        'gradient-x': 'gradient-x 15s ease infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-10px)' },
                        },
                        'pulse-soft': {
                            '0%, 100%': { opacity: 1 },
                            '50%': { opacity: 0.7 },
                        },
                        'gradient-x': {
                            '0%, 100%': {
                                'background-size': '200% 200%',
                                'background-position': 'left center'
                            },
                            '50%': {
                                'background-size': '200% 200%',
                                'background-position': 'right center'
                            },
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        /* Hide desktop sidebar completely */
        @media (max-width: 1024px) {
            .fi-sidebar {
                display: none !important;
            }
            .fi-main {
                margin-left: 0 !important;
            }
            .fi-topbar {
                display: none !important;
            }
        }

        /* Glassmorphic styles */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .glass-strong {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        
        .gradient-mesh {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradient-x 15s ease infinite;
        }
        
        .text-shadow-glow {
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }
        
        .shine-effect {
            position: relative;
            overflow: hidden;
        }
        
        .shine-effect:hover::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shine 0.5s ease-in-out;
        }
        
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
    </style>
    @endpush
    
    <!-- Glassmorphic Background -->
    <div class="min-h-screen gradient-mesh overflow-x-hidden relative">
        <!-- Floating Background Elements -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-10 left-10 w-20 h-20 bg-white/10 rounded-full animate-float"></div>
            <div class="absolute top-32 right-16 w-16 h-16 bg-white/5 rounded-full animate-float" style="animation-delay: 1s;"></div>
            <div class="absolute bottom-32 left-1/4 w-12 h-12 bg-white/15 rounded-full animate-float" style="animation-delay: 2s;"></div>
            <div class="absolute bottom-16 right-1/3 w-24 h-24 bg-white/8 rounded-full animate-float" style="animation-delay: 3s;"></div>
            
            <!-- Gradient Orbs -->
            <div class="absolute top-1/4 right-8 w-32 h-32 bg-gradient-to-br from-purple-400/20 to-pink-400/20 rounded-full blur-xl animate-pulse-soft"></div>
            <div class="absolute bottom-1/4 left-8 w-40 h-40 bg-gradient-to-br from-blue-400/15 to-cyan-400/15 rounded-full blur-2xl animate-pulse-soft" style="animation-delay: 1.5s;"></div>
        </div>

        <!-- Main Container -->
        <div class="relative z-10 max-w-md mx-auto min-h-screen p-6">
            
            <!-- Profile Header -->
            <header class="mb-8 pt-8">
                <div class="glass-strong rounded-3xl p-6 mb-6 shine-effect">
                    <div class="flex items-center space-x-4">
                        <!-- Avatar -->
                        <div class="relative">
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-400 to-pink-400 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                {{ strtoupper(substr($user->name ?? 'DR', 0, 2)) }}
                            </div>
                            <!-- Online Status -->
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-400 rounded-full border-2 border-white animate-pulse"></div>
                        </div>
                        
                        <!-- User Info -->
                        <div class="flex-1">
                            <h1 class="text-white text-xl font-bold text-shadow-glow">{{ $user->name ?? 'Dr. Dokter' }}</h1>
                            <p class="text-white/80 text-sm">{{ $user->role->name ?? 'Dokter Umum' }}</p>
                            <div class="flex items-center mt-1">
                                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse mr-2"></div>
                                <span class="text-white/70 text-xs font-medium">Online</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Current Time & Date -->
            <section class="mb-8">
                <div class="glass-strong rounded-3xl p-8 text-center">
                    <!-- Date -->
                    <div id="current-date" class="text-white/80 text-lg font-medium mb-4">Tuesday, July 13, 2025</div>
                    
                    <!-- Time -->
                    <div id="current-time" class="text-white text-6xl font-thin mb-4 text-shadow-glow">17:30</div>
                    
                    <!-- Location -->
                    <div class="glass rounded-xl p-3 mx-4">
                        <div class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 text-white/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span id="coordinates-value" class="text-white/70 text-sm font-mono">-6.4074650, 106.8062669</span>
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Action Buttons -->
            <section class="mb-8 space-y-4">
                <!-- Check In Button -->
                <button 
                    id="checkin-button"
                    wire:click="checkinWithLocation"
                    wire:loading.attr="disabled"
                    class="w-full glass-strong rounded-2xl p-6 hover:glass transition-all duration-300 hover:scale-105 hover:shadow-2xl shine-effect group"
                    disabled
                    style="display: {{ $canCheckin ? 'block' : 'none' }}"
                >
                    <div class="flex items-center justify-center space-x-4">
                        <div class="p-3 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="text-white text-lg font-semibold">
                                <span wire:loading.remove wire:target="checkinWithLocation">Check In</span>
                                <span wire:loading wire:target="checkinWithLocation">Processing...</span>
                            </div>
                            <div class="text-white/70 text-sm">Start your day</div>
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div wire:loading.remove wire:target="checkinWithLocation">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                            <div wire:loading wire:target="checkinWithLocation">
                                <div class="animate-spin w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </button>
                
                <!-- Check Out Button -->
                <button 
                    id="checkout-button"
                    wire:click="checkoutWithLocation"
                    wire:loading.attr="disabled"
                    class="w-full glass-strong rounded-2xl p-6 hover:glass transition-all duration-300 hover:scale-105 hover:shadow-2xl shine-effect group"
                    disabled
                    style="display: {{ $canCheckout ? 'block' : 'none' }}"
                >
                    <div class="flex items-center justify-center space-x-4">
                        <div class="p-3 bg-gradient-to-br from-orange-400 to-red-500 rounded-xl group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="text-white text-lg font-semibold">
                                <span wire:loading.remove wire:target="checkoutWithLocation">Check Out</span>
                                <span wire:loading wire:target="checkoutWithLocation">Processing...</span>
                            </div>
                            <div class="text-white/70 text-sm">End your shift</div>
                        </div>
                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            <div wire:loading.remove wire:target="checkoutWithLocation">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                            <div wire:loading wire:target="checkoutWithLocation">
                                <div class="animate-spin w-5 h-5 border-2 border-white/30 border-t-white rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </button>

                @if($todayAttendance && $todayAttendance->jam_pulang)
                    <div class="glass-strong rounded-2xl p-4 border border-green-400/30">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 bg-green-400/20 rounded-lg">
                                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <div class="text-white font-medium">Presensi Completed!</div>
                                <div class="text-white/70 text-sm">
                                    Check In: {{ Carbon\Carbon::parse($todayAttendance->jam_masuk)->format('H:i') }} • 
                                    Check Out: {{ Carbon\Carbon::parse($todayAttendance->jam_pulang)->format('H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            <!-- Bottom Spacing -->
            <div class="h-8"></div>
        </div>
    </div>

    @push('scripts')
    <script>
        // 🌟 Glassmorphic Presensi System
        let currentUserLocation = null;
        let locationWatchId = null;
        let timeUpdateInterval = null;
        
        // 🏢 Load work locations from admin geofencing system
        const workLocations = @json($workLocations ?? []);
        const primaryLocation = @json($primaryLocation ?? null);

        document.addEventListener('DOMContentLoaded', function() {
            startRealTimeClock();
            startLocationTracking();
            setupButtonListeners();
            addTouchFeedback();
        });
        
        // Real-time clock with enhanced formatting
        function startRealTimeClock() {
            function updateDateTime() {
                const now = new Date();
                const timeOptions = {
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'Asia/Jakarta',
                    hour12: false
                };
                const dateOptions = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    timeZone: 'Asia/Jakarta'
                };
                
                document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
                document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', dateOptions);
            }
            
            updateDateTime(); // Initial update
            timeUpdateInterval = setInterval(updateDateTime, 1000); // Update every second
        }
        
        // Add touch feedback for enhanced mobile experience
        function addTouchFeedback() {
            document.querySelectorAll('button').forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.transform = '';
                });
                
                button.addEventListener('touchcancel', function() {
                    this.style.transform = '';
                });
            });
        }

        // ⏰ Real-time clock - English format like design
        function startRealTimeClock() {
            function updateDateTime() {
                const now = new Date();
                
                // Format English date like design: "Tuesday, Apr 23, 2024"
                const dateOptions = { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric',
                    timeZone: 'Asia/Jakarta'
                };
                const dateStr = now.toLocaleDateString('en-US', dateOptions);
                
                // Format time: "09:41" 
                const timeOptions = {
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'Asia/Jakarta',
                    hour12: false
                };
                const timeStr = now.toLocaleTimeString('en-US', timeOptions);
                
                document.getElementById('current-date').textContent = dateStr;
                document.getElementById('current-time').textContent = timeStr;
            }
            
            updateDateTime(); // Initial update
            timeUpdateInterval = setInterval(updateDateTime, 1000); // Update every second
        }
        
        // 📍 Simple location tracking
        function startLocationTracking() {
            if (!navigator.geolocation) {
                console.log('GPS not available');
                return;
            }

            const options = {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            };

            // Get position and update coordinates display
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Store for button clicks
                    currentUserLocation = {
                        latitude: lat,
                        longitude: lng,
                        accuracy: position.coords.accuracy
                    };
                    
                    // Update coordinates display to match design format
                    document.getElementById('coordinates-value').textContent = 
                        `${lat.toFixed(7)}, ${lng.toFixed(7)}`;
                    
                    // Enable buttons if location found
                    enableButtons();
                },
                function(error) {
                    console.log('Location error:', error.message);
                    // Keep default coordinates in design
                },
                options
            );
        }

        // 🔘 Simple button setup with Livewire integration
        function setupButtonListeners() {
            const checkinBtn = document.getElementById('checkin-button');
            const checkoutBtn = document.getElementById('checkout-button');
            
            if (checkinBtn) {
                checkinBtn.addEventListener('click', function(e) {
                    if (!currentUserLocation) {
                        alert('Please wait for location detection...');
                        e.preventDefault();
                        return;
                    }
                    
                    // Set location data to Livewire properties before action
                    @this.set('userLatitude', currentUserLocation.latitude);
                    @this.set('userLongitude', currentUserLocation.longitude);
                    @this.set('userAccuracy', currentUserLocation.accuracy);
                });
            }
            
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function(e) {
                    if (!currentUserLocation) {
                        alert('Please wait for location detection...');
                        e.preventDefault();
                        return;
                    }
                    
                    // Set location data to Livewire properties before action
                    @this.set('userLatitude', currentUserLocation.latitude);
                    @this.set('userLongitude', currentUserLocation.longitude);
                    @this.set('userAccuracy', currentUserLocation.accuracy);
                });
            }
        }

        // 🔘 Simple enable/disable buttons
        function enableButtons() {
            const checkinBtn = document.getElementById('checkin-button');
            const checkoutBtn = document.getElementById('checkout-button');
            
            if (checkinBtn && checkinBtn.style.display !== 'none') {
                checkinBtn.disabled = false;
            }
            
            if (checkoutBtn && checkoutBtn.style.display !== 'none') {
                checkoutBtn.disabled = false;
            }
        }
        
        // 🧹 Cleanup
        window.addEventListener('beforeunload', function() {
            if (timeUpdateInterval) {
                clearInterval(timeUpdateInterval);
            }
        });
    </script>
    @endpush
</x-filament-panels::page>