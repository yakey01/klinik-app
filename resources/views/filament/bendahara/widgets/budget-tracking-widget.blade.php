<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Budget Overview -->
            @php
                $budgetData = $this->getBudgetData();
            @endphp
            
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                    Budget Tracking - {{ now()->format('F Y') }}
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Pendapatan Progress -->
                    <x-filament::card>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                    Pendapatan Target
                                </h4>
                                <x-filament::badge
                                    :color="$budgetData['status']['pendapatan']['color']"
                                >
                                    {{ $budgetData['status']['pendapatan']['label'] }}
                                </x-filament::badge>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Target</span>
                                    <span class="font-medium">Rp {{ number_format($budgetData['budget']['target_pendapatan'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Actual</span>
                                    <span class="font-medium">Rp {{ number_format($budgetData['actual']['pendapatan'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-success-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $budgetData['percentages']['pendapatan'] }}%"
                                ></div>
                            </div>
                            
                            <div class="text-right">
                                <span class="text-2xl font-bold text-success-600">
                                    {{ number_format($budgetData['percentages']['pendapatan'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </x-filament::card>
                    
                    <!-- Pengeluaran Progress -->
                    <x-filament::card>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">
                                    Pengeluaran Budget
                                </h4>
                                <x-filament::badge
                                    :color="$budgetData['status']['pengeluaran']['color']"
                                >
                                    {{ $budgetData['status']['pengeluaran']['label'] }}
                                </x-filament::badge>
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Budget</span>
                                    <span class="font-medium">Rp {{ number_format($budgetData['budget']['target_pengeluaran'], 0, ',', '.') }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Actual</span>
                                    <span class="font-medium">Rp {{ number_format($budgetData['actual']['pengeluaran'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-danger-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $budgetData['percentages']['pengeluaran'] }}%"
                                ></div>
                            </div>
                            
                            <div class="text-right">
                                <span class="text-2xl font-bold text-danger-600">
                                    {{ number_format($budgetData['percentages']['pengeluaran'], 1) }}%
                                </span>
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            </div>
            
            <!-- Alerts -->
            @if(!empty($budgetData['alerts']))
                <div class="space-y-2">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">
                        Budget Alerts
                    </h4>
                    @foreach($budgetData['alerts'] as $alert)
                        <x-filament::card>
                            <div class="flex items-start space-x-3">
                                <x-filament::icon
                                    :icon="$alert['type'] === 'success' ? 'heroicon-o-check-circle' : ($alert['type'] === 'warning' ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-exclamation-circle')"
                                    :class="$alert['type'] === 'success' ? 'text-success-500' : ($alert['type'] === 'warning' ? 'text-warning-500' : 'text-danger-500')"
                                    class="w-5 h-5 mt-0.5"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $alert['message'] }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $alert['action'] }}
                                    </p>
                                </div>
                            </div>
                        </x-filament::card>
                    @endforeach
                </div>
            @endif
            
            <!-- Quarterly Comparison -->
            @php
                $quarterlyData = $this->getQuarterlyComparison();
            @endphp
            
            <div>
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-4">
                    Quarterly Performance
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    @foreach($quarterlyData as $quarter)
                        <x-filament::card>
                            <div class="text-center">
                                <h5 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $quarter['quarter'] }}
                                </h5>
                                <div class="mt-2 space-y-1">
                                    <div class="text-sm text-gray-500">
                                        Pendapatan
                                    </div>
                                    <div class="text-lg font-bold text-success-600">
                                        Rp {{ number_format($quarter['pendapatan'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Pengeluaran
                                    </div>
                                    <div class="text-lg font-bold text-danger-600">
                                        Rp {{ number_format($quarter['pengeluaran'], 0, ',', '.') }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Net
                                    </div>
                                    <div class="text-lg font-bold {{ $quarter['net'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                        Rp {{ number_format($quarter['net'], 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </x-filament::card>
                    @endforeach
                </div>
            </div>
            
            <!-- Category Breakdown -->
            @php
                $categoryData = $this->getCategoryBreakdown();
            @endphp
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Pengeluaran by Category -->
                <x-filament::card>
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                            Pengeluaran by Category
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Breakdown pengeluaran bulan ini
                        </p>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($categoryData['pengeluaran'] as $category)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-{{ $category['color'] }}-500"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $category['category'] }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    Rp {{ number_format($category['amount'], 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
                
                <!-- Pendapatan by Source -->
                <x-filament::card>
                    <div class="mb-4">
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">
                            Pendapatan by Source
                        </h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Breakdown pendapatan bulan ini
                        </p>
                    </div>
                    
                    <div class="space-y-3">
                        @foreach($categoryData['pendapatan'] as $source)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-{{ $source['color'] }}-500"></div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $source['source'] }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    Rp {{ number_format($source['amount'], 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-filament::card>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>