<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Event Details</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $record->action_description }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">User</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $record->user?->name ?? 'System' }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $record->ip_address }}</p>
            </div>
            
            <div>
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Timestamp</p>
                <p class="text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('Y-m-d H:i:s') }}</p>
            </div>
        </div>
    </div>
    
    @if($record->description)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Description</h3>
            <p class="text-sm text-gray-900 dark:text-white">{{ $record->description }}</p>
        </div>
    @endif
    
    @if($record->metadata)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Event Metadata</h3>
            
            <div class="space-y-3">
                @foreach($record->metadata as $key => $value)
                    <div class="flex items-start justify-between py-2 border-b border-gray-200 dark:border-gray-700 last:border-b-0">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $key)) }}</p>
                            <div class="mt-1">
                                @if(is_array($value))
                                    <pre class="text-xs text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 rounded p-2 overflow-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                @elseif(is_bool($value))
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $value ? 'Yes' : 'No' }}
                                    </span>
                                @else
                                    <p class="text-sm text-gray-900 dark:text-white">{{ $value }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    
    @if($record->user_agent)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">User Agent</h3>
            <p class="text-xs text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-700 rounded p-2 font-mono">{{ $record->user_agent }}</p>
        </div>
    @endif
</div>