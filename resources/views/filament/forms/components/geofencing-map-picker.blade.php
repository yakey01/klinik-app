<div class="geofencing-map-container space-y-4" wire:ignore>
    <!-- Map Status Display -->
    <div class="grid grid-cols-3 gap-4">
        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="text-sm font-medium text-blue-900">📍 Lokasi</div>
            <div id="{{ $getId() }}-location-status" class="text-xs text-blue-600 mt-1">Belum dipilih</div>
        </div>
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="text-sm font-medium text-green-900">🎯 Radius</div>
            <div id="{{ $getId() }}-radius-display" class="text-xs text-green-600 mt-1">100 meter</div>
        </div>
        <div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
            <div class="text-sm font-medium text-purple-900">⚡ GPS</div>
            <div id="{{ $getId() }}-gps-status" class="text-xs text-purple-600 mt-1">Siap</div>
        </div>
    </div>
    
    <!-- Main Map Container -->
    <div class="relative border-2 border-gray-300 rounded-xl overflow-hidden shadow-lg" style="height: 500px;">
        <!-- Map Canvas -->
        <div id="{{ $getId() }}-map" class="w-full h-full relative bg-gradient-to-br from-green-50 to-blue-50"></div>
        
        <!-- Control Buttons -->
        <div class="absolute top-4 right-4 flex flex-col gap-2 z-[1000]">
            <!-- GPS Location Button -->
            <button 
                type="button" 
                id="{{ $getId() }}-gps-btn"
                class="bg-blue-500 hover:bg-blue-600 text-white p-3 rounded-lg shadow-lg transition-all duration-200 flex items-center gap-2"
                onclick="getGPSLocation{{ $getId() }}()"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span class="text-sm font-medium">GPS</span>
            </button>
            
            <!-- Zoom Controls -->
            <div class="bg-white border border-gray-300 rounded-lg shadow-lg overflow-hidden">
                <button type="button" id="{{ $getId() }}-zoom-in" class="block w-full px-3 py-2 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </button>
                <div class="border-t border-gray-300"></div>
                <button type="button" id="{{ $getId() }}-zoom-out" class="block w-full px-3 py-2 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Coordinates Display -->
        <div class="absolute bottom-4 left-4 bg-white bg-opacity-90 px-4 py-2 rounded-lg shadow-lg">
            <div id="{{ $getId() }}-coordinates" class="text-sm text-gray-700 font-mono">
                Klik pada peta untuk memilih lokasi
            </div>
        </div>
    </div>
    
    <!-- Instructions -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">📋 Cara Menggunakan Peta Geofencing:</h4>
        <ul class="text-xs text-gray-600 space-y-1">
            <li>• <strong>GPS:</strong> Klik tombol "GPS" untuk deteksi lokasi otomatis</li>
            <li>• <strong>Manual:</strong> Klik langsung pada peta untuk memilih titik lokasi</li>
            <li>• <strong>Zoom:</strong> Gunakan kontrol + dan - untuk memperbesar/memperkecil</li>
            <li>• <strong>Radius:</strong> Area biru menunjukkan zona geofencing yang valid</li>
            <li>• <strong>Koordinat:</strong> Latitude dan longitude akan terisi otomatis</li>
        </ul>
    </div>

    <!-- JavaScript and CSS moved inside the main container -->
    <script>
        let map{{ $getId() }}_zoom = 15;
        let map{{ $getId() }}_center = { lat: -7.89946200, lng: 111.96239900 }; // Default Madiun
        let map{{ $getId() }}_marker = null;
        let map{{ $getId() }}_radius = 100;
        
        function initGeofencingMap{{ $getId() }}() {
            const mapContainer = document.getElementById('{{ $getId() }}-map');
            
            // Initialize map display
            updateMapDisplay{{ $getId() }}();
            
            // Add click handler
            mapContainer.addEventListener('click', function(e) {
                const rect = mapContainer.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                // Convert pixel coordinates to lat/lng (simplified)
                const centerX = rect.width / 2;
                const centerY = rect.height / 2;
                const deltaX = x - centerX;
                const deltaY = y - centerY;
                
                // Calculate new coordinates based on zoom level
                const scale = Math.pow(2, 15 - map{{ $getId() }}_zoom) * 0.00001;
                const newLat = map{{ $getId() }}_center.lat - (deltaY * scale);
                const newLng = map{{ $getId() }}_center.lng + (deltaX * scale);
                
                setLocationManual{{ $getId() }}(newLat, newLng);
            });
            
            // Zoom controls
            document.getElementById('{{ $getId() }}-zoom-in').addEventListener('click', function() {
                map{{ $getId() }}_zoom = Math.min(map{{ $getId() }}_zoom + 1, 20);
                updateMapDisplay{{ $getId() }}();
            });
            
            document.getElementById('{{ $getId() }}-zoom-out').addEventListener('click', function() {
                map{{ $getId() }}_zoom = Math.max(map{{ $getId() }}_zoom - 1, 1);
                updateMapDisplay{{ $getId() }}();
            });
            
            // Watch for radius changes
            watchRadiusChanges{{ $getId() }}();
        }
        
        function updateMapDisplay{{ $getId() }}() {
            const mapContainer = document.getElementById('{{ $getId() }}-map');
            const radiusDisplay = document.getElementById('{{ $getId() }}-radius-display');
            
            // Get current radius from form
            const radiusField = document.querySelector('input[name="radius_meters"]');
            if (radiusField) {
                map{{ $getId() }}_radius = parseInt(radiusField.value) || 100;
                if (radiusDisplay) {
                    radiusDisplay.textContent = map{{ $getId() }}_radius + ' meter';
                }
            }
            
            // Create map visualization
            const zoomLevel = map{{ $getId() }}_zoom;
            const gridSize = Math.max(20, Math.min(100, 80 - zoomLevel * 2));
            
            mapContainer.innerHTML = `
                <div class="absolute inset-0 overflow-hidden">
                    <!-- Background with tile-like pattern -->
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 to-sky-50">
                        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <pattern id="grid-{{ $getId() }}" width="${gridSize}" height="${gridSize}" patternUnits="userSpaceOnUse">
                                    <path d="M ${gridSize} 0 L 0 0 0 ${gridSize}" fill="none" stroke="rgba(34, 197, 94, 0.1)" stroke-width="1"/>
                                </pattern>
                                <pattern id="roads-{{ $getId() }}" width="${gridSize*2}" height="${gridSize*2}" patternUnits="userSpaceOnUse">
                                    <line x1="0" y1="${gridSize}" x2="${gridSize*2}" y2="${gridSize}" stroke="rgba(34, 197, 94, 0.2)" stroke-width="2"/>
                                    <line x1="${gridSize}" y1="0" x2="${gridSize}" y2="${gridSize*2}" stroke="rgba(34, 197, 94, 0.2)" stroke-width="2"/>
                                </pattern>
                            </defs>
                            <rect width="100%" height="100%" fill="url(#grid-{{ $getId() }})"/>
                            <rect width="100%" height="100%" fill="url(#roads-{{ $getId() }})" opacity="0.6"/>
                        </svg>
                    </div>
                    
                    <!-- Geofencing radius circle -->
                    <div id="{{ $getId() }}-geofence-circle" class="absolute border-4 border-blue-400 bg-blue-400 bg-opacity-20 rounded-full pointer-events-none" style="display: none;"></div>
                    
                    <!-- Location marker -->
                    <div id="{{ $getId() }}-marker" class="absolute transform -translate-x-1/2 -translate-y-full pointer-events-none" style="display: none; z-index: 10;">
                        <div class="text-red-500 text-4xl drop-shadow-lg">📍</div>
                    </div>
                    
                    <!-- Center crosshair -->
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pointer-events-none">
                        <div class="w-6 h-6 border-2 border-gray-400 rounded-full bg-white bg-opacity-80"></div>
                    </div>
                    
                    <!-- Map info overlay -->
                    <div class="absolute top-4 left-4 bg-white bg-opacity-90 px-3 py-2 rounded-lg shadow-lg">
                        <div class="text-sm font-semibold text-gray-700">Zoom: ${zoomLevel}</div>
                        <div class="text-xs text-gray-600">Madiun Area</div>
                    </div>
                </div>
            `;
            
            // Update marker position if exists
            if (map{{ $getId() }}_marker) {
                updateMarkerPosition{{ $getId() }}();
            }
        }
        
        function updateMarkerPosition{{ $getId() }}() {
            if (!map{{ $getId() }}_marker) return;
            
            const marker = document.getElementById('{{ $getId() }}-marker');
            const circle = document.getElementById('{{ $getId() }}-geofence-circle');
            if (!marker || !circle) return;
            
            // Calculate pixel position for marker
            const mapContainer = document.getElementById('{{ $getId() }}-map');
            const rect = mapContainer.getBoundingClientRect();
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            // Calculate offset from center
            const scale = Math.pow(2, 15 - map{{ $getId() }}_zoom) * 0.00001;
            const deltaLat = map{{ $getId() }}_marker.lat - map{{ $getId() }}_center.lat;
            const deltaLng = map{{ $getId() }}_marker.lng - map{{ $getId() }}_center.lng;
            
            const offsetX = deltaLng / scale;
            const offsetY = -deltaLat / scale;
            
            const markerX = centerX + offsetX;
            const markerY = centerY + offsetY;
            
            // Position marker
            marker.style.left = markerX + 'px';
            marker.style.top = markerY + 'px';
            marker.style.display = 'block';
            
            // Calculate circle radius in pixels (approximate)
            const metersPerPixel = (40075016.686 * Math.cos(map{{ $getId() }}_marker.lat * Math.PI / 180)) / Math.pow(2, map{{ $getId() }}_zoom + 8);
            const radiusPixels = map{{ $getId() }}_radius / metersPerPixel;
            
            // Position and size geofence circle
            circle.style.left = (markerX - radiusPixels) + 'px';
            circle.style.top = (markerY - radiusPixels) + 'px';
            circle.style.width = (radiusPixels * 2) + 'px';
            circle.style.height = (radiusPixels * 2) + 'px';
            circle.style.display = 'block';
        }
        
        function getGPSLocation{{ $getId() }}() {
            const gpsBtn = document.getElementById('{{ $getId() }}-gps-btn');
            const gpsStatus = document.getElementById('{{ $getId() }}-gps-status');
            
            if (!navigator.geolocation) {
                gpsStatus.textContent = 'GPS tidak didukung';
                return;
            }
            
            gpsStatus.textContent = 'Mengambil GPS...';
            gpsBtn.disabled = true;
            gpsBtn.classList.add('opacity-60');
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    setLocationGPS{{ $getId() }}(lat, lng, accuracy);
                    gpsStatus.textContent = 'GPS Aktif';
                    gpsBtn.disabled = false;
                    gpsBtn.classList.remove('opacity-60');
                },
                function(error) {
                    console.error('GPS Error:', error);
                    gpsStatus.textContent = 'GPS Error';
                    gpsBtn.disabled = false;
                    gpsBtn.classList.remove('opacity-60');
                    
                    // Fallback to default location
                    setLocationManual{{ $getId() }}(-7.89946200, 111.96239900);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 300000
                }
            );
        }
        
        function setLocationGPS{{ $getId() }}(lat, lng, accuracy) {
            updateLocationData{{ $getId() }}(lat, lng, `GPS (±${Math.round(accuracy)}m)`);
            
            // Center map on GPS location
            map{{ $getId() }}_center = { lat, lng };
            updateMapDisplay{{ $getId() }}();
        }
        
        function setLocationManual{{ $getId() }}(lat, lng) {
            updateLocationData{{ $getId() }}(lat, lng, 'Manual Selection');
        }
        
        function updateLocationData{{ $getId() }}(lat, lng, source) {
            // Update displays
            const locationStatus = document.getElementById('{{ $getId() }}-location-status');
            const coordinates = document.getElementById('{{ $getId() }}-coordinates');
            
            if (locationStatus) {
                locationStatus.textContent = 'Terpilih';
                locationStatus.className = 'text-xs text-green-600 mt-1';
            }
            
            if (coordinates) {
                coordinates.innerHTML = `<strong>Lat:</strong> ${lat.toFixed(6)}<br><strong>Lng:</strong> ${lng.toFixed(6)}<br><small class="text-blue-600">${source}</small>`;
            }
            
            // Update marker
            map{{ $getId() }}_marker = { lat, lng };
            updateMarkerPosition{{ $getId() }}();
            
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
            
            console.log('Geofencing location updated:', { lat, lng, source });
        }
        
        function watchRadiusChanges{{ $getId() }}() {
            const radiusField = document.querySelector('input[name="radius_meters"]');
            if (radiusField) {
                radiusField.addEventListener('input', function() {
                    map{{ $getId() }}_radius = parseInt(this.value) || 100;
                    const radiusDisplay = document.getElementById('{{ $getId() }}-radius-display');
                    if (radiusDisplay) {
                        radiusDisplay.textContent = map{{ $getId() }}_radius + ' meter';
                    }
                    updateMarkerPosition{{ $getId() }}();
                });
            }
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(initGeofencingMap{{ $getId() }}, 100);
            });
        } else {
            setTimeout(initGeofencingMap{{ $getId() }}, 100);
        }
        
        // Initialize from existing data if available
        setTimeout(function() {
            const latField = document.querySelector('input[name="latitude"]');
            const lngField = document.querySelector('input[name="longitude"]');
            
            if (latField && lngField && latField.value && lngField.value) {
                const lat = parseFloat(latField.value);
                const lng = parseFloat(lngField.value);
                if (!isNaN(lat) && !isNaN(lng)) {
                    updateLocationData{{ $getId() }}(lat, lng, 'Existing Data');
                    map{{ $getId() }}_center = { lat, lng };
                    updateMapDisplay{{ $getId() }}();
                }
            }
        }, 200);
    </script>

    <style>
        .geofencing-map-container #{{ $getId() }}-map {
            cursor: crosshair;
            user-select: none;
        }
        
        .geofencing-map-container button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        #{{ $getId() }}-geofence-circle {
            transition: all 0.3s ease;
        }
        
        #{{ $getId() }}-marker {
            transition: all 0.2s ease;
            filter: drop-shadow(2px 2px 4px rgba(0,0,0,0.3));
        }
    </style>
</div>
