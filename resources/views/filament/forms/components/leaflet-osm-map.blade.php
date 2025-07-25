<div class="leaflet-osm-map-wrapper space-y-4">
@php
    $statePath = $getStatePath();
    $lat = -6.2088; // Default Jakarta latitude  
    $lng = 106.8456; // Default Jakarta longitude
    $zoom = 15;
    $height = 500;
    $mapId = 'map-' . str_replace(['.', '[', ']'], '-', $statePath);
@endphp
    <!-- Status Information Cards -->
    <div class="grid grid-cols-3 gap-4">
        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="text-sm font-medium text-blue-900 flex items-center space-x-2">
                <span>📍</span>
                <span>Lokasi</span>
            </div>
            <div id="{{ $mapId }}-location-status" class="text-xs text-blue-600 mt-1">Belum dipilih</div>
        </div>
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="text-sm font-medium text-green-900 flex items-center space-x-2">
                <span>🎯</span>
                <span>Koordinat</span>
            </div>
            <div id="{{ $mapId }}-coords" class="text-xs text-green-600 mt-1 font-mono">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</div>
        </div>
        <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
            <div class="text-sm font-medium text-purple-900 flex items-center space-x-2">
                <span>⚡</span>
                <span>GPS</span>
            </div>
            <div id="{{ $mapId }}-gps-status" class="text-xs text-purple-600 mt-1">Siap</div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="relative">
        <div 
            id="{{ $mapId }}" 
            style="height: {{ $height }}px; width: 100%; min-height: 300px; z-index: 1;"
            class="border-2 border-gray-300 rounded-xl bg-gray-100 shadow-lg"
            wire:ignore
        ></div>
        
        <!-- Control Buttons -->
        <div class="absolute top-4 right-4 flex flex-col gap-2 z-[1000]">
            <button 
                type="button"
                id="{{ $mapId }}-gps-btn"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium"
                onclick="getCurrentLocation{{ Str::studly($mapId) }}()"
            >
                <span>📍</span>
                <span>GPS</span>
            </button>
        </div>
        
        <!-- Coordinates Display -->
        <div class="absolute bottom-4 left-4 bg-white bg-opacity-90 px-4 py-2 rounded-lg shadow-lg">
            <div class="text-sm text-gray-700 font-mono">
                <div>Lat: <span id="{{ $mapId }}-lat-display">{{ number_format($lat, 6) }}</span></div>
                <div>Lng: <span id="{{ $mapId }}-lng-display">{{ number_format($lng, 6) }}</span></div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2 flex items-center space-x-2">
            <span>📋</span>
            <span>Cara Menggunakan Peta Geofencing:</span>
        </h4>
        <ul class="text-xs text-gray-600 space-y-1">
            <li>• <strong>GPS:</strong> Klik tombol "GPS" untuk deteksi lokasi otomatis</li>
            <li>• <strong>Manual:</strong> Klik langsung pada peta untuk memilih titik lokasi</li>
            <li>• <strong>Drag:</strong> Seret marker merah untuk penyesuaian presisi</li>
            <li>• <strong>Koordinat:</strong> Latitude dan longitude akan terisi otomatis di form</li>
            <li>• <strong>Radius:</strong> Area geofencing ditentukan oleh field "Radius" di atas</li>
        </ul>
    </div>

    <!-- Initialize Leaflet -->
    <script>
        // Only load once per page
        if (!window.leafletLoaded) {
            window.leafletLoaded = true;
            
            // Load CSS
            if (!document.querySelector('link[href*="leaflet"]')) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                link.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                link.crossOrigin = '';
                document.head.appendChild(link);
            }
            
            // Load JS
            if (!document.querySelector('script[src*="leaflet"]')) {
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                script.crossOrigin = '';
                document.head.appendChild(script);
            }
        }

        // Initialize this specific map
        function initMap{{ Str::studly($mapId) }}() {
            try {
                if (typeof L === 'undefined') {
                    setTimeout(initMap{{ Str::studly($mapId) }}, 100);
                    return;
                }

                // Check if map already exists
                if (window['map{{ Str::studly($mapId) }}']) {
                    return;
                }

                const mapContainer = document.getElementById('{{ $mapId }}');
                if (!mapContainer) {
                    return;
                }

                // Initialize map
                const map = L.map('{{ $mapId }}', {
                    center: [{{ $lat }}, {{ $lng }}],
                    zoom: {{ $zoom }},
                    zoomControl: true,
                    attributionControl: true
                });
                
                // Add OpenStreetMap tiles
                const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                    maxZoom: 19,
                    subdomains: ['a', 'b', 'c']
                });
                
                osmLayer.addTo(map);
                
                // Add marker
                const marker = L.marker([{{ $lat }}, {{ $lng }}], {
                    draggable: true
                }).addTo(map);
                
                // Store references
                window['map{{ Str::studly($mapId) }}'] = map;
                window['marker{{ Str::studly($mapId) }}'] = marker;
                
                // Update coordinates function
                function updateCoords(lat, lng) {
                    const coordsEl = document.getElementById('{{ $mapId }}-coords');
                    if (coordsEl) {
                        coordsEl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                    }
                    
                    // Update form fields
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    if (latField) {
                        latField.value = lat.toFixed(6);
                        latField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (lngField) {
                        lngField.value = lng.toFixed(6);
                        lngField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
                
                // Event handlers
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    updateCoords(pos.lat, pos.lng);
                });
                
                map.on('click', function(e) {
                    marker.setLatLng(e.latlng);
                    updateCoords(e.latlng.lat, e.latlng.lng);
                });
                
                // Initialize from form if data exists
                setTimeout(function() {
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    if (latField && lngField && latField.value && lngField.value) {
                        const existingLat = parseFloat(latField.value);
                        const existingLng = parseFloat(lngField.value);
                        if (!isNaN(existingLat) && !isNaN(existingLng)) {
                            map.setView([existingLat, existingLng], {{ $zoom }});
                            marker.setLatLng([existingLat, existingLng]);
                            updateCoords(existingLat, existingLng);
                        }
                    }
                }, 500);
                
            } catch (error) {
                console.error('Map initialization error:', error);
            }
        }
        
        // GPS function
        function getCurrentLocation{{ Str::studly($mapId) }}() {
            const button = document.getElementById('{{ $mapId }}-gps-btn');
            if (!navigator.geolocation) {
                alert('GPS tidak didukung');
                return;
            }
            
            button.textContent = '🔄 Mencari...';
            button.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    const map = window['map{{ Str::studly($mapId) }}'];
                    const marker = window['marker{{ Str::studly($mapId) }}'];
                    
                    if (map && marker) {
                        map.setView([lat, lng], {{ $zoom }});
                        marker.setLatLng([lat, lng]);
                        
                        // Update coordinates
                        const coordsEl = document.getElementById('{{ $mapId }}-coords');
                        if (coordsEl) {
                            coordsEl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                        }
                        
                        // Update form
                        const latField = document.querySelector('input[name="latitude"]');
                        const lngField = document.querySelector('input[name="longitude"]');
                        if (latField) latField.value = lat.toFixed(6);
                        if (lngField) lngField.value = lng.toFixed(6);
                    }
                    
                    button.textContent = '📍 Deteksi GPS';
                    button.disabled = false;
                },
                function(error) {
                    alert('Error GPS: ' + error.message);
                    button.textContent = '📍 Deteksi GPS';
                    button.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        }
        
        // Initialize when ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initMap{{ Str::studly($mapId) }}, 200);
            });
        } else {
            setTimeout(initMap{{ Str::studly($mapId) }}, 200);
        }
    </script>
</div>