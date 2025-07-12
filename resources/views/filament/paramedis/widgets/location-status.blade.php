<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-x-3">
                    <x-heroicon-o-map-pin class="h-6 w-6 text-blue-500" />
                    <span class="text-lg font-semibold">📍 Status Lokasi</span>
                </div>
                
                <x-filament::button
                    wire:click="toggleDetails"
                    color="gray"
                    variant="ghost"
                    size="sm"
                >
                    {{ $showDetails ? 'Sembunyikan' : 'Detail' }}
                    <x-heroicon-o-chevron-down class="ml-1 h-4 w-4 {{ $showDetails ? 'rotate-180' : '' }} transition-transform" />
                </x-filament::button>
            </div>
        </x-slot>

        <div class="space-y-4">
            {{-- Main Status --}}
            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">{{ $statusIcon }}</span>
                        <div>
                            <h3 class="font-semibold {{ $statusColor }}">{{ $statusText }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Jarak ke klinik: {{ $distance }}m
                            </p>
                        </div>
                    </div>
                    
                    @if($withinRadius)
                        <div class="text-center">
                            <div class="text-green-600 text-sm font-medium">Siap Presensi</div>
                            <div class="text-xs text-gray-500">Dalam radius 100m</div>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="text-red-600 text-sm font-medium">Tidak Dapat Presensi</div>
                            <div class="text-xs text-gray-500">Mendekati lokasi klinik</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Detailed Information (Collapsible) --}}
            @if($showDetails)
                <div class="space-y-3" x-show="true" x-transition>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                            <div class="text-lg mb-1">🌐</div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Browser</p>
                            <p class="text-sm font-medium">Chrome</p>
                        </div>
                        
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                            <div class="text-lg mb-1">📱</div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Device</p>
                            <p class="text-sm font-medium">Desktop</p>
                        </div>
                        
                        <div class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-center">
                            <div class="text-lg mb-1">🎯</div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Akurasi</p>
                            <p class="text-sm font-medium">{{ $accuracy }}m</p>
                        </div>
                    </div>
                    
                    {{-- Quick Actions --}}
                    <div class="flex gap-2">
                        <x-filament::button
                            color="blue"
                            size="sm"
                            icon="heroicon-o-arrow-path"
                        >
                            Refresh Lokasi
                        </x-filament::button>
                        
                        <x-filament::button
                            color="gray"
                            variant="outlined"
                            size="sm"
                            icon="heroicon-o-map"
                        >
                            Lihat Peta
                        </x-filament::button>
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>