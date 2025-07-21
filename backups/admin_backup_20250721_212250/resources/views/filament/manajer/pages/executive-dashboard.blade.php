<x-filament-panels::page>
    @php
        $executiveKPIs = $this->getExecutiveKPIs();
        $financialTrends = $this->getFinancialTrends();
        $operationalMetrics = $this->getOperationalMetrics();
        $performanceIndicators = $this->getPerformanceIndicators();
        $strategicInsights = $this->getStrategicInsights();
    @endphp

    <!-- Executive KPI Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <!-- Financial Health -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Revenue Bulan Ini</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($executiveKPIs['current']['revenue'], 0, ',', '.') }}</p>
                    <p class="text-sm {{ $executiveKPIs['changes']['revenue'] >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ $executiveKPIs['changes']['revenue'] >= 0 ? '+' : '' }}{{ number_format($executiveKPIs['changes']['revenue'], 1) }}% dari bulan lalu
                    </p>
                </div>
            </div>
        </div>

        <!-- Profitability -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 {{ $executiveKPIs['current']['net_profit'] >= 0 ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-red-100 dark:bg-red-900/30' }} rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 {{ $executiveKPIs['current']['net_profit'] >= 0 ? 'text-blue-600 dark:text-blue-300' : 'text-red-600 dark:text-red-300' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Net Profit</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($executiveKPIs['current']['net_profit'], 0, ',', '.') }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        Margin: {{ number_format($executiveKPIs['current']['profit_margin'], 1) }}%
                    </p>
                </div>
            </div>
        </div>

        <!-- Operational Efficiency -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Pasien Bulan Ini</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($executiveKPIs['current']['patients']) }}</p>
                    <p class="text-sm {{ $executiveKPIs['changes']['patients'] >= 0 ? 'text-green-600 dark:text-green-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ $executiveKPIs['changes']['patients'] >= 0 ? '+' : '' }}{{ number_format($executiveKPIs['changes']['patients'], 1) }}% dari bulan lalu
                    </p>
                </div>
            </div>
        </div>

        <!-- Gfi-grid fi-grid-cols-autoth Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Avg Revenue per Patient</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp {{ number_format($executiveKPIs['current']['avg_revenue_per_patient'], 0, ',', '.') }}</p>
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        {{ number_format($executiveKPIs['current']['procedures']) }} tindakan
                    </p>
                </div>
            </div>
        </div>

        <!-- Strategic Goals - Revenue Target -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-amber-600 dark:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Revenue Target Progress</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($performanceIndicators['revenue_progress'], 1) }}%</p>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-2">
                        <div class="bg-amber-600 dark:bg-amber-500 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $performanceIndicators['revenue_progress']) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Risk Indicators - Staff Utilization -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 p-6 transition-all duration-200">
            <div class="flex items-center">
                <div class="p-2 bg-teal-100 dark:bg-teal-900/30 rounded-lg transition-colors duration-200">
                    <svg class="w-8 h-8 text-teal-600 dark:text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-200">Staff Utilization</h3>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($operationalMetrics['staff_utilization'], 1) }}%</p>
                    <p class="text-sm text-gray-500 dark:text-gray-300">
                        {{ $operationalMetrics['total_staff'] }} staff aktif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Financial Trends Chart -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Financial Trends - 6 Bulan Terakhir</h3>
            </div>
            <div class="p-6">
                <div id="financialTrendsChart" style="height: 350px;" class="chart-fi-container"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Performance Indicators -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Target Achievement</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                            <span>Revenue Target</span>
                            <span>{{ number_format($performanceIndicators['revenue_progress'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-green-600 dark:bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $performanceIndicators['revenue_progress']) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Rp {{ number_format($performanceIndicators['revenue_achieved'], 0, ',', '.') }} / Rp {{ number_format($performanceIndicators['revenue_target'], 0, ',', '.') }}
                        </p>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                            <span>Patient Target</span>
                            <span>{{ number_format($performanceIndicators['patient_progress'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full transition-all duration-300" style="width: {{ min(100, $performanceIndicators['patient_progress']) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ number_format($performanceIndicators['patient_achieved']) }} / {{ number_format($performanceIndicators['patient_target']) }} pasien
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Procedures -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Procedures</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($strategicInsights['top_procedures'] as $index => $procedure)
                    <div class="flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-lg transition-colors duration-200">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center text-sm font-medium text-blue-600 dark:text-blue-300">
                                {{ $index + 1 }}
                            </div>
                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white">{{ $procedure['name'] }}</span>
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-300">{{ $procedure['count'] }} times</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Revenue by Service -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow dark:shadow-lg border dark:border-gray-700 transition-all duration-200">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Revenue by Service</h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($strategicInsights['revenue_by_service'] as $service)
                    <div class="hover:bg-gray-50 dark:hover:bg-gray-700/50 p-2 rounded-lg transition-colors duration-200">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-200">
                            <span>{{ $service['service'] }}</span>
                            <span>{{ $service['percentage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mt-1">
                            <div class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300" style="width: {{ $service['percentage'] }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Rp {{ number_format($service['revenue'], 0, ',', '.') }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Dark mode detection and chart theme management
        function detectDarkMode() {
            return document.documentElement.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function getChartTheme() {
            const isDark = detectDarkMode();
            
            return {
                colors: isDark ? 
                    ['#34d399', '#f87171', '#60a5fa', '#a78bfa'] : // Dark mode colors
                    ['#10B981', '#EF4444', '#3B82F6', '#8B5CF6'],  // Light mode colors
                chart: {
                    background: 'transparent',
                    foreColor: isDark ? '#ffffff' : '#374151'
                },
                grid: {
                    borderColor: isDark ? '#6b7280' : '#F3F4F6',
                    strokeDashArray: 3,
                    xaxis: {
                        lines: {
                            show: true
                        }
                    },
                    yaxis: {
                        lines: {
                            show: true
                        }
                    }
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    },
                    axisBorder: {
                        color: isDark ? '#6b7280' : '#e5e7eb'
                    },
                    axisTicks: {
                        color: isDark ? '#6b7280' : '#e5e7eb'
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#f3f4f6' : '#6b7280'
                        }
                    }
                },
                legend: {
                    labels: {
                        colors: isDark ? '#ffffff' : '#374151',
                        useSeriesColors: false
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    style: {
                        fontSize: '12px'
                    }
                }
            };
        }

        // Financial Trends Chart with dynamic theme
        function createFinancialChart() {
            const theme = getChartTheme();
            
            const financialOptions = {
                chart: {
                    type: 'line',
                    height: 350,
                    toolbar: {
                        show: false
                    },
                    background: theme.chart.background,
                    foreColor: theme.chart.foreColor,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: true,
                            delay: 150
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 350
                        }
                    }
                },
                series: [
                    {
                        name: 'Revenue',
                        data: @json($financialTrends['revenue'])
                    },
                    {
                        name: 'Expenses',
                        data: @json($financialTrends['expenses'])
                    },
                    {
                        name: 'Net Profit',
                        data: @json($financialTrends['net_profit'])
                    },
                    {
                        name: 'Profit Margin (%)',
                        data: @json($financialTrends['profit_margin'])
                    }
                ],
                xaxis: {
                    categories: @json($financialTrends['months']),
                    labels: {
                        style: theme.xaxis.labels.style
                    },
                    axisBorder: {
                        show: true,
                        color: theme.xaxis.axisBorder.color
                    },
                    axisTicks: {
                        show: true,
                        color: theme.xaxis.axisTicks.color
                    }
                },
                yaxis: [
                    {
                        seriesName: 'Revenue',
                        labels: {
                            style: theme.yaxis.labels.style,
                            formatter: function (value) {
                                if (value >= 1000000) {
                                    return (value / 1000000).toFixed(1) + 'M';
                                } else if (value >= 1000) {
                                    return (value / 1000).toFixed(0) + 'K';
                                }
                                return value;
                            }
                        }
                    },
                    {
                        seriesName: 'Profit Margin (%)',
                        opposite: true,
                        labels: {
                            style: theme.yaxis.labels.style,
                            formatter: function (value) {
                                return value.toFixed(1) + '%';
                            }
                        }
                    }
                ],
                colors: theme.colors,
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    lineCap: 'round'
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    floating: false,
                    offsetY: -10,
                    labels: theme.legend.labels,
                    markers: {
                        width: 8,
                        height: 8,
                        radius: 4
                    }
                },
                grid: theme.grid,
                tooltip: {
                    theme: theme.tooltip.theme,
                    style: theme.tooltip.style,
                    shared: true,
                    intersect: false,
                    x: {
                        show: true
                    },
                    y: [{
                        formatter: function (value, { seriesIndex }) {
                            if (seriesIndex === 3) { // Profit Margin
                                return value.toFixed(1) + '%';
                            } else { // Revenue, Expenses, Net Profit
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }]
                },
                markers: {
                    size: 4,
                    colors: theme.colors,
                    strokeColors: detectDarkMode() ? '#1f2937' : '#ffffff',
                    strokeWidth: 2,
                    hover: {
                        size: 6
                    }
                }
            };

            return new ApexCharts(document.querySelector("#financialTrendsChart"), financialOptions);
        }

        // Initialize chart
        let financialChart = createFinancialChart();
        financialChart.render();

        // Theme change detection and chart update
        function updateChartTheme() {
            if (financialChart) {
                financialChart.destroy();
                financialChart = createFinancialChart();
                financialChart.render();
            }
        }

        // Listen for theme changes
        const manajerObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'class' && 
                    mutation.target === document.documentElement) {
                    
                    // Debounce the update to avoid too frequent redraws
                    clearTimeout(window.chartUpdateTimeout);
                    window.chartUpdateTimeout = setTimeout(updateChartTheme, 100);
                }
            });
        });

        // Start observing theme changes
        manajerObserver.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            setTimeout(updateChartTheme, 100);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (observer) observer.disconnect();
            if (financialChart) financialChart.destroy();
        });
    </script>
    @endpush
</x-filament-panels::page>