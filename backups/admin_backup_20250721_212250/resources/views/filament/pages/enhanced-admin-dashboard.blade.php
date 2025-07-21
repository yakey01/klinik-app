<x-filament-panels::page>
    @php
        $systemHealth = $this->getSystemHealthOverview();
        $securityDashboard = $this->getSecurityDashboard();
        $userManagement = $this->getUserManagementSummary();
        $systemPerformance = $this->getSystemPerformance();
        $financialOverview = $this->getFinancialOverview();
        $medicalOperations = $this->getMedicalOperations();
        $recentActivities = $this->getRecentAdminActivities();
        $sixMonthTrends = $this->getSixMonthTrends();
    @endphp

    <div class="fi-page-content space-y-6">
        <!-- Hero Stats Grid using Pure Filament Components -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- System Health Card -->
            <x-filament::section
                heading="🛡️ System Health"
                description="Memory: {{ $systemHealth['memory_usage'] }}% | CPU: {{ $systemHealth['cpu_usage'] }}%"
                icon="heroicon-o-cpu-chip"
            >
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Status</span>
                        <x-filament::badge 
                            :color="$systemHealth['status'] === 'healthy' ? 'success' : ($systemHealth['status'] === 'warning' ? 'warning' : 'danger')"
                        >
                            {{ ucfirst($systemHealth['status']) }}
                        </x-filament::badge>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Memory Usage</span>
                            <span class="font-medium">{{ $systemHealth['memory_usage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $systemHealth['memory_usage'] }}%"></div>
                        </div>
                        
                        <div class="flex justify-between text-sm">
                            <span>CPU Usage</span>
                            <span class="font-medium">{{ $systemHealth['cpu_usage'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-600 h-2 rounded-full" style="width: {{ $systemHealth['cpu_usage'] }}%"></div>
                        </div>
                    </div>
                    
                    <div class="pt-2 border-t text-xs text-gray-500">
                        Active Alerts: {{ $systemHealth['active_alerts'] }} | Last check: {{ $systemHealth['last_check'] }}
                    </div>
                </div>
            </x-filament::section>

            <!-- Security Dashboard -->
            <x-filament::section
                heading="🔐 Security Status"  
                description="{{ $securityDashboard['failed_logins'] }} Failed Logins Today"
                icon="heroicon-o-shield-check"
            >
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium">Active Sessions</span>
                        <span class="text-2xl font-bold">{{ $securityDashboard['active_sessions'] }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Security Events (24h)</span>
                            <div class="font-medium">{{ $securityDashboard['security_events'] }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Suspicious Activities</span>
                            <div class="font-medium {{ $securityDashboard['suspicious_activities'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                {{ $securityDashboard['suspicious_activities'] }}
                            </div>
                        </div>
                    </div>
                    
                    @if($securityDashboard['last_incident'])
                    <div class="pt-2 border-t text-xs text-gray-500">
                        Last incident: {{ $securityDashboard['last_incident'] }}
                    </div>
                    @endif
                </div>
            </x-filament::section>

            <!-- User Management -->
            <x-filament::section
                heading="👥 User Management"
                description="{{ $userManagement['total_users'] }} Total Users"
                icon="heroicon-o-users"
            >
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Active Users</span>
                            <div class="font-medium text-green-600">{{ $userManagement['active_users'] }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">New Today</span>
                            <div class="font-medium text-blue-600">{{ $userManagement['new_users_today'] ?? 0 }}</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="text-center">
                            <div class="font-medium">{{ $userManagement['admin_count'] ?? 0 }}</div>
                            <span class="text-gray-500">Admins</span>
                        </div>
                        <div class="text-center">
                            <div class="font-medium">{{ $userManagement['staff_count'] ?? 0 }}</div>
                            <span class="text-gray-500">Staff</span>
                        </div>
                        <div class="text-center">
                            <div class="font-medium">{{ $userManagement['doctor_count'] ?? 0 }}</div>
                            <span class="text-gray-500">Doctors</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- System Performance -->
            <x-filament::section
                heading="📊 System Performance"
                description="Response Time: {{ $systemPerformance['avg_response_time'] }}ms"
                icon="heroicon-o-chart-bar"
            >
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Uptime</span>
                            <div class="font-medium text-green-600">{{ $systemPerformance['uptime_percentage'] }}%</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Database Queries</span>
                            <div class="font-medium">{{ $systemPerformance['db_queries_per_sec'] }}/sec</div>
                        </div>
                    </div>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Performance Score</span>
                            <span class="font-medium">{{ $systemPerformance['performance_score'] }}/100</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full" style="width: {{ $systemPerformance['performance_score'] }}%"></div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Financial Overview -->
            <x-filament::section
                heading="💰 Financial Overview"
                description="Today's Revenue: Rp {{ number_format($financialOverview['daily_revenue'] ?? 0, 0, ',', '.') }}"
                icon="heroicon-o-banknotes"
            >
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Monthly Revenue</span>
                            <div class="font-medium text-green-600">Rp {{ number_format($financialOverview['monthly_revenue'], 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Profit Margin</span>
                            <div class="font-medium">{{ $financialOverview['profit_margin'] }}%</div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-gray-500">Expenses</span>
                            <div class="font-medium text-red-600">Rp {{ number_format($financialOverview['monthly_expenses'], 0, ',', '.') }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Net Profit</span>
                            <div class="font-medium {{ $financialOverview['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                Rp {{ number_format($financialOverview['net_profit'], 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Medical Operations -->
            <x-filament::section
                heading="🏥 Medical Operations"
                description="{{ $medicalOperations['daily_procedures'] ?? 0 }} Procedures Today"
                icon="heroicon-o-heart"
            >
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500">Patients Today</span>
                            <div class="font-medium text-blue-600">{{ $medicalOperations['daily_patients'] }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Patient Growth</span>
                            <div class="font-medium {{ $medicalOperations['patient_growth'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $medicalOperations['patient_growth'] >= 0 ? '+' : '' }}{{ $medicalOperations['patient_growth'] }}%
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="text-center">
                            <div class="font-medium">{{ $medicalOperations['emergency_cases'] }}</div>
                            <span class="text-gray-500">Emergency</span>
                        </div>
                        <div class="text-center">
                            <div class="font-medium">{{ $medicalOperations['routine_checkups'] }}</div>
                            <span class="text-gray-500">Routine</span>
                        </div>
                        <div class="text-center">
                            <div class="font-medium">{{ $medicalOperations['follow_ups'] }}</div>
                            <span class="text-gray-500">Follow-ups</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        <!-- Recent Activities Section -->
        <x-filament::section
            heading="📋 Recent Admin Activities"
            description="Latest system activities and logs"
            icon="heroicon-o-clock"
            collapsible
        >
            <div class="space-y-3">
                @foreach($recentActivities as $activity)
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
                    <div class="flex-shrink-0">
                        <x-filament::icon
                            :icon="match($activity['type']) {
                                'user' => 'heroicon-o-user',
                                'security' => 'heroicon-o-shield-check', 
                                'system' => 'heroicon-o-cog',
                                'medical' => 'heroicon-o-heart',
                                default => 'heroicon-o-information-circle'
                            }"
                            class="w-5 h-5 text-gray-400"
                        />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                        <p class="text-sm text-gray-500">{{ $activity['description'] }}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="text-xs text-gray-400">{{ $activity['timestamp'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </x-filament::section>

        <!-- Six Month Trends -->
        <x-filament::section
            heading="📈 Six Month Trends"
            description="Performance trends over the last 6 months"
            icon="heroicon-o-chart-pie"
            collapsible
        >
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-900">Revenue Trend</h4>
                    <div class="space-y-2">
                        @foreach($sixMonthTrends['months'] as $index => $month)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">{{ $month }}</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ max($sixMonthTrends['revenue']) > 0 ? ($sixMonthTrends['revenue'][$index] / max($sixMonthTrends['revenue'])) * 100 : 0 }}%"></div>
                                </div>
                                <span class="text-sm font-medium w-20 text-right">Rp {{ number_format($sixMonthTrends['revenue'][$index], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="space-y-4">
                    <h4 class="text-sm font-medium text-gray-900">Patient Growth</h4>
                    <div class="space-y-2">
                        @foreach($sixMonthTrends['months'] as $index => $month)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">{{ $month }}</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-20 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ max($sixMonthTrends['patients']) > 0 ? ($sixMonthTrends['patients'][$index] / max($sixMonthTrends['patients'])) * 100 : 0 }}%"></div>
                                </div>
                                <span class="text-sm font-medium w-16 text-right">{{ $sixMonthTrends['patients'][$index] }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>