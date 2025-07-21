<div class="space-y-6">
    <!-- Error Info -->
    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <x-heroicon-o-exclamation-triangle class="h-8 w-8 text-red-400" />
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-red-800 dark:text-red-200">
                    Execution Failed
                </h3>
                <p class="text-sm text-red-700 dark:text-red-300">
                    The report execution encountered an error and could not be completed.
                </p>
            </div>
        </div>
    </div>

    <!-- Execution Details -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                <p class="text-lg font-semibold text-red-600">{{ ucfirst($execution->status) }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Started</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $execution->started_at ? $execution->started_at->format('Y-m-d H:i:s') : 'N/A' }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</p>
                <p class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $execution->getFormattedExecutionTime() }}
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

    <!-- Error Message -->
    @if($error)
        <div class="bg-white dark:bg-gray-900 rounded-lg border border-red-200 dark:border-red-700">
            <div class="p-4 border-b border-red-200 dark:border-red-700">
                <h3 class="text-lg font-medium text-red-900 dark:text-red-100">Error Details</h3>
            </div>
            
            <div class="p-4">
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                    <pre class="text-sm text-red-900 dark:text-red-100 whitespace-pre-wrap">{{ $error }}</pre>
                </div>
            </div>
        </div>
    @endif

    <!-- Report Configuration -->
    <div class="bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Report Configuration</h3>
        </div>
        
        <div class="p-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Report Name</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $execution->report->name }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Report Type</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ ucfirst($execution->report->report_type) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ ucfirst($execution->report->category) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Created By</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $execution->report->user->name }}
                    </p>
                </div>
            </div>
            
            @if($execution->report->query_config)
                <div class="mt-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Query Configuration</p>
                    <div class="mt-2 bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <pre class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ json_encode($execution->report->query_config, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Troubleshooting Tips -->
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <x-heroicon-o-information-circle class="h-6 w-6 text-blue-400" />
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200">
                    Troubleshooting Tips
                </h3>
                <ul class="mt-2 text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>• Check if the data source model exists and is accessible</li>
                    <li>• Verify that the query configuration is valid</li>
                    <li>• Ensure sufficient memory and execution time limits</li>
                    <li>• Check database connection and permissions</li>
                    <li>• Review the report filters and parameters</li>
                </ul>
            </div>
        </div>
    </div>
</div>