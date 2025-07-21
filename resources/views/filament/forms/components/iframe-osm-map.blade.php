@php
    $statePath = $getStatePath();
    $defaultLocation = $getDefaultLocation();
    $lat = data_get($getState(), 'lat', $defaultLocation['lat'] ?? -7.89946200);
    $lng = data_get($getState(), 'lng', $defaultLocation['lng'] ?? 111.96239900);
    $zoom = $getZoom() ?? 15;
    $height = $getHeight() ?? 400;
    $mapId = 'iframe-map-' . str_replace(['.', '[', ']'], '-', $statePath);
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
    <div class="relative border border-gray-300 rounded-lg overflow-hidden bg-gray-100">
        <!-- Iframe container untuk peta -->
        <div class="relative w-full" style="height: {{ $height }}px;">
            <iframe 
                id="{{ $mapId }}-frame"
                src="data:text/html;charset=utf-8,
                <!DOCTYPE html>
                <html>
                <head>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css' />
                    <style>
                        body { margin: 0; padding: 0; }
                        #map { height: 100vh; width: 100vw; }
                        .leaflet-container { background: #aadaff !important; }
                    </style>
                </head>
                <body>
                    <div id='map'></div>
                    <script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>
                    <script>
                        let map, marker;
                        let currentLat = {{ $lat }};
                        let currentLng = {{ $lng }};
                        
                        function initMap() {
                            map = L.map('map', {
                                center: [currentLat, currentLng],
                                zoom: {{ $zoom }},
                                zoomControl: true
                            });
                            
                            // Add OpenStreetMap tiles
                            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                                attribution: '© OpenStreetMap contributors',
                                maxZoom: 19,
                                subdomains: ['a', 'b', 'c']
                            }).addTo(map);
                            
                            // Add marker
                            marker = L.marker([currentLat, currentLng], {
                                draggable: true
                            }).addTo(map);
                            
                            // Event handlers
                            marker.on('dragend', function(e) {
                                const pos = e.target.getLatLng();
                                updateLocation(pos.lat, pos.lng);
                            });
                            
                            map.on('click', function(e) {
                                marker.setLatLng(e.latlng);
                                updateLocation(e.latlng.lat, e.latlng.lng);
                            });
                            
                            // Listen for external updates
                            window.addEventListener('message', function(event) {
                                if (event.data.type === 'updateLocation') {
                                    currentLat = event.data.lat;
                                    currentLng = event.data.lng;
                                    map.setView([currentLat, currentLng]);
                                    marker.setLatLng([currentLat, currentLng]);
                                }
                                if (event.data.type === 'getCurrentLocation') {
                                    if (navigator.geolocation) {
                                        navigator.geolocation.getCurrentPosition(function(position) {
                                            const lat = position.coords.latitude;
                                            const lng = position.coords.longitude;
                                            map.setView([lat, lng], 16);
                                            marker.setLatLng([lat, lng]);
                                            updateLocation(lat, lng);
                                        });
                                    }
                                }
                            });
                        }
                        
                        function updateLocation(lat, lng) {
                            currentLat = lat;
                            currentLng = lng;
                            window.parent.postMessage({
                                type: 'locationUpdate',
                                lat: lat,
                                lng: lng,
                                mapId: '{{ $mapId }}'
                            }, '*');
                        }
                        
                        // Initialize when DOM is ready
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', initMap);
                        } else {
                            initMap();
                        }
                    </script>
                </body>
                </html>"
                class="w-full h-full border-0"
                wire:ignore
                loading="lazy"
            ></iframe>
        </div>
        
        <!-- GPS Button Overlay -->
        <button 
            type="button"
            id="{{ $mapId }}-gps"
            class="absolute top-3 left-3 z-10 bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-lg transition-all"
            onclick="triggerGPS{{ Str::studly(str_replace('-', '', $mapId)) }}()"
        >
            📍 Deteksi GPS
        </button>
        
        <!-- Coordinates Display -->
        <div class="absolute bottom-3 left-3 z-10 bg-white/95 backdrop-blur-sm rounded-lg px-3 py-2 text-sm shadow-lg">
            <div class="text-gray-600">
                <span class="font-medium">Lokasi:</span>
                <div id="{{ $mapId }}-coords" class="font-mono text-blue-600">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</div>
            </div>
        </div>
    </div>

    <script>
        // Listen for messages from iframe
        window.addEventListener('message', function(event) {
            if (event.data.type === 'locationUpdate' && event.data.mapId === '{{ $mapId }}') {
                const lat = event.data.lat;
                const lng = event.data.lng;
                
                // Update coordinate display
                document.getElementById('{{ $mapId }}-coords').textContent = 
                    lat.toFixed(6) + ', ' + lng.toFixed(6);
                
                // Update Livewire state
                @this.set('{{ $statePath }}', {
                    lat: parseFloat(lat.toFixed(6)),
                    lng: parseFloat(lng.toFixed(6))
                });
            }
        });
        
        // GPS function
        function triggerGPS{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const iframe = document.getElementById('{{ $mapId }}-frame');
            const button = document.getElementById('{{ $mapId }}-gps');
            
            button.textContent = '🔄 Mencari...';
            button.disabled = true;
            
            iframe.contentWindow.postMessage({
                type: 'getCurrentLocation'
            }, '*');
            
            // Reset button after timeout
            setTimeout(() => {
                button.textContent = '📍 Deteksi GPS';
                button.disabled = false;
            }, 5000);
        }
        
        // Update map when state changes externally
        function updateMapLocation{{ Str::studly(str_replace('-', '', $mapId)) }}(lat, lng) {
            const iframe = document.getElementById('{{ $mapId }}-frame');
            iframe.contentWindow.postMessage({
                type: 'updateLocation',
                lat: lat,
                lng: lng
            }, '*');
        }
    </script>
</x-dynamic-component>