<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $heading }}
        </x-slot>

        @php
            $budgetData = $this->getBudgetData();
            $budget = $budgetData['budget_tracking'];
            $metrics = $budgetData['financial_metrics'];
            $todayStats = $budgetData['today_stats'];
            $monthProgress = $budgetData['month_progress'];
            $alerts = $budgetData['alerts'];
            $recommendations = $budgetData['recommendations'];
        @endphp

        <div class="space-y-6">
            <!-- Progress Overview -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Income Progress -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Target Pendapatan</h3>
                        <span class="text-xs text-green-600 dark:text-green-400">{{ $budget['income_progress'] }}%</span>
                    </div>
                    <div class="w-full bg-green-200 dark:bg-green-700 rounded-full h-2 mb-2">
                        <div class="bg-green-500 h-2 rounded-full transition-all duration-500" style="width: {{ min($budget['income_progress'], 100) }}%"></div>
                    </div>
                    <p class="text-xs text-green-700 dark:text-green-300">
                        Rp {{ number_format($budget['monthly_income_target'], 0, ',', '.') }} target
                    </p>
                </div>

                <!-- Expense Progress -->
                <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-lg p-4 border border-red-200 dark:border-red-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Batas Pengeluaran</h3>
                        <span class="text-xs text-red-600 dark:text-red-400">{{ $budget['expense_progress'] }}%</span>
                    </div>
                    <div class="w-full bg-red-200 dark:bg-red-700 rounded-full h-2 mb-2">
                        <div class="bg-red-500 h-2 rounded-full transition-all duration-500" style="width: {{ min($budget['expense_progress'], 100) }}%"></div>
                    </div>
                    <p class="text-xs text-red-700 dark:text-red-300">
                        Rp {{ number_format($budget['monthly_expense_limit'], 0, ',', '.') }} limit
                    </p>
                </div>

                <!-- Month Progress -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Progres Bulan</h3>
                        <span class="text-xs text-blue-600 dark:text-blue-400">{{ $monthProgress }}%</span>
                    </div>
                    <div class="w-full bg-blue-200 dark:bg-blue-700 rounded-full h-2 mb-2">
                        <div class="bg-blue-500 h-2 rounded-full transition-all duration-500" style="width: {{ $monthProgress }}%"></div>
                    </div>
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        {{ $budget['days_remaining'] }} hari tersisa
                    </p>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $metrics['profit_margin'] }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Margin Keuntungan</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        {{ $metrics['income_growth'] > 0 ? '+' : '' }}{{ $metrics['income_growth'] }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Pertumbuhan Pendapatan</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($budget['daily_target_needed'], 0, ',', '.') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Target Harian Tersisa</div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-3 border border-gray-200 dark:border-gray-700">
                    <div class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        @php
                            $healthColors = [
                                'excellent' => 'text-green-600',
                                'good' => 'text-green-500',
                                'fair' => 'text-yellow-500',
                                'poor' => 'text-red-500',
                                'unknown' => 'text-gray-500'
                            ];
                        @endphp
                        <span class="{{ $healthColors[$metrics['financial_health']] ?? 'text-gray-500' }}">
                            {{ ucfirst($metrics['financial_health']) }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Kesehatan Keuangan</div>
                </div>
            </div>

            <!-- Alerts Section -->
            @if(count($alerts) > 0)
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 mr-2 text-amber-500"/>
                        Alerts & Notifications
                    </h4>
                    <div class="space-y-2">
                        @foreach($alerts as $alert)
                            @php
                                $alertClasses = [
                                    'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-700 dark:text-green-200',
                                    'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-700 dark:text-yellow-200',
                                    'danger' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-700 dark:text-red-200',
                                ];
                            @endphp
                            <div class="rounded-lg p-3 border {{ $alertClasses[$alert['type']] ?? $alertClasses['warning'] }}">
                                <div class="flex items-start">
                                    <span class="text-lg mr-2">{{ $alert['icon'] }}</span>
                                    <div class="flex-1">
                                        <h5 class="font-medium">{{ $alert['title'] }}</h5>
                                        <p class="text-sm mt-1">{{ $alert['message'] }}</p>
                                        <p class="text-xs mt-2 font-medium">{{ $alert['action'] }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Recommendations Section -->
            @if(count($recommendations) > 0)
                <div class="space-y-3">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-light-bulb class="w-4 h-4 mr-2 text-blue-500"/>
                        Rekomendasi Strategis
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($recommendations as $recommendation)
                            @php
                                $priorityClasses = [
                                    'high' => 'border-l-4 border-l-red-500',
                                    'medium' => 'border-l-4 border-l-yellow-500',
                                    'low' => 'border-l-4 border-l-blue-500',
                                ];
                                $priorityBadgeClasses = [
                                    'high' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                                    'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                                    'low' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
                                ];
                            @endphp
                            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 {{ $priorityClasses[$recommendation['priority']] ?? '' }}">
                                <div class="flex items-start justify-between mb-2">
                                    <h5 class="font-medium text-gray-900 dark:text-gray-100">{{ $recommendation['title'] }}</h5>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $priorityBadgeClasses[$recommendation['priority']] ?? '' }}">
                                        {{ ucfirst($recommendation['priority']) }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                    {{ $recommendation['description'] }}
                                </p>
                                <div class="space-y-1">
                                    <h6 class="text-xs font-medium text-gray-700 dark:text-gray-300">Action Items:</h6>
                                    <ul class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                                        @foreach($recommendation['actions'] as $action)
                                            <li class="flex items-start">
                                                <span class="text-gray-400 mr-2">•</span>
                                                {{ $action }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Today's Performance Summary -->
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center">
                    <x-heroicon-o-calendar-days class="w-4 h-4 mr-2 text-gray-500"/>
                    Performa Hari Ini
                </h4>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-lg font-semibold text-green-600 dark:text-green-400">
                            Rp {{ number_format($todayStats['pendapatan_approved'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Pendapatan</div>
                    </div>
                    <div>
                        <div class="text-lg font-semibold text-red-600 dark:text-red-400">
                            Rp {{ number_format($todayStats['pengeluaran_approved'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Pengeluaran</div>
                    </div>
                    <div>
                        <div class="text-lg font-semibold {{ $todayStats['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            Rp {{ number_format($todayStats['net_income'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Net Income</div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>