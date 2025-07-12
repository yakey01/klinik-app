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
                    @if($accuracy)
                        <div class="text-xs px-2 py-1 rounded-full bg-{{ $accuracyStatus['color'] }}-100 text-{{ $accuracyStatus['color'] }}-600 dark:bg-{{ $accuracyStatus['color'] }}-900/20">
                            {{ $accuracyStatus['message'] }} (±{{ round($accuracy) }}m)
                        </div>
                    @endif
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
                        
                        {{-- Device Info --}}
                        @if($locationDetected)
                            <div class="text-sm text-gray-600">
                                <p><strong>📱 Device Info:</strong></p>
                                <p>{{ $deviceInfo }} / {{ $browserInfo }}</p>
                                <p>IP: {{ $ipAddress }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Enhanced Map Display --}}
                <div class="border rounded-lg overflow-hidden relative" style="height: 350px;">
                    @if($locationDetected)
                        {{-- OpenStreetMap dengan Leaflet --}}
                        <div id="osm-map" class="w-full h-full"></div>
                        
                        {{-- Overlay with location info --}}
                        <div class="absolute top-2 left-2 bg-white dark:bg-gray-800 rounded-lg shadow-lg p-3 max-w-xs z-10">
                            <div class="text-xs">
                                <p class="font-semibold text-gray-800 dark:text-gray-200 mb-1">📍 Lokasi Anda</p>
                                <p class="text-gray-600 dark:text-gray-400">Jarak: {{ round($distanceToClinic) }}m dari klinik</p>
                                @if($accuracy)
                                    <p class="text-gray-600 dark:text-gray-400">Akurasi: ±{{ round($accuracy) }}m</p>
                                @endif
                                <div class="mt-2">
                                    @if($withinRadius)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                            ✅ Dalam radius
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">
                                            ❌ Di luar radius
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        {{-- Bottom action buttons --}}
                        <div class="absolute bottom-2 right-2 flex space-x-2 z-10">
                            <a href="https://maps.google.com/maps?q={{ $latitude }},{{ $longitude }}" 
                               target="_blank" 
                               class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                🗺️ Google Maps
                            </a>
                            <a href="https://www.openstreetmap.org/?mlat={{ $latitude }}&mlon={{ $longitude }}&zoom=17" 
                               target="_blank" 
                               class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-xs transition-colors">
                                🗺️ OSM
                            </a>
                        </div>
                    @else
                        {{-- Placeholder when no location --}}
                        <div id="map-placeholder" class="w-full h-full flex items-center justify-center bg-gray-50 dark:bg-gray-800">
                            <div class="text-center">
                                <div class="text-4xl mb-2">🗺️</div>
                                <p class="text-gray-600 dark:text-gray-400">Peta akan muncul setelah lokasi terdeteksi</p>
                                <p class="text-sm text-gray-500 mt-1">Klik "Deteksi Lokasi Otomatis" di atas</p>
                            </div>
                        </div>
                    @endif
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

    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
              crossorigin="" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.84.2/dist/L.Control.Locate.min.css" />
        <style>
            /* Enhanced map animations */
            .radius-circle-animated {
                animation: pulse-radius 3s ease-in-out infinite;
            }
            
            .enhanced-clinic-marker {
                animation: bounce-clinic 2s ease-in-out infinite;
            }
            
            .enhanced-user-marker {
                animation: pulse-user 2s ease-in-out infinite;
            }
            
            @keyframes pulse-radius {
                0%, 100% { 
                    opacity: 0.15; 
                    transform: scale(1);
                }
                50% { 
                    opacity: 0.25; 
                    transform: scale(1.02);
                }
            }
            
            @keyframes bounce-clinic {
                0%, 100% { 
                    transform: translateY(0px); 
                }
                50% { 
                    transform: translateY(-3px); 
                }
            }
            
            @keyframes pulse-user {
                0%, 100% { 
                    transform: scale(1); 
                    box-shadow: 0 3px 6px rgba(0,0,0,0.4);
                }
                50% { 
                    transform: scale(1.1); 
                    box-shadow: 0 5px 10px rgba(59, 130, 246, 0.6);
                }
            }
            
            /* Accuracy status badges */
            .accuracy-excellent { background: linear-gradient(45deg, #22c55e, #16a34a); }
            .accuracy-good { background: linear-gradient(45deg, #3b82f6, #2563eb); }
            .accuracy-fair { background: linear-gradient(45deg, #f59e0b, #d97706); }
            .accuracy-poor { background: linear-gradient(45deg, #ef4444, #dc2626); }
            
            /* Loading spinner for GPS */
            .gps-loading {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            /* Map container enhancements */
            #attendance-map {
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                transition: all 0.3s ease;
            }
            
            #attendance-map:hover {
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }
            
            /* Leaflet popup styling */
            .leaflet-popup-content-wrapper {
                border-radius: 8px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            
            /* Enhanced mobile responsiveness */
            @media (max-width: 768px) {
                .enhanced-clinic-marker,
                .enhanced-user-marker {
                    transform: scale(0.9);
                }
                
                .leaflet-control-layers {
                    font-size: 12px;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
                crossorigin=""></script>
        <script src="https://cdn.jsdelivr.net/npm/leaflet.locatecontrol@0.84.2/dist/L.Control.Locate.min.js"></script>
    
    <script>
        let map = null;
        let userMarker = null;
        let clinicMarker = null;
        let radiusCircle = null;
        let accuracyCircle = null;
        let locateControl = null;
        
        // Clock function
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

        // Enhanced OSM Map with Auto-Locate Control
        function initializeOSMMap(userLat, userLng, clinicLat, clinicLng, radius, accuracy = null) {
            const mapContainer = document.getElementById('osm-map');
            if (!mapContainer) {
                console.error('OSM map container not found');
                showStaticLocationInfo(userLat, userLng, accuracy);
                return;
            }
            
            console.log('Initializing Enhanced OSM map with Auto-Locate:', {userLat, userLng, clinicLat, clinicLng, radius, accuracy});
            
            // Check if Leaflet and LocateControl are loaded
            if (typeof L === 'undefined') {
                console.error('Leaflet library not loaded');
                showStaticLocationInfo(userLat, userLng, accuracy);
                return;
            }
            
            if (typeof L.Control.Locate === 'undefined') {
                console.error('Leaflet LocateControl not loaded');
                // Fallback to basic implementation
                initializeBasicOSMMap(userLat, userLng, clinicLat, clinicLng, radius, accuracy);
                return;
            }
            
            try {
                // Clear existing map if any
                if (map) {
                    map.remove();
                    map = null;
                    locateControl = null;
                }
                
                // Calculate center point between user and clinic
                const centerLat = (userLat + clinicLat) / 2;
                const centerLng = (userLng + clinicLng) / 2;
                
                // Initialize enhanced OSM map
                map = L.map('osm-map', {
                    attributionControl: false,
                    zoomControl: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    touchZoom: true
                }).setView([centerLat, centerLng], 16);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                // Add enhanced locate control
                locateControl = L.control.locate({
                    position: 'topright',
                    layer: null,
                    setView: 'untilPanOrZoom',
                    keepCurrentZoomLevel: false,
                    getLocationBounds: function (locationEvent) {
                        return locationEvent.bounds.extend(L.latLng(clinicLat, clinicLng));
                    },
                    flyTo: true,
                    clickBehavior: {
                        inView: 'stop',
                        outOfView: 'setView',
                        inViewNotFollowing: 'inView'
                    },
                    returnToPrevBounds: false,
                    icon: 'fa fa-map-marker-alt',
                    iconLoading: 'fa fa-spinner fa-spin',
                    iconElementTag: 'span',
                    circlePadding: [0, 0],
                    metric: true,
                    createButtonCallback: function (container, options) {
                        var link = L.DomUtil.create('a', 'leaflet-bar-part leaflet-bar-part-single', container);
                        link.innerHTML = '🎯';
                        link.href = '#';
                        link.title = 'Auto Detect GPS Location';
                        link.setAttribute('role', 'button');
                        link.setAttribute('aria-label', options.strings.title);
                        return { link: link, icon: link };
                    },
                    locateOptions: {
                        enableHighAccuracy: true,
                        watch: true,
                        maximumAge: 60000,
                        timeout: 15000
                    },
                    strings: {
                        title: "🎯 Auto Detect GPS",
                        popup: "📍 Anda berada dalam radius {distance} {unit} dari titik ini",
                        outsideMapBoundsMsg: "Lokasi Anda di luar area peta"
                    },
                    onLocationError: function(err) {
                        console.error('Auto-locate error:', err);
                        @this.dispatch('location-error', { error: err.message });
                    }
                }).addTo(map);
                
                // Enhanced location event handlers
                map.on('locationfound', function(e) {
                    console.log('Auto-location found:', e);
                    
                    // Send to Livewire
                    @this.dispatch('location-received', { 
                        latitude: e.latlng.lat, 
                        longitude: e.latlng.lng, 
                        accuracy: e.accuracy 
                    });
                    
                    // Add custom markers and radius
                    addMapMarkers(e.latlng.lat, e.latlng.lng, clinicLat, clinicLng, radius, e.accuracy);
                });
                
                map.on('locationerror', function(e) {
                    console.error('Auto-location error:', e);
                    @this.dispatch('location-error', { error: e.message });
                });
                
                console.log('Enhanced OSM map with auto-locate initialized successfully');
                
                // Add static markers if location already known
                if (userLat && userLng) {
                    addMapMarkers(userLat, userLng, clinicLat, clinicLng, radius, accuracy);
                }
                
            } catch (error) {
                console.error('Error initializing enhanced OSM map:', error);
                // Fallback to basic implementation
                initializeBasicOSMMap(userLat, userLng, clinicLat, clinicLng, radius, accuracy);
                return;
            }
        }
        
        // Fallback basic OSM implementation
        function initializeBasicOSMMap(userLat, userLng, clinicLat, clinicLng, radius, accuracy = null) {
            console.log('Falling back to basic OSM implementation');
            
            try {
                // Clear existing map if any
                if (map) {
                    map.remove();
                    map = null;
                }
                
                // Calculate center point between user and clinic
                const centerLat = (userLat + clinicLat) / 2;
                const centerLng = (userLng + clinicLng) / 2;
                
                // Initialize basic OSM map
                map = L.map('osm-map', {
                    attributionControl: false,
                    zoomControl: true,
                    scrollWheelZoom: true,
                    doubleClickZoom: true,
                    touchZoom: true
                }).setView([centerLat, centerLng], 16);
                
                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);
                
                console.log('Basic OSM map initialized successfully');
                
                // Add markers
                addMapMarkers(userLat, userLng, clinicLat, clinicLng, radius, accuracy);
                
            } catch (error) {
                console.error('Error initializing basic OSM map:', error);
                showStaticLocationInfo(userLat, userLng, accuracy);
                return;
            }
        }
        
        // Add markers to map
        function addMapMarkers(userLat, userLng, clinicLat, clinicLng, radius, accuracy) {
            try {
                // Add clinic marker (green)
                const clinicIcon = L.divIcon({
                    html: '<div style="background: #22c55e; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-size: 10px;">🏥</div>',
                    className: 'custom-div-icon',
                    iconSize: [20, 20],
                    iconAnchor: [10, 10]
                });
                
                L.marker([clinicLat, clinicLng], {icon: clinicIcon})
                    .addTo(map)
                    .bindPopup('<b>🏥 Klinik Dokterku</b><br>Lokasi kerja resmi');
                
                // Add user marker (blue/red based on radius)
                const withinRadius = calculateDistance(userLat, userLng, clinicLat, clinicLng) <= radius;
                const userColor = withinRadius ? '#3b82f6' : '#ef4444';
                const userIcon = L.divIcon({
                    html: `<div style="background: ${userColor}; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white; font-size: 8px;">📍</div>`,
                    className: 'custom-div-icon',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });
                
                const distance = calculateDistance(userLat, userLng, clinicLat, clinicLng);
                L.marker([userLat, userLng], {icon: userIcon})
                    .addTo(map)
                    .bindPopup(`<b>📍 Lokasi Anda</b><br>Jarak: ${distance.toFixed(0)}m<br>${accuracy ? `Akurasi: ±${accuracy.toFixed(0)}m<br>` : ''}Status: ${withinRadius ? '✅ Dalam radius' : '❌ Di luar radius'}`);
                
                // Add radius circle
                L.circle([clinicLat, clinicLng], {
                    radius: radius,
                    color: '#22c55e',
                    fillColor: '#22c55e',
                    fillOpacity: 0.1,
                    weight: 2,
                    dashArray: '5, 5'
                }).addTo(map).bindPopup(`<b>🎯 Area Presensi</b><br>Radius: ${radius}m`);
                
                // Add accuracy circle if available
                if (accuracy && accuracy > 0) {
                    L.circle([userLat, userLng], {
                        radius: accuracy,
                        color: '#3b82f6',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.05,
                        weight: 1,
                        dashArray: '3, 3'
                    }).addTo(map).bindPopup(`<b>📡 Akurasi GPS</b><br>±${accuracy.toFixed(0)}m`);
                }
                
                console.log('Map markers added successfully');
                
            } catch (error) {
                console.error('Error adding markers:', error);
            }
        }
        
        // Show static location info if map fails
        function showStaticLocationInfo(lat, lng, accuracy) {
            const mapContainer = document.getElementById('osm-map');
            if (mapContainer) {
                const distance = calculateDistance(lat, lng, {{ $clinicLat }}, {{ $clinicLng }});
                const withinRadius = distance <= {{ $clinicRadius }};
                
                mapContainer.innerHTML = `
                    <div class="w-full h-full flex items-center justify-center bg-blue-50 dark:bg-blue-900/20">
                        <div class="text-center p-4">
                            <div class="text-3xl mb-3">📍</div>
                            <p class="text-blue-600 dark:text-blue-400 font-bold text-lg mb-2">Lokasi Terdeteksi</p>
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 mb-3 text-sm">
                                <p class="text-gray-700 dark:text-gray-300"><strong>Koordinat:</strong></p>
                                <p class="text-gray-600 dark:text-gray-400">Lat: ${lat.toFixed(6)}</p>
                                <p class="text-gray-600 dark:text-gray-400">Lng: ${lng.toFixed(6)}</p>
                                <p class="text-gray-600 dark:text-gray-400 mt-2"><strong>Jarak:</strong> ${distance.toFixed(0)}m dari klinik</p>
                                ${accuracy ? `<p class="text-gray-600 dark:text-gray-400"><strong>Akurasi:</strong> ±${accuracy.toFixed(0)}m</p>` : ''}
                                <p class="text-sm font-bold mt-2 ${withinRadius ? 'text-green-600' : 'text-red-600'}">
                                    ${withinRadius ? '✅ Dalam radius klinik' : '❌ Di luar radius klinik'}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <a href="https://maps.google.com/maps?q=${lat},${lng}" 
                                   target="_blank" 
                                   class="inline-block w-full px-4 py-2 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors">
                                    🗺️ Buka di Google Maps
                                </a>
                                <a href="https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}&zoom=17" 
                                   target="_blank" 
                                   class="inline-block w-full px-4 py-2 bg-orange-500 text-white text-sm rounded hover:bg-orange-600 transition-colors">
                                    🗺️ Buka di OpenStreetMap
                                </a>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        // Keep old function for compatibility but update to use new OSM function
        function initializeMap(userLat, userLng, clinicLat, clinicLng, radius, accuracy = null) {
            // Redirect to OSM implementation
            initializeOSMMap(userLat, userLng, clinicLat, clinicLng, radius, accuracy);
        }
        
        // Helper function to calculate distance
        function calculateDistance(lat1, lng1, lat2, lng2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLng = (lng2 - lng1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLng/2) * Math.sin(dLng/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        // Geolocation functions
        function requestLocation() {
            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        // Send location to Livewire
                        @this.dispatch('location-received', { 
                            latitude: latitude, 
                            longitude: longitude, 
                            accuracy: accuracy 
                        });
                        
                        // Initialize OSM map with coordinates
                        initializeOSMMap(latitude, longitude, {{ $clinicLat }}, {{ $clinicLng }}, {{ $clinicRadius }}, accuracy);
                    },
                    function(error) {
                        let errorMessage = 'Geolocation error: ';
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage += 'Izin lokasi ditolak. Silakan aktifkan GPS dan refresh halaman.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage += 'Lokasi tidak tersedia. Pastikan GPS aktif.';
                                break;
                            case error.TIMEOUT:
                                errorMessage += 'Waktu deteksi habis. Coba lagi.';
                                break;
                            default:
                                errorMessage += 'Terjadi kesalahan tidak dikenal.';
                                break;
                        }
                        
                        @this.dispatch('location-error', { error: errorMessage });
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 60000
                    }
                );
            } else {
                @this.dispatch('location-error', { error: 'Geolocation tidak didukung browser ini' });
            }
        }

        // Enhanced event listeners for auto-locate control
        window.addEventListener('auto-locate-start', function() {
            console.log('Auto-locate start triggered');
            if (locateControl && map) {
                locateControl.start();
            } else {
                console.log('LocateControl not available, falling back to manual detection');
                requestLocation();
            }
        });
        
        window.addEventListener('request-location-manual', function() {
            console.log('Manual location request triggered');
            requestLocation();
        });
        
        window.addEventListener('reset-location-control', function() {
            console.log('Reset location control triggered');
            if (locateControl && map) {
                locateControl.stop();
            }
            if (map) {
                map.setView([{{ $clinicLat }}, {{ $clinicLng }}], 16);
            }
        });
        
        // Legacy event listeners for backward compatibility
        window.addEventListener('request-location', function() {
            requestLocation();
        });
        
        // Listen for map updates from Livewire
        window.addEventListener('update-map-location', function(e) {
            const data = e.detail[0];
            if (data.lat && data.lng) {
                initializeOSMMap(data.lat, data.lng, {{ $clinicLat }}, {{ $clinicLng }}, {{ $clinicRadius }}, data.accuracy);
            }
        });

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call
        
        // Enhanced auto-detect location when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded with enhanced auto-locate, checking location state...');
            
            // Check if location is already detected (from server state)
            const locationDetected = @json($locationDetected);
            const latitude = @json($latitude);
            const longitude = @json($longitude);
            const accuracy = @json($accuracy);
            
            console.log('Server state:', {locationDetected, latitude, longitude, accuracy});
            
            if (locationDetected && latitude && longitude) {
                console.log('Location already detected, initializing Enhanced OSM map...');
                
                setTimeout(function() {
                    console.log('Initializing Enhanced OSM map with auto-locate...');
                    try {
                        initializeOSMMap(latitude, longitude, {{ $clinicLat }}, {{ $clinicLng }}, {{ $clinicRadius }}, accuracy);
                        
                        // Hide placeholder if exists
                        const placeholder = document.getElementById('map-placeholder');
                        if (placeholder) {
                            placeholder.style.display = 'none';
                            console.log('Placeholder hidden');
                        }
                    } catch (error) {
                        console.error('Failed to initialize Enhanced OSM map:', error);
                        showStaticLocationInfo(latitude, longitude, accuracy);
                    }
                }, 1000);
            } else {
                console.log('Location not detected, initializing map with auto-locate control...');
                
                setTimeout(function() {
                    // Initialize map first without location
                    try {
                        initializeOSMMap(null, null, {{ $clinicLat }}, {{ $clinicLng }}, {{ $clinicRadius }}, null);
                        
                        // Then try auto-locate if control is available
                        setTimeout(function() {
                            if (locateControl && map) {
                                console.log('Starting auto-locate control...');
                                locateControl.start();
                            } else {
                                console.log('Auto-locate control not available, using manual detection');
                                requestLocation();
                            }
                        }, 500);
                        
                    } catch (error) {
                        console.error('Failed to initialize map:', error);
                        // Fallback to manual location request
                        setTimeout(function() {
                            requestLocation();
                        }, 1000);
                    }
                }, 1000);
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
