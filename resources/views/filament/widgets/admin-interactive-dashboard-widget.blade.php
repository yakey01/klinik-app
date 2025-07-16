<x-filament-widgets::widget>
    @php
        $medicalSummary = $this->getMedicalSummary();
        $weeklyTrends = $this->getWeeklyTrends();
        $medicalKpi = $this->getMedicalKpiData();
        $topPerformers = $this->getTopPerformers();
    @endphp
    
    <div class="space-y-6">
        <!-- Control Panel -->
        <x-filament::section>
            <div class="p-4">
                {{ $this->form }}
            </div>
        </x-filament::section>

        <!-- Medical KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total Procedures -->
            <x-filament::section>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            🏥 Total Prosedur
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($medicalKpi['procedures']['value']) }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$medicalKpi['procedures']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$medicalKpi['procedures']['trend'] === 'up' ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $medicalKpi['procedures']['trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $medicalKpi['procedures']['change'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-clipboard-document-list"
                            class="w-6 h-6 text-blue-600 dark:text-blue-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Patients -->
            <x-filament::section>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            👥 Total Pasien
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ number_format($medicalKpi['patients']['value']) }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$medicalKpi['patients']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$medicalKpi['patients']['trend'] === 'up' ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $medicalKpi['patients']['trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $medicalKpi['patients']['change'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-user-group"
                            class="w-6 h-6 text-green-600 dark:text-green-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Total Revenue -->
            <x-filament::section>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            💰 Pendapatan
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            Rp {{ number_format($medicalKpi['revenue']['value'], 0, ',', '.') }}
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$medicalKpi['revenue']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$medicalKpi['revenue']['trend'] === 'up' ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $medicalKpi['revenue']['trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $medicalKpi['revenue']['change'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-banknotes"
                            class="w-6 h-6 text-yellow-600 dark:text-yellow-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Success Rate -->
            <x-filament::section>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            ✅ Tingkat Keberhasilan
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $medicalKpi['success_rate']['value'] }}%
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$medicalKpi['success_rate']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$medicalKpi['success_rate']['trend'] === 'up' ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $medicalKpi['success_rate']['trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $medicalKpi['success_rate']['change'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-check-circle"
                            class="w-6 h-6 text-purple-600 dark:text-purple-400"
                        />
                    </div>
                </div>
            </x-filament::section>

            <!-- Utilization Rate -->
            <x-filament::section>
                <div class="flex items-center justify-between p-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            📊 Tingkat Utilisasi
                        </p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $medicalKpi['utilization']['value'] }}%
                        </p>
                        <div class="flex items-center mt-1">
                            <x-filament::icon
                                :icon="$medicalKpi['utilization']['trend'] === 'up' ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down'"
                                :class="$medicalKpi['utilization']['trend'] === 'up' ? 'text-success-500' : 'text-danger-500'"
                                class="w-4 h-4 mr-1"
                            />
                            <span class="text-sm {{ $medicalKpi['utilization']['trend'] === 'up' ? 'text-success-600' : 'text-danger-600' }}">
                                {{ $medicalKpi['utilization']['change'] }}%
                            </span>
                        </div>
                    </div>
                    <div class="p-3 bg-cyan-100 dark:bg-cyan-900 rounded-full">
                        <x-filament::icon
                            icon="heroicon-o-chart-bar"
                            class="w-6 h-6 text-cyan-600 dark:text-cyan-400"
                        />
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Charts and Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Weekly Trends -->
            <x-filament::section>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        📈 Tren Mingguan
                    </h3>
                    <div class="space-y-4">
                        @foreach($weeklyTrends['weeks'] as $index => $week)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $week }}
                                    </div>
                                    <div class="flex items-center space-x-4 mt-2">
                                        <div class="flex items-center space-x-1">
                                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                                            <span class="text-xs text-gray-500">
                                                {{ $weeklyTrends['procedures'][$index] ?? 0 }} prosedur
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-1">
                                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                            <span class="text-xs text-gray-500">
                                                {{ $weeklyTrends['patients'][$index] ?? 0 }} pasien
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($weeklyTrends['revenue'][$index] ?? 0, 0, ',', '.') }}
                                    </div>
                                    <!-- Simple progress bar -->
                                    <div class="progress-bar mt-2">
                                        <div class="progress-bar-fill" style="width: {{ min(($weeklyTrends['revenue'][$index] ?? 0) / (max($weeklyTrends['revenue']) ?: 1) * 100, 100) }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-filament::section>

            <!-- Top Performers -->
            <x-filament::section>
                <div class="p-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                        🏆 Performa Terbaik
                    </h3>
                    
                    <div class="space-y-4">
                        <!-- Top Doctors -->
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                👨‍⚕️ Top Dokter
                            </h4>
                            <div class="space-y-2">
                                @foreach($topPerformers['doctors'] as $doctor)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $doctor['name'] }}
                                            </span>
                                            <span class="text-xs text-gray-500 block">
                                                {{ $doctor['specialty'] }}
                                            </span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-sm font-medium text-blue-600">
                                                {{ $doctor['procedures'] }} prosedur
                                            </span>
                                            <div class="text-xs text-gray-500">
                                                Rp {{ number_format($doctor['revenue'], 0, ',', '.') }}
                                            </div>
                                        </div>
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
                                    <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
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
                </div>
            </x-filament::section>
        </div>

        <!-- Error Handling -->
        @if($this->error)
            <x-filament::section>
                <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                    <div class="flex items-center">
                        <x-filament::icon
                            icon="heroicon-o-exclamation-triangle"
                            class="w-5 h-5 text-red-600 mr-2"
                        />
                        <span class="text-sm text-red-600 dark:text-red-400">
                            {{ $this->message }}
                        </span>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-widgets::widget>