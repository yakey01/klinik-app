<x-filament-panels::page>
    @php
        $financialSummary = $this->getFinancialSummary();
        $validationStats = $this->getValidationStats();
        $recentTransactions = $this->getRecentTransactions();
        $monthlyTrends = $this->getMonthlyTrends();
        $topPerformers = $this->getTopPerformers();
    @endphp
    
    <div class="space-y-6">
        <!-- Financial Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Pendapatan -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Pendapatan
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['pendapatan'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['pendapatan'] >= 0 ? 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-up' : 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-down'"
                                :class="$financialSummary['changes']['pendapatan'] >= 0 ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['pendapatan'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $financialSummary['changes']['pendapatan'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-success-100 dark:bg-success-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-banknotes"
                            class="w-6 h-6 text-success-600 dark:text-success-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Pengeluaran -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Pengeluaran
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['pengeluaran'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['pengeluaran'] >= 0 ? 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-up' : 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-down'"
                                :class="$financialSummary['changes']['pengeluaran'] >= 0 ? 'text-danger-500' : 'text-success-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['pengeluaran'] >= 0 ? 'text-danger-600' : 'text-success-600' }}">
                                {{ $financialSummary['changes']['pengeluaran'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-danger-100 dark:bg-danger-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-arfi-grid fi-grid-cols-auto-trending-down"
                            class="w-6 h-6 text-danger-600 dark:text-danger-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Jaspel -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Total Jaspel
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['jaspel'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['jaspel'] >= 0 ? 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-up' : 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-down'"
                                :class="$financialSummary['changes']['jaspel'] >= 0 ? 'text-warning-500' : 'text-success-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['jaspel'] >= 0 ? 'text-warning-600' : 'text-success-600' }}">
                                {{ $financialSummary['changes']['jaspel'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-warning-100 dark:bg-warning-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-user-group"
                            class="w-6 h-6 text-warning-600 dark:text-warning-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Net Profit -->
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            Net Profit
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($financialSummary['current']['net_profit'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$financialSummary['changes']['net_profit'] >= 0 ? 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-up' : 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-down'"
                                :class="$financialSummary['changes']['net_profit'] >= 0 ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $financialSummary['changes']['net_profit'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $financialSummary['changes']['net_profit'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 {{ $financialSummary['current']['net_profit'] >= 0 ? 'bg-success-100 dark:bg-success-900' : 'bg-danger-100 dark:bg-danger-900' }} rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-chart-bar"
                            class="w-6 h-6 {{ $financialSummary['current']['net_profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}"
                        />
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Validation Statistics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Validation Queue -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Antrian Validasi
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Status validasi transaksi
                    </p>
                </div>
                
                <div class="space-y-4">
                    <!-- Pending Items -->
                    <div class="flex items-center justify-between p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                icon="heroicon-o-clock"
                                class="w-5 h-5 text-warning-600"
                            />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    Menunggu Validasi
                                </p>
                                <div class="text-sm text-gray-500 space-x-2">
                                    <span>{{ $validationStats['pending']['pendapatan'] }} Pendapatan</span>
                                    <span>{{ $validationStats['pending']['pengeluaran'] }} Pengeluaran</span>
                                    <span>{{ $validationStats['pending']['jaspel'] }} Jaspel</span>
                                </div>
                            </div>
                        </div>
                        <x-filament::badge color="warning" size="lg">
                            {{ $validationStats['total_pending'] }}
                        </x-filament::badge>
                    </div>
                    
                    <!-- Approved Items -->
                    <div class="flex items-center justify-between p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                icon="heroicon-o-check-circle"
                                class="w-5 h-5 text-success-600"
                            />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    Sudah Disetujui
                                </p>
                                <div class="text-sm text-gray-500 space-x-2">
                                    <span>{{ $validationStats['approved']['pendapatan'] }} Pendapatan</span>
                                    <span>{{ $validationStats['approved']['pengeluaran'] }} Pengeluaran</span>
                                    <span>{{ $validationStats['approved']['jaspel'] }} Jaspel</span>
                                </div>
                            </div>
                        </div>
                        <x-filament::badge color="success" size="lg">
                            {{ $validationStats['total_approved'] }}
                        </x-filament::badge>
                    </div>
                    
                    <!-- Rejected Items -->
                    <div class="flex items-center justify-between p-4 bg-danger-50 dark:bg-danger-900/20 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <x-filament::icon
                                icon="heroicon-o-x-circle"
                                class="w-5 h-5 text-danger-600"
                            />
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100">
                                    Ditolak
                                </p>
                                <div class="text-sm text-gray-500 space-x-2">
                                    <span>{{ $validationStats['rejected']['pendapatan'] }} Pendapatan</span>
                                    <span>{{ $validationStats['rejected']['pengeluaran'] }} Pengeluaran</span>
                                    <span>{{ $validationStats['rejected']['jaspel'] }} Jaspel</span>
                                </div>
                            </div>
                        </div>
                        <x-filament::badge color="danger" size="lg">
                            {{ $validationStats['total_rejected'] }}
                        </x-filament::badge>
                    </div>
                </div>
            </x-filament::section>

            <!-- Monthly Trends -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Trend 6 Bulan Terakhir
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Perbandingan keuangan bulanan
                    </p>
                </div>
                
                <div class="space-y-3">
                    @foreach($monthlyTrends['months'] as $index => $month)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $month }}
                                </div>
                                <div class="flex items-center space-x-4 mt-1">
                                    <div class="flex items-center space-x-1">
                                        <div class="w-3 h-3 bg-success-500 rounded-full"></div>
                                        <span class="text-xs text-gray-500">
                                            {{ number_format($monthlyTrends['pendapatan'][$index] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-3 h-3 bg-danger-500 rounded-full"></div>
                                        <span class="text-xs text-gray-500">
                                            {{ number_format($monthlyTrends['pengeluaran'][$index] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <div class="w-3 h-3 bg-warning-500 rounded-full"></div>
                                        <span class="text-xs text-gray-500">
                                            {{ number_format($monthlyTrends['jaspel'][$index] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                @php
                                    $net = ($monthlyTrends['pendapatan'][$index] ?? 0) - ($monthlyTrends['pengeluaran'][$index] ?? 0) - ($monthlyTrends['jaspel'][$index] ?? 0);
                                @endphp
                                <div class="text-sm font-semibold {{ $net >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ number_format($net, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <!-- Recent Transactions and Top Performers -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Transactions -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Transaksi Terbaru
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        10 transaksi terakhir
                    </p>
                </div>
                
                <div class="space-y-3">
                    @foreach($recentTransactions as $transaction)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 {{ $transaction['type'] === 'pendapatan' ? 'bg-success-100 dark:bg-success-900' : ($transaction['type'] === 'pengeluaran' ? 'bg-danger-100 dark:bg-danger-900' : 'bg-warning-100 dark:bg-warning-900') }} rounded-full">
                                    <x-filament::icon
                                        :icon="$transaction['type'] === 'pendapatan' ? 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-up' : ($transaction['type'] === 'pengeluaran' ? 'heroicon-o-arfi-grid fi-grid-cols-auto-trending-down' : 'heroicon-o-user-group')"
                                        class="w-4 h-4 {{ $transaction['type'] === 'pendapatan' ? 'text-success-600' : ($transaction['type'] === 'pengeluaran' ? 'text-danger-600' : 'text-warning-600') }}"
                                    />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $transaction['code'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ Str::limit($transaction['description'], 30) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    Rp {{ number_format($transaction['amount'], 0, ',', '.') }}
                                </p>
                                <x-filament::badge
                                    :color="$transaction['status'] === 'pending' ? 'warning' : ($transaction['status'] === 'disetujui' || $transaction['status'] === 'approved' ? 'success' : 'danger')"
                                    size="sm"
                                >
                                    {{ $transaction['status'] }}
                                </x-filament::badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>

            <!-- Top Performers -->
            <x-filament::section>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Performa Terbaik
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Top dokter dan prosedur
                    </p>
                </div>
                
                <div class="space-y-4">
                    <!-- Top Doctors -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            💼 Top Dokter (Jaspel)
                        </h4>
                        <div class="space-y-2">
                            @foreach($topPerformers['doctors'] as $doctor)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $doctor['name'] }}
                                    </span>
                                    <span class="text-sm font-medium text-success-600">
                                        Rp {{ number_format($doctor['total'], 0, ',', '.') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Top Procedures -->
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            🏥 Top Prosedur
                        </h4>
                        <div class="space-y-2">
                            @foreach($topPerformers['procedures'] as $procedure)
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-gray-900 dark:text-gray-100">
                                        {{ $procedure['name'] }}
                                    </span>
                                    <x-filament::badge color="info" size="sm">
                                        {{ $procedure['total'] }}x
                                    </x-filament::badge>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>