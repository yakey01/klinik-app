<x-filament-widgets::widget>
    <x-filament::section>
        @if($error)
            <div class="flex items-center justify-center p-8">
                <div class="text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        {{ $message }}
                    </h3>
                    <button 
                        wire:click="refreshData"
                        class="text-primary-600 hover:text-primary-500 font-medium"
                    >
                        Coba Lagi
                    </button>
                </div>
            </div>
        @else
            <!-- Control Panel -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <!-- Period Selector -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Periode:
                        </label>
                        <select 
                            wire:model.live="selectedPeriod"
                            class="text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700"
                        >
                            @foreach($period_options as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Month Range Selector -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Rentang:
                        </label>
                        <select 
                            wire:model.live="selectedMonths"
                            class="text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700"
                        >
                            <option value="6">6 Bulan</option>
                            <option value="12">12 Bulan</option>
                            <option value="18">18 Bulan</option>
                            <option value="24">24 Bulan</option>
                        </select>
                    </div>

                    <!-- Chart Type Selector -->
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Tampilan:
                        </label>
                        <select 
                            wire:model.live="selectedChart"
                            class="text-sm border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700"
                        >
                            @foreach($chart_options as $value => $config)
                                <option value="{{ $value }}">{{ $config['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 ml-auto">
                        <button 
                            wire:click="refreshData"
                            class="flex items-center gap-1 px-3 py-1 text-xs bg-primary-100 text-primary-700 rounded-md hover:bg-primary-200 transition-colors"
                            title="Refresh Data"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                        
                        <button 
                            wire:click="exportChart('{{ $selected_chart }}')"
                            class="flex items-center gap-1 px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors"
                            title="Export Chart"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chart Display Area -->
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                @if($selected_chart === 'overview')
                    <!-- Overview Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Revenue Trend -->
                        @if(isset($charts['revenue_trend']))
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    {{ $charts['revenue_trend']['title'] }}
                                </h3>
                                <div class="h-64">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        @endif

                        <!-- Expense Breakdown -->
                        @if(isset($charts['expense_breakdown']))
                            <div class="bg-gradient-to-r from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20 rounded-lg p-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    {{ $charts['expense_breakdown']['title'] }}
                                </h3>
                                <div class="h-64">
                                    <canvas id="expenseChart"></canvas>
                                </div>
                            </div>
                        @endif

                        <!-- Cash Flow -->
                        @if(isset($charts['cash_flow_analysis']))
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4 lg:col-span-2">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    {{ $charts['cash_flow_analysis']['title'] }}
                                </h3>
                                <div class="h-80">
                                    <canvas id="cashFlowChart"></canvas>
                                </div>
                            </div>
                        @endif
                    </div>
                @elseif(isset($charts[$selected_chart]))
                    <!-- Single Chart Display -->
                    <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900/20 dark:to-slate-900/20 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $charts[$selected_chart]['title'] }}
                            </h2>
                            
                            @if(isset($chart_options[$selected_chart]['description']))
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $chart_options[$selected_chart]['description'] }}
                                </p>
                            @endif
                        </div>
                        
                        <div class="h-96">
                            <canvas id="mainChart"></canvas>
                        </div>
                        
                        <!-- Insights Section -->
                        @if(isset($charts[$selected_chart]['insights']))
                            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <h4 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">
                                    📊 Insights
                                </h4>
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    @foreach($charts[$selected_chart]['insights'] as $key => $value)
                                        <div class="flex justify-between py-1">
                                            <span class="capitalize">{{ str_replace('_', ' ', $key) }}:</span>
                                            <span class="font-medium">
                                                @if(is_numeric($value))
                                                    {{ number_format($value, 2) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- KPI Dashboard -->
                @if($selected_chart === 'kpi_dashboard' && isset($charts['kpi_dashboard']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        @foreach($charts['kpi_dashboard']['widgets'] as $widget)
                            <div class="bg-gradient-to-r from-{{ $widget['color'] }}-50 to-{{ $widget['color'] }}-100 dark:from-{{ $widget['color'] }}-900/20 dark:to-{{ $widget['color'] }}-800/20 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-{{ $widget['color'] }}-700 dark:text-{{ $widget['color'] }}-300 mb-2">
                                    {{ $widget['title'] }}
                                </h4>
                                <div class="text-2xl font-bold text-{{ $widget['color'] }}-900 dark:text-{{ $widget['color'] }}-100">
                                    {{ $widget['value'] }}
                                </div>
                                @if(isset($widget['change']))
                                    <div class="text-xs text-{{ $widget['color'] }}-600 dark:text-{{ $widget['color'] }}-400 mt-1">
                                        {{ $widget['change'] > 0 ? '+' : '' }}{{ number_format($widget['change'], 1) }}%
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Chart Type Grid for Easy Navigation -->
            <div class="mt-6 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                @foreach($chart_options as $type => $config)
                    <button 
                        wire:click="changeChart('{{ $type }}')"
                        class="flex flex-col items-center p-3 text-center rounded-lg border-2 transition-all
                               {{ $selected_chart === $type 
                                  ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}"
                    >
                        <x-dynamic-component 
                            :component="$config['icon']" 
                            class="w-6 h-6 mb-2 {{ $selected_chart === $type ? 'text-primary-600' : 'text-gray-500' }}"
                        />
                        <span class="text-xs font-medium {{ $selected_chart === $type ? 'text-primary-900 dark:text-primary-100' : 'text-gray-700 dark:text-gray-300' }}">
                            {{ $config['label'] }}
                        </span>
                    </button>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    @if(!$error)
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Chart.js initialization and data rendering would go here
            // This would include all the chart configurations from the DataVisualizationService
            
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize charts based on selected chart type and data
                initializeCharts();
            });

            function initializeCharts() {
                @if($selected_chart === 'overview')
                    // Initialize multiple charts for overview
                    initializeRevenueChart();
                    initializeExpenseChart(); 
                    initializeCashFlowChart();
                @else
                    // Initialize single chart
                    initializeMainChart();
                @endif
            }

            function initializeRevenueChart() {
                const ctx = document.getElementById('revenueChart');
                if (!ctx) return;
                
                const data = @json($charts['revenue_trend']['data'] ?? []);
                const options = @json($charts['revenue_trend']['options'] ?? []);
                
                new Chart(ctx, {
                    type: 'line',
                    data: data,
                    options: options
                });
            }

            function initializeExpenseChart() {
                const ctx = document.getElementById('expenseChart');
                if (!ctx) return;
                
                const data = @json($charts['expense_breakdown']['data'] ?? []);
                const options = @json($charts['expense_breakdown']['options'] ?? []);
                
                new Chart(ctx, {
                    type: 'doughnut',
                    data: data,
                    options: options
                });
            }

            function initializeCashFlowChart() {
                const ctx = document.getElementById('cashFlowChart');
                if (!ctx) return;
                
                const data = @json($charts['cash_flow_analysis']['data'] ?? []);
                const options = @json($charts['cash_flow_analysis']['options'] ?? []);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: data,
                    options: options
                });
            }

            function initializeMainChart() {
                const ctx = document.getElementById('mainChart');
                if (!ctx) return;
                
                @if(isset($charts[$selected_chart]))
                    const chartData = @json($charts[$selected_chart]);
                    
                    new Chart(ctx, {
                        type: chartData.type,
                        data: chartData.data,
                        options: chartData.options
                    });
                @endif
            }

            // Listen for Livewire events
            window.addEventListener('chart-exported', event => {
                alert(event.detail.message);
            });

            window.addEventListener('chart-export-failed', event => {
                alert(event.detail.message);
            });

            window.addEventListener('data-refreshed', event => {
                location.reload();
            });
        </script>
        @endpush
    @endif
</x-filament-widgets::widget>