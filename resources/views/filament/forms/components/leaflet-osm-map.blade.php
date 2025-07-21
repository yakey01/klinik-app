@php
    $statePath = $getStatePath();
    $defaultLocation = $getDefaultLocation();
    $lat = data_get($getState(), 'lat', $defaultLocation['lat'] ?? -7.89946200);
    $lng = data_get($getState(), 'lng', $defaultLocation['lng'] ?? 111.96239900);
    $zoom = $getZoom() ?? 15;
    $height = $getHeight() ?? 400;
    $mapId = 'map-' . str_replace(['.', '[', ']'], '-', $statePath);
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
    :id="$getId()"
    :label="$getLabel()"
    :label-sr-only="$isLabelHidden()"
    :helper-text="$getHelperText()"
    :hint="$getHint()"
    :required="$isRequired()"
    :state-path="$getStatePath()"
>
    <div class="leaflet-osm-map-container">
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" 
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" 
              crossorigin=""/>

        <div class="relative">
            <div 
                id="{{ $mapId }}" 
                style="height: {{ $height }}px; width: 100%; min-height: 300px; z-index: 1;"
                class="border border-gray-300 rounded-lg bg-gray-100"
                wire:ignore
            ></div>
            
            <button 
                type="button"
                id="{{ $mapId }}-gps-btn"
                class="absolute top-2 left-2 z-[1000] bg-white border border-gray-300 rounded px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-md"
                onclick="getCurrentLocation{{ Str::studly($mapId) }}()"
            >
                📍 Deteksi GPS
            </button>
            
            <div class="mt-2 text-sm text-gray-600">
                <span>Koordinat: </span>
                <span id="{{ $mapId }}-coords">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</span>
            </div>
        </div>

        <!-- Leaflet JavaScript -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" 
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" 
                crossorigin=""></script>

        <script>
            // Wait for Leaflet to load completely
            function initMap{{ Str::studly($mapId) }}() {
                try {
                    // Initialize map with error handling
                    const map = L.map('{{ $mapId }}', {
                        center: [{{ $lat }}, {{ $lng }}],
                        zoom: {{ $zoom }},
                        zoomControl: true,
                        attributionControl: true
                    });
                    
                    // Add OpenStreetMap tile layer with multiple tile servers
                    const osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                        subdomains: ['a', 'b', 'c'],
                        crossOrigin: true
                    });
                    
                    osmLayer.addTo(map);
                    
                    // Add marker
                    const marker = L.marker([{{ $lat }}, {{ $lng }}], {
                        draggable: true
                    }).addTo(map);
                    
                    // Store references globally
                    window['map{{ Str::studly($mapId) }}'] = map;
                    window['marker{{ Str::studly($mapId) }}'] = marker;
                    
                    // Update coordinates function
                    function updateCoords(lat, lng) {
                        document.getElementById('{{ $mapId }}-coords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                        
                        // Update Filament state
                        @this.set('{{ $statePath }}', {
                            lat: parseFloat(lat.toFixed(6)),
                            lng: parseFloat(lng.toFixed(6))
                        });
                        
                        // Update form fields if they exist
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
                    
                    // Force tile reload after a short delay
                    setTimeout(function() {
                        map.invalidateSize();
                        osmLayer.redraw();
                    }, 500);
                    
                    // Initialize from existing form data
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
                    }, 1000);
                    
                } catch (error) {
                    console.error('Error initializing map:', error);
                    document.getElementById('{{ $mapId }}').innerHTML = '<div class="flex items-center justify-center h-full text-red-500">Error loading map. Please refresh the page.</div>';
                }
            }
            
            // GPS location function
            function getCurrentLocation{{ Str::studly($mapId) }}() {
                if (navigator.geolocation) {
                    const button = document.getElementById('{{ $mapId }}-gps-btn');
                    button.textContent = '🔄 Mencari lokasi...';
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
                                updateCoords(lat, lng);
                            }
                            
                            button.textContent = '📍 Deteksi GPS';
                            button.disabled = false;
                        },
                        function(error) {
                            alert('Error mendapatkan lokasi: ' + error.message);
                            button.textContent = '📍 Deteksi GPS';
                            button.disabled = false;
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 60000
                        }
                    );
                } else {
                    alert('Geolocation tidak didukung oleh browser ini.');
                }
            }
            
            // Initialize when DOM is ready and Leaflet is loaded
            document.addEventListener('DOMContentLoaded', function() {
                // Check if Leaflet is loaded
                function checkLeaflet() {
                    if (typeof L !== 'undefined') {
                        initMap{{ Str::studly($mapId) }}();
                    } else {
                        setTimeout(checkLeaflet, 100);
                    }
                }
                checkLeaflet();
            });
            
            // Also try on window load as fallback
            window.addEventListener('load', function() {
                if (typeof L !== 'undefined' && !window['map{{ Str::studly($mapId) }}']) {
                    initMap{{ Str::studly($mapId) }}();
                }
            });
        </script>
    </div>
</x-dynamic-component>