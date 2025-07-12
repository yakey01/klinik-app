<x-filament-widgets::widget>
    <x-filament::section>
        <div class="text-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                {{ $currentDate }}
            </h3>
            <p class="text-3xl font-bold text-primary-600 mb-4">{{ $currentTime }}</p>
            
            @if($attendance)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-2 gap-4 text-left">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Check In</p>
                            <p class="text-lg font-semibold">{{ $attendance->time_in }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Check Out</p>
                            <p class="text-lg font-semibold">{{ $attendance->time_out ?? '-' }}</p>
                        </div>
                    </div>
                    @if($attendance->time_out)
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Durasi Kerja</p>
                            <p class="text-lg font-semibold">{{ $attendance->formatted_work_duration }}</p>
                        </div>
                    @endif
                </div>
                
                @if($canCheckOut)
                    <x-filament::button
                        color="danger"
                        size="lg"
                        class="w-full"
                        wire:click="checkOut"
                        icon="heroicon-o-arrow-right-on-rectangle"
                    >
                        Check Out
                    </x-filament::button>
                @else
                    <div class="text-green-600 dark:text-green-400">
                        <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-2" />
                        <p class="font-medium">Presensi Hari Ini Selesai</p>
                    </div>
                @endif
            @else
                <div class="mb-4">
                    <x-heroicon-o-clock class="w-16 h-16 mx-auto text-gray-400 mb-2" />
                    <p class="text-gray-500 dark:text-gray-400">Anda belum melakukan presensi</p>
                </div>
                
                <x-filament::button
                    color="success"
                    size="lg"
                    class="w-full"
                    onclick="initiateCheckIn()"
                    icon="heroicon-o-arrow-left-on-rectangle"
                >
                    Check In Sekarang
                </x-filament::button>
            @endif
        </div>
    </x-filament::section>
    
    <script>
        // Clinic location configuration (should be from env/config)
        const CLINIC_CONFIG = {
            lat: {{ config('app.clinic_latitude', -6.2088) }},
            lng: {{ config('app.clinic_longitude', 106.8456) }},
            radius: {{ config('app.clinic_radius', 100) }} // meters
        };
        
        function initiateCheckIn() {
            if (!navigator.geolocation) {
                alert('Browser Anda tidak mendukung geolocation');
                return;
            }
            
            const button = event.currentTarget;
            const originalContent = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<svg class="animate-spin h-5 w-5 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mendapatkan lokasi...';
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const distance = calculateDistance(
                        position.coords.latitude,
                        position.coords.longitude,
                        CLINIC_CONFIG.lat,
                        CLINIC_CONFIG.lng
                    );
                    
                    if (distance > CLINIC_CONFIG.radius) {
                        button.disabled = false;
                        button.innerHTML = originalContent;
                        alert(`Anda berada ${Math.round(distance)} meter dari klinik. Maksimal jarak ${CLINIC_CONFIG.radius} meter.`);
                        return;
                    }
                    
                    // Livewire component method call
                    @this.checkIn(position.coords.latitude, position.coords.longitude, position.coords.accuracy);
                },
                (error) => {
                    button.disabled = false;
                    button.innerHTML = originalContent;
                    handleLocationError(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }
        
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;
            
            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            
            return R * c;
        }
        
        function handleLocationError(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    alert('Izin lokasi ditolak. Aktifkan GPS untuk check-in.');
                    break;
                case error.POSITION_UNAVAILABLE:
                    alert('Informasi lokasi tidak tersedia.');
                    break;
                case error.TIMEOUT:
                    alert('Timeout mendapatkan lokasi.');
                    break;
                default:
                    alert('Error mendapatkan lokasi.');
            }
        }
    </script>
</x-filament-widgets::widget>