<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Widget Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Strategic Metrics
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Strategic goals tracking and future projections
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Overall Progress:</span>
                <div class="progress-indicator">
                    @php
                        $totalProgress = collect($strategic_goals)->avg('progress');
                    @endphp
                    {{ round($totalProgress) }}%
                </div>
            </div>
        </div>

        {{-- Strategic Goals --}}
        <div class="mb-6">
            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Strategic Goals Progress</h4>
            <div class="goals-grid">
                @foreach($strategic_goals as $key => $goal)
                <div class="goal-card">
                    <div class="goal-header">
                        <div class="goal-icon {{ $goal['status'] }}">
                            @if($goal['status'] === 'achieved')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($goal['status'] === 'on-track')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                            @endif
                        </div>
                        <div class="goal-info">
                            <div class="goal-label">{{ $goal['label'] }}</div>
                            <div class="goal-status">{{ ucfirst(str_replace('-', ' ', $goal['status'])) }}</div>
                        </div>
                    </div>
                    
                    <div class="goal-metrics">
                        <div class="metric-row">
                            <span class="metric-label">Target:</span>
                            <span class="metric-value">{{ $goal['unit'] }}{{ number_format($goal['target']) }}</span>
                        </div>
                        <div class="metric-row">
                            <span class="metric-label">Actual:</span>
                            <span class="metric-value">{{ $goal['unit'] }}{{ number_format($goal['actual']) }}</span>
                        </div>
                    </div>
                    
                    <div class="goal-progress">
                        <div class="progress-bar">
                            <div class="progress-fill {{ $goal['status'] }}" 
                                 style="width: {{ min($goal['progress'], 100) }}%"></div>
                        </div>
                        <div class="progress-text">{{ round($goal['progress']) }}% Complete</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Growth & Market Position --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Growth Metrics --}}
            <div class="growth-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Growth Trajectory</h4>
                <div class="growth-cards">
                    <div class="growth-card">
                        <div class="growth-header">
                            <div class="growth-icon monthly">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                            <div class="growth-info">
                                <div class="growth-label">Monthly Growth</div>
                                <div class="growth-value {{ $growth_metrics['monthly_revenue_growth'] >= 0 ? 'positive' : 'negative' }}">
                                    {{ $growth_metrics['monthly_revenue_growth'] >= 0 ? '+' : '' }}{{ $growth_metrics['monthly_revenue_growth'] }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="growth-card">
                        <div class="growth-header">
                            <div class="growth-icon yearly">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div class="growth-info">
                                <div class="growth-label">Yearly Growth</div>
                                <div class="growth-value {{ $growth_metrics['yearly_revenue_growth'] >= 0 ? 'positive' : 'negative' }}">
                                    {{ $growth_metrics['yearly_revenue_growth'] >= 0 ? '+' : '' }}{{ $growth_metrics['yearly_revenue_growth'] }}%
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="growth-card">
                        <div class="growth-header">
                            <div class="growth-icon patients">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="growth-info">
                                <div class="growth-label">Patient Growth</div>
                                <div class="growth-value {{ $growth_metrics['patient_growth'] >= 0 ? 'positive' : 'negative' }}">
                                    {{ $growth_metrics['patient_growth'] >= 0 ? '+' : '' }}{{ $growth_metrics['patient_growth'] }}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Growth Chart --}}
                <div class="growth-chart">
                    <canvas id="growthTrendChart" width="400" height="200"></canvas>
                </div>
            </div>

            {{-- Market Position --}}
            <div class="market-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Market Position</h4>
                <div class="market-metrics">
                    <div class="market-card">
                        <div class="market-header">
                            <div class="market-icon share">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                                </svg>
                            </div>
                            <div class="market-info">
                                <div class="market-label">Market Share</div>
                                <div class="market-value">{{ $market_position['market_share'] }}%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="market-card">
                        <div class="market-header">
                            <div class="market-icon satisfaction">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="market-info">
                                <div class="market-label">Customer Satisfaction</div>
                                <div class="market-value">{{ $market_position['customer_satisfaction'] }}%</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="market-card">
                        <div class="market-header">
                            <div class="market-icon quality">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div class="market-info">
                                <div class="market-label">Service Rating</div>
                                <div class="market-value">{{ $market_position['service_quality_rating'] }}/5</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="competitive-position">
                        <div class="position-label">Competitive Position</div>
                        <div class="position-badge {{ $market_position['competitive_position'] }}">
                            {{ ucfirst($market_position['competitive_position']) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Future Projections --}}
        <div class="projections-section">
            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Future Projections</h4>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="projections-chart">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Revenue Forecast (6 Months)</h5>
                    <canvas id="projectionChart" width="300" height="200"></canvas>
                </div>
                
                <div class="confidence-metrics">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Confidence Level</h5>
                    <div class="confidence-indicator">
                        <div class="confidence-circle">
                            <div class="confidence-percentage">{{ $future_projections['confidence_level'] }}%</div>
                            <div class="confidence-label">Confidence</div>
                        </div>
                    </div>
                </div>
                
                <div class="assumptions-list">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Key Assumptions</h5>
                    <div class="assumptions">
                        @foreach($future_projections['key_assumptions'] as $assumption)
                        <div class="assumption-item">
                            <div class="assumption-bullet"></div>
                            <span class="assumption-text">{{ $assumption }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Custom Styles --}}
    <style>
        .progress-indicator {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
            color: white;
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
        }

        .goal-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .goal-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .dark .goal-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .goal-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .goal-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .goal-icon.achieved {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .goal-icon.on-track {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .goal-icon.needs-attention {
            background: linear-gradient(135deg, #EF4444, #DC2626);
        }

        .goal-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .goal-label {
            color: #F9FAFB;
        }

        .goal-status {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .goal-status {
            color: #9CA3AF;
        }

        .goal-metrics {
            margin-bottom: 1rem;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            font-size: 0.75rem;
            color: #6B7280;
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

        .goal-progress {
            margin-top: 1rem;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #E5E7EB;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.5s ease;
        }

        .progress-fill.achieved {
            background: linear-gradient(90deg, #10B981, #059669);
        }

        .progress-fill.on-track {
            background: linear-gradient(90deg, #F59E0B, #D97706);
        }

        .progress-fill.needs-attention {
            background: linear-gradient(90deg, #EF4444, #DC2626);
        }

        .progress-text {
            font-size: 0.75rem;
            color: #6B7280;
            text-align: center;
        }

        .dark .progress-text {
            color: #9CA3AF;
        }

        .growth-section, .market-section, .projections-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .dark .growth-section, .dark .market-section, .dark .projections-section {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.3);
        }

        .growth-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .growth-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 8px;
            padding: 1rem;
        }

        .dark .growth-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .growth-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .growth-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .growth-icon.monthly {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .growth-icon.yearly {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
        }

        .growth-icon.patients {
            background: linear-gradient(135deg, #8B5CF6, #7C3AED);
        }

        .growth-label {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
        }

        .dark .growth-label {
            color: #9CA3AF;
        }

        .growth-value {
            font-size: 1rem;
            font-weight: 700;
        }

        .growth-value.positive {
            color: #10B981;
        }

        .growth-value.negative {
            color: #EF4444;
        }

        .market-metrics {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .market-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 8px;
            padding: 1rem;
        }

        .dark .market-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .market-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .market-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .market-icon.share {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
        }

        .market-icon.satisfaction {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .market-icon.quality {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .market-label {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
        }

        .dark .market-label {
            color: #9CA3AF;
        }

        .market-value {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .market-value {
            color: #F9FAFB;
        }

        .competitive-position {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(229, 231, 235, 0.5);
            text-align: center;
        }

        .position-label {
            font-size: 0.75rem;
            color: #6B7280;
            margin-bottom: 0.5rem;
        }

        .dark .position-label {
            color: #9CA3AF;
        }

        .position-badge {
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .position-badge.strong {
            background: #D1FAE5;
            color: #065F46;
        }

        .dark .position-badge.strong {
            background: #064E3B;
            color: #A7F3D0;
        }

        .confidence-indicator {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }

        .confidence-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: conic-gradient(#6366F1 0deg, #6366F1 {{ $future_projections['confidence_level'] * 3.6 }}deg, #E5E7EB {{ $future_projections['confidence_level'] * 3.6 }}deg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .confidence-circle::before {
            content: '';
            position: absolute;
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
        }

        .confidence-percentage {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            z-index: 1;
        }

        .confidence-label {
            font-size: 0.625rem;
            color: #6B7280;
            z-index: 1;
        }

        .dark .confidence-circle::before {
            background: rgba(55, 65, 81, 0.9);
        }

        .dark .confidence-percentage {
            color: #F9FAFB;
        }

        .dark .confidence-label {
            color: #9CA3AF;
        }

        .assumptions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .assumption-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .assumption-bullet {
            width: 0.375rem;
            height: 0.375rem;
            border-radius: 50%;
            background: #6366F1;
            margin-top: 0.375rem;
            flex-shrink: 0;
        }

        .assumption-text {
            font-size: 0.75rem;
            color: #6B7280;
            line-height: 1.4;
        }

        .dark .assumption-text {
            color: #9CA3AF;
        }
    </style>

    {{-- Chart Scripts --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Growth Trend Chart
            const growthCtx = document.getElementById('growthTrendChart').getContext('2d');
            const growthData = @json($growth_metrics['growth_trajectory']);
            
            const growthLabels = growthData.map(item => item.month);
            const revenueData = growthData.map(item => item.revenue);
            
            new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: growthLabels,
                    datasets: [{
                        label: 'Revenue Trend',
                        data: revenueData,
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
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID', {notation: 'compact'}).format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Projection Chart
            const projectionCtx = document.getElementById('projectionChart').getContext('2d');
            const projectionData = @json($future_projections['revenue_projections']);
            
            const projectionLabels = projectionData.map(item => item.month);
            const projectedRevenue = projectionData.map(item => item.projected_revenue);
            
            new Chart(projectionCtx, {
                type: 'bar',
                data: {
                    labels: projectionLabels,
                    datasets: [{
                        label: 'Projected Revenue',
                        data: projectedRevenue,
                        backgroundColor: 'rgba(99, 102, 241, 0.7)',
                        borderColor: '#6366F1',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID', {notation: 'compact'}).format(value);
                                }
                            }
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