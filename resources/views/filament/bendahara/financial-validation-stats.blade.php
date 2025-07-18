<div class="space-y-6">
    {{-- Financial Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Income Stats --}}
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg border border-green-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-700">💰 Total Income</p>
                    <p class="text-2xl font-bold text-green-900">{{ number_format($stats['pendapatan']['total']) }}</p>
                    <p class="text-xs text-green-600">Rp {{ number_format($stats['pendapatan']['total_value'] / 1000000, 1) }}M</p>
                </div>
                <div class="p-2 bg-green-200 rounded-lg">
                    <svg class="w-6 h-6 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex justify-between text-xs">
                <span class="text-green-600">Pending: {{ $stats['pendapatan']['pending'] }}</span>
                <span class="text-green-800">Approved: {{ $stats['pendapatan']['approved'] }}</span>
            </div>
        </div>

        {{-- Expense Stats --}}
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg border border-red-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-700">💸 Total Expenses</p>
                    <p class="text-2xl font-bold text-red-900">{{ number_format($stats['pengeluaran']['total']) }}</p>
                    <p class="text-xs text-red-600">Rp {{ number_format($stats['pengeluaran']['total_value'] / 1000000, 1) }}M</p>
                </div>
                <div class="p-2 bg-red-200 rounded-lg">
                    <svg class="w-6 h-6 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex justify-between text-xs">
                <span class="text-red-600">Pending: {{ $stats['pengeluaran']['pending'] }}</span>
                <span class="text-red-800">Approved: {{ $stats['pengeluaran']['approved'] }}</span>
            </div>
        </div>

        {{-- Net Cash Flow --}}
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-700">📊 Net Cash Flow</p>
                    <p class="text-2xl font-bold {{ $stats['combined']['net_cash_flow'] >= 0 ? 'text-green-900' : 'text-red-900' }}">
                        {{ $stats['combined']['net_cash_flow'] >= 0 ? '+' : '' }}{{ number_format($stats['combined']['net_cash_flow'] / 1000000, 1) }}M
                    </p>
                    <p class="text-xs text-blue-600">This month balance</p>
                </div>
                <div class="p-2 bg-blue-200 rounded-lg">
                    <svg class="w-6 h-6 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex justify-between text-xs">
                <span class="text-blue-600">Income: {{ number_format($stats['pendapatan']['total_value'] / 1000000, 1) }}M</span>
                <span class="text-blue-600">Expenses: {{ number_format($stats['pengeluaran']['total_value'] / 1000000, 1) }}M</span>
            </div>
        </div>

        {{-- Pending Validations --}}
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg border border-yellow-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-yellow-700">🕐 Pending Validations</p>
                    <p class="text-2xl font-bold text-yellow-900">{{ number_format($stats['combined']['total_pending']) }}</p>
                    <p class="text-xs text-yellow-600">Requires attention</p>
                </div>
                <div class="p-2 bg-yellow-200 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-2 flex justify-between text-xs">
                <span class="text-yellow-600">Income: {{ $stats['pendapatan']['pending'] }}</span>
                <span class="text-yellow-600">Expenses: {{ $stats['pengeluaran']['pending'] }}</span>
            </div>
        </div>
    </div>

    {{-- Detailed Analysis --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Today's Activity --}}
        <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📅 Today's Activity</h3>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Income Transactions:</span>
                    <div class="text-right">
                        <span class="font-semibold">{{ number_format($stats['pendapatan']['today']) }}</span>
                        <span class="text-sm text-green-600 ml-2">{{ $stats['pendapatan']['today_approved'] }} approved</span>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Expense Transactions:</span>
                    <div class="text-right">
                        <span class="font-semibold">{{ number_format($stats['pengeluaran']['today']) }}</span>
                        <span class="text-sm text-green-600 ml-2">{{ $stats['pengeluaran']['today_approved'] }} approved</span>
                    </div>
                </div>
                <div class="flex justify-between items-center pt-2 border-t">
                    <span class="text-gray-600">Total Today:</span>
                    <span class="font-semibold text-purple-600">{{ number_format($stats['combined']['total_today']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Completion Rate:</span>
                    <span class="font-semibold">{{ $stats['combined']['today_completion'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

        {{-- Validation Performance --}}
        <div class="bg-white rounded-lg border p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">📊 Validation Performance</h3>
            <div class="space-y-4">
                @php
                    $incomeProcessed = $stats['pendapatan']['approved'] + $stats['pendapatan']['rejected'];
                    $expenseProcessed = $stats['pengeluaran']['approved'] + $stats['pengeluaran']['rejected'];
                    $totalProcessed = $incomeProcessed + $expenseProcessed;
                    $totalRecords = $stats['pendapatan']['total'] + $stats['pengeluaran']['total'];
                    
                    $incomeApprovalRate = $incomeProcessed > 0 ? round(($stats['pendapatan']['approved'] / $incomeProcessed) * 100, 1) : 0;
                    $expenseApprovalRate = $expenseProcessed > 0 ? round(($stats['pengeluaran']['approved'] / $expenseProcessed) * 100, 1) : 0;
                    $overallProcessingRate = $totalRecords > 0 ? round(($totalProcessed / $totalRecords) * 100, 1) : 0;
                @endphp
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Income Approval Rate:</span>
                    <span class="font-semibold text-green-600">{{ $incomeApprovalRate }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Expense Approval Rate:</span>
                    <span class="font-semibold text-green-600">{{ $expenseApprovalRate }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Overall Processing:</span>
                    <span class="font-semibold">{{ $overallProcessingRate }}%</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">High Value Items:</span>
                    <span class="font-semibold text-orange-600">{{ $stats['combined']['total_high_value'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Category Breakdown --}}
    <div class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">🏷️ Category Analysis</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Income Categories --}}
            <div>
                <h4 class="font-medium text-green-700 mb-3">💰 Income Categories</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Medical Procedures:</span>
                        <span class="font-medium">High Priority</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Consultations:</span>
                        <span class="font-medium">Standard</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Medications:</span>
                        <span class="font-medium">Standard</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Others:</span>
                        <span class="font-medium text-yellow-600">Review Required</span>
                    </div>
                </div>
            </div>

            {{-- Expense Categories --}}
            <div>
                <h4 class="font-medium text-red-700 mb-3">💸 Expense Categories</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Operational:</span>
                        <span class="font-medium">Standard</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Medical Supplies:</span>
                        <span class="font-medium">High Priority</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Administration:</span>
                        <span class="font-medium">Standard</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Infrastructure:</span>
                        <span class="font-medium text-orange-600">Special Approval</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Tips --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">💡 Financial Validation Tips</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Use Quick Actions for batch processing of routine transactions</li>
                        <li>High value transactions (>5M) are automatically flagged for manual review</li>
                        <li>Infrastructure expenses require special approval workflows</li>
                        <li>Monitor cash flow trends and category patterns for anomaly detection</li>
                        <li>Use tabs to efficiently switch between income and expense validations</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>