<div class="space-y-4">
    <!-- Location Status Display -->
    <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-blue-900">📍 Status Lokasi</span>
            <span id="{{ $getId() }}-status" class="text-xs text-blue-600">Belum dipilih</span>
        </div>
        <div id="{{ $getId() }}-coordinates" class="mt-2 text-xs text-blue-700"></div>
    </div>
    
    <!-- Map Container -->
    <div class="relative border border-gray-300 rounded-lg overflow-hidden" style="height: 400px;">
        <div id="{{ $getId() }}-map" style="width: 100%; height: 100%;"></div>
        
        <!-- GPS Button -->
        <button 
            type="button" 
            id="{{ $getId() }}-gps-btn"
            class="absolute top-2 right-2 bg-blue-500 hover:bg-blue-600 text-white p-2 rounded shadow-lg transition-colors z-[1000]"
            onclick="getGPSLocation{{ $getId() }}()"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </button>
    </div>
    
    <!-- Instructions -->
    <div class="text-xs text-gray-600 bg-gray-50 p-3 rounded">
        <p><strong>Cara menggunakan:</strong></p>
        <p>• Klik tombol GPS (📍) untuk deteksi otomatis</p>
        <p>• Atau klik langsung pada peta untuk pilih lokasi</p>
    </div>

    <!-- JavaScript moved inside main container -->
    <script>
    function initMap{{ $getId() }}() {
        // Simple map implementation without external dependencies
        const mapContainer = document.getElementById('{{ $getId() }}-map');
        const statusEl = document.getElementById('{{ $getId() }}-status');
        const coordEl = document.getElementById('{{ $getId() }}-coordinates');
        
        // Create simple tile-based map
        mapContainer.innerHTML = `
            <div style="background: linear-gradient(45deg, #e8f5e8, #f0f8ff); width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; position: relative;">
                <div style="text-align: center; z-index: 10;">
                    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 300px;">
                        <h3 style="margin: 0 0 10px 0; color: #2563eb; font-size: 14px; font-weight: 600;">🗺️ Peta Lokasi</h3>
                        <p style="margin: 0 0 15px 0; color: #6b7280; font-size: 12px;">Gunakan tombol GPS untuk deteksi lokasi otomatis</p>
                        <div id="{{ $getId() }}-selected-location" style="margin-top: 10px; padding: 8px; background: #f3f4f6; border-radius: 4px; font-size: 11px; color: #4b5563;">
                            Belum ada lokasi terpilih
                        </div>
                    </div>
                </div>
                
                <!-- Grid pattern to simulate map tiles -->
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; opacity: 0.1; pointer-events: none;">
                    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                        <pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse">
                            <path d="M 50 0 L 0 0 0 50" fill="none" stroke="#2563eb" stroke-width="1"/>
                        </pattern>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                    </svg>
                </div>
                
                <!-- Location marker -->
                <div id="{{ $getId() }}-marker" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #dc2626; font-size: 24px; display: none;">
                    📍
                </div>
            </div>
        `;
        
        // Add click handler for map
        mapContainer.addEventListener('click', function(e) {
            const rect = mapContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Convert pixel coordinates to lat/lng (approximate)
            const lat = -7.89946200 + (0.01 * ((y - 200) / 200));
            const lng = 111.96239900 + (0.01 * ((x - 200) / 200));
            
            updateLocation{{ $getId() }}(lat, lng, 'Manual selection');
        });
    }
    
    function getGPSLocation{{ $getId() }}() {
        const statusEl = document.getElementById('{{ $getId() }}-status');
        const gpsBtn = document.getElementById('{{ $getId() }}-gps-btn');
        
        if (!navigator.geolocation) {
            statusEl.textContent = 'GPS tidak didukung';
            return;
        }
        
        statusEl.textContent = 'Mengambil lokasi GPS...';
        gpsBtn.disabled = true;
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                updateLocation{{ $getId() }}(lat, lng, 'GPS Detection', accuracy);
                gpsBtn.disabled = false;
            },
            function(error) {
                console.error('GPS Error:', error);
                statusEl.textContent = 'Gagal mengambil lokasi GPS';
                gpsBtn.disabled = false;
                
                // Fallback to Madiun coordinates
                updateLocation{{ $getId() }}(-7.89946200, 111.96239900, 'Default Location (Madiun)');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }
        );
    }
    
    function updateLocation{{ $getId() }}(lat, lng, source, accuracy = null) {
        const statusEl = document.getElementById('{{ $getId() }}-status');
        const coordEl = document.getElementById('{{ $getId() }}-coordinates');
        const locationEl = document.getElementById('{{ $getId() }}-selected-location');
        const markerEl = document.getElementById('{{ $getId() }}-marker');
        
        // Update displays
        statusEl.textContent = 'Lokasi terpilih';
        coordEl.innerHTML = `<strong>Lat:</strong> ${lat.toFixed(6)} | <strong>Lng:</strong> ${lng.toFixed(6)}`;
        
        const locationText = `📍 ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        const accuracyText = accuracy ? ` (±${Math.round(accuracy)}m)` : '';
        locationEl.innerHTML = `${locationText}<br><small style="color: #10b981;">${source}${accuracyText}</small>`;
        
        // Show marker
        if (markerEl) {
            markerEl.style.display = 'block';
        }
        
        // Update form fields
        const latField = document.querySelector('input[name="latitude"]');
        const lngField = document.querySelector('input[name="longitude"]');
        
        if (latField) latField.value = lat;
        if (lngField) lngField.value = lng;
        
        // Trigger change events
        if (latField) latField.dispatchEvent(new Event('change', { bubbles: true }));
        if (lngField) lngField.dispatchEvent(new Event('change', { bubbles: true }));
        
        console.log('Location updated:', { lat, lng, source, accuracy });
    }
    
    // Initialize map when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMap{{ $getId() }});
    } else {
        initMap{{ $getId() }}();
    }
    </script>

    <!-- CSS moved inside main container -->
    <style>
    #{{ $getId() }}-map {
        cursor: crosshair;
        background: linear-gradient(45deg, #e8f5e8, #f0f8ff);
    }
    
    #{{ $getId() }}-gps-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    </style>
</div>