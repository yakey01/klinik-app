<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-x-2">
                    <x-heroicon-o-map-pin class="h-5 w-5 text-blue-500" />
                    <span class="text-base font-medium">📍 Lokasi</span>
                </div>
                
                <x-filament::button
                    wire:click="toggleDetails"
                    color="gray"
                    variant="ghost"
                    size="sm"
                >
                    {{ $showDetails ? 'Tutup' : 'Detail' }}
                </x-filament::button>
            </div>
        </x-slot>

        <div class="space-y-2">
            {{-- Compact Status --}}
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ $statusIcon }}</span>
                        <div>
                            <h3 class="text-sm font-medium {{ $statusColor }}">{{ $statusText }}</h3>
                            <p class="text-xs text-gray-500">{{ $distance }}m dari klinik</p>
                        </div>
                    </div>
                    
                    <div class="text-right text-xs">
                        @if($withinRadius)
                            <div class="text-green-600 font-medium">Siap Presensi</div>
                        @else
                            <div class="text-red-600 font-medium">Terlalu Jauh</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Collapsible Details --}}
            @if($showDetails)
                <div class="space-y-2" x-show="true" x-transition>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded text-center">
                            <div class="text-sm mb-1">🌐</div>
                            <p class="text-xs font-medium">Chrome</p>
                        </div>
                        
                        <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded text-center">
                            <div class="text-sm mb-1">📱</div>
                            <p class="text-xs font-medium">Desktop</p>
                        </div>
                        
                        <div class="p-2 bg-gray-50 dark:bg-gray-800 rounded text-center">
                            <div class="text-sm mb-1">🎯</div>
                            <p class="text-xs font-medium">{{ $accuracy }}m</p>
                        </div>
                    </div>
                    
                    {{-- Link to Map --}}
                    <div class="text-center">
                        <a href="/paramedis/peta-lokasi" class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                            🗺️ Lihat Peta Lengkap
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>