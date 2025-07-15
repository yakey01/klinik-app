<div class="space-y-6">
    {{-- Loading Overlay --}}
    <div wire:loading class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500 mx-auto mb-4"></div>
            <p class="text-gray-600">Memproses...</p>
        </div>
    </div>

    {{-- Status Information --}}
    <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100">
        <div class="text-center">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Status Presensi</h2>
            <div class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                {{ $attendance && $attendance->isCheckedOut() ? 'bg-green-100 text-green-800' : 
                   ($attendance && $attendance->isCheckedIn() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600') }}">
                {{ $this->statusText }}
            </div>
            
            @if($attendance && $attendance->workLocation)
                <p class="text-sm text-gray-500 mt-2">
                    📍 {{ $attendance->workLocation->name }}
                </p>
            @endif
        </div>
    </div>

    {{-- GPS Status --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-4 border border-blue-100">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-3 h-3 rounded-full {{ $locationStatus === 'success' ? 'bg-green-500' : ($locationStatus === 'detecting' ? 'bg-yellow-500 animate-pulse' : 'bg-red-500') }}"></div>
                <span class="text-sm font-medium text-gray-700">
                    @if($locationStatus === 'success')
                        📍 Lokasi Terdeteksi
                    @elseif($locationStatus === 'detecting')
                        📡 Mendeteksi Lokasi...
                    @else
                        ❌ Gagal Mendeteksi Lokasi
                    @endif
                </span>
            </div>
            
            <button wire:click="refreshLocation" 
                    class="px-3 py-1 text-xs bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                🔄 Refresh
            </button>
        </div>
        
        @if($latitude && $longitude)
            <div class="mt-2 text-xs text-gray-500">
                Lat: {{ number_format($latitude, 6) }}, Lng: {{ number_format($longitude, 6) }}
                @if($accuracy) | Akurasi: {{ number_format($accuracy) }}m @endif
            </div>
        @endif
    </div>

    {{-- Error Message --}}
    @if($errorMessage)
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4">
            <div class="flex items-center space-x-2">
                <span class="text-red-500">⚠️</span>
                <p class="text-red-700 text-sm">{{ $errorMessage }}</p>
            </div>
        </div>
    @endif

    {{-- Success Message --}}
    @if(session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4">
            <div class="flex items-center space-x-2">
                <span class="text-green-500">✅</span>
                <p class="text-green-700 text-sm">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="space-y-4">
        @if($this->canCheckin)
            <button id="checkin-btn" 
                    class="w-full bg-gradient-to-r from-green-500 to-emerald-400 text-white font-bold py-5 px-6 rounded-3xl shadow-2xl transition-all duration-300 hover:scale-105 hover:shadow-3xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
                    @if(!$latitude || !$longitude) disabled @endif>
                <span class="text-lg">🟢</span>
                <span class="text-lg">Check In</span>
            </button>
        @endif

        @if($this->canCheckout)
            <button id="checkout-btn" 
                    class="w-full bg-gradient-to-r from-orange-500 to-red-400 text-white font-bold py-5 px-6 rounded-3xl shadow-2xl transition-all duration-300 hover:scale-105 hover:shadow-3xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center space-x-2"
                    @if(!$latitude || !$longitude) disabled @endif>
                <span class="text-lg">🔴</span>
                <span class="text-lg">Check Out</span>
            </button>
        @endif
    </div>

    {{-- Manual GPS Input (Debug Mode) --}}
    <div class="mt-6">
        <button wire:click="toggleManualInput" 
                class="w-full text-sm text-gray-500 hover:text-gray-700 transition-colors">
            🛠️ Mode Debug GPS
        </button>
        
        @if($showManualInput)
            <div class="mt-4 bg-gray-50 rounded-2xl p-4 space-y-3">
                <h4 class="font-semibold text-gray-700">Input Manual Koordinat</h4>
                
                <div class="grid grid-cols-2 gap-3">
                    <input wire:model="latitude" 
                           type="number" 
                           step="any" 
                           placeholder="Latitude" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <input wire:model="longitude" 
                           type="number" 
                           step="any" 
                           placeholder="Longitude" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                
                <div class="flex space-x-2">
                    @if($this->canCheckin)
                        <button wire:click="manualCheckin" 
                                class="flex-1 bg-green-500 text-white py-2 px-4 rounded-lg text-sm hover:bg-green-600">
                            Manual Check In
                        </button>
                    @endif
                    
                    @if($this->canCheckout)
                        <button wire:click="manualCheckout" 
                                class="flex-1 bg-orange-500 text-white py-2 px-4 rounded-lg text-sm hover:bg-orange-600">
                            Manual Check Out
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- Work Locations Info --}}
    @if($workLocations && $workLocations->count() > 0)
        <div class="bg-blue-50 rounded-2xl p-4 border border-blue-100">
            <h4 class="font-semibold text-blue-800 mb-2">📍 Lokasi Kerja Tersedia</h4>
            <div class="space-y-2">
                @foreach($workLocations as $location)
                    <div class="text-sm text-blue-700">
                        <span class="font-medium">{{ $location->name }}</span>
                        <span class="text-blue-500"> • Radius: {{ $location->formatted_radius }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@script
<script>
    let gpsWatchId = null;
    let isGettingLocation = false;

    // GPS Options with progressive fallback
    const gpsOptions = {
        high: { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 },
        medium: { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 },
        low: { enableHighAccuracy: false, timeout: 60000, maximumAge: 300000 }
    };

    function startLocationDetection() {
        // Check if HTTPS (required for geolocation)
        if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
            $wire.set('errorMessage', 'HTTPS diperlukan untuk akses GPS');
            $wire.set('locationStatus', 'error');
            return;
        }

        // Check geolocation support
        if (!navigator.geolocation) {
            $wire.set('errorMessage', 'Browser tidak mendukung GPS');
            $wire.set('locationStatus', 'error');
            return;
        }

        $wire.set('locationStatus', 'detecting');
        $wire.set('errorMessage', '');
        
        tryGetLocationWithFallback();
    }

    function tryGetLocationWithFallback() {
        if (isGettingLocation) return;
        
        isGettingLocation = true;
        
        // Try high accuracy first
        navigator.geolocation.getCurrentPosition(
            handleLocationSuccess,
            (error) => {
                console.log('High accuracy failed, trying medium...');
                // Try medium accuracy
                navigator.geolocation.getCurrentPosition(
                    handleLocationSuccess,
                    (error) => {
                        console.log('Medium accuracy failed, trying low...');
                        // Try low accuracy as last resort
                        navigator.geolocation.getCurrentPosition(
                            handleLocationSuccess,
                            handleLocationError,
                            gpsOptions.low
                        );
                    },
                    gpsOptions.medium
                );
            },
            gpsOptions.high
        );
    }

    function handleLocationSuccess(position) {
        isGettingLocation = false;
        
        const latitude = position.coords.latitude;
        const longitude = position.coords.longitude;
        const accuracy = position.coords.accuracy;

        $wire.set('latitude', latitude);
        $wire.set('longitude', longitude);
        $wire.set('accuracy', accuracy);
        $wire.set('locationStatus', 'success');
        $wire.set('gpsEnabled', true);
        
        console.log(`GPS berhasil: ${latitude}, ${longitude} (±${accuracy}m)`);
    }

    function handleLocationError(error) {
        isGettingLocation = false;
        
        let errorMessage = 'Gagal mendapatkan lokasi GPS';
        
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'Akses GPS ditolak. Izinkan akses lokasi di browser.';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'Informasi lokasi tidak tersedia';
                break;
            case error.TIMEOUT:
                errorMessage = 'Timeout GPS. Coba lagi.';
                break;
        }
        
        $wire.set('errorMessage', errorMessage);
        $wire.set('locationStatus', 'error');
        $wire.set('gpsEnabled', false);
        
        console.error('GPS Error:', error);
    }

    // Setup event listeners
    document.addEventListener('DOMContentLoaded', function() {
        startLocationDetection();
        
        // Check-in button
        document.getElementById('checkin-btn')?.addEventListener('click', function() {
            if ($wire.latitude && $wire.longitude) {
                $wire.checkinWithLocation($wire.latitude, $wire.longitude, $wire.accuracy);
            } else {
                $wire.set('errorMessage', 'GPS belum terdeteksi. Coba refresh lokasi.');
            }
        });
        
        // Check-out button
        document.getElementById('checkout-btn')?.addEventListener('click', function() {
            if ($wire.latitude && $wire.longitude) {
                $wire.checkoutWithLocation($wire.latitude, $wire.longitude, $wire.accuracy);
            } else {
                $wire.set('errorMessage', 'GPS belum terdeteksi. Coba refresh lokasi.');
            }
        });
    });

    // Listen for Livewire events
    $wire.on('refresh-gps', () => {
        startLocationDetection();
    });

    $wire.on('attendance-updated', () => {
        // Refresh location after attendance update
        setTimeout(() => {
            startLocationDetection();
        }, 1000);
    });

    // Continuous GPS monitoring (optional, every 5 minutes)
    setInterval(() => {
        if ($wire.gpsEnabled && !isGettingLocation) {
            tryGetLocationWithFallback();
        }
    }, 300000); // 5 minutes
</script>
@endscript