@php
    $dokterInfo = $this->getDokterInfo();
    $meritInfo = $this->getMeritInfo();
@endphp

<div x-data="attendanceApp()" x-init="initializeLocation()" class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
    {{-- Status Bar --}}
    <div class="bg-white px-6 py-3 flex justify-between items-center text-sm font-medium text-gray-900 shadow-sm">
        <div class="flex items-center space-x-1">
            <span x-text="currentTime"></span>
        </div>
        <div class="flex items-center space-x-1">
            <div class="flex space-x-1">
                <div class="w-1 h-3 bg-gray-900 rounded-full"></div>
                <div class="w-1 h-3 bg-gray-900 rounded-full"></div>
                <div class="w-1 h-3 bg-gray-900 rounded-full"></div>
                <div class="w-1 h-3 bg-gray-400 rounded-full"></div>
            </div>
            <div class="w-6 h-3 border border-gray-900 rounded-sm relative">
                <div class="w-4 h-2 bg-gray-900 rounded-sm absolute top-0.5 left-0.5"></div>
                <div class="w-0.5 h-1 bg-gray-900 rounded-sm absolute top-1 -right-0.5"></div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="px-6 py-6 space-y-6">
        {{-- Doctor Profile Card --}}
        <div class="bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-600 rounded-3xl p-6 text-white shadow-xl transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h2 class="text-2xl font-bold mb-1">{{ $dokterInfo['name'] }}</h2>
                    <p class="text-blue-100 text-base opacity-90">{{ $dokterInfo['specialty'] }}</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center overflow-hidden shadow-lg">
                    <img src="{{ $dokterInfo['avatar'] }}" alt="Avatar" class="w-14 h-14 rounded-full">
                </div>
            </div>
            
            {{-- Decorative elements --}}
            <div class="absolute top-4 right-4 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute bottom-2 left-4 w-12 h-12 bg-white/5 rounded-full blur-lg"></div>
        </div>

        {{-- Location Coordinates --}}
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-5 shadow-lg">
            <h3 class="text-gray-800 font-semibold mb-4 text-center">Koordinat Presensi Terakhir</h3>
            <div class="grid grid-cols-2 gap-4">
                {{-- Longitude --}}
                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl p-4 border border-yellow-200">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-6 h-6 bg-yellow-400 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-600">Longitude</span>
                    </div>
                    <div class="text-xl font-bold text-gray-800" x-text="longitude || '0.0000'"></div>
                </div>

                {{-- Latitude --}}
                <div class="bg-gradient-to-br from-red-50 to-pink-50 rounded-xl p-4 border border-red-200">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="w-6 h-6 bg-red-400 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-600">Latitude</span>
                    </div>
                    <div class="text-xl font-bold text-gray-800" x-text="latitude || '0.0000'"></div>
                </div>
            </div>
        </div>

        {{-- Check In/Out Buttons --}}
        <div class="grid grid-cols-2 gap-4">
            {{-- Check In Button --}}
            <button 
                x-on:click="checkIn()"
                :disabled="!locationEnabled || isCheckedIn"
                :class="locationEnabled && !isCheckedIn ? 'opacity-100 scale-100' : 'opacity-60 scale-95'"
                class="bg-gradient-to-r from-yellow-400 via-orange-400 to-orange-500 hover:from-yellow-500 hover:via-orange-500 hover:to-orange-600 text-white font-bold text-lg py-6 px-6 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 active:scale-95 flex flex-col items-center space-y-2"
            >
                <div class="w-8 h-8 bg-white/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <span>Check In</span>
            </button>

            {{-- Check Out Button --}}
            <button 
                x-on:click="checkOut()"
                :disabled="!locationEnabled || !isCheckedIn"
                :class="locationEnabled && isCheckedIn ? 'opacity-100 scale-100' : 'opacity-60 scale-95'"
                class="bg-gradient-to-r from-blue-500 via-blue-600 to-indigo-600 hover:from-blue-600 hover:via-blue-700 hover:to-indigo-700 text-white font-bold text-lg py-6 px-6 rounded-2xl shadow-xl transform transition-all duration-300 hover:scale-105 active:scale-95 flex flex-col items-center space-y-2"
            >
                <div class="w-8 h-8 bg-white/30 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <span>Check Out</span>
            </button>
        </div>

        {{-- Merit Information --}}
        <div class="bg-white/80 backdrop-blur-sm rounded-2xl p-5 shadow-lg">
            <div class="grid grid-cols-2 gap-4">
                {{-- Target Merit --}}
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 mb-2">
                        <div class="w-5 h-5 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-600">Target Menit</span>
                    </div>
                    <div class="text-sm text-gray-500 mb-1">Hari Ini</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $meritInfo['target'] }} Menit</div>
                </div>

                {{-- Deficit Merit --}}
                <div class="text-center">
                    <div class="flex items-center justify-center space-x-2 mb-2">
                        <div class="w-5 h-5 bg-orange-500 rounded-full flex items-center justify-center">
                            <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-600">Kekurangan Menit</span>
                    </div>
                    <div class="text-sm text-gray-500 mb-1">Hari Ini</div>
                    <div class="text-2xl font-bold text-gray-800">{{ $meritInfo['deficit'] }} Menit</div>
                </div>
            </div>
        </div>

        {{-- Location Status --}}
        <div class="bg-white/80 backdrop-blur-sm rounded-xl p-4 shadow-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full" :class="locationEnabled ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></div>
                    <span class="text-sm font-medium text-gray-700" x-text="locationEnabled ? 'GPS Terdeteksi' : 'GPS Tidak Terdeteksi'"></span>
                </div>
                <button 
                    x-on:click="requestLocation()"
                    class="text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 px-3 py-1 rounded-full transition-colors duration-200"
                >
                    Refresh GPS
                </button>
            </div>
        </div>
    </div>

    {{-- Bottom Navigation (Optional - can be hidden if using Filament navigation) --}}
    <div class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-gray-200 px-6 py-3">
        <div class="flex justify-around">
            <div class="flex flex-col items-center space-y-1">
                <div class="w-6 h-6 text-blue-600">
                    <svg fill="currentColor" viewBox="0 0 24 24">
                        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                    </svg>
                </div>
                <span class="text-xs font-medium text-blue-600">Beranda</span>
            </div>
            <div class="flex flex-col items-center space-y-1">
                <div class="w-6 h-6 text-gray-400">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-xs text-gray-400">Riwayat</span>
            </div>
            <div class="flex flex-col items-center space-y-1">
                <div class="w-6 h-6 text-gray-400">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <span class="text-xs text-gray-400">Presensi</span>
            </div>
            <div class="flex flex-col items-center space-y-1">
                <div class="w-6 h-6 text-gray-400">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <span class="text-xs text-gray-400">Akun</span>
            </div>
        </div>
    </div>
</div>

<script>
function attendanceApp() {
    return {
        latitude: null,
        longitude: null,
        accuracy: null,
        locationEnabled: false,
        isCheckedIn: @js($this->todayAttendance && !$this->todayAttendance->jam_pulang),
        currentTime: '',

        initializeLocation() {
            this.updateTime();
            setInterval(() => this.updateTime(), 1000);
            this.requestLocation();
        },

        updateTime() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('en-US', { 
                hour12: false, 
                hour: '2-digit', 
                minute: '2-digit'
            });
        },

        requestLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.latitude = position.coords.latitude.toFixed(4);
                        this.longitude = position.coords.longitude.toFixed(4);
                        this.accuracy = position.coords.accuracy;
                        this.locationEnabled = true;
                        
                        // Update Livewire component
                        @this.set('userLatitude', this.latitude);
                        @this.set('userLongitude', this.longitude);
                        @this.set('userAccuracy', this.accuracy);
                    },
                    (error) => {
                        console.error('Location error:', error);
                        this.locationEnabled = false;
                        this.showError('GPS tidak dapat diakses. Pastikan izin lokasi diaktifkan.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                this.showError('Browser tidak mendukung GPS.');
            }
        },

        checkIn() {
            if (!this.locationEnabled) {
                this.showError('GPS tidak terdeteksi. Aktifkan GPS terlebih dahulu.');
                return;
            }
            
            @this.call('checkinWithLocation').then(() => {
                this.isCheckedIn = true;
            });
        },

        checkOut() {
            if (!this.locationEnabled) {
                this.showError('GPS tidak terdeteksi. Aktifkan GPS terlebih dahulu.');
                return;
            }
            
            @this.call('checkoutWithLocation').then(() => {
                this.isCheckedIn = false;
            });
        },

        showError(message) {
            // You can customize this to match Filament notifications
            alert(message);
        }
    }
}
</script>

<style>
/* Custom animations and gradients */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}

/* Glassmorphism effect */
.backdrop-blur-sm {
    backdrop-filter: blur(8px);
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 4px;
}

::-webkit-scrollbar-track {
    background: transparent;
}

::-webkit-scrollbar-thumb {
    background: rgba(59, 130, 246, 0.3);
    border-radius: 2px;
}

::-webkit-scrollbar-thumb:hover {
    background: rgba(59, 130, 246, 0.5);
}
</style>