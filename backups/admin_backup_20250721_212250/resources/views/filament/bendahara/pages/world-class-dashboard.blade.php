<x-filament-panels::page>
    @php
        $financial = $this->getFinancialSummary();
        $validation = $this->getValidationMetrics();
        $trends = $this->getMonthlyTrends();
        $activities = $this->getRecentActivities();
    @endphp

    <!-- World-Class Treasury Dashboard -->
    <div class="space-y-6">
        
        <!-- Core Financial Metrics - 4 Essential Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            
            <!-- Revenue Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    @if($financial['gfi-grid fi-grid-cols-autoth']['revenue'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($financial['gfi-grid fi-grid-cols-autoth']['revenue'] > 0)
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-600 font-medium">+{{ $financial['gfi-grid fi-grid-cols-autoth']['revenue'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-600 font-medium">{{ $financial['gfi-grid fi-grid-cols-autoth']['revenue'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    Rp {{ number_format($financial['current']['revenue'], 0, ',', '.') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue This Month</p>
            </div>

            <!-- Expenses Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    </div>
                    @if($financial['gfi-grid fi-grid-cols-autoth']['expenses'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($financial['gfi-grid fi-grid-cols-autoth']['expenses'] > 0)
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-red-600 font-medium">+{{ $financial['gfi-grid fi-grid-cols-autoth']['expenses'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-emerald-600 font-medium">{{ $financial['gfi-grid fi-grid-cols-autoth']['expenses'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    Rp {{ number_format($financial['current']['expenses'], 0, ',', '.') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Expenses This Month</p>
            </div>

            <!-- Net Income Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                    @if($financial['gfi-grid fi-grid-cols-autoth']['net_income'] != 0)
                        <div class="flex items-center space-x-1 text-sm">
                            @if($financial['gfi-grid fi-grid-cols-autoth']['net_income'] > 0)
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"></path>
                                </svg>
                                <span class="text-emerald-600 font-medium">+{{ $financial['gfi-grid fi-grid-cols-autoth']['net_income'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7l-9.2 9.2M7 7v10h10"></path>
                                </svg>
                                <span class="text-red-600 font-medium">{{ $financial['gfi-grid fi-grid-cols-autoth']['net_income'] }}%</span>
                            @endif
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold {{ $financial['current']['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }} mb-1">
                    Rp {{ number_format($financial['current']['net_income'], 0, ',', '.') }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Net Income This Month</p>
            </div>

            <!-- Validation Performance Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    @if($validation['total_pending'] > 0)
                        <div class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full text-xs font-medium">
                            {{ $validation['total_pending'] }} Pending
                        </div>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
                    {{ $validation['total_approved'] }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Items Validated Today</p>
            </div>
        </div>

        <!-- Financial Trends Chart & Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Monthly Trends Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Financial Trends</h3>
                    <div class="flex items-center space-x-4 text-sm">
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">Revenue</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">Expenses</span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                            <span class="text-gray-600 dark:text-gray-400">Net Income</span>
                        </div>
                    </div>
                </div>
                <div id="financial-trends-chart" style="height: 300px;"></div>
            </div>

            <!-- Recent Activities - Side by Side Layout -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Recent Revenue Activities -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Revenue</h3>
                        <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @php
                            $revenueActivities = array_filter($activities, fn($activity) => $activity['type'] === 'revenue');
                            $revenueActivities = array_slice($revenueActivities, 0, 4);
                        @endphp
                        @forelse($revenueActivities as $activity)
                            <div class="flex items-center justify-between p-3 bg-emerald-50 dark:bg-emerald-900/10 rounded-lg border border-emerald-200 dark:border-emerald-800">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $activity['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $activity['date']->diffForHumans() }} • {{ $activity['user'] }}
                                    </p>
                                </div>
                                <div class="text-right ml-3">
                                    <span class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                        +Rp {{ number_format($activity['amount'], 0, ',', '.') }}
                                    </span>
                                    <div class="text-xs mt-1">
                                        @if($activity['status'] === 'disetujui')
                                            <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">
                                                Approved
                                            </span>
                                        @elseif($activity['status'] === 'pending')
                                            <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full">
                                                Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                <p class="text-sm">No recent revenue</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Recent Expense Activities -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Expenses</h3>
                        <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                            <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="space-y-3">
                        @php
                            $expenseActivities = array_filter($activities, fn($activity) => $activity['type'] === 'expense');
                            $expenseActivities = array_slice($expenseActivities, 0, 4);
                        @endphp
                        @forelse($expenseActivities as $activity)
                            <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/10 rounded-lg border border-red-200 dark:border-red-800">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $activity['title'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $activity['date']->diffForHumans() }} • {{ $activity['user'] }}
                                    </p>
                                </div>
                                <div class="text-right ml-3">
                                    <span class="text-sm font-semibold text-red-600 dark:text-red-400">
                                        -Rp {{ number_format($activity['amount'], 0, ',', '.') }}
                                    </span>
                                    <div class="text-xs mt-1">
                                        @if($activity['status'] === 'disetujui' || $activity['status'] === 'approved')
                                            <span class="px-2 py-1 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-full">
                                                Approved
                                            </span>
                                        @elseif($activity['status'] === 'pending')
                                            <span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 rounded-full">
                                                Pending
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                                <p class="text-sm">No recent expenses</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ApexCharts Integration -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Financial Trends Chart
            const chartOptions = {
                series: [{
                    name: 'Revenue',
                    data: @json($trends['data']['revenue'])
                }, {
                    name: 'Expenses', 
                    data: @json($trends['data']['expenses'])
                }, {
                    name: 'Net Income',
                    data: @json($trends['data']['net_income'])
                }],
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent'
                },
                colors: ['#10b981', '#ef4444', '#f59e0b'],
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                xaxis: {
                    categories: @json($trends['labels']),
                    labels: {
                        style: {
                            colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                        },
                        formatter: function(value) {
                            if (value >= 1000000) {
                                return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return 'Rp ' + (value / 1000).toFixed(0) + 'K';
                            }
                            return 'Rp ' + value.toLocaleString();
                        }
                    }
                },
                grid: {
                    borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb',
                    strokeDashArray: 5
                },
                legend: {
                    show: false
                },
                tooltip: {
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    y: {
                        formatter: function(value) {
                            return 'Rp ' + value.toLocaleString();
                        }
                    }
                },
                markers: {
                    size: 4,
                    hover: {
                        size: 6
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector("#financial-trends-chart"), chartOptions);
            chart.render();

            // Handle dark mode changes
            const bendaharaObserver = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        chart.destroy();
                        const newChart = new ApexCharts(document.querySelector("#financial-trends-chart"), {
                            ...chartOptions,
                            grid: {
                                ...chartOptions.grid,
                                borderColor: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                            },
                            xaxis: {
                                ...chartOptions.xaxis,
                                labels: {
                                    style: {
                                        colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                                    }
                                }
                            },
                            yaxis: {
                                ...chartOptions.yaxis,
                                labels: {
                                    ...chartOptions.yaxis.labels,
                                    style: {
                                        colors: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                                    }
                                }
                            },
                            tooltip: {
                                ...chartOptions.tooltip,
                                theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                            }
                        });
                        newChart.render();
                    }
                });
            });

            bendaharaObserver.observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    </script>
</x-filament-panels::page>