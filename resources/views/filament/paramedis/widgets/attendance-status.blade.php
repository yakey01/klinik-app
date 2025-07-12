<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-clock class="h-6 w-6 text-green-500" />
                <span class="text-lg font-semibold">Status Presensi Hari Ini</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            {{-- Current Status Card --}}
            <div class="p-6 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="text-2xl">{{ $statusIcon }}</span>
                            <h3 class="text-xl font-bold {{ $statusColor }}">{{ $status }}</h3>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400">Waktu Sekarang: {{ $currentTime }}</p>
                    </div>
                    
                    @if(!$hasCheckedIn)
                        <x-filament::button
                            wire:click="checkin"
                            color="success"
                            icon="heroicon-o-clock"
                            size="lg"
                        >
                            📝 Check In
                        </x-filament::button>
                    @elseif(!$hasCheckedOut)
                        <x-filament::button
                            wire:click="checkout"
                            color="warning"
                            icon="heroicon-o-arrow-right-end-on-rectangle"
                            size="lg"
                        >
                            🏃‍♂️ Check Out
                        </x-filament::button>
                    @else
                        <div class="text-center">
                            <div class="text-2xl">🎉</div>
                            <p class="text-sm text-green-600 font-medium">Selesai!</p>
                        </div>
                    @endif
                </div>
                
                {{-- Time Summary --}}
                @if($hasCheckedIn || $hasCheckedOut)
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-green-200 dark:border-green-700">
                        @if($checkinTime)
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Masuk</p>
                                <p class="text-lg font-bold text-green-600">{{ $checkinTime }}</p>
                            </div>
                        @endif
                        
                        @if($checkoutTime)
                            <div class="text-center">
                                <p class="text-sm text-gray-600 dark:text-gray-400">Pulang</p>
                                <p class="text-lg font-bold text-orange-600">{{ $checkoutTime }}</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Quick Info --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                    <div class="text-2xl mb-2">⏰</div>
                    <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Jam Kerja Normal</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">08:00 - 17:00</p>
                </div>
                
                <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                    <div class="text-2xl mb-2">📍</div>
                    <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">Lokasi</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">Klinik Dokterku</p>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>