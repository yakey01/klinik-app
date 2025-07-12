<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-map-pin class="h-6 w-6 text-green-500" />
                <span class="text-lg font-semibold">Location & Device Information</span>
            </div>
        </x-slot>

        <x-slot name="headerActions">
            <x-filament::button
                wire:click="detectLocation"
                color="success"
                icon="heroicon-o-map-pin"
                size="sm"
            >
                📍 Detect My Location
            </x-filament::button>
        </x-slot>

        {{-- Location Detection Status --}}
        <div class="mb-6">
            <div class="p-4 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <h3 class="font-medium text-green-800 dark:text-green-300 mb-3">📍 Location Detection</h3>
                
                @if(!$locationDetected)
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded text-yellow-800 dark:text-yellow-300 text-sm mb-3">
                        ⚠️ Click "Detect My Location" button above to get your current position
                    </div>
                @endif
                
                @if($locationDetected)
                    <div class="mt-4 p-3 bg-white dark:bg-gray-700 rounded-md border">
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Latitude:</span>
                                <span class="font-medium">{{ number_format($latitude, 6) }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Longitude:</span>
                                <span class="font-medium">{{ number_format($longitude, 6) }}</span>
                            </div>
                            @if($accuracy)
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Accuracy:</span>
                                <span class="font-medium">{{ round($accuracy) }}m</span>
                            </div>
                            @endif
                            <div>
                                <span class="text-gray-600 dark:text-gray-400">Distance to Clinic:</span>
                                <span class="font-medium {{ $withinRadius ? 'text-green-600' : 'text-red-600' }}">
                                    {{ round($distanceToClinic) }}m
                                    {{ $withinRadius ? '✅' : '❌' }}
                                </span>
                            </div>
                        </div>
                        
                        @if($withinRadius)
                            <div class="mt-3 p-2 bg-green-100 dark:bg-green-900/30 rounded text-green-800 dark:text-green-300 text-sm">
                                ✅ You are within the attendance area ({{ $clinicRadius }}m radius)
                            </div>
                        @else
                            <div class="mt-3 p-2 bg-red-100 dark:bg-red-900/30 rounded text-red-800 dark:text-red-300 text-sm">
                                ❌ You are outside the attendance area ({{ $clinicRadius }}m radius required)
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Device Information Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-2">
                    <x-heroicon-o-globe-alt class="h-5 w-5 text-blue-500" />
                    <h4 class="font-medium text-gray-800 dark:text-gray-300">Browser</h4>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $browserInfo }}
                </div>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-2">
                    <x-heroicon-o-device-phone-mobile class="h-5 w-5 text-green-500" />
                    <h4 class="font-medium text-gray-800 dark:text-gray-300">Device</h4>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $deviceInfo }}
                </div>
            </div>

            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-2">
                    <x-heroicon-o-signal class="h-5 w-5 text-purple-500" />
                    <h4 class="font-medium text-gray-800 dark:text-gray-300">IP Address</h4>
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $ipAddress }}
                </div>
            </div>
        </div>

        {{-- Clinic Information --}}
        <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-2 mb-2">
                <x-heroicon-o-building-office-2 class="h-5 w-5 text-blue-500" />
                <h4 class="font-medium text-blue-800 dark:text-blue-300">Klinik Dokterku</h4>
            </div>
            <div class="text-sm text-blue-600 dark:text-blue-400">
                Location: {{ $clinicLat }}, {{ $clinicLng }} • Attendance Radius: {{ $clinicRadius }}m
            </div>
        </div>
    </x-filament::section>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('request-location', () => {
                if (!navigator.geolocation) {
                    Livewire.dispatch('location-error', { error: 'Geolocation is not supported by this browser' });
                    return;
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        Livewire.dispatch('location-received', {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy
                        });
                    },
                    (error) => {
                        let errorMessage = 'Unknown error';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = 'Location access denied by user';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = 'Location information unavailable';
                                break;
                            case error.TIMEOUT:
                                errorMessage = 'Location request timed out';
                                break;
                        }
                        Livewire.dispatch('location-error', { error: errorMessage });
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 30000
                    }
                );
            });
        });
    </script>
</x-filament-widgets::widget>