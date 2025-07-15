<div class="space-y-6">
    <!-- Operation Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-squares-plus class="h-6 w-6 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Operations</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $operationStats['total_operations'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <x-heroicon-o-arrow-path class="h-6 w-6 text-yellow-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Operations</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $operationStats['active_operations'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $operationStats['completed_operations'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <x-heroicon-o-x-circle class="h-6 w-6 text-red-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $operationStats['failed_operations'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Operations -->
    @if($activeOperations->isNotEmpty())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Operations</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Operations currently running or queued
                </p>
            </div>
            
            <div class="p-6 space-y-4">
                @foreach($activeOperations as $operation)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$operation->getTypeIcon()" 
                                        class="h-6 w-6 text-{{ $operation->getStatusColor() }}-600" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucfirst($operation->operation_type) }} {{ class_basename($operation->model_type) }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Started by {{ $operation->user->name }} • {{ $operation->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $operation->getStatusColor() }}-100 text-{{ $operation->getStatusColor() }}-800">
                                    {{ ucfirst($operation->status) }}
                                </span>
                                @if($operation->canCancel())
                                    <button 
                                        type="button" 
                                        wire:click="cancelOperation({{ $operation->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200">
                                        Cancel
                                    </button>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mb-2">
                            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                                <span>Progress</span>
                                <span>{{ $operation->processed_records }}/{{ $operation->total_records }} ({{ number_format($operation->progress_percentage, 1) }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div 
                                    class="bg-{{ $operation->getStatusColor() }}-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $operation->progress_percentage }}%"
                                ></div>
                            </div>
                        </div>
                        
                        <!-- Operation Details -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Successful</p>
                                <p class="font-medium text-green-600">{{ number_format($operation->successful_records) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Failed</p>
                                <p class="font-medium text-red-600">{{ number_format($operation->failed_records) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Success Rate</p>
                                <p class="font-medium text-blue-600">{{ number_format($operation->getSuccessRate(), 1) }}%</p>
                            </div>
                            <div>
                                <p class="text-gray-500 dark:text-gray-400">Est. Time</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ $operation->getEstimatedTimeRemaining() ?? 'Calculating...' }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Recent Operations -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Operations</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Operations from the last 24 hours
            </p>
        </div>
        
        <div class="p-6">
            @if($recentOperations->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-squares-plus class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No recent operations</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating a new bulk operation.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($recentOperations as $operation)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$operation->getTypeIcon()" 
                                        class="h-6 w-6 text-{{ $operation->getStatusColor() }}-600" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ ucfirst($operation->operation_type) }} {{ class_basename($operation->model_type) }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $operation->user->name }} • {{ $operation->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($operation->processed_records) }}/{{ number_format($operation->total_records) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $operation->getDuration() ?? 'N/A' }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $operation->getStatusColor() }}-100 text-{{ $operation->getStatusColor() }}-800">
                                        {{ ucfirst($operation->status) }}
                                    </span>
                                    @if($operation->operation_type === 'export' && $operation->isCompleted())
                                        <button 
                                            type="button" 
                                            wire:click="downloadExport({{ $operation->id }})"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200">
                                            Download
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Available Models -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Available Models</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Models available for bulk operations
            </p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($availableModels as $modelClass => $modelInfo)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            {{ $modelInfo['name'] }}
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            {{ $modelClass }}
                        </p>
                        <div class="mb-3">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Available Fields:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($modelInfo['fields'] as $field)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $field }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Supported Operations:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($modelInfo['operations'] as $operation)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                        {{ ucfirst($operation) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-refresh active operations every 5 seconds
    setInterval(function() {
        @this.call('loadOperationsData');
    }, 5000);
</script>