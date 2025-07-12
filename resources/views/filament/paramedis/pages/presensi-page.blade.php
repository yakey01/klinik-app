<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Current Time & Date --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-2">
                    <x-heroicon-o-clock class="h-6 w-6 text-green-500" />
                    <span class="text-lg font-semibold">Waktu Saat Ini</span>
                </div>
            </x-slot>
            
            <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg">
                <div id="live-clock" class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                    {{ $currentTime }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $currentDate }}</p>
            </div>
        </x-filament::section>

        {{-- Enhanced Location Status --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-x-2">
                        <x-heroicon-o-map-pin class="h-5 w-5 text-blue-500" />
                        <span>Status Lokasi & Peta</span>
                    </div>
                    <div class="flex items-center gap-2">
                        {{-- Live Tracking Indicator --}}
                        <div id="tracking-indicator" style="display: none;" class="text-xs px-3 py-1 rounded-full bg-green-100 text-green-700 dark:bg-green-900/20 dark:text-green-400 font-medium pulse">
                            🎯 Live Tracking Active
                        </div>
                        @if($accuracy)
                            <div class="text-xs px-2 py-1 rounded-full bg-{{ $accuracyStatus['color'] }}-100 text-{{ $accuracyStatus['color'] }}-600 dark:bg-{{ $accuracyStatus['color'] }}-900/20">
                                {{ $accuracyStatus['message'] }} (±{{ round($accuracy) }}m)
                            </div>
                        @endif
                    </div>
                </div>
            </x-slot>
            
            <div class="space-y-4">
                {{-- Enhanced Location Detection Status --}}
                <div class="p-4 rounded-lg border {{ $locationDetected ? ($withinRadius ? 'bg-green-50 border-green-200 dark:bg-green-900/20' : 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20') : 'bg-gray-50 border-gray-200 dark:bg-gray-800' }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Status Icon & Text --}}
                        <div class="flex items-center gap-3">
                            <div class="text-2xl">
                                @if($locationDetected)
                                    @if($withinRadius)
                                        ✅
                                    @else
                                        ⚠️
                                    @endif
                                @else
                                    📍
                                @endif
                            </div>
                            <div>
                                <p class="font-medium {{ $locationDetected ? ($withinRadius ? 'text-green-600' : 'text-yellow-600') : 'text-gray-600' }}">
                                    @if($locationDetected)
                                        @if($withinRadius)
                                            Dalam Radius Klinik
                                        @else
                                            Di Luar Radius Klinik
                                        @endif
                                    @else
                                        Lokasi Belum Terdeteksi
                                    @endif
                                </p>
                                @if($locationDetected && $distanceToClinic)
                                    <p class="text-sm text-gray-500">
                                        Jarak: {{ round($distanceToClinic) }} meter
                                        @if($withinRadius)
                                            (Radius: {{ $clinicRadius }}m)
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Coordinates Info --}}
                        @if($locationDetected)
                            <div class="text-sm text-gray-600">
                                <p><strong>📍 Koordinat:</strong></p>
                                <p>Lat: {{ number_format($latitude, 6) }}</p>
                                <p>Lng: {{ number_format($longitude, 6) }}</p>
                            </div>
                        @endif
                        
                        {{-- Work Location Info --}}
                        <div class="text-sm text-gray-600">
                            <p><strong>🏢 Lokasi Kerja:</strong></p>
                            <p>{{ $clinicName }}</p>
                            <p>Radius: {{ $clinicRadius }}m</p>
                            @if($currentWorkLocation)
                                <p class="text-xs mt-1">
                                    {{ $currentWorkLocation->location_type_label }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Interactive Map using Filament Map Plugin (Same as Admin Geofencing) --}}
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">🗺️ Peta Lokasi Interaktif</h4>
                    
                    {{-- Map Container --}}
                    <div class="border rounded-lg overflow-hidden bg-white dark:bg-gray-800">
                        <div class="p-4">
                            <div class="space-y-3">
                                {{-- Instructions --}}
                                <div class="text-xs text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                                    <p class="font-medium mb-1">💡 Cara Menggunakan Peta:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>Klik tombol "🌐 Get Location" untuk deteksi GPS otomatis</li>
                                        <li>Drag marker pada peta untuk mengubah posisi</li>
                                        <li>Gunakan search box untuk mencari alamat</li>
                                        <li>Zoom in/out dengan scroll mouse atau kontrol peta</li>
                                    </ul>
                                </div>
                                
                                {{-- The actual Filament Map field (rendered as form component) --}}
                                <div wire:ignore>
                                    {{ $this->form }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Quick Location Stats --}}
                @if($locationDetected)
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="text-lg font-bold text-blue-600">{{ round($distanceToClinic) }}m</div>
                            <div class="text-xs text-gray-600">Jarak ke Klinik</div>
                        </div>
                        <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <div class="text-lg font-bold text-purple-600">{{ $accuracy ? round($accuracy) . 'm' : 'N/A' }}</div>
                            <div class="text-xs text-gray-600">Akurasi GPS</div>
                        </div>
                        <div class="text-center p-3 bg-{{ $withinRadius ? 'green' : 'red' }}-50 dark:bg-{{ $withinRadius ? 'green' : 'red' }}-900/20 rounded-lg">
                            <div class="text-lg font-bold text-{{ $withinRadius ? 'green' : 'red' }}-600">{{ $withinRadius ? '✅' : '❌' }}</div>
                            <div class="text-xs text-gray-600">Status Radius</div>
                        </div>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- Attendance Status --}}
        <x-filament::section>
            <x-slot name="heading">Status Presensi Hari Ini</x-slot>
            
            <div class="space-y-4">
                {{-- Status Cards --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 {{ $hasCheckedIn ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }} dark:bg-gray-800 rounded-lg border text-center">
                        <div class="text-2xl mb-2">{{ $hasCheckedIn ? '✅' : '⏰' }}</div>
                        <p class="text-xs font-medium {{ $hasCheckedIn ? 'text-green-600' : 'text-gray-600' }}">
                            {{ $hasCheckedIn ? 'Sudah Masuk' : 'Belum Masuk' }}
                        </p>
                        @if($checkinTime)
                            <p class="text-sm font-bold text-green-600 mt-1">{{ $checkinTime }}</p>
                        @endif
                    </div>
                    
                    <div class="p-4 {{ $hasCheckedOut ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200' }} dark:bg-gray-800 rounded-lg border text-center">
                        <div class="text-2xl mb-2">{{ $hasCheckedOut ? '🏁' : '⏳' }}</div>
                        <p class="text-xs font-medium {{ $hasCheckedOut ? 'text-orange-600' : 'text-gray-600' }}">
                            {{ $hasCheckedOut ? 'Sudah Pulang' : 'Belum Pulang' }}
                        </p>
                        @if($checkoutTime)
                            <p class="text-sm font-bold text-orange-600 mt-1">{{ $checkoutTime }}</p>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="space-y-3">
                    @if(!$hasCheckedIn)
                        <x-filament::button
                            wire:click="checkin"
                            color="success"
                            icon="heroicon-o-play"
                            size="lg"
                            class="w-full text-lg py-4"
                        >
                            📝 CHECK IN - Mulai Bekerja
                        </x-filament::button>
                    @elseif(!$hasCheckedOut)
                        <x-filament::button
                            wire:click="checkout"
                            color="warning"
                            icon="heroicon-o-stop"
                            size="lg"
                            class="w-full text-lg py-4"
                        >
                            🏃‍♂️ CHECK OUT - Selesai Bekerja
                        </x-filament::button>
                    @else
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 text-center">
                            <div class="text-3xl mb-2">🎉</div>
                            <p class="font-medium text-green-600">Presensi Hari Ini Selesai!</p>
                            <p class="text-sm text-gray-600 mt-1">Terima kasih sudah bekerja hari ini</p>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- Quick Info --}}
        <x-filament::section>
            <x-slot name="heading">Informasi</x-slot>
            
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                    <div class="text-xl mb-1">⏰</div>
                    <p class="text-xs font-medium text-blue-600">Jam Kerja</p>
                    <p class="text-xs text-gray-600">08:00 - 17:00</p>
                </div>
                
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                    <div class="text-xl mb-1">📍</div>
                    <p class="text-xs font-medium text-purple-600">Lokasi</p>
                    <p class="text-xs text-gray-600">Klinik Dokterku</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    @push('scripts')
    <script>
        // Simple clock function
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Jakarta',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = now.toLocaleTimeString('id-ID', options);
            
            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }
        
        // Initialize clock
        document.addEventListener('DOMContentLoaded', function() {
            setInterval(updateClock, 1000);
        });
        
        // GPS location functions (called from header actions)
        function requestLocationManual() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        @this.handleLocationReceived(
                            position.coords.latitude, 
                            position.coords.longitude, 
                            position.coords.accuracy
                        );
                    },
                    function(error) {
                        @this.handleLocationError('GPS error: ' + error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 30000
                    }
                );
            } else {
                @this.handleLocationError('Geolocation not supported by browser');
            }
        }
        
        // Continuous tracking functions
        let watchId = null;
        let isTracking = false;
        
        function startContinuousTracking() {
            if (navigator.geolocation && !isTracking) {
                isTracking = true;
                document.getElementById('tracking-indicator').style.display = 'block';
                
                watchId = navigator.geolocation.watchPosition(
                    function(position) {
                        @this.handleLocationReceived(
                            position.coords.latitude, 
                            position.coords.longitude, 
                            position.coords.accuracy
                        );
                    },
                    function(error) {
                        console.warn('Tracking error:', error.message);
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 30000
                    }
                );
            }
        }
        
        function stopContinuousTracking() {
            if (watchId !== null) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
                isTracking = false;
                document.getElementById('tracking-indicator').style.display = 'none';
            }
        }
        
        // Listen for Livewire events
        document.addEventListener('livewire:init', function () {
            Livewire.on('start-tracking', startContinuousTracking);
            Livewire.on('stop-tracking', stopContinuousTracking);
            Livewire.on('request-location-manual', requestLocationManual);
            Livewire.on('reset-location-control', stopContinuousTracking);
        });
    </script>
    @endpush
</x-filament-panels::page>