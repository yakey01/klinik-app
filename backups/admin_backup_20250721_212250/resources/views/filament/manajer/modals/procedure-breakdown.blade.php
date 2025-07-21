<div class="space-y-6">
    <!-- Performance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">This Month</p>
                    <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">
                        {{ $staff->tindakanAsParamedis()->whereMonth('created_at', now()->month)->count() + 
                           $staff->tindakanAsNonParamedis()->whereMonth('created_at', now()->month)->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-500 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Last Month</p>
                    <p class="text-2xl font-bold text-green-900 dark:text-green-100">
                        {{ $staff->tindakanAsParamedis()->whereMonth('created_at', now()->subMonth()->month)->count() + 
                           $staff->tindakanAsNonParamedis()->whereMonth('created_at', now()->subMonth()->month)->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-500 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Avg Revenue</p>
                    <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">
                        Rp {{ number_format(
                            \App\Models\Pendapatan::whereHas('tindakan', function($query) use ($staff) {
                                $query->where('paramedis_id', $staff->id)->orWhere('non_paramedis_id', $staff->id);
                            })->whereMonth('created_at', now()->month)->avg('nominal') ?? 0
                        ) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Procedure Types Chart -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Procedure Types Distribution</h3>
        <div class="h-64">
            <canvas id="procedureTypesChart"></canvas>
        </div>
    </div>

    <!-- Recent Procedures Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Procedures</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Procedure</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Revenue</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @php
                        $recentProcedures = \App\Models\Tindakan::with(['pasien', 'jenisTindakan', 'pendapatan'])
                            ->where(function($query) use ($staff) {
                                $query->where('paramedis_id', $staff->id)->orWhere('non_paramedis_id', $staff->id);
                            })
                            ->whereMonth('created_at', now()->month)
                            ->latest()
                            ->limit(10)
                            ->get();
                    @endphp
                    
                    @foreach($recentProcedures as $procedure)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $procedure->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $procedure->pasien->nama }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $procedure->jenisTindakan->nama }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    Completed
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                Rp {{ number_format($procedure->pendapatan->sum('nominal')) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-4">Performance Insights</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <h4 class="font-medium text-blue-800 dark:text-blue-200">Strengths</h4>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Consistent monthly performance</li>
                    <li>• High patient satisfaction ratings</li>
                    <li>• Efficient procedure completion</li>
                </ul>
            </div>
            <div class="space-y-2">
                <h4 class="font-medium text-blue-800 dark:text-blue-200">Areas for Improvement</h4>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Increase procedure variety</li>
                    <li>• Focus on revenue optimization</li>
                    <li>• Enhance patient engagement</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    @php
        $procedureTypes = \Illuminate\Support\Facades\DB::table('tindakan')
            ->join('jenis_tindakan', 'tindakan.jenis_tindakan_id', '=', 'jenis_tindakan.id')
            ->where(function($query) use ($staff) {
                $query->where('tindakan.paramedis_id', $staff->id)
                      ->orWhere('tindakan.non_paramedis_id', $staff->id);
            })
            ->whereMonth('tindakan.created_at', now()->month)
            ->whereYear('tindakan.created_at', now()->year)
            ->whereNull('tindakan.deleted_at')
            ->select('jenis_tindakan.nama', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('jenis_tindakan.nama')
            ->orderByDesc('count')
            ->get();
    @endphp

    const ctx = document.getElementById('procedureTypesChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($procedureTypes->pluck('nama')->toArray()) !!},
            datasets: [{
                data: {!! json_encode($procedureTypes->pluck('count')->toArray()) !!},
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(6, 182, 212, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                ],
                borderColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#8B5CF6',
                    '#EF4444',
                    '#06B6D4',
                    '#EC4899',
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });
});
</script>