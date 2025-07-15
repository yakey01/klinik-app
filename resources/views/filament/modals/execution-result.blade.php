<div class="space-y-6">
    <!-- Execution Info -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                <p class="text-lg font-semibold text-green-600">{{ ucfirst($execution->status) }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $execution->getFormattedExecutionTime() }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Results</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ number_format($execution->result_count ?? 0) }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Memory</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $execution->memory_usage ? number_format($execution->memory_usage / 1024 / 1024, 2) . 'MB' : 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Results Display -->
    @if($data)
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Results</h3>
            </div>
            
            <div class="p-4">
                @if($execution->report->report_type === 'table')
                    <!-- Table Results -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            @if(!empty($data) && is_array($data))
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        @foreach(array_keys($data[0] ?? []) as $header)
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                {{ $header }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($data as $row)
                                        <tr>
                                            @foreach($row as $cell)
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                                    {{ $cell }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            @endif
                        </table>
                    </div>
                @elseif($execution->report->report_type === 'chart')
                    <!-- Chart Results -->
                    <div class="h-96">
                        <canvas id="reportChart"></canvas>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const ctx = document.getElementById('reportChart').getContext('2d');
                            const chartData = @json($data);
                            
                            new Chart(ctx, {
                                type: '{{ $execution->report->chart_config['type'] ?? 'bar' }}',
                                data: chartData,
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: {
                                        title: {
                                            display: true,
                                            text: '{{ $execution->report->name }}'
                                        }
                                    }
                                }
                            });
                        });
                    </script>
                @elseif($execution->report->report_type === 'kpi')
                    <!-- KPI Results -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($data as $kpi)
                            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold">{{ substr($kpi['label'] ?? '', 0, 1) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ $kpi['label'] ?? 'KPI' }}
                                        </div>
                                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ $kpi['value'] ?? 0 }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Raw JSON Results -->
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">No data available</p>
        </div>
    @endif
</div>