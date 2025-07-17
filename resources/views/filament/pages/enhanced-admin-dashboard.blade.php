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

    <!-- Pinterest-Inspired Hero Stats Grid - 6 Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Card 1: System Health Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 {{ $systemHealth['status'] === 'healthy' ? 'bg-green-100 dark:bg-green-900/30' : ($systemHealth['status'] === 'warning' ? 'bg-yellow-100 dark:bg-yellow-900/30' : 'bg-red-100 dark:bg-red-900/30') }} rounded-lg">
                        <svg class="w-6 h-6 {{ $systemHealth['status'] === 'healthy' ? 'text-green-600 dark:text-green-400' : ($systemHealth['status'] === 'warning' ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">System Health</h3>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white capitalize">{{ $systemHealth['status'] }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $systemHealth['memory_usage'] }}%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Memory Usage</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Memory Usage -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Memory</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-blue-600 dark:bg-blue-500 h-2 rounded-full" style="width: {{ $systemHealth['memory_usage'] }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemHealth['memory_usage'] }}%</span>
                    </div>
                </div>
                
                <!-- CPU Usage -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">CPU</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-purple-600 dark:bg-purple-500 h-2 rounded-full" style="width: {{ $systemHealth['cpu_usage'] }}%"></div>
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemHealth['cpu_usage'] }}%</span>
                    </div>
                </div>
                
                <!-- Active Alerts -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Active Alerts</span>
                        <span class="text-sm font-medium {{ $systemHealth['active_alerts'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $systemHealth['active_alerts'] }}
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Last check: {{ $systemHealth['last_check'] }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Security Dashboard -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">Security Status</h3>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $securityDashboard['failed_logins'] }} Failed Logins</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $securityDashboard['active_sessions'] }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Active Sessions</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Security Events -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Security Events (24h)</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $securityDashboard['security_events'] }}</span>
                </div>
                
                <!-- Suspicious Activities -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Suspicious Activities</span>
                    <span class="text-sm font-medium {{ $securityDashboard['suspicious_activities'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ $securityDashboard['suspicious_activities'] }}
                    </span>
                </div>
                
                <!-- Trend Indicator -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Security Trend</span>
                        <div class="flex items-center space-x-1">
                            @if($securityDashboard['trend_direction'] === 'up')
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-red-600 dark:text-red-400">Increasing</span>
                            @elseif($securityDashboard['trend_direction'] === 'down')
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-green-600 dark:text-green-400">Decreasing</span>
                            @else
                                <span class="text-sm text-gray-600 dark:text-gray-400">Stable</span>
                            @endif
                        </div>
                    </div>
                    @if($securityDashboard['last_incident'])
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Last incident: {{ $securityDashboard['last_incident'] }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 3: User Management Summary -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">User Management</h3>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $userManagement['total_users'] }} Total Users</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $userManagement['activity_percentage'] }}%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Active Rate</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Active vs Inactive Users -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Active Users</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $userManagement['active_users'] }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Inactive Users</span>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $userManagement['inactive_users'] }}</span>
                </div>
                
                <!-- New Users This Month -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">New This Month</span>
                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $userManagement['new_users_this_month'] }}</span>
                </div>
                
                <!-- Pending Approvals -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Pending Approvals</span>
                        <span class="text-sm font-medium {{ $userManagement['pending_approvals'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $userManagement['pending_approvals'] }}
                        </span>
                    </div>
                    
                    <!-- Role Distribution -->
                    <div class="mt-3 space-y-1">
                        @foreach($userManagement['users_by_role'] as $role => $count)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 dark:text-gray-400 capitalize">{{ str_replace('_', ' ', $role) }}</span>
                                <span class="text-gray-700 dark:text-gray-300">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: System Performance -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">System Performance</h3>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $systemPerformance['response_time'] }}ms</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold {{ $systemPerformance['performance_score'] >= 80 ? 'text-green-600 dark:text-green-400' : ($systemPerformance['performance_score'] >= 60 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $systemPerformance['performance_score'] }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Performance Score</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Cache Hit Rate -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Cache Hit Rate</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $systemPerformance['cache_hit_rate'] }}%</span>
                </div>
                
                <!-- Database Queries -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">DB Queries</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $systemPerformance['database_queries'] }}</span>
                </div>
                
                <!-- Queue Jobs -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Queue Jobs</span>
                    <span class="text-sm font-medium {{ $systemPerformance['queue_jobs'] > 50 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ $systemPerformance['queue_jobs'] }}
                    </span>
                </div>
                
                <!-- Performance Indicator -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="relative">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="h-2 rounded-full {{ $systemPerformance['performance_score'] >= 80 ? 'bg-green-500' : ($systemPerformance['performance_score'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                 style="width: {{ $systemPerformance['performance_score'] }}%"></div>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $systemPerformance['performance_score'] >= 80 ? 'Excellent' : ($systemPerformance['performance_score'] >= 60 ? 'Good' : 'Needs Attention') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 5: Financial Overview -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">Financial Overview</h3>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($financialOverview['current_revenue'], 0, ',', '.') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold {{ $financialOverview['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $financialOverview['profit_margin'] }}%
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Profit Margin</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Revenue Trend -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Revenue Trend</span>
                    <div class="flex items-center space-x-1">
                        @if($financialOverview['revenue_trend'] > 0)
                            <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-green-600 dark:text-green-400">+{{ $financialOverview['revenue_trend'] }}%</span>
                        @else
                            <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-red-600 dark:text-red-400">{{ $financialOverview['revenue_trend'] }}%</span>
                        @endif
                    </div>
                </div>
                
                <!-- Expenses -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Total Expenses</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">Rp {{ number_format($financialOverview['current_expenses'], 0, ',', '.') }}</span>
                </div>
                
                <!-- Net Income -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Net Income</span>
                    <span class="text-sm font-medium {{ $financialOverview['net_income'] >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        Rp {{ number_format($financialOverview['net_income'], 0, ',', '.') }}
                    </span>
                </div>
                
                <!-- Pending Approvals -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Pending Approvals</span>
                        <span class="text-sm font-medium {{ $financialOverview['pending_approvals'] > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $financialOverview['pending_approvals'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 6: Medical Operations -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 p-6 hover:shadow-md dark:hover:shadow-xl transition-all duration-300">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-300">Medical Operations</h3>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $medicalOperations['total_patients'] }} Patients</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $medicalOperations['completion_rate'] }}%</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Completion Rate</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <!-- Procedures Completed -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Procedures Completed</span>
                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{ $medicalOperations['procedures_completed'] }}</span>
                </div>
                
                <!-- Staff Efficiency -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Staff Efficiency</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">{{ $medicalOperations['staff_efficiency'] }}/staff</span>
                </div>
                
                <!-- Active Staff -->
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">Active Staff</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $medicalOperations['active_staff'] }}</span>
                </div>
                
                <!-- Patient Growth -->
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Patient Growth</span>
                        <div class="flex items-center space-x-1">
                            @if($medicalOperations['patient_growth'] > 0)
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-green-600 dark:text-green-400">+{{ $medicalOperations['patient_growth'] }}%</span>
                            @else
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l4.293-4.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm text-red-600 dark:text-red-400">{{ $medicalOperations['patient_growth'] }}%</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 6-Month Trends Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 transition-all duration-300">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">6-Month Trends</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">System metrics over the last 6 months</p>
            </div>
            <div class="p-6">
                <div id="sixMonthTrendsChart" style="height: 300px;" class="chart-container"></div>
            </div>
        </div>

        <!-- Recent Admin Activities -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm dark:shadow-lg border dark:border-gray-700 transition-all duration-300">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Activities</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Latest admin actions and system events</p>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach(array_slice($recentActivities, 0, 8) as $activity)
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            @if($activity['type'] === 'user')
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @elseif($activity['type'] === 'security')
                                <div class="w-8 h-8 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @else
                                <div class="w-8 h-8 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity['description'] }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $activity['user'] }} • {{ $activity['timestamp'] }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                {{ $activity['risk_level'] === 'high' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 
                                   ($activity['risk_level'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 
                                   'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400') }}">
                                {{ ucfirst($activity['risk_level']) }}
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Dark mode detection and chart theme management
        function detectDarkMode() {
            return document.documentElement.classList.contains('dark') || 
                   window.matchMedia('(prefers-color-scheme: dark)').matches;
        }

        function getChartTheme() {
            const isDark = detectDarkMode();
            
            return {
                colors: isDark ? 
                    ['#60a5fa', '#34d399', '#fbbf24', '#f87171'] : // Dark mode colors
                    ['#3b82f6', '#10b981', '#f59e0b', '#ef4444'],  // Light mode colors
                chart: {
                    background: 'transparent',
                    foreColor: isDark ? '#e5e7eb' : '#374151'
                },
                grid: {
                    borderColor: isDark ? '#4b5563' : '#f3f4f6',
                    strokeDashArray: 3,
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280'
                        }
                    },
                    axisBorder: {
                        color: isDark ? '#4b5563' : '#e5e7eb'
                    },
                    axisTicks: {
                        color: isDark ? '#4b5563' : '#e5e7eb'
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#9ca3af' : '#6b7280'
                        }
                    }
                },
                legend: {
                    labels: {
                        colors: isDark ? '#e5e7eb' : '#374151'
                    }
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light'
                }
            };
        }

        // 6-Month Trends Chart
        function createSixMonthTrendsChart() {
            const theme = getChartTheme();
            const trendsData = @json($sixMonthTrends);
            
            const options = {
                chart: {
                    type: 'line',
                    height: 300,
                    toolbar: {
                        show: false
                    },
                    background: theme.chart.background,
                    foreColor: theme.chart.foreColor,
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    }
                },
                series: [
                    {
                        name: 'New Users',
                        data: trendsData.users
                    },
                    {
                        name: 'Revenue',
                        data: trendsData.revenue
                    },
                    {
                        name: 'Patients',
                        data: trendsData.patients
                    },
                    {
                        name: 'Security Events',
                        data: trendsData.security_events
                    }
                ],
                xaxis: {
                    categories: trendsData.months,
                    labels: {
                        style: theme.xaxis.labels.style
                    },
                    axisBorder: {
                        show: true,
                        color: theme.xaxis.axisBorder.color
                    },
                    axisTicks: {
                        show: true,
                        color: theme.xaxis.axisTicks.color
                    }
                },
                yaxis: {
                    labels: {
                        style: theme.yaxis.labels.style
                    }
                },
                colors: theme.colors,
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.1
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    labels: theme.legend.labels
                },
                grid: theme.grid,
                tooltip: {
                    theme: theme.tooltip.theme,
                    shared: true,
                    intersect: false
                }
            };

            return new ApexCharts(document.querySelector("#sixMonthTrendsChart"), options);
        }

        // Initialize chart
        let sixMonthChart = createSixMonthTrendsChart();
        sixMonthChart.render();

        // Theme change detection
        function updateChartTheme() {
            if (sixMonthChart) {
                sixMonthChart.destroy();
                sixMonthChart = createSixMonthTrendsChart();
                sixMonthChart.render();
            }
        }

        // Listen for theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'class' && 
                    mutation.target === document.documentElement) {
                    
                    clearTimeout(window.chartUpdateTimeout);
                    window.chartUpdateTimeout = setTimeout(updateChartTheme, 100);
                }
            });
        });

        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
            setTimeout(updateChartTheme, 100);
        });

        // Cleanup
        window.addEventListener('beforeunload', function() {
            if (observer) observer.disconnect();
            if (sixMonthChart) sixMonthChart.destroy();
        });
    </script>
    @endpush
</x-filament-panels::page>