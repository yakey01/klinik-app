<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-clock class="h-6 w-6 text-green-500" />
                <span class="text-lg font-semibold">Absensi Cepat - Real Time</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Real Time Clock with Alpine.js --}}
            <div 
                x-data="{ 
                    currentTime: new Date('{{ $currentTime->format('c') }}'), // ISO format with timezone
                    init() {
                        // Server time in Jakarta timezone
                        const serverTime = new Date('{{ $currentTime->format('c') }}');
                        const clientTime = new Date();
                        this.timeOffset = serverTime.getTime() - clientTime.getTime();
                        
                        console.log('Server Jakarta time:', serverTime.toString());
                        console.log('Client time:', clientTime.toString());
                        console.log('Time offset:', this.timeOffset / 1000, 'seconds');
                        
                        // Update clock every second
                        setInterval(() => {
                            this.currentTime = new Date(new Date().getTime() + this.timeOffset);
                        }, 1000);
                    },
                    formatTime() {
                        return this.currentTime.toLocaleTimeString('id-ID', {
                            timeZone: 'Asia/Jakarta',
                            hour12: false,
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                    },
                    formatDate() {
                        return this.currentTime.toLocaleDateString('id-ID', {
                            timeZone: 'Asia/Jakarta',
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                }"
                class="text-center p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800"
            >
                <div 
                    x-text="formatTime()"
                    class="text-5xl font-bold text-blue-600 dark:text-blue-400"
                >
                    {{ $currentTime->format('H:i:s') }}
                </div>
                <div 
                    x-text="formatDate()"
                    class="text-lg text-gray-600 dark:text-gray-400 mt-2"
                >
                    {{ $currentTime->format('l, d F Y') }}
                </div>
                <div class="text-sm text-blue-500 dark:text-blue-400 mt-1">
                    🕐 Waktu Real-Time WIB
                </div>
                <div 
                    x-text="'● Live ' + currentTime.getSeconds()"
                    class="text-xs text-green-500 mt-1"
                >
                    ● Live
                </div>
            </div>

            {{-- Current Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Check-in Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-500" />
                            <div>
                                <div class="font-medium text-green-700 dark:text-green-400">✅ Sudah Absen Masuk</div>
                                <div class="text-sm text-green-600 dark:text-green-500">
                                    Waktu: {{ \Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-green-500">
                                    Status: {{ $todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu' }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">⏰ Belum Absen Masuk</div>
                                <div class="text-sm text-gray-500">Silakan lakukan presensi masuk</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Check-out Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance && $todayAttendance->time_out ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance && $todayAttendance->time_out)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-blue-500" />
                            <div>
                                <div class="font-medium text-blue-700 dark:text-blue-400">✅ Sudah Absen Pulang</div>
                                <div class="text-sm text-blue-600 dark:text-blue-500">
                                    Waktu: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-blue-500">
                                    Durasi: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(\Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">🏠 Belum Absen Pulang</div>
                                <div class="text-sm text-gray-500">
                                    {{ $todayAttendance ? 'Silakan lakukan presensi pulang' : 'Absen masuk dulu' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @if($canCheckin)
                    <button 
                        wire:click="checkin"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg shadow-lg"
                    >
                        <x-heroicon-o-play-circle class="h-6 w-6" />
                        <span wire:loading.remove wire:target="checkin">✅ Check In Masuk</span>
                        <span wire:loading wire:target="checkin">⏳ Sedang Absen...</span>
                    </button>
                @endif
                
                @if($canCheckout)
                    <button 
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="flex-1 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg shadow-lg"
                    >
                        <x-heroicon-o-stop-circle class="h-6 w-6" />
                        <span wire:loading.remove wire:target="checkout">🏠 Check Out Pulang</span>
                        <span wire:loading wire:target="checkout">⏳ Sedang Absen...</span>
                    </button>
                @endif
                
                @if($todayAttendance && $todayAttendance->time_out)
                    <div class="flex-1 text-center p-4 bg-green-100 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="text-green-800 dark:text-green-300 font-bold text-lg">✅ Absensi Selesai</div>
                        <div class="text-sm text-green-600 dark:text-green-400 mt-1">
                            Total Durasi Kerja: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(\Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                        </div>
                        <div class="text-xs text-green-500 mt-2">
                            Terima kasih atas dedikasi Anda hari ini! 👏
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Quick Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="font-medium text-blue-800 dark:text-blue-300">⏰ Jam Kerja</div>
                    <div class="text-blue-600 dark:text-blue-400">08:00 - 17:00</div>
                </div>
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg cursor-pointer" onclick="toggleMap()">
                    <div class="text-center">
                        <div class="font-medium text-green-800 dark:text-green-300">📍 Lokasi</div>
                        <div class="text-green-600 dark:text-green-400">Klinik Dokterku</div>
                        <div class="text-xs text-green-500 mt-1">Klik untuk lihat peta</div>
                    </div>
                </div>
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="font-medium text-purple-800 dark:text-purple-300">👤 Petugas</div>
                    <div class="text-purple-600 dark:text-purple-400">{{ $user->name }}</div>
                </div>
            </div>

            {{-- Interactive Map Section --}}
            <div id="map-container" class="hidden mt-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="p-4 bg-green-600 text-white">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-lg font-semibold">📍 Lokasi Klinik Dokterku</h3>
                                <p class="text-sm opacity-90">Lokasi yang ditentukan Admin untuk presensi</p>
                            </div>
                            <button onclick="toggleMap()" class="text-white hover:text-gray-200">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div id="klinik-map" class="h-64 w-full"></div>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 text-sm text-gray-600 dark:text-gray-300">
                        <div class="flex items-center justify-between">
                            <div>
                                <strong>📍 Koordinat:</strong> -6.2088, 106.8456 (Jakarta)
                            </div>
                            <div>
                                <strong>📍 Radius:</strong> 100 meter
                            </div>
                        </div>
                        <div class="mt-1 text-xs text-gray-500">
                            * Presensi hanya bisa dilakukan dalam radius yang ditentukan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Leaflet CSS dan JS untuk peta --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Map Script --}}
    <script>
        let klinikMap = null;
        let mapInitialized = false;

        // Koordinat klinik yang ditentukan admin
        const klinikCoordinates = [-6.2088, 106.8456]; // Jakarta coordinates
        const allowedRadius = 100; // meter

        function toggleMap() {
            const mapContainer = document.getElementById('map-container');
            
            if (mapContainer.classList.contains('hidden')) {
                // Show map
                mapContainer.classList.remove('hidden');
                
                // Initialize map if not already done
                if (!mapInitialized) {
                    initializeMap();
                }
            } else {
                // Hide map
                mapContainer.classList.add('hidden');
            }
        }

        function initializeMap() {
            try {
                // Initialize Leaflet map
                klinikMap = L.map('klinik-map').setView(klinikCoordinates, 16);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(klinikMap);
                
                // Add marker for klinik location
                const klinikMarker = L.marker(klinikCoordinates).addTo(klinikMap);
                klinikMarker.bindPopup(`
                    <div class="text-center">
                        <strong>🏥 Klinik Dokterku</strong><br>
                        <small>Lokasi Presensi</small><br>
                        <small>Lat: ${klinikCoordinates[0]}</small><br>
                        <small>Lng: ${klinikCoordinates[1]}</small>
                    </div>
                `).openPopup();
                
                // Add circle to show allowed radius
                const allowedArea = L.circle(klinikCoordinates, {
                    color: 'green',
                    fillColor: '#22c55e',
                    fillOpacity: 0.2,
                    radius: allowedRadius
                }).addTo(klinikMap);
                
                allowedArea.bindPopup(`
                    <div class="text-center">
                        <strong>✅ Area Presensi</strong><br>
                        <small>Radius: ${allowedRadius} meter</small><br>
                        <small>Presensi hanya bisa dilakukan dalam area ini</small>
                    </div>
                `);
                
                // Try to get user's current location
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(position) {
                        const userLat = position.coords.latitude;
                        const userLng = position.coords.longitude;
                        
                        // Add user location marker
                        const userMarker = L.marker([userLat, userLng], {
                            icon: L.icon({
                                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
                                iconSize: [25, 41],
                                iconAnchor: [12, 41],
                                popupAnchor: [1, -34]
                            })
                        }).addTo(klinikMap);
                        
                        userMarker.bindPopup(`
                            <div class="text-center">
                                <strong>📍 Lokasi Anda</strong><br>
                                <small>Lat: ${userLat.toFixed(6)}</small><br>
                                <small>Lng: ${userLng.toFixed(6)}</small>
                            </div>
                        `);
                        
                        // Calculate distance
                        const distance = klinikMap.distance(klinikCoordinates, [userLat, userLng]);
                        
                        // Show distance info
                        const distanceInfo = L.popup()
                            .setLatLng([userLat, userLng])
                            .setContent(`
                                <div class="text-center">
                                    <strong>${distance <= allowedRadius ? '✅' : '❌'} Jarak: ${Math.round(distance)}m</strong><br>
                                    <small>${distance <= allowedRadius ? 'Dalam area presensi' : 'Di luar area presensi'}</small>
                                </div>
                            `);
                        
                        // Optional: Auto-open distance popup
                        setTimeout(() => {
                            distanceInfo.openOn(klinikMap);
                        }, 1000);
                        
                    }, function(error) {
                        console.log('Geolocation error:', error);
                        // Add info about location access
                        L.popup()
                            .setLatLng(klinikCoordinates)
                            .setContent(`
                                <div class="text-center text-orange-600">
                                    <strong>⚠️ Akses Lokasi Ditolak</strong><br>
                                    <small>Izinkan akses lokasi untuk melihat posisi Anda</small>
                                </div>
                            `)
                            .openOn(klinikMap);
                    });
                }
                
                mapInitialized = true;
                console.log('✅ Klinik map initialized successfully');
                
            } catch (error) {
                console.error('❌ Error initializing map:', error);
            }
        }

        // Auto-resize map when container becomes visible
        function resizeMap() {
            if (klinikMap) {
                setTimeout(() => {
                    klinikMap.invalidateSize();
                }, 100);
            }
        }

        // Listen for map container visibility changes
        const mapContainer = document.getElementById('map-container');
        if (mapContainer) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (!mapContainer.classList.contains('hidden')) {
                            resizeMap();
                        }
                    }
                });
            });
            
            observer.observe(mapContainer, {
                attributes: true,
                attributeFilter: ['class']
            });
        }
    </script>
</x-filament-widgets::widget>