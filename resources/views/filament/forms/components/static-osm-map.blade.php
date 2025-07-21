@php
    $statePath = $getStatePath();
    $defaultLocation = $getDefaultLocation();
    $lat = data_get($getState(), 'lat', $defaultLocation['lat'] ?? -7.89946200);
    $lng = data_get($getState(), 'lng', $defaultLocation['lng'] ?? 111.96239900);
    $zoom = $getZoom() ?? 15;
    $height = $getHeight() ?? 400;
    $width = 800; // Fixed width for static map
    $mapId = 'static-map-' . str_replace(['.', '[', ']'], '-', $statePath);
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
        <div 
            id="{{ $mapId }}-container"
            class="relative w-full cursor-crosshair bg-blue-50"
            style="height: {{ $height }}px;"
            wire:ignore
        >
            <!-- Static map image -->
            <img 
                id="{{ $mapId }}-image"
                src="https://staticmap.openstreetmap.de/staticmap.php?center={{ $lat }},{{ $lng }}&zoom={{ $zoom }}&size={{ $width }}x{{ $height }}&maptype=mapnik&markers={{ $lat }},{{ $lng }},red-pushpin"
                alt="Map"
                class="w-full h-full object-cover cursor-pointer"
                onclick="openMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}()"
                onerror="this.src='data:image/svg+xml;base64,' + btoa('<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{{ $width }}\" height=\"{{ $height }}\" viewBox=\"0 0 {{ $width }} {{ $height }}\"><rect width=\"100%\" height=\"100%\" fill=\"#e5e7eb\"/><text x=\"50%\" y=\"50%\" text-anchor=\"middle\" dy=\"0.3em\" font-family=\"Arial\" font-size=\"16\" fill=\"#6b7280\">Klik untuk membuka peta</text></svg>')"
            />
            
            <!-- Marker overlay -->
            <div 
                id="{{ $mapId }}-marker" 
                class="absolute w-6 h-6 -translate-x-1/2 -translate-y-1/2 pointer-events-none z-10"
                style="left: 50%; top: 50%;"
            >
                <svg class="w-full h-full drop-shadow-lg" viewBox="0 0 24 24" fill="#ef4444">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                </svg>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="absolute top-3 left-3 z-20 flex flex-col gap-2">
            <button 
                type="button"
                id="{{ $mapId }}-gps"
                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-lg"
                onclick="getGPSLocation{{ Str::studly(str_replace('-', '', $mapId)) }}()"
            >
                📍 GPS
            </button>
            
            <button 
                type="button"
                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-lg"
                onclick="openMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}()"
            >
                🗺️ Pilih
            </button>
        </div>
        
        <!-- Coordinates display -->
        <div class="absolute bottom-3 left-3 z-20 bg-white/95 backdrop-blur-sm rounded-lg px-3 py-2 text-sm shadow-lg">
            <div class="text-gray-600">
                <span class="font-medium">Lokasi:</span>
                <div id="{{ $mapId }}-coords" class="font-mono text-blue-600">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</div>
            </div>
        </div>
    </div>

    <!-- Modal untuk interactive map -->
    <div 
        id="{{ $mapId }}-modal" 
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden items-center justify-center p-4"
        onclick="closeMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}()"
    >
        <div 
            class="bg-white rounded-lg w-full max-w-4xl max-h-full overflow-hidden"
            onclick="event.stopPropagation()"
        >
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Pilih Lokasi</h3>
                <button 
                    type="button"
                    class="text-gray-400 hover:text-gray-600"
                    onclick="closeMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}()"
                >
                    ✕
                </button>
            </div>
            
            <div class="p-4">
                <!-- Embed OSM map in iframe -->
                <iframe 
                    id="{{ $mapId }}-modal-frame"
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $lng - 0.01 }},{{ $lat - 0.01 }},{{ $lng + 0.01 }},{{ $lat + 0.01 }}&amp;layer=mapnik&amp;marker={{ $lat }},{{ $lng }}"
                    class="w-full h-96 border-0 rounded"
                ></iframe>
                
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Klik pada peta untuk memilih lokasi
                    </div>
                    <button 
                        type="button"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
                        onclick="closeMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}()"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentLat{{ Str::studly(str_replace('-', '', $mapId)) }} = {{ $lat }};
        let currentLng{{ Str::studly(str_replace('-', '', $mapId)) }} = {{ $lng }};
        
        function updateMap{{ Str::studly(str_replace('-', '', $mapId)) }}(lat, lng) {
            currentLat{{ Str::studly(str_replace('-', '', $mapId)) }} = lat;
            currentLng{{ Str::studly(str_replace('-', '', $mapId)) }} = lng;
            
            // Update static map image
            const img = document.getElementById('{{ $mapId }}-image');
            img.src = `https://staticmap.openstreetmap.de/staticmap.php?center=${lat},${lng}&zoom={{ $zoom }}&size={{ $width }}x{{ $height }}&maptype=mapnik&markers=${lat},${lng},red-pushpin`;
            
            // Update coordinates display
            document.getElementById('{{ $mapId }}-coords').textContent = lat.toFixed(6) + ', ' + lng.toFixed(6);
            
            // Update modal iframe
            const modalFrame = document.getElementById('{{ $mapId }}-modal-frame');
            if (modalFrame) {
                modalFrame.src = `https://www.openstreetmap.org/export/embed.html?bbox=${lng - 0.01},${lat - 0.01},${lng + 0.01},${lat + 0.01}&layer=mapnik&marker=${lat},${lng}`;
            }
            
            // Update Livewire state
            @this.set('{{ $statePath }}', {
                lat: parseFloat(lat.toFixed(6)),
                lng: parseFloat(lng.toFixed(6))
            });
        }
        
        function getGPSLocation{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const button = document.getElementById('{{ $mapId }}-gps');
            
            if (!navigator.geolocation) {
                alert('Browser tidak mendukung GPS');
                return;
            }
            
            button.textContent = '🔄';
            button.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    updateMap{{ Str::studly(str_replace('-', '', $mapId)) }}(lat, lng);
                    button.textContent = '📍 GPS';
                    button.disabled = false;
                },
                function(error) {
                    alert('GPS Error: ' + error.message);
                    button.textContent = '📍 GPS';
                    button.disabled = false;
                }
            );
        }
        
        function openMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const modal = document.getElementById('{{ $mapId }}-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        
        function closeMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const modal = document.getElementById('{{ $mapId }}-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
        
        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeMapModal{{ Str::studly(str_replace('-', '', $mapId)) }}();
            }
        });
    </script>
</x-dynamic-component>