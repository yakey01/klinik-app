<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-currency-dollar class="h-6 w-6 text-yellow-500" />
                <span class="text-lg font-semibold">💰 Jaspel & Pendapatan</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Today's Jaspel - Prominent Card --}}
            <div class="p-6 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200 dark:border-yellow-800 shadow-lg">
                <div class="text-center">
                    <div class="text-4xl mb-2">💰</div>
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-300 mb-2">Jaspel Hari Ini</h3>
                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400 mb-1">{{ $formattedToday }}</p>
                    <p class="text-sm text-yellow-700 dark:text-yellow-500">{{ \Carbon\Carbon::now('Asia/Jakarta')->format('d M Y') }}</p>
                </div>
            </div>

            {{-- Monthly Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Monthly Total --}}
                <div class="p-5 bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="text-2xl">📈</div>
                        <div>
                            <h4 class="font-semibold text-green-800 dark:text-green-300">Jaspel Bulan Ini</h4>
                            <p class="text-xl font-bold text-green-600">{{ $formattedMonthly }}</p>
                        </div>
                    </div>
                    
                    {{-- Progress Bar --}}
                    <div class="mt-3">
                        <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                            <span>Progress</span>
                            <span>{{ number_format($progress, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                            <div class="bg-green-600 h-3 rounded-full transition-all duration-300" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Target: {{ $formattedTarget }}</p>
                    </div>
                </div>

                {{-- Quick Stats --}}
                <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl border border-blue-200 dark:border-blue-800">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="text-2xl">🎯</div>
                            <div>
                                <h4 class="font-semibold text-blue-800 dark:text-blue-300">Sisa Hari Kerja</h4>
                                <p class="text-xl font-bold text-blue-600">{{ $remainingDays }} Hari</p>
                            </div>
                        </div>
                        
                        <div class="pt-3 border-t border-blue-200 dark:border-blue-700">
                            <div class="flex justify-between text-sm">
                                <span class="text-blue-700 dark:text-blue-400">Rata-rata per hari:</span>
                                <span class="font-medium text-blue-800 dark:text-blue-300">
                                    Rp {{ number_format($monthlyJaspel / (\Carbon\Carbon::now('Asia/Jakarta')->day ?: 1), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Achievement Badge --}}
            @if($progress >= 80)
                <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg border border-purple-200 dark:border-purple-800 text-center">
                    <div class="text-2xl mb-2">🏆</div>
                    <p class="text-sm font-medium text-purple-700 dark:text-purple-400">Kinerja Excellent!</p>
                    <p class="text-xs text-purple-600 dark:text-purple-500">Anda sudah mencapai {{ number_format($progress, 1) }}% target bulanan</p>
                </div>
            @elseif($progress >= 50)
                <div class="p-4 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 rounded-lg border border-blue-200 dark:border-blue-800 text-center">
                    <div class="text-2xl mb-2">💪</div>
                    <p class="text-sm font-medium text-blue-700 dark:text-blue-400">Kerja Bagus!</p>
                    <p class="text-xs text-blue-600 dark:text-blue-500">{{ number_format($progress, 1) }}% target tercapai, terus semangat!</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>