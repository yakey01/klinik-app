<div class="space-y-6">
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Records</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pending Validation</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($stats['pending']) }}</p>
                </div>
                <div class="p-2 bg-yellow-100 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Approved</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($stats['approved']) }}</p>
                </div>
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Rejected</p>
                    <p class="text-2xl font-bold text-red-600">{{ number_format($stats['rejected']) }}</p>
                </div>
                <div class="p-2 bg-red-100 rounded-lg">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Today's Activity --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📅 Today's Activity</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Today's Records:</span>
                    <span class="font-semibold">{{ number_format($stats['today']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">High Value Items:</span>
                    <span class="font-semibold text-purple-600">{{ number_format($stats['high_value']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Avg Processing:</span>
                    <span class="font-semibold">{{ $stats['avg_processing_time'] }} hrs</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">💰 Financial Summary</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Value:</span>
                    <span class="font-semibold">Rp {{ number_format($stats['total_value']) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Approved Value:</span>
                    <span class="font-semibold text-green-600">Rp {{ number_format($stats['total_value'] * 0.8) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Pending Value:</span>
                    <span class="font-semibold text-yellow-600">Rp {{ number_format($stats['total_value'] * 0.15) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Validation Rate</h3>
            <div class="space-y-3">
                @php
                    $totalProcessed = $stats['approved'] + $stats['rejected'];
                    $approvalRate = $totalProcessed > 0 ? round(($stats['approved'] / $totalProcessed) * 100, 1) : 0;
                    $rejectionRate = $totalProcessed > 0 ? round(($stats['rejected'] / $totalProcessed) * 100, 1) : 0;
                @endphp
                <div class="flex justify-between">
                    <span class="text-gray-600">Approval Rate:</span>
                    <span class="font-semibold text-green-600">{{ $approvalRate }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Rejection Rate:</span>
                    <span class="font-semibold text-red-600">{{ $rejectionRate }}%</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Processing Rate:</span>
                    <span class="font-semibold">{{ $stats['total'] > 0 ? round(($totalProcessed / $stats['total']) * 100, 1) : 0 }}%</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions Reminder --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">💡 Quick Tips</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Use Quick Actions for batch processing of routine validations</li>
                        <li>High value items (>1M) are automatically flagged for manual review</li>
                        <li>Use the tabs to filter by status for focused validation workflows</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>