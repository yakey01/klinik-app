<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-2">
                <x-heroicon-o-currency-dollar class="h-5 w-5 text-yellow-500" />
                <span class="text-base font-medium">💰 Jaspel & Pendapatan</span>
            </div>
        </x-slot>

        <div class="space-y-3">
            {{-- Today's Jaspel - Compact Card --}}
            <div class="p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">Jaspel Hari Ini</h3>
                        <p class="text-xl font-bold text-yellow-600 dark:text-yellow-400">{{ $formattedToday }}</p>
                    </div>
                    <div class="text-2xl">💰</div>
                </div>
            </div>

            {{-- Monthly Summary - Grid --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="text-center">
                        <div class="text-lg mb-1">📈</div>
                        <p class="text-xs text-green-700 dark:text-green-400 font-medium">Bulan Ini</p>
                        <p class="text-sm font-bold text-green-600">{{ $formattedMonthly }}</p>
                        
                        {{-- Compact Progress Bar --}}
                        <div class="mt-2">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">{{ number_format($progress, 0) }}% target</p>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="text-center">
                        <div class="text-lg mb-1">🎯</div>
                        <p class="text-xs text-blue-700 dark:text-blue-400 font-medium">Sisa Hari</p>
                        <p class="text-sm font-bold text-blue-600">{{ $remainingDays }} Hari</p>
                        
                        <div class="mt-2">
                            <p class="text-xs text-blue-600 dark:text-blue-400">Rata-rata:</p>
                            <p class="text-xs font-medium text-blue-800 dark:text-blue-300">
                                Rp {{ number_format($monthlyJaspel / (\Carbon\Carbon::now('Asia/Jakarta')->day ?: 1) / 1000, 0) }}k/hari
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Achievement Badge - Compact --}}
            @if($progress >= 80)
                <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg border border-purple-200 dark:border-purple-800 text-center">
                    <span class="text-lg">🏆</span>
                    <span class="text-xs font-medium text-purple-700 dark:text-purple-400 ml-2">Kinerja Excellent!</span>
                </div>
            @elseif($progress >= 50)
                <div class="p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-center">
                    <span class="text-lg">💪</span>
                    <span class="text-xs font-medium text-blue-700 dark:text-blue-400 ml-2">Kerja Bagus!</span>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>