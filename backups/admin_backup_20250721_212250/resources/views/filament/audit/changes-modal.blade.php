<div class="space-y-6">
    @if(!empty($record->old_values) || !empty($record->new_values))
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Previous Values -->
            @if(!empty($record->old_values))
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Previous Values</h3>
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <pre class="text-sm text-red-800 dark:text-red-200 whitespace-pre-wrap">{{ json_encode($record->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif

            <!-- New Values -->
            @if(!empty($record->new_values))
                <div>
                    <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">New Values</h3>
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <pre class="text-sm text-green-800 dark:text-green-200 whitespace-pre-wrap">{{ json_encode($record->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>

        <!-- Diff View -->
        @if(!empty($record->old_values) && !empty($record->new_values))
            <div>
                <h3 class="text-lg font-semibold mb-3 text-gray-900 dark:text-white">Changes Summary</h3>
                <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    @php
                        $oldValues = $record->old_values ?? [];
                        $newValues = $record->new_values ?? [];
                        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
                    @endphp
                    
                    @foreach($allKeys as $key)
                        @php
                            $oldValue = $oldValues[$key] ?? null;
                            $newValue = $newValues[$key] ?? null;
                            $hasChanged = $oldValue !== $newValue;
                        @endphp
                        
                        @if($hasChanged)
                            <div class="mb-2 p-2 border-l-4 border-blue-400 bg-blue-50 dark:bg-blue-900/20">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $key }}</div>
                                <div class="text-sm">
                                    @if($oldValue !== null)
                                        <span class="text-red-600 dark:text-red-400">- {{ is_array($oldValue) ? json_encode($oldValue) : $oldValue }}</span><br>
                                    @endif
                                    @if($newValue !== null)
                                        <span class="text-green-600 dark:text-green-400">+ {{ is_array($newValue) ? json_encode($newValue) : $newValue }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="text-center text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p class="mt-2">No change data available for this audit log entry.</p>
        </div>
    @endif
</div>