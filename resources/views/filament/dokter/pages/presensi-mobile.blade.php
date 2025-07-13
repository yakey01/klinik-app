<x-filament-panels::page>
    @push('styles')
    <link rel="stylesheet" href="{{ asset('build/assets/css/dokter-mobile-D_kgz9Xy.css') }}">
    @endpush
    
    <div class="dokter-presensi-mobile">
        <!-- Simple Location Display Container -->
        <div class="map-container">
            <div id="location-display" class="location-display-card">
                <div class="location-header">
                    <div class="clinic-info">
                        <div class="clinic-icon">🏥</div>
                        <div class="clinic-details">
                            <div class="clinic-name">Klinik Dokterku</div>
                            <div class="clinic-coords">📍 -6.1754, 106.8272</div>
                            <div class="clinic-radius">📏 Radius: 100 meter</div>
                        </div>
                    </div>
                </div>
                
                <div class="location-separator"></div>
                
                <div id="user-location-info" class="user-location-info">
                    <div class="user-icon">👤</div>
                    <div class="user-details">
                        <div class="user-label">Lokasi Anda:</div>
                        <div id="user-coords-text" class="user-coords">Mengambil lokasi...</div>
                        <div id="distance-info" class="distance-info">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Location Status -->
        <div class="location-status-container">
            <div id="location-status" class="location-status loading">
                <div class="status-icon">📍</div>
                <div class="status-text">Mengambil lokasi...</div>
            </div>
            <div id="coordinates-display" class="coordinates-display">
                <span id="user-coordinates">Koordinat: -</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button 
                id="checkin-button"
                wire:click="checkin"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="check-button check-in-button"
                disabled
                style="display: {{ $canCheckin ? 'flex' : 'none' }}"
            >
                <span wire:loading.remove wire:target="checkin">Check In</span>
                <span wire:loading wire:target="checkin">Processing...</span>
            </button>
            
            <button 
                id="checkout-button"
                wire:click="checkout"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50"
                class="check-button check-out-button"
                disabled
                style="display: {{ $canCheckout ? 'flex' : 'none' }}"
            >
                <span wire:loading.remove wire:target="checkout">Check Out</span>
                <span wire:loading wire:target="checkout">Processing...</span>
            </button>

            @if($todayAttendance && $todayAttendance->jam_pulang)
                <div class="completed-message">
                    <div class="completed-title">✅ Presensi Selesai</div>
                    <div class="completed-subtitle">
                        Masuk: {{ Carbon\Carbon::parse($todayAttendance->jam_masuk)->format('H:i') }} - 
                        Pulang: {{ Carbon\Carbon::parse($todayAttendance->jam_pulang)->format('H:i') }}
                    </div>
                </div>
            @endif

            <div id="location-error" class="location-error" style="display: none;">
                <div class="error-title">⚠️ Akses Lokasi Diperlukan</div>
                <div class="error-subtitle">Izinkan akses lokasi untuk melakukan presensi</div>
                
                <!-- Manual Location Input for Testing -->
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #d97706;">
                    <div style="font-size: 0.75rem; margin-bottom: 0.5rem; font-weight: 600;">🔧 Mode Debug:</div>
                    <div style="display: flex; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <input type="number" id="manual-lat" placeholder="Latitude" 
                               style="flex: 1; padding: 0.375rem; font-size: 0.75rem; border: 1px solid #d97706; border-radius: 0.25rem;"
                               step="any" value="-6.175400">
                        <input type="number" id="manual-lng" placeholder="Longitude" 
                               style="flex: 1; padding: 0.375rem; font-size: 0.75rem; border: 1px solid #d97706; border-radius: 0.25rem;"
                               step="any" value="106.827200">
                    </div>
                    <div style="display: flex; gap: 0.25rem; margin-bottom: 0.5rem;">
                        <button onclick="useManualLocation()" 
                                style="flex: 1; padding: 0.5rem; font-size: 0.7rem; background: #d97706; color: white; border: none; border-radius: 0.25rem;">
                            📍 Test Lokasi
                        </button>
                        <button onclick="getCurrentLocationDebug()" 
                                style="flex: 1; padding: 0.5rem; font-size: 0.7rem; background: #059669; color: white; border: none; border-radius: 0.25rem;">
                            🔄 Coba GPS
                        </button>
                    </div>
                    <div id="debug-info" style="font-size: 0.65rem; color: #7c2d12; background: #fef3c7; padding: 0.5rem; border-radius: 0.25rem; margin-top: 0.5rem;"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // 🚀 Lightweight Geolocation System 2024
        // Global variables
        let currentUserLocation = null;
        let watchId = null;
        let isHTTPS = window.location.protocol === 'https:';
        
        // Admin geofencing coordinates (Jakarta)
        const adminCoordinates = {lat: -6.1754, lng: 106.8272};
        const geofenceRadius = 100; // 100 meter radius

        document.addEventListener('DOMContentLoaded', function() {
            checkHTTPSRequirement();
            initializeLocationDisplay();
            requestLocationPermission();
        });

        // ✅ Check HTTPS requirement (mandatory in 2024)
        function checkHTTPSRequirement() {
            if (!isHTTPS && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                updateLocationStatus('error', '🔒 HTTPS diperlukan untuk akses lokasi');
                updateDebugInfo('❌ HTTPS diperlukan: Geolocation API memerlukan koneksi aman');
                showLocationError();
                return false;
            }
            updateDebugInfo('✅ HTTPS: Koneksi aman terdeteksi');
            return true;
        }
        
        // 📍 Initialize simple location display
        function initializeLocationDisplay() {
            updateDebugInfo('📍 Location display initialized');
            // Simple display is already rendered in HTML, no external dependencies needed
        }

        // 📱 Request location permission using 2024 best practices
        async function requestLocationPermission() {
            if (!navigator.geolocation) {
                updateLocationStatus('error', 'Geolocation tidak didukung browser');
                updateDebugInfo('❌ Navigator.geolocation tidak tersedia');
                showLocationError();
                return;
            }

            updateDebugInfo('🔍 Checking geolocation support...');
            updateLocationStatus('loading', 'Memeriksa dukungan lokasi...');

            // Check permission status first (2024 best practice)
            if (navigator.permissions) {
                try {
                    const permission = await navigator.permissions.query({name: 'geolocation'});
                    updateDebugInfo(`🔐 Permission state: ${permission.state}`);
                    
                    switch(permission.state) {
                        case 'granted':
                            updateDebugInfo('✅ Permission already granted');
                            startLocationTracking();
                            break;
                        case 'denied':
                            updateLocationStatus('error', 'Akses lokasi ditolak permanen');
                            updateDebugInfo('❌ Permission permanently denied. Reset in browser settings.');
                            showLocationError();
                            break;
                        case 'prompt':
                            updateDebugInfo('❓ Permission will be requested');
                            updateLocationStatus('loading', 'Meminta izin akses lokasi...');
                            startLocationTracking();
                            break;
                    }
                } catch (error) {
                    updateDebugInfo('⚠️ Permission API error, trying direct geolocation');
                    startLocationTracking();
                }
            } else {
                updateDebugInfo('⚠️ Permission API not available, trying direct geolocation');
                startLocationTracking();
            }
        }

        // 🔍 Start location tracking with 2024 optimized settings
        function startLocationTracking() {
            updateLocationStatus('loading', 'Mengambil lokasi GPS...');
            updateDebugInfo('📰 Starting GPS location tracking...');
            
            // 2024 best practice: Progressive accuracy approach
            const geoOptions = {
                enableHighAccuracy: true,
                timeout: 10000,           // 10 seconds for mobile
                maximumAge: 60000         // Accept 1 minute old location
            };

            // Try high accuracy first
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    updateDebugInfo('✅ High accuracy GPS successful');
                    handleLocationSuccess(position);
                    startLocationWatching(position);
                },
                function(error) {
                    updateDebugInfo(`❌ High accuracy failed: ${getErrorMessage(error)}`);
                    
                    // Fallback to medium accuracy
                    const mediumOptions = {
                        enableHighAccuracy: false,
                        timeout: 15000,
                        maximumAge: 300000  // Accept 5 min old location
                    };
                    
                    updateLocationStatus('loading', 'Mencoba akurasi standar...');
                    updateDebugInfo('🔄 Trying medium accuracy...');
                    
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            updateDebugInfo('✅ Medium accuracy GPS successful');
                            handleLocationSuccess(position);
                            startLocationWatching(position);
                        },
                        function(finalError) {
                            updateDebugInfo(`❌ All GPS strategies failed: ${getErrorMessage(finalError)}`);
                            handleLocationError(finalError);
                        },
                        mediumOptions
                    );
                },
                geoOptions
            );
        }
        
        // Start watching position (lighter approach)
        function startLocationWatching(initialPosition) {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }
            
            watchId = navigator.geolocation.watchPosition(
                handleLocationSuccess,
                function(error) {
                    updateDebugInfo(`⚠️ Watch error: ${getErrorMessage(error)}`);
                    // Don't stop on watch errors, keep using last known position
                },
                {
                    enableHighAccuracy: false, // Use lower accuracy for watching
                    timeout: 20000,
                    maximumAge: 120000  // Accept 2min old positions for watching
                }
            );
            
            updateDebugInfo(`👁️ Started position watching (ID: ${watchId})`);
        }

        // 🎯 Handle successful location detection
        function handleLocationSuccess(position) {
            const userLat = position.coords.latitude;
            const userLng = position.coords.longitude;
            const accuracy = position.coords.accuracy;
            const timestamp = new Date(position.timestamp);

            // Store current location globally
            currentUserLocation = {
                latitude: userLat,
                longitude: userLng,
                accuracy: accuracy,
                timestamp: timestamp
            };

            updateDebugInfo(`🎯 Location: ${userLat.toFixed(6)}, ${userLng.toFixed(6)} (±${Math.round(accuracy)}m)`);

            // Update coordinates display
            updateCoordinatesDisplay(userLat, userLng, accuracy, timestamp);

            // Update simple location display
            updateLocationDisplay(userLat, userLng, accuracy);

            // Calculate distance using Haversine formula
            const distance = calculateDistance(
                adminCoordinates.lat, adminCoordinates.lng,
                userLat, userLng
            );

            updateDebugInfo(`📏 Distance: ${distance.toFixed(1)}m from clinic (max: ${geofenceRadius}m)`);

            // Check if user is within geofence
            const isWithinGeofence = distance <= geofenceRadius;
            updateGeofenceStatus(isWithinGeofence, distance);

            hideLocationError();
        }
        
        // 📍 Update simple location display (no external maps)
        function updateLocationDisplay(userLat, userLng, accuracy) {
            const userCoordsElement = document.getElementById('user-coords-text');
            const distanceElement = document.getElementById('distance-info');
            
            if (userCoordsElement) {
                userCoordsElement.innerHTML = `📍 ${userLat.toFixed(6)}, ${userLng.toFixed(6)}<br><small>Akurasi: ±${Math.round(accuracy)}m</small>`;
            }
            
            const distance = calculateDistance(
                adminCoordinates.lat, adminCoordinates.lng,
                userLat, userLng
            );
            
            if (distanceElement) {
                const isWithin = distance <= geofenceRadius;
                const status = isWithin ? '✅ Dalam radius' : '❌ Di luar radius';
                const color = isWithin ? '#10b981' : '#ef4444';
                
                distanceElement.innerHTML = `<span style="color: ${color}">${status}</span><br><small>Jarak: ${distance.toFixed(1)}m</small>`;
            }
            
            updateDebugInfo('📍 Location display updated');
        }

        // ❌ Handle location errors with helpful messaging
        function handleLocationError(error) {
            const errorMsg = getErrorMessage(error);
            updateDebugInfo(`❌ Location error: ${errorMsg}`);
            updateLocationStatus('error', errorMsg);
            showLocationError();
            disableButtons();
        }
        
        // Get user-friendly error message
        function getErrorMessage(error) {
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    return 'Akses lokasi ditolak. Izinkan di pengaturan browser.';
                case error.POSITION_UNAVAILABLE:
                    return 'Lokasi tidak tersedia. Aktifkan GPS dan coba lagi.';
                case error.TIMEOUT:
                    return 'Timeout lokasi. Coba di area dengan sinyal GPS lebih baik.';
                default:
                    return 'Error lokasi tidak dikenal. Coba refresh halaman.';
            }
        }

        // 📏 Calculate distance using optimized Haversine formula
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }

        function updateCoordinatesDisplay(lat, lng, accuracy, timestamp) {
            const coordElement = document.getElementById('user-coordinates');
            if (coordElement) {
                const timeStr = timestamp ? ` - ${timestamp.toLocaleTimeString()}` : '';
                coordElement.innerHTML = `
                    <strong>Koordinat:</strong> ${lat.toFixed(6)}, ${lng.toFixed(6)}<br>
                    <strong>Akurasi:</strong> ±${Math.round(accuracy)}m${timeStr}
                `;
            }
        }

        function updateLocationStatus(status, message) {
            const statusElement = document.getElementById('location-status');
            const statusText = statusElement.querySelector('.status-text');
            const statusIcon = statusElement.querySelector('.status-icon');
            
            statusElement.className = `location-status ${status}`;
            statusText.textContent = message;
            
            switch(status) {
                case 'loading':
                    statusIcon.textContent = '📍';
                    break;
                case 'inside':
                    statusIcon.textContent = '🟢';
                    break;
                case 'outside':
                    statusIcon.textContent = '🔴';
                    break;
                case 'error':
                    statusIcon.textContent = '⚠️';
                    break;
            }
        }

        function updateGeofenceStatus(isWithin, distance) {
            if (isWithin) {
                updateLocationStatus('inside', `Anda berada di dalam area presensi (${distance.toFixed(1)}m)`);
                enableButtons();
            } else {
                updateLocationStatus('outside', `Anda berada di luar area presensi (${distance.toFixed(1)}m)`);
                disableButtons();
                
                // Show notification
                if (window.Livewire) {
                    window.Livewire.emit('showNotification', {
                        type: 'warning',
                        title: '⚠️ Di Luar Area Presensi',
                        message: 'Presensi hanya dapat dilakukan di area yang telah ditentukan.'
                    });
                }
            }
        }

        function enableButtons() {
            const checkinBtn = document.getElementById('checkin-button');
            const checkoutBtn = document.getElementById('checkout-button');
            
            if (checkinBtn && checkinBtn.style.display !== 'none') {
                checkinBtn.disabled = false;
                checkinBtn.classList.remove('disabled');
            }
            
            if (checkoutBtn && checkoutBtn.style.display !== 'none') {
                checkoutBtn.disabled = false;
                checkoutBtn.classList.remove('disabled');
            }
        }

        function disableButtons() {
            const checkinBtn = document.getElementById('checkin-button');
            const checkoutBtn = document.getElementById('checkout-button');
            
            if (checkinBtn) {
                checkinBtn.disabled = true;
                checkinBtn.classList.add('disabled');
            }
            
            if (checkoutBtn) {
                checkoutBtn.disabled = true;
                checkoutBtn.classList.add('disabled');
            }
        }

        function showLocationError() {
            const errorElement = document.getElementById('location-error');
            if (errorElement) {
                errorElement.style.display = 'block';
            }
        }

        function hideLocationError() {
            const errorElement = document.getElementById('location-error');
            if (errorElement) {
                errorElement.style.display = 'none';
            }
        }

        // Override Livewire actions to include location data
        let currentUserLocation = null;

        // Store current location for server validation
        function storeCurrentLocation(lat, lng) {
            currentUserLocation = { latitude: lat, longitude: lng };
        }

        // Override checkin button click
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.sent', (message, component) => {
                if (message.updateQueue && message.updateQueue.some(update => 
                    update.method === 'checkin' || update.method === 'checkout')) {
                    
                    if (currentUserLocation) {
                        // Add location data to the request
                        message.payload.serverMemo.data.latitude = currentUserLocation.latitude;
                        message.payload.serverMemo.data.longitude = currentUserLocation.longitude;
                    }
                }
            });
        });

        // 📡 Simplified location data transmission (2024 optimized)
        document.addEventListener('DOMContentLoaded', function() {
            // Set up location data transmission for both buttons
            setupLocationTransmission('checkin-button', 'checkin');
            setupLocationTransmission('checkout-button', 'checkout');
        });
        
        function setupLocationTransmission(buttonId, action) {
            const button = document.getElementById(buttonId);
            if (!button) return;
            
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent default Livewire action
                
                if (!currentUserLocation) {
                    updateDebugInfo(`❌ No location for ${action}. Try GPS refresh first.`);
                    return;
                }
                
                updateDebugInfo(`📡 Sending ${action} with location...`);
                
                // Send location data directly to Livewire method
                if (action === 'checkin') {
                    @this.call('checkinWithLocation', {
                        latitude: currentUserLocation.latitude,
                        longitude: currentUserLocation.longitude,
                        accuracy: currentUserLocation.accuracy || 0
                    });
                } else if (action === 'checkout') {
                    @this.call('checkoutWithLocation', {
                        latitude: currentUserLocation.latitude,
                        longitude: currentUserLocation.longitude,
                        accuracy: currentUserLocation.accuracy || 0
                    });
                }
            });
        }


        // 🧪 Manual location testing function (optimized)
        function useManualLocation() {
            const lat = parseFloat(document.getElementById('manual-lat').value);
            const lng = parseFloat(document.getElementById('manual-lng').value);
            
            if (isNaN(lat) || isNaN(lng)) {
                updateDebugInfo('❌ Koordinat tidak valid');
                return;
            }
            
            updateDebugInfo(`🧪 Testing manual coordinates: ${lat}, ${lng}`);
            
            // Create mock position object
            const mockPosition = {
                coords: {
                    latitude: lat,
                    longitude: lng,
                    accuracy: 10
                },
                timestamp: Date.now()
            };
            
            handleLocationSuccess(mockPosition);
        }
        
        // 🔄 Enhanced GPS debugging function (2024 optimized)
        function getCurrentLocationDebug() {
            updateDebugInfo('🔄 Force GPS refresh...');
            
            if (!checkHTTPSRequirement()) {
                return;
            }
            
            if (!navigator.geolocation) {
                updateDebugInfo('❌ Geolocation not supported');
                return;
            }
            
            // Clear any existing watch
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
                watchId = null;
            }
            
            updateLocationStatus('loading', 'Memaksa refresh GPS...');
            
            // Force fresh GPS reading
            const forceOptions = {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0  // Force fresh reading
            };
            
            navigator.geolocation.getCurrentPosition(
                handleLocationSuccess,
                handleLocationError,
                forceOptions
            );
        }
        
        
        // Update debug info display
        function updateDebugInfo(message) {
            const debugElement = document.getElementById('debug-info');
            if (debugElement) {
                const timestamp = new Date().toLocaleTimeString();
                debugElement.innerHTML += `[${timestamp}] ${message}<br>`;
                debugElement.scrollTop = debugElement.scrollHeight;
                console.log(`[DEBUG] ${message}`);
            }
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (watchId) {
                navigator.geolocation.clearWatch(watchId);
            }
        });
    </script>
    @endpush
</x-filament-panels::page>