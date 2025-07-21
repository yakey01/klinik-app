@php
    $statePath = $getStatePath();
    $defaultLocation = $getDefaultLocation();
    $lat = data_get($getState(), 'lat', $defaultLocation['lat'] ?? -7.89946200);
    $lng = data_get($getState(), 'lng', $defaultLocation['lng'] ?? 111.96239900);
    $zoom = $getZoom() ?? 15;
    $height = $getHeight() ?? 400;
    $mapId = 'canvas-map-' . str_replace(['.', '[', ']'], '-', $statePath);
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
        <!-- Main canvas map container -->
        <div 
            id="{{ $mapId }}-container" 
            class="relative w-full cursor-crosshair bg-blue-50"
            style="height: {{ $height }}px;"
            wire:ignore
        >
            <canvas 
                id="{{ $mapId }}-canvas" 
                class="absolute inset-0 w-full h-full"
                style="image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;"
            ></canvas>
            
            <!-- Marker overlay -->
            <div 
                id="{{ $mapId }}-marker" 
                class="absolute w-8 h-8 -translate-x-1/2 -translate-y-1/2 z-10 pointer-events-none"
                style="left: 50%; top: 50%;"
            >
                <svg class="w-full h-full drop-shadow-lg" viewBox="0 0 32 32">
                    <circle cx="16" cy="16" r="10" fill="#ef4444" stroke="#ffffff" stroke-width="2"/>
                    <circle cx="16" cy="16" r="4" fill="#ffffff"/>
                    <path d="M16 2 L20 10 L16 16 L12 10 Z" fill="#dc2626"/>
                </svg>
            </div>
        </div>
        
        <!-- Controls overlay -->
        <div class="absolute top-3 left-3 z-20 flex flex-col gap-2">
            <button 
                id="{{ $mapId }}-gps"
                class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-lg transition-all"
                onclick="gpsLocation{{ Str::studly(str_replace('-', '', $mapId)) }}()"
            >
                📍 GPS
            </button>
            
            <div class="flex flex-col bg-white border border-gray-300 rounded-lg shadow-lg">
                <button 
                    id="{{ $mapId }}-zoom-in"
                    class="px-3 py-2 text-lg font-bold text-gray-700 hover:bg-gray-50 border-b border-gray-200"
                    onclick="zoomIn{{ Str::studly(str_replace('-', '', $mapId)) }}()"
                >+</button>
                <button 
                    id="{{ $mapId }}-zoom-out"
                    class="px-3 py-2 text-lg font-bold text-gray-700 hover:bg-gray-50"
                    onclick="zoomOut{{ Str::studly(str_replace('-', '', $mapId)) }}()"
                >−</button>
            </div>
        </div>
        
        <!-- Info display -->
        <div class="absolute bottom-3 left-3 z-20 bg-white/95 backdrop-blur-sm rounded-lg px-3 py-2 text-sm shadow-lg">
            <div class="text-gray-600">
                <span class="font-medium">Lokasi:</span>
                <div id="{{ $mapId }}-coords" class="font-mono text-blue-600">{{ number_format($lat, 6) }}, {{ number_format($lng, 6) }}</div>
                <div id="{{ $mapId }}-zoom-display" class="text-xs text-gray-500">Zoom: {{ $zoom }}</div>
            </div>
        </div>
        
        <!-- Loading overlay -->
        <div id="{{ $mapId }}-loading" class="absolute inset-0 bg-gray-100 flex items-center justify-center z-30">
            <div class="text-center">
                <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent mx-auto mb-2"></div>
                <div class="text-sm text-gray-600">Memuat peta...</div>
            </div>
        </div>
    </div>

    <script>
        class CanvasOSMMap {
            constructor(containerId, options = {}) {
                this.containerId = containerId;
                this.canvas = document.getElementById(containerId + '-canvas');
                this.ctx = this.canvas.getContext('2d');
                this.container = document.getElementById(containerId + '-container');
                this.loading = document.getElementById(containerId + '-loading');
                
                // Map state
                this.lat = options.lat || -7.89946200;
                this.lng = options.lng || 111.96239900;
                this.zoom = options.zoom || 15;
                this.tileSize = 256;
                
                // Canvas dimensions
                this.width = this.container.offsetWidth;
                this.height = this.container.offsetHeight;
                this.canvas.width = this.width * 2; // Retina support
                this.canvas.height = this.height * 2;
                this.canvas.style.width = this.width + 'px';
                this.canvas.style.height = this.height + 'px';
                this.ctx.scale(2, 2);
                
                // Tile cache
                this.tileCache = new Map();
                this.loadingTiles = new Set();
                
                this.init();
            }
            
            init() {
                this.setupEventListeners();
                this.loadVisibleTiles();
            }
            
            setupEventListeners() {
                // Mouse/touch events for panning
                let isPanning = false;
                let startX, startY, startLat, startLng;
                
                this.container.addEventListener('mousedown', (e) => {
                    isPanning = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    startLat = this.lat;
                    startLng = this.lng;
                    this.container.style.cursor = 'grabbing';
                });
                
                this.container.addEventListener('mousemove', (e) => {
                    if (!isPanning) return;
                    
                    const deltaX = e.clientX - startX;
                    const deltaY = e.clientY - startY;
                    
                    // Convert pixel delta to lat/lng delta
                    const pixelToLat = 360 / (this.tileSize * Math.pow(2, this.zoom));
                    const pixelToLng = 360 / (this.tileSize * Math.pow(2, this.zoom)) / Math.cos(this.lat * Math.PI / 180);
                    
                    this.lat = startLat + deltaY * pixelToLat;
                    this.lng = startLng - deltaX * pixelToLng;
                    
                    this.updateDisplay();
                    this.loadVisibleTiles();
                });
                
                this.container.addEventListener('mouseup', () => {
                    isPanning = false;
                    this.container.style.cursor = 'crosshair';
                    this.updateCoordinates();
                });
                
                // Click to set location
                this.container.addEventListener('click', (e) => {
                    if (isPanning) return;
                    
                    const rect = this.container.getBoundingClientRect();
                    const x = e.clientX - rect.left - this.width / 2;
                    const y = e.clientY - rect.top - this.height / 2;
                    
                    const pixelToLat = 360 / (this.tileSize * Math.pow(2, this.zoom));
                    const pixelToLng = 360 / (this.tileSize * Math.pow(2, this.zoom)) / Math.cos(this.lat * Math.PI / 180);
                    
                    this.lat += y * pixelToLat;
                    this.lng -= x * pixelToLng;
                    
                    this.updateDisplay();
                    this.updateCoordinates();
                    this.loadVisibleTiles();
                });
            }
            
            loadVisibleTiles() {
                this.loading.style.display = 'flex';
                
                // Calculate which tiles are visible
                const centerTileX = Math.floor((this.lng + 180) / 360 * Math.pow(2, this.zoom));
                const centerTileY = Math.floor((1 - Math.log(Math.tan(this.lat * Math.PI / 180) + 1 / Math.cos(this.lat * Math.PI / 180)) / Math.PI) / 2 * Math.pow(2, this.zoom));
                
                // Load tiles in a 3x3 grid around center
                const promises = [];
                for (let dx = -1; dx <= 1; dx++) {
                    for (let dy = -1; dy <= 1; dy++) {
                        const tileX = centerTileX + dx;
                        const tileY = centerTileY + dy;
                        promises.push(this.loadTile(tileX, tileY));
                    }
                }
                
                Promise.allSettled(promises).then(() => {
                    this.renderTiles();
                    this.loading.style.display = 'none';
                });
            }
            
            loadTile(x, y) {
                return new Promise((resolve, reject) => {
                    const key = `${this.zoom}/${x}/${y}`;
                    
                    if (this.tileCache.has(key)) {
                        resolve(this.tileCache.get(key));
                        return;
                    }
                    
                    if (this.loadingTiles.has(key)) {
                        setTimeout(() => resolve(this.tileCache.get(key)), 100);
                        return;
                    }
                    
                    this.loadingTiles.add(key);
                    
                    const img = new Image();
                    img.crossOrigin = 'anonymous';
                    img.onload = () => {
                        this.tileCache.set(key, { img, x, y, zoom: this.zoom });
                        this.loadingTiles.delete(key);
                        resolve(this.tileCache.get(key));
                    };
                    img.onerror = () => {
                        this.loadingTiles.delete(key);
                        reject(new Error(`Failed to load tile ${key}`));
                    };
                    
                    // Use multiple tile servers for reliability
                    const servers = ['a', 'b', 'c'];
                    const server = servers[Math.abs(x + y) % servers.length];
                    img.src = `https://${server}.tile.openstreetmap.org/${this.zoom}/${x}/${y}.png`;
                });
            }
            
            renderTiles() {
                this.ctx.clearRect(0, 0, this.width, this.height);
                
                // Calculate center tile position
                const centerTileX = (this.lng + 180) / 360 * Math.pow(2, this.zoom);
                const centerTileY = (1 - Math.log(Math.tan(this.lat * Math.PI / 180) + 1 / Math.cos(this.lat * Math.PI / 180)) / Math.PI) / 2 * Math.pow(2, this.zoom);
                
                // Render each cached tile
                this.tileCache.forEach(tile => {
                    if (tile.zoom !== this.zoom) return;
                    
                    const pixelX = (tile.x - centerTileX) * this.tileSize + this.width / 2;
                    const pixelY = (tile.y - centerTileY) * this.tileSize + this.height / 2;
                    
                    if (pixelX + this.tileSize >= 0 && pixelX <= this.width &&
                        pixelY + this.tileSize >= 0 && pixelY <= this.height) {
                        this.ctx.drawImage(tile.img, pixelX, pixelY, this.tileSize, this.tileSize);
                    }
                });
            }
            
            updateDisplay() {
                document.getElementById(this.containerId + '-coords').textContent = 
                    this.lat.toFixed(6) + ', ' + this.lng.toFixed(6);
                document.getElementById(this.containerId + '-zoom-display').textContent = 
                    'Zoom: ' + this.zoom;
            }
            
            updateCoordinates() {
                @this.set('{{ $statePath }}', {
                    lat: parseFloat(this.lat.toFixed(6)),
                    lng: parseFloat(this.lng.toFixed(6))
                });
            }
            
            setLocation(lat, lng) {
                this.lat = lat;
                this.lng = lng;
                this.updateDisplay();
                this.updateCoordinates();
                this.loadVisibleTiles();
            }
            
            zoomIn() {
                if (this.zoom < 18) {
                    this.zoom++;
                    this.updateDisplay();
                    this.loadVisibleTiles();
                }
            }
            
            zoomOut() {
                if (this.zoom > 1) {
                    this.zoom--;
                    this.updateDisplay();
                    this.loadVisibleTiles();
                }
            }
        }
        
        // Initialize map
        let canvasMap{{ Str::studly(str_replace('-', '', $mapId)) }};
        
        document.addEventListener('DOMContentLoaded', function() {
            canvasMap{{ Str::studly(str_replace('-', '', $mapId)) }} = new CanvasOSMMap('{{ $mapId }}', {
                lat: {{ $lat }},
                lng: {{ $lng }},
                zoom: {{ $zoom }}
            });
        });
        
        // GPS function
        function gpsLocation{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            const button = document.getElementById('{{ $mapId }}-gps');
            button.textContent = '🔄';
            button.disabled = true;
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    canvasMap{{ Str::studly(str_replace('-', '', $mapId)) }}.setLocation(
                        position.coords.latitude,
                        position.coords.longitude
                    );
                    button.textContent = '📍 GPS';
                    button.disabled = false;
                },
                (error) => {
                    alert('GPS Error: ' + error.message);
                    button.textContent = '📍 GPS';
                    button.disabled = false;
                },
                { enableHighAccuracy: true, timeout: 10000 }
            );
        }
        
        // Zoom functions
        function zoomIn{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            canvasMap{{ Str::studly(str_replace('-', '', $mapId)) }}.zoomIn();
        }
        
        function zoomOut{{ Str::studly(str_replace('-', '', $mapId)) }}() {
            canvasMap{{ Str::studly(str_replace('-', '', $mapId)) }}.zoomOut();
        }
    </script>
</x-dynamic-component>