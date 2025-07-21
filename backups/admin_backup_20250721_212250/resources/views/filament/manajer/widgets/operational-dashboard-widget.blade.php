<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Widget Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Operational Dashboard
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Real-time operational metrics and capacity monitoring
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">System Health:</span>
                <div class="health-indicator {{ $capacity_metrics['status'] }}">
                    {{ ucfirst($capacity_metrics['status']) }}
                </div>
            </div>
        </div>

        {{-- Capacity Overview --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Capacity Utilization --}}
            <div class="capacity-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Capacity Utilization</h4>
                <div class="capacity-fi-card">
                    <div class="capacity-header">
                        <div class="capacity-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="capacity-info">
                            <div class="capacity-title">Today's Utilization</div>
                            <div class="capacity-percentage">{{ $capacity_metrics['utilization_percentage'] }}%</div>
                        </div>
                    </div>
                    
                    <div class="capacity-progress">
                        <div class="progress-bar">
                            <div class="progress-fill {{ $capacity_metrics['status'] }}" 
                                 style="width: {{ min($capacity_metrics['utilization_percentage'], 100) }}%"></div>
                        </div>
                        <div class="capacity-details">
                            <span class="detail-item">Used: {{ $capacity_metrics['used_capacity'] }}h</span>
                            <span class="detail-item">Total: {{ $capacity_metrics['total_capacity'] }}h</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Quality Metrics --}}
            <div class="quality-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Quality Metrics</h4>
                <div class="quality-grid">
                    <div class="quality-fi-card">
                        <div class="quality-icon success">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="quality-info">
                            <div class="quality-label">Quality Score</div>
                            <div class="quality-value">{{ $quality_metrics['quality_score'] }}%</div>
                        </div>
                    </div>
                    
                    <div class="quality-fi-card">
                        <div class="quality-icon info">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="quality-info">
                            <div class="quality-label">Patient Satisfaction</div>
                            <div class="quality-value">{{ $quality_metrics['patient_satisfaction'] }}%</div>
                        </div>
                    </div>
                    
                    <div class="quality-fi-card">
                        <div class="quality-icon warning">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="quality-info">
                            <div class="quality-label">Rejection Rate</div>
                            <div class="quality-value">{{ $quality_metrics['rejection_rate'] }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Operations & Alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Daily Operations --}}
            <div class="operations-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Today's Operations</h4>
                <div class="operations-grid">
                    <div class="operation-item">
                        <div class="operation-icon patients">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div class="operation-info">
                            <div class="operation-label">Patients Today</div>
                            <div class="operation-value">{{ number_format($daily_operations['patients_today']) }}</div>
                        </div>
                    </div>
                    
                    <div class="operation-item">
                        <div class="operation-icon procedures">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.78 0-2.678-2.153-1.415-3.414l5-5A2 2 0 009 9.172V5L8 4z" />
                            </svg>
                        </div>
                        <div class="operation-info">
                            <div class="operation-label">Procedures</div>
                            <div class="operation-value">{{ number_format($daily_operations['procedures_today']) }}</div>
                        </div>
                    </div>
                    
                    <div class="operation-item">
                        <div class="operation-icon staff">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                        </div>
                        <div class="operation-info">
                            <div class="operation-label">Staff on Duty</div>
                            <div class="operation-value">{{ number_format($daily_operations['staff_on_duty']) }}</div>
                        </div>
                    </div>
                    
                    <div class="operation-item">
                        <div class="operation-icon pending">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="operation-info">
                            <div class="operation-label">Pending Validations</div>
                            <div class="operation-value">{{ number_format($daily_operations['pending_validations']) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Operational Alerts --}}
            <div class="alerts-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">System Alerts</h4>
                @if(count($operational_alerts) > 0)
                    <div class="alerts-list">
                        @foreach($operational_alerts as $alert)
                        <div class="alert-item alert-{{ $alert['type'] }}">
                            <div class="alert-icon">
                                @if($alert['type'] === 'critical')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="alert-content">
                                <div class="alert-title">{{ $alert['title'] }}</div>
                                <div class="alert-message">{{ $alert['message'] }}</div>
                                <div class="alert-action">{{ $alert['action'] }}</div>
                            </div>
                            <div class="alert-priority priority-{{ $alert['priority'] }}">
                                {{ ucfirst($alert['priority']) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="no-alerts">
                        <div class="no-alerts-icon">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="no-alerts-text">All systems operating normally</div>
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- Custom Styles --}}
    <style>
        .health-indicator {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .health-indicator.good {
            background: #D1FAE5;
            color: #065F46;
        }

        .health-indicator.warning {
            background: #FEF3C7;
            color: #92400E;
        }

        .health-indicator.critical {
            background: #FEE2E2;
            color: #991B1B;
        }

        .dark .health-indicator.good {
            background: #064E3B;
            color: #A7F3D0;
        }

        .dark .health-indicator.warning {
            background: #78350F;
            color: #FCD34D;
        }

        .dark .health-indicator.critical {
            background: #7F1D1D;
            color: #FCA5A5;
        }

        .capacity-section, .quality-section, .operations-section, .alerts-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .dark .capacity-section, .dark .quality-section, .dark .operations-section, .dark .alerts-section {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.3);
        }

        .capacity-fi-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 8px;
            padding: 1rem;
        }

        .dark .capacity-fi-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .capacity-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .capacity-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            background: linear-gradient(135deg, #6366F1, #8B5CF6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .capacity-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6B7280;
        }

        .dark .capacity-title {
            color: #9CA3AF;
        }

        .capacity-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .capacity-percentage {
            color: #F9FAFB;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            transition: width 0.5s ease;
        }

        .progress-fill.good {
            background: linear-gradient(90deg, #10B981, #059669);
        }

        .progress-fill.warning {
            background: linear-gradient(90deg, #F59E0B, #D97706);
        }

        .progress-fill.critical {
            background: linear-gradient(90deg, #EF4444, #DC2626);
        }

        .capacity-details {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .capacity-details {
            color: #9CA3AF;
        }

        .quality-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .quality-fi-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 8px;
        }

        .dark .quality-fi-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .quality-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .quality-icon.success {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .quality-icon.info {
            background: linear-gradient(135deg, #06B6D4, #0891B2);
        }

        .quality-icon.warning {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .quality-label {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
        }

        .dark .quality-label {
            color: #9CA3AF;
        }

        .quality-value {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .quality-value {
            color: #F9FAFB;
        }

        .operations-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .operation-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 8px;
        }

        .dark .operation-item {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .operation-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .operation-icon.patients {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .operation-icon.procedures {
            background: linear-gradient(135deg, #6366F1, #4F46E5);
        }

        .operation-icon.staff {
            background: linear-gradient(135deg, #8B5CF6, #7C3AED);
        }

        .operation-icon.pending {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .operation-label {
            font-size: 0.75rem;
            color: #6B7280;
            font-weight: 500;
        }

        .dark .operation-label {
            color: #9CA3AF;
        }

        .operation-value {
            font-size: 1rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .operation-value {
            color: #F9FAFB;
        }

        .alerts-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .alert-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .alert-item.alert-critical {
            background: rgba(254, 226, 226, 0.5);
            border-left-color: #EF4444;
        }

        .alert-item.alert-warning {
            background: rgba(254, 243, 199, 0.5);
            border-left-color: #F59E0B;
        }

        .dark .alert-item.alert-critical {
            background: rgba(127, 29, 29, 0.3);
        }

        .dark .alert-item.alert-warning {
            background: rgba(120, 53, 15, 0.3);
        }

        .alert-icon {
            color: #6B7280;
        }

        .alert-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .dark .alert-title {
            color: #F9FAFB;
        }

        .alert-message {
            font-size: 0.75rem;
            color: #6B7280;
            margin-bottom: 0.25rem;
        }

        .dark .alert-message {
            color: #9CA3AF;
        }

        .alert-action {
            font-size: 0.75rem;
            color: #6366F1;
            font-weight: 500;
        }

        .alert-priority {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .alert-priority.priority-high {
            background: #FEE2E2;
            color: #991B1B;
        }

        .alert-priority.priority-medium {
            background: #FEF3C7;
            color: #92400E;
        }

        .dark .alert-priority.priority-high {
            background: #7F1D1D;
            color: #FCA5A5;
        }

        .dark .alert-priority.priority-medium {
            background: #78350F;
            color: #FCD34D;
        }

        .no-alerts {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .no-alerts-icon {
            color: #10B981;
            margin-bottom: 0.5rem;
        }

        .no-alerts-text {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .dark .no-alerts-text {
            color: #9CA3AF;
        }
    </style>
</x-filament-widgets::widget>