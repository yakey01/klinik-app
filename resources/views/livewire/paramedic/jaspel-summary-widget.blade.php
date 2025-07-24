<div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Ringkasan Jaspel Paramedis</h3>
        <button wire:click="refreshData" wire:loading.attr="disabled" class="text-blue-600 hover:text-blue-800">
            <svg wire:loading.remove class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <svg wire:loading class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>

    @if($isLoading)
        <div class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
    @else
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Jaspel -->
            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600">Total Bulan Ini</p>
                        <p class="text-2xl font-bold text-green-900">
                            Rp {{ number_format($currentMonthTotal, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-green-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending -->
            <div class="bg-yellow-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-yellow-600">Menunggu Validasi</p>
                        <p class="text-2xl font-bold text-yellow-900">
                            Rp {{ number_format($pendingAmount, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-yellow-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Approved -->
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-600">Disetujui</p>
                        <p class="text-2xl font-bold text-blue-900">
                            Rp {{ number_format($approvedAmount, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-blue-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Rejected -->
            <div class="bg-red-50 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-red-600">Ditolak</p>
                        <p class="text-2xl font-bold text-red-900">
                            Rp {{ number_format($rejectedAmount, 0, ',', '.') }}
                        </p>
                    </div>
                    <div class="bg-red-100 rounded-full p-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shift Breakdown -->
        @if(!empty($shiftBreakdown))
        <div class="mb-6">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Breakdown per Shift (Disetujui)</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($shiftBreakdown as $shift => $data)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h5 class="text-sm font-medium text-gray-600">Shift {{ $shift }}</h5>
                        <p class="text-lg font-bold text-gray-900 mt-1">
                            Rp {{ number_format($data['total'] ?? 0, 0, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">{{ $data['count'] ?? 0 }} tindakan</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Monthly Trend Chart -->
        <div class="mt-6">
            <h4 class="text-sm font-medium text-gray-700 mb-3">Trend 6 Bulan Terakhir</h4>
            <div class="bg-gray-50 rounded-lg p-4">
                <canvas id="jaspelParamedicTrendChart" class="w-full" height="150"></canvas>
            </div>
        </div>
    @endif

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:load', function () {
            const ctx = document.getElementById('jaspelParamedicTrendChart').getContext('2d');
            
            const trendData = @json($monthlyTrend);
            const labels = trendData.map(item => item.month);
            const data = trendData.map(item => item.total);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jaspel Disetujui',
                        data: data,
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                        notation: 'compact',
                                        compactDisplay: 'short'
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });

            // Update chart on refresh
            window.addEventListener('jaspel-refreshed', function() {
                location.reload();
            });
        });
    </script>
    @endpush
</div>