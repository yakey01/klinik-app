<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Widget Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Approval Workflow
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Approval status, workflow efficiency and pending items
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 dark:text-gray-400">Workflow Health:</span>
                <div class="workflow-health {{ $workflow_status['workflow_health']['status'] }}">
                    {{ $workflow_status['workflow_health']['score'] }}%
                </div>
            </div>
        </div>

        {{-- Approval Metrics Overview --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="approval-metric-fi-card">
                <div class="metric-icon success">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Approval Rate</div>
                    <div class="metric-value">{{ $approval_metrics['approval_rate'] }}%</div>
                </div>
            </div>
            
            <div class="approval-metric-fi-card">
                <div class="metric-icon warning">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Avg Approval Time</div>
                    <div class="metric-value">{{ $approval_metrics['avg_approval_time'] }}h</div>
                </div>
            </div>
            
            <div class="approval-metric-fi-card">
                <div class="metric-icon info">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="metric-info">
                    <div class="metric-label">Total Processed</div>
                    <div class="metric-value">{{ number_format($approval_metrics['total_processed']) }}</div>
                </div>
            </div>
            
            <div class="approval-metric-fi-card">
                <div class="metric-icon danger">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div class="metric-info">
                    <div class="metric-label">High Priority</div>
                    <div class="metric-value">{{ number_format($pending_approvals['high_priority_count']) }}</div>
                </div>
            </div>
        </div>

        {{-- Pending Approvals Overview --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {{-- Pending Procedures --}}
            <div class="pending-section">
                <div class="pending-header">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">Pending Procedures</h4>
                    <div class="pending-count {{ $pending_approvals['procedures']['priority'] }}">
                        {{ $pending_approvals['procedures']['count'] }}
                    </div>
                </div>
                
                @if($pending_approvals['procedures']['count'] > 0)
                    <div class="pending-list">
                        @foreach($pending_approvals['procedures']['items']->take(5) as $procedure)
                        <div class="pending-item">
                            <div class="pending-info">
                                <div class="pending-title">{{ $procedure->nama_tindakan ?? 'Procedure' }}</div>
                                <div class="pending-meta">
                                    <span class="pending-date">{{ $procedure->created_at->diffForHumans() }}</span>
                                    <span class="pending-amount">Rp {{ number_format($procedure->harga ?? 0) }}</span>
                                </div>
                            </div>
                            <div class="pending-status">
                                @if($procedure->created_at->diffInDays() > 3)
                                    <span class="status-badge urgent">Urgent</span>
                                @else
                                    <span class="status-badge normal">Normal</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                        
                        @if($pending_approvals['procedures']['count'] > 5)
                        <div class="pending-more">
                            +{{ $pending_approvals['procedures']['count'] - 5 }} more pending
                        </div>
                        @endif
                    </div>
                @else
                    <div class="no-pending">
                        <div class="no-pending-icon">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="no-pending-text">All caught up!</div>
                    </div>
                @endif
            </div>

            {{-- Pending Revenue --}}
            <div class="pending-section">
                <div class="pending-header">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">Pending Revenue</h4>
                    <div class="pending-count normal">
                        {{ $pending_approvals['revenue']['count'] }}
                    </div>
                </div>
                
                <div class="revenue-summary">
                    <div class="revenue-total">
                        <div class="revenue-label">Total Amount</div>
                        <div class="revenue-value">Rp {{ number_format($pending_approvals['revenue']['total_amount']) }}</div>
                    </div>
                </div>
                
                @if($pending_approvals['revenue']['count'] > 0)
                    <div class="pending-list">
                        @foreach($pending_approvals['revenue']['items']->take(3) as $revenue)
                        <div class="pending-item">
                            <div class="pending-info">
                                <div class="pending-title">Revenue Entry</div>
                                <div class="pending-meta">
                                    <span class="pending-date">{{ $revenue->created_at->diffForHumans() }}</span>
                                    <span class="pending-amount">Rp {{ number_format($revenue->jumlah) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Pending Expenses --}}
            <div class="pending-section">
                <div class="pending-header">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white">Pending Expenses</h4>
                    <div class="pending-count normal">
                        {{ $pending_approvals['expenses']['count'] }}
                    </div>
                </div>
                
                <div class="expense-summary">
                    <div class="expense-total">
                        <div class="expense-label">Total Amount</div>
                        <div class="expense-value">Rp {{ number_format($pending_approvals['expenses']['total_amount']) }}</div>
                    </div>
                </div>
                
                @if($pending_approvals['expenses']['count'] > 0)
                    <div class="pending-list">
                        @foreach($pending_approvals['expenses']['items']->take(3) as $expense)
                        <div class="pending-item">
                            <div class="pending-info">
                                <div class="pending-title">{{ $expense->kategori ?? 'Expense' }}</div>
                                <div class="pending-meta">
                                    <span class="pending-date">{{ $expense->created_at->diffForHumans() }}</span>
                                    <span class="pending-amount">Rp {{ number_format($expense->jumlah) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Workflow Status & History --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Workflow Bottlenecks --}}
            <div class="bottlenecks-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Workflow Issues</h4>
                
                @if(count($workflow_status['bottlenecks']) > 0)
                    <div class="bottlenecks-list">
                        @foreach($workflow_status['bottlenecks'] as $bottleneck)
                        <div class="bottleneck-item bottleneck-{{ $bottleneck['severity'] }}">
                            <div class="bottleneck-icon">
                                @if($bottleneck['severity'] === 'high')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                @else
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @endif
                            </div>
                            <div class="bottleneck-content">
                                <div class="bottleneck-message">{{ $bottleneck['message'] }}</div>
                                <div class="bottleneck-count">{{ $bottleneck['count'] }} items affected</div>
                            </div>
                            <div class="bottleneck-severity">{{ ucfirst($bottleneck['severity']) }}</div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="no-bottlenecks">
                        <div class="no-bottlenecks-icon">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="no-bottlenecks-text">Workflow running smoothly</div>
                    </div>
                @endif
                
                {{-- Backlog Distribution --}}
                <div class="backlog-distribution">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Approval Backlog</h5>
                    <div class="backlog-chart">
                        @foreach($approval_metrics['backlog_distribution'] as $period => $count)
                        <div class="backlog-item">
                            <div class="backlog-period">{{ $period }} days</div>
                            <div class="backlog-bar">
                                <div class="backlog-fill" style="width: {{ $count > 0 ? ($count / max($approval_metrics['backlog_distribution']) * 100) : 0 }}%"></div>
                            </div>
                            <div class="backlog-count">{{ $count }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Approval History Trend --}}
            <div class="history-section">
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">Approval Trend (7 Days)</h4>
                <div class="history-chart">
                    <canvas id="approvalHistoryChart" width="400" height="250"></canvas>
                </div>
                
                {{-- Validator Performance --}}
                <div class="validator-performance">
                    <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Top Validators</h5>
                    <div class="validator-list">
                        @foreach($workflow_status['validator_performance']->take(3) as $validator)
                        <div class="validator-item">
                            <div class="validator-avatar">
                                {{ strtoupper(substr($validator->name, 0, 2)) }}
                            </div>
                            <div class="validator-info">
                                <div class="validator-name">{{ $validator->name }}</div>
                                <div class="validator-count">{{ $validator->validated_tindakan_count }} approvals</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Custom Styles --}}
    <style>
        .workflow-health {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .workflow-health.excellent {
            background: #D1FAE5;
            color: #065F46;
        }

        .workflow-health.good {
            background: #FEF3C7;
            color: #92400E;
        }

        .workflow-health.needs-attention {
            background: #FEE2E2;
            color: #991B1B;
        }

        .workflow-health.critical {
            background: #FEE2E2;
            color: #991B1B;
        }

        .dark .workflow-health.excellent {
            background: #064E3B;
            color: #A7F3D0;
        }

        .dark .workflow-health.good {
            background: #78350F;
            color: #FCD34D;
        }

        .dark .workflow-health.needs-attention,
        .dark .workflow-health.critical {
            background: #7F1D1D;
            color: #FCA5A5;
        }

        .approval-metric-fi-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .approval-metric-fi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .dark .approval-metric-fi-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .metric-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .metric-icon.success {
            background: linear-gradient(135deg, #10B981, #059669);
        }

        .metric-icon.warning {
            background: linear-gradient(135deg, #F59E0B, #D97706);
        }

        .metric-icon.info {
            background: linear-gradient(135deg, #06B6D4, #0891B2);
        }

        .metric-icon.danger {
            background: linear-gradient(135deg, #EF4444, #DC2626);
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
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .metric-value {
            color: #F9FAFB;
        }

        .pending-section, .bottlenecks-section, .history-section {
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(229, 231, 235, 0.3);
            border-radius: 12px;
            padding: 1.5rem;
            backdrop-filter: blur(10px);
        }

        .dark .pending-section, .dark .bottlenecks-section, .dark .history-section {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.3);
        }

        .pending-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .pending-count {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .pending-count.normal {
            background: #F3F4F6;
            color: #374151;
        }

        .pending-count.high {
            background: #FEE2E2;
            color: #991B1B;
        }

        .dark .pending-count.normal {
            background: #374151;
            color: #D1D5DB;
        }

        .dark .pending-count.high {
            background: #7F1D1D;
            color: #FCA5A5;
        }

        .revenue-summary, .expense-summary {
            margin-bottom: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        .dark .revenue-summary, .dark .expense-summary {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .revenue-label, .expense-label {
            font-size: 0.75rem;
            color: #6B7280;
            margin-bottom: 0.25rem;
        }

        .dark .revenue-label, .dark .expense-label {
            color: #9CA3AF;
        }

        .revenue-value, .expense-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
        }

        .dark .revenue-value, .dark .expense-value {
            color: #F9FAFB;
        }

        .pending-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .pending-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        .dark .pending-item {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .pending-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .dark .pending-title {
            color: #F9FAFB;
        }

        .pending-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .pending-meta {
            color: #9CA3AF;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.urgent {
            background: #FEE2E2;
            color: #991B1B;
        }

        .status-badge.normal {
            background: #F3F4F6;
            color: #374151;
        }

        .dark .status-badge.urgent {
            background: #7F1D1D;
            color: #FCA5A5;
        }

        .dark .status-badge.normal {
            background: #374151;
            color: #D1D5DB;
        }

        .pending-more {
            text-align: center;
            padding: 0.75rem;
            font-size: 0.75rem;
            color: #6B7280;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 8px;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        .dark .pending-more {
            background: rgba(55, 65, 81, 0.5);
            border-color: rgba(75, 85, 99, 0.5);
            color: #9CA3AF;
        }

        .no-pending, .no-bottlenecks {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }

        .no-pending-icon, .no-bottlenecks-icon {
            color: #10B981;
            margin-bottom: 0.5rem;
        }

        .no-pending-text, .no-bottlenecks-text {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .dark .no-pending-text, .dark .no-bottlenecks-text {
            color: #9CA3AF;
        }

        .bottlenecks-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .bottleneck-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .bottleneck-item.bottleneck-high {
            background: rgba(254, 226, 226, 0.5);
            border-left-color: #EF4444;
        }

        .bottleneck-item.bottleneck-medium {
            background: rgba(254, 243, 199, 0.5);
            border-left-color: #F59E0B;
        }

        .dark .bottleneck-item.bottleneck-high {
            background: rgba(127, 29, 29, 0.3);
        }

        .dark .bottleneck-item.bottleneck-medium {
            background: rgba(120, 53, 15, 0.3);
        }

        .bottleneck-message {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.25rem;
        }

        .dark .bottleneck-message {
            color: #F9FAFB;
        }

        .bottleneck-count {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .bottleneck-count {
            color: #9CA3AF;
        }

        .bottleneck-severity {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            background: #F3F4F6;
            color: #374151;
        }

        .dark .bottleneck-severity {
            background: #374151;
            color: #D1D5DB;
        }

        .backlog-distribution {
            margin-top: 1.5rem;
        }

        .backlog-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        .backlog-period {
            font-size: 0.75rem;
            color: #6B7280;
            min-width: 60px;
        }

        .dark .backlog-period {
            color: #9CA3AF;
        }

        .backlog-bar {
            flex: 1;
            height: 8px;
            background: #E5E7EB;
            border-radius: 4px;
            overflow: hidden;
        }

        .backlog-fill {
            height: 100%;
            background: linear-gradient(90deg, #6366F1, #8B5CF6);
            transition: width 0.5s ease;
        }

        .backlog-count {
            font-size: 0.75rem;
            font-weight: 600;
            color: #111827;
            min-width: 30px;
            text-align: right;
        }

        .dark .backlog-count {
            color: #F9FAFB;
        }

        .validator-performance {
            margin-top: 1.5rem;
        }

        .validator-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .validator-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            border: 1px solid rgba(229, 231, 235, 0.5);
        }

        .dark .validator-item {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .validator-avatar {
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

        .validator-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #111827;
        }

        .dark .validator-name {
            color: #F9FAFB;
        }

        .validator-count {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .validator-count {
            color: #9CA3AF;
        }
    </style>

    {{-- Chart Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('approvalHistoryChart').getContext('2d');
            const historyData = @json($approval_history);
            
            const labels = historyData.map(item => item.day);
            const approvals = historyData.map(item => item.approvals);
            const rejections = historyData.map(item => item.rejections);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Approvals',
                            data: approvals,
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'Rejections',
                            data: rejections,
                            borderColor: '#EF4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-widgets::widget>