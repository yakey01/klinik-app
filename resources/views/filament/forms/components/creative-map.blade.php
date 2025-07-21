@php
    $statePath = $getStatePath();
    $defaultLocation = $getDefaultLocation();
    $lat = data_get($getState(), 'lat', $defaultLocation['lat'] ?? -7.89946200);
    $lng = data_get($getState(), 'lng', $defaultLocation['lng'] ?? 111.96239900);
    $zoom = $getZoom() ?? 15;
    $height = $getHeight() ?? 400;
    $mapId = 'creative-map-' . str_replace(['.', '[', ']'], '-', $statePath);
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
    <style>
        /* Ensure map loads properly */
        .creative-map-container {
            position: relative;
            width: 100%;
            height: {{ $height }}px;
            background: #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .creative-map-container canvas,
        .creative-map-container img {
            max-width: none !important;
        }
        .gm-style {
            font-family: inherit !important;
        }
    </style>

    <div class="creative-map-container border border-gray-300">
        <div 
            id="{{ $mapId }}" 
            style="width: 100%; height: 100%;"
            wire:ignore
        ></div>
        
        <button 
            type="button"
            id="{{ $mapId }}-gps"
            class="absolute top-3 left-3 z-[1001] bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-lg transition-all duration-200"
            onclick="detectGPS{{ Str::studly(str_replace('-', '', $mapId)) }}()"
        >
            🎯 Deteksi GPS
        </button>

        <div class="absolute bottom-3 left-3 z-[1001] bg-white/90 backdrop-blur-sm rounded-lg px-3 py-2 text-xs text-gray-600 shadow-lg">
            <span class="font-medium">Koordinat:</span>
            <span id="{{ $mapId }}-display">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</span>
        </div>
    </div>

    <!-- Creative approach: Use Google Maps API with custom styling to look like OSM -->
    <script>
        let map{{ Str::studly(str_replace('-', '', $mapId)) }};
        let marker{{ Str::studly(str_replace('-', '', $mapId)) }};
        
        function initMap{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const center = { lat: {{ $lat }}, lng: {{ $lng }} };
            
            // Initialize Google Map with OSM-like styling
            map{{ Str::studly(str_replace('-', '', $mapId)) }} = new google.maps.Map(document.getElementById("{{ $mapId }}"), {
                zoom: {{ $zoom }},
                center: center,
                styles: [
                    {
                        "featureType": "all",
                        "elementType": "all",
                        "stylers": [
                            { "saturation": -100 },
                            { "lightness": 50 }
                        ]
                    },
                    {
                        "featureType": "road",
                        "elementType": "all",
                        "stylers": [
                            { "color": "#ffffff" }
                        ]
                    },
                    {
                        "featureType": "landscape",
                        "elementType": "all",
                        "stylers": [
                            { "color": "#f0f0f0" }
                        ]
                    },
                    {
                        "featureType": "water",
                        "elementType": "all",
                        "stylers": [
                            { "color": "#aadaff" }
                        ]
                    }
                ],
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_CENTER
                }
            });

            // Add draggable marker
            marker{{ Str::studly(str_replace('-', '', $mapId)) }} = new google.maps.Marker({
                position: center,
                map: map{{ Str::studly(str_replace('-', '', $mapId)) }},
                draggable: true,
                title: "Lokasi Dipilih",
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="16" cy="16" r="8" fill="#ef4444" stroke="#ffffff" stroke-width="2"/>
                            <circle cx="16" cy="16" r="3" fill="#ffffff"/>
                        </svg>
                    `),
                    scaledSize: new google.maps.Size(32, 32),
                    anchor: new google.maps.Point(16, 16)
                }
            });

            // Update coordinates function
            function updateCoordinates(lat, lng) {
                document.getElementById('{{ $mapId }}-display').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                @this.set('{{ $statePath }}', {
                    lat: parseFloat(lat.toFixed(6)),
                    lng: parseFloat(lng.toFixed(6))
                });
            }

            // Event listeners
            marker{{ Str::studly(str_replace('-', '', $mapId)) }}.addListener('dragend', function() {
                const position = marker{{ Str::studly(str_replace('-', '', $mapId)) }}.getPosition();
                updateCoordinates(position.lat(), position.lng());
            });

            map{{ Str::studly(str_replace('-', '', $mapId)) }}.addListener('click', function(e) {
                marker{{ Str::studly(str_replace('-', '', $mapId)) }}.setPosition(e.latLng);
                updateCoordinates(e.latLng.lat(), e.latLng.lng());
            });
        }

        // GPS detection function
        function detectGPS{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const button = document.getElementById('{{ $mapId }}-gps');
            
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung geolocation');
                return;
            }

            button.textContent = '🔄 Mencari...';
            button.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const newPos = { lat, lng };

                    map{{ Str::studly(str_replace('-', '', $mapId)) }}.setCenter(newPos);
                    map{{ Str::studly(str_replace('-', '', $mapId)) }}.setZoom(16);
                    marker{{ Str::studly(str_replace('-', '', $mapId)) }}.setPosition(newPos);
                    
                    document.getElementById('{{ $mapId }}-display').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
                    @this.set('{{ $statePath }}', {
                        lat: parseFloat(lat.toFixed(6)),
                        lng: parseFloat(lng.toFixed(6))
                    });

                    button.textContent = '🎯 Deteksi GPS';
                    button.disabled = false;
                },
                function(error) {
                    alert('Error: ' + error.message);
                    button.textContent = '🎯 Deteksi GPS';
                    button.disabled = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        }

        // Load Google Maps API if not already loaded
        if (typeof google === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://maps.googleapis.com/maps/api/js?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dO0uGdN_34C4bw&libraries=geometry&callback=initMap{{ Str::studly(str_replace('-', '', $mapId)) }}';
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        } else {
            initMap{{ Str::studly(str_replace('-', '', $mapId)) }}();
        }
    </script>
</x-dynamic-component>