<x-filament-widgets::widget>
    <x-filament::section>
        @if($error)
            <div class="flex items-center justify-center p-8">
                <div class="text-center">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="w-12 h-12 mx-auto text-danger-500 mb-4"
                    />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ $message }}
                    </h3>
                    <x-filament::button
                        wire:click="refreshData"
                        color="primary"
                        size="sm"
                    >
                        Coba Lagi
                    </x-filament::button>
                </div>
            </div>
        @else
            <!-- Control Panel -->
            <div class="mb-6">
                {{ $this->form }}
            </div>

            <!-- KPI Cards -->
            @php
                $kpiData = $this->getKpiData();
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <!-- Pendapatan Card -->
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Total Pendapatan
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Rp {{ number_format($kpiData['pendapatan']['value'], 0, ',', '.') }}
                            </p>
                            <div class="flex items-center mt-1">
                                <x-filament::icon
                                    :icon="$kpiData['pendapatan']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                    :class="$kpiData['pendapatan']['trend'] === 'up' ? 'text-success-500' : 'text-danger-500'"
                                    class="w-4 h-4 mr-1"
                                />
                                <span class="text-sm {{ $kpiData['pendapatan']['trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $kpiData['pendapatan']['change'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="p-3 bg-success-100 dark:bg-success-900 rounded-full">
                            <x-filament::icon
                                icon="heroicon-o-arrow-trending-up"
                                class="w-6 h-6 text-success-600 dark:text-success-400"
                            />
                        </div>
                    </div>
                </x-filament::card>

                <!-- Pengeluaran Card -->
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Total Pengeluaran
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Rp {{ number_format($kpiData['pengeluaran']['value'], 0, ',', '.') }}
                            </p>
                            <div class="flex items-center mt-1">
                                <x-filament::icon
                                    :icon="$kpiData['pengeluaran']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                    :class="$kpiData['pengeluaran']['trend'] === 'up' ? 'text-danger-500' : 'text-success-500'"
                                    class="w-4 h-4 mr-1"
                                />
                                <span class="text-sm {{ $kpiData['pengeluaran']['trend'] === 'up' ? 'text-danger-600' : 'text-success-600' }}">
                                    {{ $kpiData['pengeluaran']['change'] }}%
                                </span>
                            </div>
                        </div>
                        <div class="p-3 bg-danger-100 dark:bg-danger-900 rounded-full">
                            <x-filament::icon
                                icon="heroicon-o-arrow-trending-down"
                                class="w-6 h-6 text-danger-600 dark:text-danger-400"
                            />
                        </div>
                    </div>
                </x-filament::card>

                <!-- Profit Card -->
                <x-filament::card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                Net Profit
                            </p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                Rp {{ number_format($kpiData['profit']['value'], 0, ',', '.') }}
                            </p>
                            <div class="flex items-center mt-1">
                                <x-filament::icon
                                    :icon="$kpiData['profit']['value'] >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                    :class="$kpiData['profit']['value'] >= 0 ? 'text-success-500' : 'text-danger-500'"
                                    class="w-4 h-4 mr-1"
                                />
                                <span class="text-sm {{ $kpiData['profit']['value'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $kpiData['profit']['value'] >= 0 ? 'Profit' : 'Loss' }}
                                </span>
                            </div>
                        </div>
                        <div class="p-3 {{ $kpiData['profit']['value'] >= 0 ? 'bg-success-100 dark:bg-success-900' : 'bg-danger-100 dark:bg-danger-900' }} rounded-full">
                            <x-filament::icon
                                icon="heroicon-o-banknotes"
                                class="w-6 h-6 {{ $kpiData['profit']['value'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}"
                            />
                        </div>
                    </div>
                </x-filament::card>
            </div>

            <!-- Charts Section - Using Filament Components -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Trend Analysis -->
                <x-filament::card>
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Trend Keuangan
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Perbandingan pendapatan dan pengeluaran {{ $selectedMonths }} bulan terakhir
                        </p>
                    </div>
                    
                    @php
                        $trendData = $this->getMonthlyTrends();
                    @endphp
                    
                    <div class="space-y-3">
                        @foreach($trendData['months'] as $index => $month)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $month }}
                                    </div>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <div class="flex items-center space-x-1">
                                            <div class="w-3 h-3 bg-success-500 rounded-full"></div>
                                            <span class="text-xs text-gray-500">
                                                Rp {{ number_format($trendData['pendapatan'][$index] ?? 0, 0, ',', '.') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <div class="w-3 h-3 bg-danger-500 rounded-full"></div>
                                            <span class="text-xs text-gray-500">
                                                Rp {{ number_format($trendData['pengeluaran'][$index] ?? 0, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    @php
                                        $net = ($trendData['pendapatan'][$index] ?? 0) - ($trendData['pengeluaran'][$index] ?? 0);
                                    @endphp
                                    <div class="text-sm font-semibold {{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        Rp {{ number_format($net, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>

                <!-- Monthly Summary -->
                <x-filament::card>
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Ringkasan Bulanan
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Analisis performa bulan ini
                        </p>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Progress Bars -->
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-500">Pendapatan</span>
                                <span class="font-medium">Rp {{ number_format($kpiData['pendapatan']['value'], 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-success-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ min(($kpiData['pendapatan']['value'] / 50000000) * 100, 100) }}%"
                                ></div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-500">Pengeluaran</span>
                                <span class="font-medium">Rp {{ number_format($kpiData['pengeluaran']['value'], 0, ',', '.') }}</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-danger-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ min(($kpiData['pengeluaran']['value'] / 35000000) * 100, 100) }}%"
                                ></div>
                            </div>
                        </div>
                        
                        <div class="pt-3 border-t">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Net Profit</span>
                                <span class="text-lg font-bold {{ $kpiData['profit']['value'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    Rp {{ number_format($kpiData['profit']['value'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </x-filament::card>
            </div>
        @endif
    </x-filament::section>

</x-filament-widgets::widget>