<div class="space-y-4">
    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
        <div class="flex items-center mb-3">
            <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-red-600 mr-2" />
            <h3 class="text-lg font-medium text-red-900 dark:text-red-100">Operation Errors</h3>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div class="text-center">
                <p class="text-sm text-red-700 dark:text-red-300">Total Records</p>
                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ number_format($record->total_records) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-red-700 dark:text-red-300">Failed Records</p>
                <p class="text-2xl font-bold text-red-900 dark:text-red-100">{{ number_format($record->failed_records) }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-red-700 dark:text-red-300">Failure Rate</p>
                <p class="text-2xl font-bold text-red-900 dark:text-red-100">
                    {{ $record->total_records > 0 ? number_format(($record->failed_records / $record->total_records) * 100, 2) : 0 }}%
                </p>
            </div>
        </div>
    </div>

    @if($record->error_details && is_array($record->error_details))
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Error Details</h4>
            
            <div class="space-y-3 max-h-96 overflow-y-auto">
                @foreach($record->error_details as $index => $error)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    Error #{{ $index + 1 }}
                                </p>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    Record: {{ $error['record'] ?? 'Unknown' }}
                                </p>
                                <p class="text-sm text-red-600 dark:text-red-400 mt-2">
                                    {{ $error['error'] ?? 'Unknown error' }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                @if(isset($error['timestamp']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ \Carbon\Carbon::parse($error['timestamp'])->format('H:i:s') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No error details available</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Error details were not captured for this operation.
            </p>
        </div>
    @endif
</div>