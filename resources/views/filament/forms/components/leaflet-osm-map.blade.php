{{-- Enhanced Creative Leaflet OSM Map Component - Single Root Element --}}
<div 
    class="creative-leaflet-osm-map-container" 
    x-data="leafletMapComponent()"
    x-init="initializeMap()"
    wire:ignore
>
    @php
        $statePath = $getStatePath();
        $defaultLat = -6.2088; // Jakarta latitude  
        $defaultLng = 106.8456; // Jakarta longitude
        $defaultZoom = 15;
        $mapHeight = 450;
        $uniqueMapId = 'leaflet-map-' . str_replace(['.', '[', ']'], '-', $statePath) . '-' . uniqid();
    @endphp

    <!-- Creative Glassmorphic Status Dashboard -->
    <div class="creative-status-dashboard mb-6 relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 border border-white/20 backdrop-blur-sm shadow-xl">
        <!-- Animated Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 animate-gradient-x"></div>
            <div class="absolute top-0 left-0 w-full h-full">
                <div class="floating-circles">
                    <div class="circle circle-1"></div>
                    <div class="circle circle-2"></div>
                    <div class="circle circle-3"></div>
                </div>
            </div>
        </div>
        
        <!-- Status Grid -->
        <div class="relative z-10 grid grid-cols-1 md:grid-cols-4 gap-4 p-6">
            <!-- GPS Status -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-green-400 to-emerald-500">
                        <span class="text-white text-lg">🌍</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">GPS Status</h3>
                        <p id="{{ $uniqueMapId }}-gps-status" class="text-xs text-gray-600 font-medium">Initializing...</p>
                    </div>
                </div>
                <div class="status-progress">
                    <div id="{{ $uniqueMapId }}-gps-progress" class="progress-bar bg-gradient-to-r from-green-400 to-emerald-500" style="width: 0%"></div>
                </div>
            </div>

            <!-- Location Coordinates -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-blue-400 to-cyan-500">
                        <span class="text-white text-lg">🎯</span>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-gray-800 text-sm">Coordinates</h3>
                        <p id="{{ $uniqueMapId }}-coordinates" class="text-xs text-gray-600 font-mono">{{ number_format($defaultLat, 4) }}, {{ number_format($defaultLng, 4) }}</p>
                    </div>
                </div>
                <button 
                    type="button"
                    onclick="copyCoordinates('{{ $uniqueMapId }}')"
                    class="copy-btn"
                    title="Copy coordinates"
                >
                    📋
                </button>
            </div>

            <!-- GPS Accuracy -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-purple-400 to-pink-500">
                        <span class="text-white text-lg">📡</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Accuracy</h3>
                        <p id="{{ $uniqueMapId }}-accuracy" class="text-xs text-gray-600 font-medium">Not detected</p>
                    </div>
                </div>
                <div class="status-indicator">
                    <div id="{{ $uniqueMapId }}-accuracy-dot" class="indicator-dot bg-gray-400"></div>
                </div>
            </div>

            <!-- Map Actions -->
            <div class="status-card group">
                <div class="flex items-center space-x-3 mb-2">
                    <div class="status-icon bg-gradient-to-r from-orange-400 to-red-500">
                        <span class="text-white text-lg">⚡</span>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 text-sm">Quick Actions</h3>
                        <p class="text-xs text-gray-600">Map controls</p>
                    </div>
                </div>
                <div class="flex space-x-1">
                    <button 
                        type="button"
                        onclick="getCurrentLocation('{{ $uniqueMapId }}')"
                        class="action-btn bg-gradient-to-r from-green-400 to-emerald-500"
                        title="Auto-detect location"
                    >
                        🌍
                    </button>
                    <button 
                        type="button"
                        onclick="resetMapView('{{ $uniqueMapId }}')"
                        class="action-btn bg-gradient-to-r from-blue-400 to-cyan-500"
                        title="Reset view"
                    >
                        🎯
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Creative Interactive Map Container -->
    <div class="creative-map-wrapper relative overflow-hidden rounded-2xl shadow-2xl bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200">
        <!-- Map Loading Overlay -->
        <div id="{{ $uniqueMapId }}-loader" class="map-loader">
            <div class="loader-content">
                <div class="loader-spinner"></div>
                <p class="text-sm text-gray-600 mt-3 font-medium">Loading interactive map...</p>
                <div class="loader-progress">
                    <div class="loader-progress-bar"></div>
                </div>
            </div>
        </div>

        <!-- Main Map Container -->
        <div 
            id="{{ $uniqueMapId }}" 
            class="creative-map-canvas"
            style="height: {{ $mapHeight }}px; width: 100%; position: relative; z-index: 1;"
        ></div>

        <!-- Creative Floating Controls -->
        <div class="creative-controls">
            <!-- Primary GPS Button -->
            <div class="control-group top-controls">
                <button 
                    type="button"
                    id="{{ $uniqueMapId }}-gps-main"
                    onclick="getCurrentLocation('{{ $uniqueMapId }}')"
                    class="primary-gps-btn"
                    title="Use My Location"
                >
                    <div class="btn-icon">
                        <span id="{{ $uniqueMapId }}-gps-icon">🌍</span>
                    </div>
                    <div class="btn-text">
                        <span id="{{ $uniqueMapId }}-gps-text">Use My Location</span>
                        <div class="btn-subtext">Auto-detect GPS</div>
                    </div>
                    <div class="btn-arrow">→</div>
                </button>
            </div>

            <!-- Secondary Controls -->
            <div class="control-group side-controls">
                <button 
                    type="button"
                    onclick="refreshLocation('{{ $uniqueMapId }}')"
                    class="secondary-btn refresh-btn"
                    title="Refresh location"
                >
                    <span>🔄</span>
                </button>
                
                <button 
                    type="button"
                    onclick="centerMap('{{ $uniqueMapId }}')"
                    class="secondary-btn center-btn"
                    title="Center map"
                >
                    <span>🎯</span>
                </button>
                
                <button 
                    type="button"
                    onclick="toggleMapStyle('{{ $uniqueMapId }}')"
                    class="secondary-btn style-btn"
                    title="Toggle map style"
                >
                    <span>🗺️</span>
                </button>
            </div>
        </div>

        <!-- Creative Coordinate Display -->
        <div class="creative-coord-display">
            <div class="coord-header">
                <span class="coord-icon">📍</span>
                <span class="coord-title">Selected Location</span>
                <div class="coord-indicator"></div>
            </div>
            <div class="coord-values">
                <div class="coord-item">
                    <span class="coord-label">Lat:</span>
                    <span id="{{ $uniqueMapId }}-lat-display" class="coord-value">{{ number_format($defaultLat, 6) }}</span>
                </div>
                <div class="coord-item">
                    <span class="coord-label">Lng:</span>
                    <span id="{{ $uniqueMapId }}-lng-display" class="coord-value">{{ number_format($defaultLng, 6) }}</span>
                </div>
            </div>
        </div>

        <!-- GPS Accuracy Circle Indicator -->
        <div id="{{ $uniqueMapId }}-accuracy-indicator" class="accuracy-indicator hidden">
            <div class="accuracy-content">
                <span class="accuracy-icon">📡</span>
                <span id="{{ $uniqueMapId }}-accuracy-text" class="accuracy-text">±0m</span>
            </div>
        </div>
    </div>

    <!-- Creative Interactive Guide -->
    <div class="creative-guide mt-6 rounded-2xl bg-gradient-to-r from-indigo-50 via-purple-50 to-pink-50 border border-indigo-100 shadow-lg overflow-hidden">
        <div class="guide-header">
            <div class="flex items-center space-x-3 p-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                <div class="guide-icon">
                    <span class="text-white text-xl">🚀</span>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Interactive Map Guide</h3>
                    <p class="text-indigo-100 text-sm">Master the map with these pro tips</p>
                </div>
            </div>
        </div>
        
        <div class="guide-content p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Desktop Guide -->
                <div class="guide-section">
                    <div class="section-header">
                        <span class="section-icon">🖥️</span>
                        <h4 class="section-title">Desktop Controls</h4>
                    </div>
                    <div class="guide-items">
                        <div class="guide-item">
                            <span class="item-icon">🌍</span>
                            <div class="item-content">
                                <span class="item-title">Auto GPS</span>
                                <span class="item-desc">Click "Use My Location" for instant detection</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">🖱️</span>
                            <div class="item-content">
                                <span class="item-title">Click & Place</span>
                                <span class="item-desc">Click anywhere on map to place marker</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">↕️</span>
                            <div class="item-content">
                                <span class="item-title">Drag & Drop</span>
                                <span class="item-desc">Drag the red marker for precise positioning</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mobile Guide -->
                <div class="guide-section">
                    <div class="section-header">
                        <span class="section-icon">📱</span>
                        <h4 class="section-title">Mobile Touch</h4>
                    </div>
                    <div class="guide-items">
                        <div class="guide-item">
                            <span class="item-icon">👆</span>
                            <div class="item-content">
                                <span class="item-title">Tap to Select</span>
                                <span class="item-desc">Tap map to choose location point</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">🤏</span>
                            <div class="item-content">
                                <span class="item-title">Pinch to Zoom</span>
                                <span class="item-desc">Use two fingers to zoom in/out</span>
                            </div>
                        </div>
                        <div class="guide-item">
                            <span class="item-icon">📍</span>
                            <div class="item-content">
                                <span class="item-title">Hold & Drag</span>
                                <span class="item-desc">Touch and hold marker to reposition</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pro Tips Banner -->
            <div class="pro-tips">
                <div class="tips-header">
                    <span class="tips-icon">💡</span>
                    <span class="tips-title">Pro Tips</span>
                </div>
                <div class="tips-content">
                    <p>For best GPS accuracy, enable location services and use outdoors. Coordinates sync automatically with form fields above!</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced JavaScript with Creative Features -->
    @push('scripts')
    <script>
        // Global Creative Map Management
        window.CreativeLeafletMaps = window.CreativeLeafletMaps || new Map();
        
        // Creative Alpine.js Component
        function leafletMapComponent() {
            return {
                mapId: '{{ $uniqueMapId }}',
                map: null,
                marker: null,
                currentStyle: 'osm',
                isLoading: true,
                gpsAccuracy: null,
                
                async initializeMap() {
                    try {
                        await this.loadLeafletResources();
                        await this.setupMap();
                        await this.setupEventListeners();
                        this.hideLoader();
                        this.updateGPSStatus('Map ready - Auto-detecting location...', 'ready');
                        
                        // Auto-detect location on page load if coordinates are not set
                        setTimeout(() => {
                            this.autoDetectOnLoad();
                        }, 1000);
                    } catch (error) {
                        console.error('Creative Map initialization failed:', error);
                        this.showError('Failed to load interactive map');
                    }
                },

                autoDetectOnLoad() {
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    // Check if coordinates are empty or default values
                    const currentLat = latField ? parseFloat(latField.value) : null;
                    const currentLng = lngField ? parseFloat(lngField.value) : null;
                    
                    const isDefaultLocation = (currentLat === -6.2088 || currentLat === -6.2088200) && 
                                            (currentLng === 106.8456 || currentLng === 106.8238800);
                    const isEmpty = !currentLat || !currentLng || isNaN(currentLat) || isNaN(currentLng);
                    
                    if (isEmpty || isDefaultLocation) {
                        this.updateGPSStatus('Auto-detecting your location...', 'searching');
                        this.getCurrentLocationSilent();
                    } else {
                        this.updateGPSStatus('Using existing coordinates', 'success');
                    }
                },

                getCurrentLocationSilent() {
                    if (!navigator.geolocation) {
                        this.updateGPSStatus('GPS not supported - Please enter manually', 'error');
                        return;
                    }
                    
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            const accuracy = position.coords.accuracy;
                            
                            this.map.setView([lat, lng], Math.max({{ $defaultZoom }}, 16));
                            this.marker.setLatLng([lat, lng]);
                            this.updateCoordinates(lat, lng);
                            this.updateAccuracy(accuracy);
                            this.animateMarker();
                            
                            this.updateGPSStatus(`Auto-detected location (±${Math.round(accuracy)}m)`, 'success');
                            
                            // Show success notification
                            if (window.Filament) {
                                window.Filament.notification()
                                    .title('🌍 Location Auto-Detected!')
                                    .body(`Your coordinates have been automatically filled with ±${Math.round(accuracy)}m accuracy`)
                                    .success()
                                    .send();
                            }
                        },
                        (error) => {
                            let errorMsg = 'Auto-detection failed: ';
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    errorMsg += 'Please enable location access';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    errorMsg += 'Location service unavailable';
                                    break;
                                case error.TIMEOUT:
                                    errorMsg += 'Request timeout';
                                    break;
                                default:
                                    errorMsg += 'Unknown error';
                            }
                            
                            this.updateGPSStatus(errorMsg, 'error');
                            
                            // Show error notification
                            if (window.Filament) {
                                window.Filament.notification()
                                    .title('⚠️ Auto-Detection Failed')
                                    .body('Please click "Get My Location" button or enter coordinates manually')
                                    .warning()
                                    .send();
                            }
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 600000
                        }
                    );
                },

                async loadLeafletResources() {
                    if (typeof L !== 'undefined') return;
                    
                    const cssLink = document.createElement('link');
                    cssLink.rel = 'stylesheet';
                    cssLink.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    cssLink.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
                    cssLink.crossOrigin = '';
                    document.head.appendChild(cssLink);

                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                        script.integrity = 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=';
                        script.crossOrigin = '';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                },

                async setupMap() {
                    // Initialize map with enhanced settings
                    this.map = L.map(this.mapId, {
                        center: [{{ $defaultLat }}, {{ $defaultLng }}],
                        zoom: {{ $defaultZoom }},
                        zoomControl: true,
                        attributionControl: true,
                        tap: true,
                        touchZoom: true,
                        dragging: true,
                        maxZoom: 19,
                        minZoom: 3,
                        fadeAnimation: true,
                        zoomAnimation: true,
                        markerZoomAnimation: true
                    });

                    // Add tile layers
                    this.addTileLayers();
                    
                    // Create custom marker
                    this.createCustomMarker();
                    
                    // Store reference
                    window.CreativeLeafletMaps.set(this.mapId, this);
                },

                addTileLayers() {
                    // OpenStreetMap (default)
                    this.osmLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap</a>',
                        maxZoom: 19,
                        detectRetina: true
                    });

                    // Satellite layer
                    this.satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                        attribution: '&copy; <a href="https://www.esri.com/">Esri</a>',
                        maxZoom: 19
                    });

                    // Add default layer
                    this.osmLayer.addTo(this.map);
                },

                createCustomMarker() {
                    // Creative pulsing marker
                    const customIcon = L.divIcon({
                        html: `
                            <div class="creative-marker">
                                <div class="marker-pulse"></div>
                                <div class="marker-core"></div>
                                <div class="marker-shadow"></div>
                            </div>
                        `,
                        className: 'creative-marker-container',
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    });

                    this.marker = L.marker([{{ $defaultLat }}, {{ $defaultLng }}], {
                        draggable: true,
                        icon: customIcon
                    }).addTo(this.map);
                },

                setupEventListeners() {
                    // Map click event
                    this.map.on('click', (e) => {
                        this.marker.setLatLng(e.latlng);
                        this.updateCoordinates(e.latlng.lat, e.latlng.lng);
                        this.animateMarker();
                    });

                    // Marker drag event
                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.updateCoordinates(pos.lat, pos.lng);
                        this.animateMarker();
                    });

                    // Form field listeners
                    this.setupFormSync();
                },

                setupFormSync() {
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    if (latField && lngField) {
                        let debounceTimer;
                        
                        const syncFromForm = () => {
                            clearTimeout(debounceTimer);
                            debounceTimer = setTimeout(() => {
                                const lat = parseFloat(latField.value);
                                const lng = parseFloat(lngField.value);
                                
                                if (this.isValidCoordinate(lat, lng)) {
                                    this.map.setView([lat, lng], this.map.getZoom());
                                    this.marker.setLatLng([lat, lng]);
                                    this.updateDisplays(lat, lng);
                                    this.animateMarker();
                                }
                            }, 300);
                        };
                        
                        latField.addEventListener('input', syncFromForm);
                        lngField.addEventListener('input', syncFromForm);
                        latField.addEventListener('blur', syncFromForm);
                        lngField.addEventListener('blur', syncFromForm);
                    }
                },

                updateCoordinates(lat, lng) {
                    // Update form fields
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    if (latField && lngField) {
                        latField.value = lat.toFixed(6);
                        lngField.value = lng.toFixed(6);
                        
                        // Trigger Livewire events
                        latField.dispatchEvent(new Event('input', { bubbles: true }));
                        lngField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    this.updateDisplays(lat, lng);
                },

                updateDisplays(lat, lng) {
                    // Update coordinate displays
                    const coordDisplay = document.getElementById(this.mapId + '-coordinates');
                    const latDisplay = document.getElementById(this.mapId + '-lat-display');
                    const lngDisplay = document.getElementById(this.mapId + '-lng-display');
                    
                    if (coordDisplay) coordDisplay.textContent = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                    if (latDisplay) latDisplay.textContent = lat.toFixed(6);
                    if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);
                },

                animateMarker() {
                    const markerElement = this.marker.getElement();
                    if (markerElement) {
                        markerElement.classList.add('marker-bounce');
                        setTimeout(() => {
                            markerElement.classList.remove('marker-bounce');
                        }, 600);
                    }
                },

                updateGPSStatus(message, type = 'info') {
                    const statusEl = document.getElementById(this.mapId + '-gps-status');
                    const progressEl = document.getElementById(this.mapId + '-gps-progress');
                    
                    if (statusEl) statusEl.textContent = message;
                    
                    if (progressEl) {
                        const widths = { ready: '100%', searching: '50%', error: '0%', success: '100%' };
                        progressEl.style.width = widths[type] || '25%';
                    }
                },

                updateAccuracy(accuracy) {
                    this.gpsAccuracy = accuracy;
                    const accuracyEl = document.getElementById(this.mapId + '-accuracy');
                    const accuracyText = document.getElementById(this.mapId + '-accuracy-text');
                    const accuracyDot = document.getElementById(this.mapId + '-accuracy-dot');
                    const accuracyIndicator = document.getElementById(this.mapId + '-accuracy-indicator');
                    
                    if (accuracy && accuracy > 0) {
                        const roundedAccuracy = Math.round(accuracy);
                        const color = roundedAccuracy <= 10 ? 'green' : roundedAccuracy <= 50 ? 'orange' : 'red';
                        
                        if (accuracyEl) accuracyEl.textContent = `±${roundedAccuracy}m`;
                        if (accuracyText) accuracyText.textContent = `±${roundedAccuracy}m`;
                        if (accuracyDot) {
                            accuracyDot.className = `indicator-dot bg-${color}-400`;
                        }
                        if (accuracyIndicator) {
                            accuracyIndicator.classList.remove('hidden');
                        }
                    }
                },

                hideLoader() {
                    const loader = document.getElementById(this.mapId + '-loader');
                    if (loader) {
                        loader.style.opacity = '0';
                        setTimeout(() => {
                            loader.style.display = 'none';
                        }, 300);
                    }
                },

                showError(message) {
                    const loader = document.getElementById(this.mapId + '-loader');
                    if (loader) {
                        loader.innerHTML = `
                            <div class="loader-content">
                                <div class="text-red-500 text-4xl mb-3">⚠️</div>
                                <p class="text-sm text-red-600 font-medium">${message}</p>
                            </div>
                        `;
                    }
                },

                isValidCoordinate(lat, lng) {
                    return !isNaN(lat) && !isNaN(lng) && 
                           lat >= -90 && lat <= 90 && 
                           lng >= -180 && lng <= 180;
                }
            };
        }

        // Global Functions for Button Actions
        function autoDetectLocation() {
            // Find the first map component (works for single map scenarios)
            const mapId = Array.from(window.CreativeLeafletMaps.keys())[0];
            if (mapId) {
                getCurrentLocation(mapId);
            } else {
                // Fallback: trigger geolocation without map
                triggerGeolocationForForm();
            }
        }

        function triggerGeolocationForForm() {
            if (!navigator.geolocation) {
                alert('GPS tidak didukung oleh browser Anda. Silakan masukkan koordinat secara manual.');
                return;
            }

            const btn = document.getElementById('get-location-btn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '🔄 Detecting...';
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    // Update form fields
                    const latField = document.querySelector('input[name="latitude"]');
                    const lngField = document.querySelector('input[name="longitude"]');
                    
                    if (latField && lngField) {
                        latField.value = lat.toFixed(6);
                        lngField.value = lng.toFixed(6);
                        
                        // Trigger Livewire events
                        latField.dispatchEvent(new Event('input', { bubbles: true }));
                        lngField.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    
                    // Show success notification
                    if (window.Filament) {
                        window.Filament.notification()
                            .title('🌍 Location Detected!')
                            .body(`Coordinates auto-filled with ±${Math.round(accuracy)}m accuracy`)
                            .success()
                            .send();
                    } else {
                        alert(`Location detected! Accuracy: ±${Math.round(accuracy)}m`);
                    }
                    
                    // Reset button
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '🌍 Get My Location';
                    }
                },
                (error) => {
                    let errorMsg = 'GPS Error: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Akses lokasi ditolak. Silakan izinkan akses lokasi.';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Lokasi tidak tersedia. Pastikan GPS aktif.';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Timeout. Silakan coba lagi.';
                            break;
                        default:
                            errorMsg += 'Error tidak diketahui.';
                    }
                    
                    // Show error notification
                    if (window.Filament) {
                        window.Filament.notification()
                            .title('❌ Location Detection Failed')
                            .body(errorMsg)
                            .danger()
                            .send();
                    } else {
                        alert(errorMsg);
                    }
                    
                    // Reset button
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '🌍 Get My Location';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 300000
                }
            );
        }

        function getCurrentLocation(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (!mapComponent) return;
            
            const btn = document.getElementById(mapId + '-gps-main');
            const icon = document.getElementById(mapId + '-gps-icon');
            const text = document.getElementById(mapId + '-gps-text');
            
            if (!navigator.geolocation) {
                mapComponent.updateGPSStatus('GPS not supported', 'error');
                return;
            }
            
            // Update button state
            if (btn) btn.classList.add('loading');
            if (icon) icon.textContent = '🔄';
            if (text) text.textContent = 'Detecting...';
            
            mapComponent.updateGPSStatus('Searching for GPS location...', 'searching');
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    mapComponent.map.setView([lat, lng], Math.max({{ $defaultZoom }}, 16));
                    mapComponent.marker.setLatLng([lat, lng]);
                    mapComponent.updateCoordinates(lat, lng);
                    mapComponent.updateAccuracy(accuracy);
                    mapComponent.animateMarker();
                    
                    mapComponent.updateGPSStatus(`Location found (±${Math.round(accuracy)}m)`, 'success');
                    
                    // Show success notification
                    if (window.Filament) {
                        window.Filament.notification()
                            .title('🌍 Location Updated!')
                            .body(`Map coordinates updated with ±${Math.round(accuracy)}m accuracy`)
                            .success()
                            .send();
                    }
                    
                    // Reset button
                    if (btn) btn.classList.remove('loading');
                    if (icon) icon.textContent = '🌍';
                    if (text) text.textContent = 'Use My Location';
                },
                (error) => {
                    let errorMsg = 'GPS Error: ';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += 'Location access denied';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += 'Location unavailable';
                            break;
                        case error.TIMEOUT:
                            errorMsg += 'Request timeout';
                            break;
                        default:
                            errorMsg += 'Unknown error';
                    }
                    
                    mapComponent.updateGPSStatus(errorMsg, 'error');
                    
                    // Show error notification
                    if (window.Filament) {
                        window.Filament.notification()
                            .title('❌ GPS Error')
                            .body(errorMsg)
                            .danger()
                            .send();
                    }
                    
                    // Reset button
                    if (btn) btn.classList.remove('loading');
                    if (icon) icon.textContent = '🌍';
                    if (text) text.textContent = 'Use My Location';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 15000,
                    maximumAge: 300000
                }
            );
        }
        
        function refreshLocation(mapId) {
            getCurrentLocation(mapId);
        }
        
        function centerMap(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (mapComponent) {
                const markerPos = mapComponent.marker.getLatLng();
                mapComponent.map.setView(markerPos, mapComponent.map.getZoom());
                mapComponent.animateMarker();
            }
        }
        
        function toggleMapStyle(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (!mapComponent) return;
            
            if (mapComponent.currentStyle === 'osm') {
                mapComponent.map.removeLayer(mapComponent.osmLayer);
                mapComponent.map.addLayer(mapComponent.satelliteLayer);
                mapComponent.currentStyle = 'satellite';
            } else {
                mapComponent.map.removeLayer(mapComponent.satelliteLayer);
                mapComponent.map.addLayer(mapComponent.osmLayer);
                mapComponent.currentStyle = 'osm';
            }
        }
        
        function copyCoordinates(mapId) {
            const latDisplay = document.getElementById(mapId + '-lat-display');
            const lngDisplay = document.getElementById(mapId + '-lng-display');
            
            if (latDisplay && lngDisplay) {
                const coords = `${latDisplay.textContent}, ${lngDisplay.textContent}`;
                navigator.clipboard.writeText(coords).then(() => {
                    // Show success feedback
                    const btn = document.querySelector('.copy-btn');
                    if (btn) {
                        btn.textContent = '✅';
                        setTimeout(() => {
                            btn.textContent = '📋';
                        }, 1500);
                    }
                });
            }
        }
        
        function resetMapView(mapId) {
            const mapComponent = window.CreativeLeafletMaps.get(mapId);
            if (mapComponent) {
                mapComponent.map.setView([{{ $defaultLat }}, {{ $defaultLng }}], {{ $defaultZoom }});
                mapComponent.marker.setLatLng([{{ $defaultLat }}, {{ $defaultLng }}]);
                mapComponent.updateCoordinates({{ $defaultLat }}, {{ $defaultLng }});
                mapComponent.animateMarker();
            }
        }
    </script>
    @endpush

    <!-- Creative Styling -->
    @push('styles')
    <style>
        /* Animated gradient background */
        @keyframes gradient-x {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
        
        .animate-gradient-x {
            background-size: 400% 400%;
            animation: gradient-x 15s ease infinite;
        }
        
        /* Floating circles animation */
        .floating-circles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        
        .circle-1 {
            width: 80px;
            height: 80px;
            background: linear-gradient(45deg, #3b82f6, #8b5cf6);
            top: 20%;
            left: 20%;
            animation-delay: 0s;
        }
        
        .circle-2 {
            width: 60px;
            height: 60px;
            background: linear-gradient(45deg, #10b981, #06b6d4);
            top: 60%;
            right: 30%;
            animation-delay: 2s;
        }
        
        .circle-3 {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #f59e0b, #ef4444);
            bottom: 30%;
            left: 60%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Status cards */
        .status-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            padding: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .status-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .status-progress {
            height: 4px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 2px;
            overflow: hidden;
            margin-top: 8px;
        }
        
        .progress-bar {
            height: 100%;
            transition: width 0.5s ease;
            border-radius: 2px;
        }
        
        .status-indicator {
            position: absolute;
            top: 12px;
            right: 12px;
        }
        
        .indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Action buttons */
        .action-btn, .copy-btn {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: white;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn:hover, .copy-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Map container */
        .creative-map-wrapper {
            position: relative;
        }
        
        .creative-map-canvas {
            border-radius: 16px;
            overflow: hidden;
        }
        
        /* Map loader */
        .map-loader {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: 16px;
            transition: opacity 0.3s ease;
        }
        
        .loader-content {
            text-align: center;
            color: white;
        }
        
        .loader-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        .loader-progress {
            width: 200px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            overflow: hidden;
            margin: 16px auto 0;
        }
        
        .loader-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0.8));
            border-radius: 2px;
            animation: loading-progress 2s ease-in-out infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes loading-progress {
            0% { width: 0%; }
            50% { width: 70%; }
            100% { width: 100%; }
        }
        
        /* Creative controls */
        .creative-controls {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 500;
        }
        
        .control-group {
            position: absolute;
            pointer-events: auto;
        }
        
        .top-controls {
            top: 20px;
            right: 20px;
        }
        
        .side-controls {
            top: 20px;
            left: 20px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .primary-gps-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 16px;
            padding: 16px 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 240px;
            overflow: hidden;
            position: relative;
        }
        
        .primary-gps-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
        }
        
        .primary-gps-btn.loading {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
            cursor: not-allowed;
        }
        
        .btn-icon {
            font-size: 20px;
            min-width: 24px;
        }
        
        .btn-text {
            flex: 1;
            text-align: left;
        }
        
        .btn-text span {
            display: block;
            font-weight: 600;
            font-size: 14px;
        }
        
        .btn-subtext {
            font-size: 11px;
            opacity: 0.8;
            margin-top: 2px;
        }
        
        .btn-arrow {
            font-size: 16px;
            opacity: 0.7;
            transition: transform 0.2s ease;
        }
        
        .primary-gps-btn:hover .btn-arrow {
            transform: translateX(2px);
        }
        
        .secondary-btn {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .secondary-btn:hover {
            transform: scale(1.05);
            background: white;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        /* Coordinate display */
        .creative-coord-display {
            position: absolute;
            bottom: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 12px 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            pointer-events: auto;
        }
        
        .coord-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .coord-icon {
            font-size: 14px;
        }
        
        .coord-title {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }
        
        .coord-indicator {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .coord-values {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .coord-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 11px;
        }
        
        .coord-label {
            color: #6b7280;
            font-weight: 500;
            min-width: 28px;
        }
        
        .coord-value {
            font-family: monospace;
            font-weight: 600;
            color: #1f2937;
        }
        
        /* Accuracy indicator */
        .accuracy-indicator {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 6px 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            pointer-events: auto;
        }
        
        .accuracy-content {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .accuracy-icon {
            font-size: 12px;
        }
        
        .accuracy-text {
            font-size: 11px;
            font-weight: 600;
            font-family: monospace;
        }
        
        /* Custom marker styling */
        .creative-marker-container {
            background: transparent !important;
            border: none !important;
        }
        
        .creative-marker {
            position: relative;
            width: 30px;
            height: 30px;
        }
        
        .marker-pulse {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 30px;
            height: 30px;
            background: rgba(239, 68, 68, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: marker-pulse 2s infinite;
        }
        
        .marker-core {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 16px;
            height: 16px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: 3px solid white;
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            z-index: 2;
        }
        
        .marker-shadow {
            position: absolute;
            bottom: -5px;
            left: 50%;
            width: 20px;
            height: 6px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            transform: translateX(-50%);
            filter: blur(2px);
        }
        
        @keyframes marker-pulse {
            0% {
                transform: translate(-50%, -50%) scale(0.8);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(2);
                opacity: 0;
            }
        }
        
        .marker-bounce {
            animation: marker-bounce 0.6s ease-out;
        }
        
        @keyframes marker-bounce {
            0%, 20%, 53%, 80%, 100% {
                animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
                transform: translate3d(0, -10px, 0);
            }
            70% {
                animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
                transform: translate3d(0, -5px, 0);
            }
            90% {
                transform: translate3d(0, -2px, 0);
            }
        }
        
        /* Guide section */
        .creative-guide {
            position: relative;
            overflow: hidden;
        }
        
        .guide-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .guide-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .guide-section {
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .section-icon {
            font-size: 20px;
        }
        
        .section-title {
            font-weight: 700;
            color: #1f2937;
            font-size: 16px;
        }
        
        .guide-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .guide-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 12px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .guide-item:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateX(4px);
        }
        
        .item-icon {
            font-size: 16px;
            min-width: 20px;
        }
        
        .item-content {
            flex: 1;
        }
        
        .item-title {
            font-weight: 600;
            color: #1f2937;
            font-size: 13px;
            display: block;
        }
        
        .item-desc {
            color: #6b7280;
            font-size: 12px;
            margin-top: 2px;
        }
        
        .pro-tips {
            margin-top: 20px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 12px;
            padding: 16px;
            color: white;
        }
        
        .tips-header {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .tips-icon {
            font-size: 18px;
        }
        
        .tips-title {
            font-weight: 700;
            font-size: 14px;
        }
        
        .tips-content {
            font-size: 13px;
            opacity: 0.95;
            line-height: 1.4;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .top-controls {
                top: 12px;
                right: 12px;
            }
            
            .side-controls {
                top: 12px;
                left: 12px;
                gap: 6px;
            }
            
            .creative-coord-display {
                bottom: 12px;
                left: 12px;
                right: 12px;
                padding: 10px 12px;
            }
            
            .primary-gps-btn {
                padding: 12px 16px;
                max-width: 200px;
            }
            
            .btn-text span {
                font-size: 13px;
            }
            
            .btn-subtext {
                font-size: 10px;
            }
            
            .secondary-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            
            .creative-map-canvas {
                min-height: 350px !important;
            }
        }
        
        @media (max-width: 640px) {
            .coord-values {
                flex-direction: row;
                gap: 12px;
            }
            
            .accuracy-indicator {
                position: relative;
                top: auto;
                left: auto;
                transform: none;
                margin: 8px 0;
                display: inline-block;
            }
        }
        
        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            .animate-gradient-x,
            .floating-circles .circle,
            .progress-bar,
            .indicator-dot,
            .marker-pulse,
            .loader-spinner,
            .loader-progress-bar {
                animation: none !important;
            }
            
            .creative-marker,
            .status-card,
            .action-btn,
            .copy-btn,
            .primary-gps-btn,
            .secondary-btn,
            .guide-item {
                transition: none !important;
            }
        }
        
        /* High contrast support */
        @media (prefers-contrast: high) {
            .status-card {
                background: white;
                border: 2px solid #000;
            }
            
            .creative-coord-display,
            .accuracy-indicator {
                background: white;
                border: 1px solid #000;
            }
            
            .marker-core {
                background: #ff0000;
                border: 3px solid #000;
            }
        }
    </style>
    @endpush
</div>