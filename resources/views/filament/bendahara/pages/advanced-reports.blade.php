<x-filament-panels::page>
    {{-- Quick Stats Section --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium">Pendapatan Hari Ini</p>
                    <p class="text-2xl font-bold">Rp {{ number_format($quickStats['today_income'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-emerald-600 bg-opacity-50 rounded-full">
                    <x-heroicon-o-currency-dollar class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-red-500 to-pink-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Pengeluaran Hari Ini</p>
                    <p class="text-2xl font-bold">Rp {{ number_format($quickStats['today_expenses'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-red-600 bg-opacity-50 rounded-full">
                    <x-heroicon-o-arrow-trending-down class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Net Income Hari Ini</p>
                    <p class="text-2xl font-bold">Rp {{ number_format($quickStats['today_net'] ?? 0, 0, ',', '.') }}</p>
                </div>
                <div class="p-3 bg-blue-600 bg-opacity-50 rounded-full">
                    <x-heroicon-o-chart-bar class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-amber-500 to-orange-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm font-medium">Pending Validasi</p>
                    <p class="text-2xl font-bold">{{ $quickStats['pending_validations'] ?? 0 }}</p>
                </div>
                <div class="p-3 bg-amber-600 bg-opacity-50 rounded-full">
                    <x-heroicon-o-clock class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Progress Target Bulanan</p>
                    <p class="text-2xl font-bold">{{ number_format($quickStats['monthly_target_progress'] ?? 0, 1) }}%</p>
                </div>
                <div class="p-3 bg-purple-600 bg-opacity-50 rounded-full">
                    <x-heroicon-o-clipboard-document-list class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-100 text-sm font-medium">Penggunaan Limit Pengeluaran</p>
                    <p class="text-2xl font-bold">{{ number_format($quickStats['expense_limit_usage'] ?? 0, 1) }}%</p>
                </div>
                <div class="p-3 bg-gray-600 bg-opacity-50 rounded-full">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6" />
                </div>
            </div>
        </div>
    </div>

    {{-- Report Configuration Form --}}
    <div class="mb-8">
        {{ $this->form }}
    </div>

    {{-- Report Content --}}
    @if(!empty($reportData))
        <div class="space-y-6">
            {{-- Report Header --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ $this->getReportTypeLabel() }}
                    </h2>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                    </div>
                </div>

                {{-- Summary Cards --}}
                @if(isset($reportData['summary']))
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        @foreach($reportData['summary'] as $key => $value)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-300 mb-1">
                                    {{ ucwords(str_replace('_', ' ', $key)) }}
                                </p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">
                                    @if(is_numeric($value))
                                        @if(str_contains($key, 'percentage') || str_contains($key, 'rate'))
                                            {{ number_format($value, 1) }}%
                                        @elseif(str_contains($key, 'amount') || str_contains($key, 'total'))
                                            Rp {{ number_format($value, 0, ',', '.') }}
                                        @else
                                            {{ number_format($value, 0, ',', '.') }}
                                        @endif
                                    @else
                                        {{ $value }}
                                    @endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Chart Section --}}
                @if(isset($reportData['charts']) && !empty($reportData['charts']))
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        @foreach($reportData['charts'] as $chartName => $chartData)
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <h3 class="text-md font-semibold text-gray-900 dark:text-white mb-4">
                                    {{ ucwords(str_replace('_', ' ', $chartName)) }}
                                </h3>
                                <div class="h-64">
                                    {{-- You would integrate with a chart library here (Chart.js, etc.) --}}
                                    <div class="flex items-center justify-center h-full text-gray-500 dark:text-gray-400">
                                        <div class="text-center">
                                            <x-heroicon-o-chart-bar class="h-12 w-12 mx-auto mb-2" />
                                            <p>Chart: {{ $chartName }}</p>
                                            <p class="text-xs">{{ count($chartData['data'] ?? []) }} data points</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Data Table --}}
                @if(isset($reportData['data']) && !empty($reportData['data']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    @if(!empty($reportData['data']))
                                        @foreach(array_keys($reportData['data'][0]) as $column)
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                {{ ucwords(str_replace('_', ' ', $column)) }}
                                            </th>
                                        @endforeach
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach(array_slice($reportData['data'], 0, 10) as $row)
                                    <tr>
                                        @foreach($row as $key => $value)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                @if(is_numeric($value) && str_contains($key, 'amount'))
                                                    Rp {{ number_format($value, 0, ',', '.') }}
                                                @elseif(is_numeric($value) && (str_contains($key, 'percentage') || str_contains($key, 'rate')))
                                                    {{ number_format($value, 1) }}%
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        
                        @if(count($reportData['data']) > 10)
                            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-700 text-sm text-gray-500 dark:text-gray-400">
                                Menampilkan 10 dari {{ count($reportData['data']) }} total records. 
                                Download report lengkap untuk melihat semua data.
                            </div>
                        @endif
                    </div>
                @endif

                {{-- Insights Section --}}
                @if(isset($reportData['insights']) && !empty($reportData['insights']))
                    <div class="mt-6 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <h3 class="text-md font-semibold text-blue-900 dark:text-blue-100 mb-3">
                            💡 Insights & Rekomendasi
                        </h3>
                        <ul class="space-y-2">
                            @foreach($reportData['insights'] as $insight)
                                <li class="flex items-start space-x-2 text-sm text-blue-800 dark:text-blue-200">
                                    <x-heroicon-o-light-bulb class="h-4 w-4 mt-0.5 flex-shrink-0" />
                                    <span>{{ $insight }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-12 shadow-sm border border-gray-200 dark:border-gray-700 text-center">
            <x-heroicon-o-document-chart-bar class="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Tidak ada data laporan
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6">
                Pilih jenis laporan dan periode tanggal untuk melihat data.
            </p>
            <button 
                wire:click="loadReport"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                <x-heroicon-o-arrow-path class="h-4 w-4 mr-2" />
                Muat Data
            </button>
        </div>
    @endif

    {{-- Loading Overlay --}}
    <div wire:loading.flex class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-4">
            <div class="flex items-center space-x-4">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <div class="text-gray-900 dark:text-white">
                    <p class="font-medium">Memproses laporan...</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Mohon tunggu sebentar</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

<script>
    // Add any custom JavaScript for charts or interactions
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts if needed
        console.log('Advanced Reports page loaded');
    });
</script>

<style>
    /* Custom styles for the reports page */
    .report-card {
        transition: all 0.3s ease;
    }
    
    .report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    
    .gradient-bg {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
</style>