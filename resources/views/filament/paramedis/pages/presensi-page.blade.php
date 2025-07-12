<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Current Time & Date --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-x-2">
                    <x-heroicon-o-clock class="h-6 w-6 text-green-500" />
                    <span class="text-lg font-semibold">Waktu Saat Ini</span>
                </div>
            </x-slot>
            
            <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg">
                <div id="live-clock" class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-2">
                    {{ $currentTime }}
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $currentDate }}</p>
            </div>
        </x-filament::section>

        {{-- Attendance Status --}}
        <x-filament::section>
            <x-slot name="heading">Status Presensi Hari Ini</x-slot>
            
            <div class="space-y-4">
                {{-- Status Cards --}}
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-4 {{ $hasCheckedIn ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }} dark:bg-gray-800 rounded-lg border text-center">
                        <div class="text-2xl mb-2">{{ $hasCheckedIn ? '✅' : '⏰' }}</div>
                        <p class="text-xs font-medium {{ $hasCheckedIn ? 'text-green-600' : 'text-gray-600' }}">
                            {{ $hasCheckedIn ? 'Sudah Masuk' : 'Belum Masuk' }}
                        </p>
                        @if($checkinTime)
                            <p class="text-sm font-bold text-green-600 mt-1">{{ $checkinTime }}</p>
                        @endif
                    </div>
                    
                    <div class="p-4 {{ $hasCheckedOut ? 'bg-orange-50 border-orange-200' : 'bg-gray-50 border-gray-200' }} dark:bg-gray-800 rounded-lg border text-center">
                        <div class="text-2xl mb-2">{{ $hasCheckedOut ? '🏁' : '⏳' }}</div>
                        <p class="text-xs font-medium {{ $hasCheckedOut ? 'text-orange-600' : 'text-gray-600' }}">
                            {{ $hasCheckedOut ? 'Sudah Pulang' : 'Belum Pulang' }}
                        </p>
                        @if($checkoutTime)
                            <p class="text-sm font-bold text-orange-600 mt-1">{{ $checkoutTime }}</p>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="space-y-3">
                    @if(!$hasCheckedIn)
                        <x-filament::button
                            wire:click="checkin"
                            color="success"
                            icon="heroicon-o-play"
                            size="lg"
                            class="w-full text-lg py-4"
                        >
                            📝 CHECK IN - Mulai Bekerja
                        </x-filament::button>
                    @elseif(!$hasCheckedOut)
                        <x-filament::button
                            wire:click="checkout"
                            color="warning"
                            icon="heroicon-o-stop"
                            size="lg"
                            class="w-full text-lg py-4"
                        >
                            🏃‍♂️ CHECK OUT - Selesai Bekerja
                        </x-filament::button>
                    @else
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 text-center">
                            <div class="text-3xl mb-2">🎉</div>
                            <p class="font-medium text-green-600">Presensi Hari Ini Selesai!</p>
                            <p class="text-sm text-gray-600 mt-1">Terima kasih sudah bekerja hari ini</p>
                        </div>
                    @endif
                </div>
            </div>
        </x-filament::section>

        {{-- Quick Info --}}
        <x-filament::section>
            <x-slot name="heading">Informasi</x-slot>
            
            <div class="grid grid-cols-2 gap-3">
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                    <div class="text-xl mb-1">⏰</div>
                    <p class="text-xs font-medium text-blue-600">Jam Kerja</p>
                    <p class="text-xs text-gray-600">08:00 - 17:00</p>
                </div>
                
                <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                    <div class="text-xl mb-1">📍</div>
                    <p class="text-xs font-medium text-purple-600">Lokasi</p>
                    <p class="text-xs text-gray-600">Klinik Dokterku</p>
                </div>
            </div>
        </x-filament::section>
    </div>

    {{-- Live Clock Script --}}
    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                timeZone: 'Asia/Jakarta',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const timeString = now.toLocaleTimeString('id-ID', options);
            
            const clockElement = document.getElementById('live-clock');
            if (clockElement) {
                clockElement.textContent = timeString;
            }
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        updateClock(); // Initial call
    </script>
</x-filament-panels::page>
