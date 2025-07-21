@php
    $statePath = $getStatePath();
    $lat = -7.89946200; // Default Madiun latitude
    $lng = 111.96239900; // Default Madiun longitude
    $zoom = 15;
    $height = 500;
    $mapId = 'map-' . str_replace(['.', '[', ']'], '-', $statePath);
@endphp

<div class="leaflet-osm-map-wrapper space-y-2">
    <!-- Map Container -->
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
                
                // DEEP DEBUGGING - Update coordinates function
                function updateCoords(lat, lng) {
                    console.log('🎯 ==> STARTING updateCoords:', lat, lng);
                    
                    const coordsEl = document.getElementById('{{ $mapId }}-coords');
                    if (coordsEl) {
                        coordsEl.textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                        console.log('✅ Coordinates display updated');
                    }
                    
                    // DEEP FIELD DEBUGGING
                    console.log('🔍 ==> SEARCHING FOR FIELDS...');
                    
                    // Multiple selector attempts for Filament fields
                    const selectors = [
                        'input[name="latitude"]',
                        'input[wire\\:model*="latitude"]',
                        'input[id*="latitude"]',
                        'input[class*="latitude"]',
                        '[data-field="latitude"] input',
                        '.fi-input input[name="latitude"]',
                        'input[type="number"][name="latitude"]'
                    ];
                    
                    let latField = null;
                    let lngField = null;
                    
                    // Try all selectors for latitude
                    for (const selector of selectors) {
                        latField = document.querySelector(selector);
                        if (latField) {
                            console.log('✅ FOUND latitude field with selector:', selector);
                            console.log('   - Field type:', latField.type);
                            console.log('   - Field name:', latField.name);
                            console.log('   - Field id:', latField.id);
                            console.log('   - Current value:', latField.value);
                            break;
                        }
                    }
                    
                    // Try all selectors for longitude
                    const lngSelectors = selectors.map(s => s.replace('latitude', 'longitude'));
                    for (const selector of lngSelectors) {
                        lngField = document.querySelector(selector);
                        if (lngField) {
                            console.log('✅ FOUND longitude field with selector:', selector);
                            console.log('   - Field type:', lngField.type);
                            console.log('   - Field name:', lngField.name);
                            console.log('   - Field id:', lngField.id);
                            console.log('   - Current value:', lngField.value);
                            break;
                        }
                    }
                    
                    // If not found, try broader search
                    if (!latField) {
                        console.log('⚠️ Latitude field not found, trying broader search...');
                        const allInputs = document.querySelectorAll('input[type="number"]');
                        console.log('🔍 Found', allInputs.length, 'number inputs total');
                        allInputs.forEach((input, index) => {
                            console.log(`   Input ${index}:`, input.name, input.id, input.placeholder);
                            if (input.name === 'latitude' || input.id.includes('latitude') || input.placeholder?.includes('lat')) {
                                latField = input;
                                console.log('🎯 FOUND latitude field via broad search!');
                            }
                            if (input.name === 'longitude' || input.id.includes('longitude') || input.placeholder?.includes('lng')) {
                                lngField = input;
                                console.log('🎯 FOUND longitude field via broad search!');
                            }
                        });
                    }
                    
                    // FORCE UPDATE WITH ALL METHODS
                    setTimeout(function() {
                        console.log('🔥 ==> STARTING FORCE UPDATE');
                        
                        if (latField) {
                            console.log('💪 UPDATING LATITUDE FIELD...');
                            
                            // Method 1: Direct value assignment
                            const latValue = lat.toFixed(6);
                            latField.value = latValue;
                            latField.setAttribute('value', latValue);
                            
                            // Method 2: Multiple events
                            const events = ['input', 'change', 'blur', 'keyup', 'focusin', 'focusout'];
                            events.forEach(eventType => {
                                latField.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
                            });
                            
                            // Method 3: Focus/blur trick
                            latField.focus();
                            setTimeout(() => latField.blur(), 10);
                            
                            // Method 4: Property setter
                            Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set?.call(latField, latValue);
                            
                            // Method 5: React/Vue compatibility
                            latField._valueTracker?.setValue?.(latValue);
                            
                            console.log('🎯 Latitude UPDATED - Final value:', latField.value);
                        } else {
                            console.error('❌ LATITUDE FIELD NOT FOUND!');
                        }
                        
                        if (lngField) {
                            console.log('💪 UPDATING LONGITUDE FIELD...');
                            
                            // Same methods for longitude
                            const lngValue = lng.toFixed(6);
                            lngField.value = lngValue;
                            lngField.setAttribute('value', lngValue);
                            
                            const events = ['input', 'change', 'blur', 'keyup', 'focusin', 'focusout'];
                            events.forEach(eventType => {
                                lngField.dispatchEvent(new Event(eventType, { bubbles: true, cancelable: true }));
                            });
                            
                            lngField.focus();
                            setTimeout(() => lngField.blur(), 10);
                            
                            Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value')?.set?.call(lngField, lngValue);
                            lngField._valueTracker?.setValue?.(lngValue);
                            
                            console.log('🎯 Longitude UPDATED - Final value:', lngField.value);
                        } else {
                            console.error('❌ LONGITUDE FIELD NOT FOUND!');
                        }
                        
                        console.log('✅ ==> FORCE UPDATE COMPLETED');
                        
                        // Final verification
                        setTimeout(function() {
                            const finalLatField = document.querySelector('input[name="latitude"]');
                            const finalLngField = document.querySelector('input[name="longitude"]');
                            console.log('🔍 FINAL VERIFICATION:');
                            console.log('   Latitude field value:', finalLatField?.value);
                            console.log('   Longitude field value:', finalLngField?.value);
                        }, 100);
                        
                    }, 200);
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
                
                // DEEP DEBUGGING - Auto-detect GPS atau initialize from form
                setTimeout(function() {
                    console.log('🚀 ==> INITIALIZATION STARTED');
                    console.log('📍 Checking for existing form data...');
                    
                    // Deep field discovery at initialization
                    const allInputs = document.querySelectorAll('input');
                    console.log('🔍 Found', allInputs.length, 'total inputs on page');
                    
                    let latField = null;
                    let lngField = null;
                    
                    allInputs.forEach((input, index) => {
                        const info = {
                            index: index,
                            type: input.type,
                            name: input.name,
                            id: input.id,
                            value: input.value,
                            placeholder: input.placeholder,
                            className: input.className
                        };
                        
                        if (input.name === 'latitude' || input.id.includes('latitude')) {
                            latField = input;
                            console.log('🎯 FOUND LATITUDE FIELD at index', index, info);
                        }
                        if (input.name === 'longitude' || input.id.includes('longitude')) {
                            lngField = input;
                            console.log('🎯 FOUND LONGITUDE FIELD at index', index, info);
                        }
                        
                        // Log all numeric inputs for debugging
                        if (input.type === 'number') {
                            console.log(`   📊 Number input ${index}:`, info);
                        }
                    });
                    
                    // Check if form already has data
                    if (latField && lngField && latField.value && lngField.value) {
                        console.log('✅ Existing data found:', latField.value, lngField.value);
                        const existingLat = parseFloat(latField.value);
                        const existingLng = parseFloat(lngField.value);
                        if (!isNaN(existingLat) && !isNaN(existingLng)) {
                            map.setView([existingLat, existingLng], {{ $zoom }});
                            marker.setLatLng([existingLat, existingLng]);
                            updateCoords(existingLat, existingLng);
                            console.log('🔄 Using existing coordinates, skipping auto-detect');
                            return; // Don't auto-detect if data already exists
                        }
                    }
                    
                    console.log('🆕 No existing data, starting auto GPS detection...');
                    
                    // Auto-detect GPS untuk form baru
                    if (navigator.geolocation) {
                        console.log('🌍 GPS available, starting auto-detection...');
                        const button = document.getElementById('{{ $mapId }}-gps-btn');
                        if (button) {
                            button.textContent = '🔄 Auto-detecting...';
                            button.disabled = true;
                            console.log('🔘 GPS button updated to detecting state');
                        }
                        
                        navigator.geolocation.getCurrentPosition(
                            function(position) {
                                const lat = position.coords.latitude;
                                const lng = position.coords.longitude;
                                console.log('✅ GPS detected:', lat, lng);
                                
                                // Update map
                                map.setView([lat, lng], {{ $zoom }});
                                marker.setLatLng([lat, lng]);
                                
                                // Force immediate field update
                                const latField = document.querySelector('input[name="latitude"]');
                                const lngField = document.querySelector('input[name="longitude"]');
                                
                                if (latField && lngField) {
                                    // Clear any existing values first
                                    latField.value = '';
                                    lngField.value = '';
                                    
                                    // Set new values
                                    latField.value = lat.toFixed(6);
                                    lngField.value = lng.toFixed(6);
                                    
                                    // Force multiple update methods
                                    setTimeout(function() {
                                        latField.setAttribute('value', lat.toFixed(6));
                                        lngField.setAttribute('value', lng.toFixed(6));
                                        
                                        // Trigger all possible events
                                        [latField, lngField].forEach(field => {
                                            field.dispatchEvent(new Event('input', { bubbles: true }));
                                            field.dispatchEvent(new Event('change', { bubbles: true }));
                                            field.dispatchEvent(new Event('blur', { bubbles: true }));
                                            field.dispatchEvent(new KeyboardEvent('keyup', { bubbles: true }));
                                        });
                                        
                                        console.log('🔥 FORCE UPDATED - Lat:', latField.value, 'Lng:', lngField.value);
                                    }, 50);
                                }
                                
                                // Update coordinates display
                                updateCoords(lat, lng);
                                
                                // Update button
                                if (button) {
                                    button.textContent = '📍 GPS Auto-Detected';
                                    button.disabled = false;
                                    button.classList.add('bg-green-500', 'hover:bg-green-600');
                                    button.classList.remove('bg-white');
                                }
                                
                                console.log('🎯 Auto GPS detection completed successfully');
                            },
                            function(error) {
                                console.warn('⚠️ Auto GPS detection failed:', error.message);
                                
                                // Fallback to default location (Madiun)
                                console.log('🏠 Using default location: Madiun');
                                updateCoords({{ $lat }}, {{ $lng }});
                                
                                if (button) {
                                    button.textContent = '📍 Deteksi GPS';
                                    button.disabled = false;
                                }
                            },
                            {
                                enableHighAccuracy: true,
                                timeout: 8000, // 8 seconds timeout for auto-detect
                                maximumAge: 300000 // 5 minutes cache
                            }
                        );
                    } else {
                        console.warn('❌ Geolocation not supported, using default location');
                        updateCoords({{ $lat }}, {{ $lng }});
                    }
                }, 800); // Delay sedikit lebih lama untuk auto-detect
                
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
                        
                        // Force update form fields
                        const latField = document.querySelector('input[name="latitude"]');
                        const lngField = document.querySelector('input[name="longitude"]');
                        
                        if (latField) {
                            latField.value = lat.toFixed(6);
                            latField.setAttribute('value', lat.toFixed(6));
                            latField.dispatchEvent(new Event('input', { bubbles: true }));
                            latField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (lngField) {
                            lngField.value = lng.toFixed(6);
                            lngField.setAttribute('value', lng.toFixed(6));
                            lngField.dispatchEvent(new Event('input', { bubbles: true }));
                            lngField.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        
                        console.log('🔄 GPS Button - Updated fields:', latField?.value, lngField?.value);
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