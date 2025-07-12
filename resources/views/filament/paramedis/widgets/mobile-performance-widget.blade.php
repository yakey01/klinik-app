<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Performa {{ $monthName }}
            </h3>
            
            {{-- Performance Cards --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Procedures Card --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                        <span class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $metrics['procedures'] }}</span>
                    </div>
                    <p class="text-xs text-blue-700 dark:text-blue-300">Tindakan</p>
                </div>
                
                {{-- Patients Card --}}
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <x-heroicon-o-user-group class="w-8 h-8 text-green-600 dark:text-green-400" />
                        <span class="text-2xl font-bold text-green-900 dark:text-green-100">{{ $metrics['patients'] }}</span>
                    </div>
                    <p class="text-xs text-green-700 dark:text-green-300">Pasien</p>
                </div>
                
                {{-- Jaspel Card --}}
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <x-heroicon-o-banknotes class="w-8 h-8 text-amber-600 dark:text-amber-400" />
                        <span class="text-lg font-bold text-amber-900 dark:text-amber-100">{{ number_format($metrics['jaspel'], 0, ',', '.') }}</span>
                    </div>
                    <p class="text-xs text-amber-700 dark:text-amber-300">Jaspel</p>
                </div>
                
                {{-- Attendance Card --}}
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-3">
                    <div class="flex items-center justify-between mb-1">
                        <x-heroicon-o-calendar-days class="w-8 h-8 text-purple-600 dark:text-purple-400" />
                        <span class="text-2xl font-bold text-purple-900 dark:text-purple-100">{{ $attendancePercentage }}%</span>
                    </div>
                    <p class="text-xs text-purple-700 dark:text-purple-300">Kehadiran</p>
                </div>
            </div>
            
            {{-- Mini Chart --}}
            <div>
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tindakan 7 Hari Terakhir</h4>
                <div class="flex items-end justify-between h-16 gap-1">
                    @foreach($trend as $value)
                        @php
                            $height = $value > 0 ? max(20, ($value / max($trend)) * 100) : 10;
                        @endphp
                        <div class="flex-1 bg-primary-200 dark:bg-primary-700 rounded-t" 
                             style="height: {{ $height }}%"
                             title="{{ $value }} tindakan">
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ now()->subDays(6)->format('d') }}</span>
                    <span>Hari ini</span>
                </div>
            </div>
            
            {{-- Quick Actions --}}
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between">
                    <a href="/paramedis/jaspels" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center">
                        <x-heroicon-o-arrow-right class="w-4 h-4 mr-1" />
                        Lihat Detail Jaspel
                    </a>
                    <a href="/paramedis/performances" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center">
                        <x-heroicon-o-chart-bar class="w-4 h-4 mr-1" />
                        Laporan Lengkap
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>