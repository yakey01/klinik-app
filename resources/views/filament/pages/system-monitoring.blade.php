<div class="space-y-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">System Status</p>
                    <div class="flex items-center mt-1">
                        <span class="text-2xl font-bold text-{{ $this->getStatusColor($healthSummary['overall_status'] ?? 'unknown') }}-600">
                            {{ ucfirst($healthSummary['overall_status'] ?? 'Unknown') }}
                        </span>
                        <x-heroicon-o-{{ $this->getStatusIcon($healthSummary['overall_status'] ?? 'unknown') }} class="h-6 w-6 ml-2 text-{{ $this->getStatusColor($healthSummary['overall_status'] ?? 'unknown') }}-500" />
                    </div>
                </div>
                <div class="p-3 bg-{{ $this->getStatusColor($healthSummary['overall_status'] ?? 'unknown') }}-100 dark:bg-{{ $this->getStatusColor($healthSummary['overall_status'] ?? 'unknown') }}-900 rounded-full">
                    <x-heroicon-o-cpu-chip class="h-8 w-8 text-{{ $this->getStatusColor($healthSummary['overall_status'] ?? 'unknown') }}-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Metrics</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $healthSummary['total_metrics'] ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-chart-bar class="h-8 w-8 text-blue-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Critical Alerts</p>
                    <p class="text-2xl font-bold text-red-600">
                        {{ $healthSummary['critical'] ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-red-600" />
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Update</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $lastUpdate }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-clock class="h-8 w-8 text-green-600" />
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Alerts Section -->
    @if(!empty($criticalAlerts) && count($criticalAlerts) > 0)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
            <div class="flex items-center mb-4">
                <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 mr-2" />
                <h3 class="text-lg font-medium text-red-900 dark:text-red-100">Critical Alerts</h3>
            </div>
            <div class="space-y-3">
                @foreach($criticalAlerts as $alert)
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-red-800/20 rounded-lg">
                        <div>
                            <p class="font-medium text-red-900 dark:text-red-100">{{ $alert->metric_name }}</p>
                            <p class="text-sm text-red-700 dark:text-red-300">
                                {{ $alert->metric_type }} • Value: {{ $alert->metric_value }}
                                @if($alert->alert_threshold)
                                    • Threshold: {{ $alert->alert_threshold }}
                                @endif
                            </p>
                        </div>
                        <div class="text-sm text-red-600 dark:text-red-400">
                            {{ $alert->recorded_at->diffForHumans() }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- System Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-heroicon-o-cpu-chip class="h-6 w-6 text-blue-600 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">System Metrics</h3>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Memory Usage</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($systemMetrics, 'memory_usage', 'N/A') }}%
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($systemMetrics, 'memory_usage')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($systemMetrics, 'memory_usage')) }}-800">
                            {{ ucfirst($this->getMetricStatus($systemMetrics, 'memory_usage')) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Disk Usage</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($systemMetrics, 'disk_usage', 'N/A') }}%
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($systemMetrics, 'disk_usage')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($systemMetrics, 'disk_usage')) }}-800">
                            {{ ucfirst($this->getMetricStatus($systemMetrics, 'disk_usage')) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Load Average (1m)</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($systemMetrics, 'load_average_1m', 'N/A') }}
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($systemMetrics, 'load_average_1m')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($systemMetrics, 'load_average_1m')) }}-800">
                            {{ ucfirst($this->getMetricStatus($systemMetrics, 'load_average_1m')) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Database Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-heroicon-o-circle-stack class="h-6 w-6 text-green-600 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Database Metrics</h3>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Connection Time</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($databaseMetrics, 'connection_time', 'N/A') }}ms
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($databaseMetrics, 'connection_time')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($databaseMetrics, 'connection_time')) }}-800">
                            {{ ucfirst($this->getMetricStatus($databaseMetrics, 'connection_time')) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Query Time</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($databaseMetrics, 'query_time', 'N/A') }}ms
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($databaseMetrics, 'query_time')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($databaseMetrics, 'query_time')) }}-800">
                            {{ ucfirst($this->getMetricStatus($databaseMetrics, 'query_time')) }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Database Size</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($databaseMetrics, 'database_size', 'N/A') }}MB
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($databaseMetrics, 'database_size')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($databaseMetrics, 'database_size')) }}-800">
                            {{ ucfirst($this->getMetricStatus($databaseMetrics, 'database_size')) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cache Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-heroicon-o-bolt class="h-6 w-6 text-yellow-600 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Cache Metrics</h3>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Response Time</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($cacheMetrics, 'cache_response_time', 'N/A') }}ms
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($cacheMetrics, 'cache_response_time')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($cacheMetrics, 'cache_response_time')) }}-800">
                            {{ ucfirst($this->getMetricStatus($cacheMetrics, 'cache_response_time')) }}
                        </span>
                    </div>
                </div>

                @if(isset($cacheMetrics['redis_memory_usage']))
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Redis Memory</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $this->getMetricValue($cacheMetrics, 'redis_memory_usage', 'N/A') }}MB
                            </p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($cacheMetrics, 'redis_memory_usage')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($cacheMetrics, 'redis_memory_usage')) }}-800">
                                {{ ucfirst($this->getMetricStatus($cacheMetrics, 'redis_memory_usage')) }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-heroicon-o-rocket-launch class="h-6 w-6 text-purple-600 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performance Metrics</h3>
                </div>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Response Time</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $this->getMetricValue($performanceMetrics, 'response_time', 'N/A') }}ms
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($performanceMetrics, 'response_time')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($performanceMetrics, 'response_time')) }}-800">
                            {{ ucfirst($this->getMetricStatus($performanceMetrics, 'response_time')) }}
                        </span>
                    </div>
                </div>

                @if(isset($performanceMetrics['opcache_hit_rate']))
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">OPcache Hit Rate</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ $this->getMetricValue($performanceMetrics, 'opcache_hit_rate', 'N/A') }}%
                            </p>
                        </div>
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($performanceMetrics, 'opcache_hit_rate')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($performanceMetrics, 'opcache_hit_rate')) }}-800">
                                {{ ucfirst($this->getMetricStatus($performanceMetrics, 'opcache_hit_rate')) }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Security Metrics -->
    @if(!empty($securityMetrics))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <x-heroicon-o-shield-check class="h-6 w-6 text-red-600 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Security Metrics</h3>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @if(isset($securityMetrics['failed_logins_1h']))
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Failed Logins (1h)</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $this->getMetricValue($securityMetrics, 'failed_logins_1h', 'N/A') }}
                                </p>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($securityMetrics, 'failed_logins_1h')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($securityMetrics, 'failed_logins_1h')) }}-800">
                                    {{ ucfirst($this->getMetricStatus($securityMetrics, 'failed_logins_1h')) }}
                                </span>
                            </div>
                        </div>
                    @endif

                    @if(isset($securityMetrics['ssl_certificate_days']))
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">SSL Expiry (days)</p>
                                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ $this->getMetricValue($securityMetrics, 'ssl_certificate_days', 'N/A') }}
                                </p>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getStatusColor($this->getMetricStatus($securityMetrics, 'ssl_certificate_days')) }}-100 text-{{ $this->getStatusColor($this->getMetricStatus($securityMetrics, 'ssl_certificate_days')) }}-800">
                                    {{ ucfirst($this->getMetricStatus($securityMetrics, 'ssl_certificate_days')) }}
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>