<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Widget Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Team Performance
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Staff efficiency and productivity metrics
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Overall Score:</span>
                <div class="performance-score {{ $performance_metrics['overall_efficiency']['score'] >= 80 ? 'excellent' : ($performance_metrics['overall_efficiency']['score'] >= 60 ? 'good' : 'needs-improvement') }}">
                    {{ round($performance_metrics['overall_efficiency']['score']) }}%
                </div>
            </div>
        </div>

        {{-- Performance Metrics Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {{-- Paramedis Performance --}}
            <div class="performance-card paramedis-card">
                <div class="performance-header">
                    <div class="performance-icon paramedis-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.78 0-2.678-2.153-1.415-3.414l5-5A2 2 0 009 9.172V5L8 4z" />
                        </svg>
                    </div>
                    <div>
                        <div class="performance-title">Paramedis</div>
                        <div class="performance-subtitle">{{ $performance_metrics['paramedis']['staff_count'] }} Staff</div>
                    </div>
                </div>
                <div class="performance-metrics">
                    <div class="metric">
                        <span class="metric-label">Procedures</span>
                        <span class="metric-value">{{ number_format($performance_metrics['paramedis']['procedures']) }}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Revenue</span>
                        <span class="metric-value">Rp {{ number_format($performance_metrics['paramedis']['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Efficiency</span>
                        <span class="metric-value">{{ round($performance_metrics['paramedis']['efficiency'], 1) }}/staff</span>
                    </div>
                </div>
                <div class="performance-score-bar">
                    <div class="score-fill" style="width: {{ $performance_metrics['paramedis']['performance_score'] }}%"></div>
                </div>
            </div>

            {{-- Non-Paramedis Performance --}}
            <div class="performance-card non-paramedis-card">
                <div class="performance-header">
                    <div class="performance-icon non-paramedis-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <div class="performance-title">Non-Paramedis</div>
                        <div class="performance-subtitle">{{ $performance_metrics['non_paramedis']['staff_count'] }} Staff</div>
                    </div>
                </div>
                <div class="performance-metrics">
                    <div class="metric">
                        <span class="metric-label">Procedures</span>
                        <span class="metric-value">{{ number_format($performance_metrics['non_paramedis']['procedures']) }}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Revenue</span>
                        <span class="metric-value">Rp {{ number_format($performance_metrics['non_paramedis']['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Efficiency</span>
                        <span class="metric-value">{{ round($performance_metrics['non_paramedis']['efficiency'], 1) }}/staff</span>
                    </div>
                </div>
                <div class="performance-score-bar">
                    <div class="score-fill" style="width: {{ $performance_metrics['non_paramedis']['performance_score'] }}%"></div>
                </div>
            </div>

            {{-- Dokter Performance --}}
            <div class="performance-card dokter-card">
                <div class="performance-header">
                    <div class="performance-icon dokter-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <div class="performance-title">Dokter</div>
                        <div class="performance-subtitle">{{ $performance_metrics['dokter']['staff_count'] }} Staff</div>
                    </div>
                </div>
                <div class="performance-metrics">
                    <div class="metric">
                        <span class="metric-label">Procedures</span>
                        <span class="metric-value">{{ number_format($performance_metrics['dokter']['procedures']) }}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Revenue</span>
                        <span class="metric-value">Rp {{ number_format($performance_metrics['dokter']['revenue'], 0, ',', '.') }}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">Efficiency</span>
                        <span class="metric-value">{{ round($performance_metrics['dokter']['efficiency'], 1) }}/staff</span>
                    </div>
                </div>
                <div class="performance-score-bar">
                    <div class="score-fill" style="width: {{ $performance_metrics['dokter']['performance_score'] }}%"></div>
                </div>
            </div>
        </div>

        {{-- Team Overview --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Top Performers --}}
            <div class="top-performers-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Top Performers</h4>
                <div class="space-y-3">
                    @foreach($top_performers['paramedis']->take(2) as $performer)
                    <div class="performer-item">
                        <div class="performer-avatar">
                            {{ strtoupper(substr($performer->nama_lengkap, 0, 2)) }}
                        </div>
                        <div class="performer-info">
                            <div class="performer-name">{{ $performer->nama_lengkap }}</div>
                            <div class="performer-role">Paramedis</div>
                        </div>
                        <div class="performer-stats">
                            <div class="stat-badge">{{ $performer->tindakan_as_paramedis_count ?? 0 }} procedures</div>
                        </div>
                    </div>
                    @endforeach
                    
                    @foreach($top_performers['dokter']->take(2) as $performer)
                    <div class="performer-item">
                        <div class="performer-avatar">
                            {{ strtoupper(substr($performer->nama_lengkap, 0, 2)) }}
                        </div>
                        <div class="performer-info">
                            <div class="performer-name">{{ $performer->nama_lengkap }}</div>
                            <div class="performer-role">Dokter</div>
                        </div>
                        <div class="performer-stats">
                            <div class="stat-badge">{{ $performer->tindakan_count ?? 0 }} procedures</div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Efficiency Trend --}}
            <div class="efficiency-trend-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Efficiency Trend</h4>
                <div class="trend-chart">
                    <canvas id="efficiencyTrendChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Custom Styles --}}
    <style>
        .performance-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .performance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .dark .performance-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .performance-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .performance-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .paramedis-icon { background: linear-gradient(135deg, #10B981, #059669); }
        .non-paramedis-icon { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .dokter-icon { background: linear-gradient(135deg, #6366F1, #4F46E5); }

        .performance-title {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .performance-title {
            color: #F9FAFB;
        }

        .performance-subtitle {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .dark .performance-subtitle {
            color: #9CA3AF;
        }

        .performance-metrics {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .metric-label {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
        }

        .dark .metric-label {
            color: #9CA3AF;
        }

        .metric-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .metric-value {
            color: #F9FAFB;
        }

        .performance-score-bar {
            width: 100%;
            height: 4px;
            background: #E5E7EB;
            border-radius: 2px;
            overflow: hidden;
        }

        .score-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #059669);
            transition: width 0.5s ease;
        }

        .performance-score {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .performance-score.excellent {
            background: #D1FAE5;
            color: #065F46;
        }

        .performance-score.good {
            background: #FEF3C7;
            color: #92400E;
        }

        .performance-score.needs-improvement {
            background: #FEE2E2;
            color: #991B1B;
        }

        .dark .performance-score.excellent {
            background: #064E3B;
            color: #A7F3D0;
        }

        .dark .performance-score.good {
            background: #78350F;
            color: #FCD34D;
        }

        .dark .performance-score.needs-improvement {
            background: #7F1D1D;
            color: #FCA5A5;
        }

        .performer-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            border: 1px solid rgba(229, 231, 235, 0.3);
        }

        .dark .performer-item {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.3);
        }

        .performer-avatar {
            width: 2rem;
            height: 2rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .performer-info {
            flex: 1;
        }

        .performer-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .performer-name {
            color: #F9FAFB;
        }

        .performer-role {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .performer-role {
            color: #9CA3AF;
        }

        .stat-badge {
            padding: 0.25rem 0.5rem;
            background: #F3F4F6;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            color: #374151;
        }

        .dark .stat-badge {
            background: #374151;
            color: #D1D5DB;
        }

        .efficiency-trend-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .dark .efficiency-trend-section {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.3);
        }

        .top-performers-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .dark .top-performers-section {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.3);
        }
    </style>

    {{-- Chart Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('efficiencyTrendChart').getContext('2d');
            const efficiencyData = @json($efficiency_data);
            
            const labels = efficiencyData.map(item => item.day);
            const data = efficiencyData.map(item => item.efficiency);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Efficiency %',
                        data: data,
                        borderColor: '#6366F1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</x-filament-widgets::widget>