<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <x-heroicon-o-clock class="h-5 w-5 text-green-500" />
                <span class="text-base font-medium">Status Presensi</span>
            </div>
        </x-slot>

        {{-- Compact Status Card --}}
        <div class="p-3 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg border border-green-200 dark:border-green-800">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xl">{{ $statusIcon }}</span>
                    <div>
                        <h3 class="text-sm font-semibold {{ $statusColor }}">{{ $status }}</h3>
                        <p class="text-xs text-gray-500">{{ $currentTime }}</p>
                    </div>
                </div>
                
                {{-- Quick Status Summary --}}
                <div class="text-right text-xs">
                    @if($hasCheckedIn)
                        <div class="text-green-600">Masuk: {{ $checkinTime }}</div>
                    @endif
                    @if($hasCheckedOut)
                        <div class="text-orange-600">Pulang: {{ $checkoutTime }}</div>
                    @endif
                    @if(!$hasCheckedIn)
                        <div class="text-red-600">Belum presensi</div>
                    @endif
                </div>
            </div>
            
            {{-- Link to Presensi Page --}}
            <div class="mt-2 pt-2 border-t border-green-200 dark:border-green-700">
                <a href="/paramedis/presensi" class="text-xs text-green-600 hover:text-green-800 font-medium">
                    → Buka Menu Presensi untuk Check In/Out
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>