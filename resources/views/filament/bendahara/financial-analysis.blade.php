<div class="space-y-6">
    {{-- Transaction Overview --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 border border-blue-200">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">📊 Financial Analysis Report</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-sm text-blue-700">Transaction Amount</p>
                <p class="text-2xl font-bold {{ $type === 'income' ? 'text-green-700' : 'text-red-700' }}">
                    Rp {{ number_format($record->nominal) }}
                </p>
                <p class="text-xs text-blue-600">{{ $analysis['amount']['amount_category'] }}</p>
            </div>
            <div class="text-center">
                <p class="text-sm text-blue-700">Amount Percentile</p>
                <p class="text-2xl font-bold text-blue-900">{{ round($analysis['amount']['percentile']) }}%</p>
                <p class="text-xs text-blue-600">
                    {{ $analysis['amount']['is_outlier'] ? 'Statistical Outlier' : 'Normal Range' }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-blue-700">Risk Assessment</p>
                <p class="text-xl font-bold 
                    {{ match($analysis['validation']['risk_level']) {
                        'High Risk' => 'text-red-700',
                        'Medium Risk' => 'text-orange-700',
                        default => 'text-green-700'
                    } }}">
                    {{ $analysis['validation']['risk_level'] }}
                </p>
                <p class="text-xs text-blue-600">
                    {{ $analysis['validation']['requires_attention'] ? 'Requires Special Attention' : 'Standard Processing' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Category Analysis --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border p-6">
            <h4 class="font-semibold text-gray-900 mb-4">🏷️ Category Analysis</h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Category:</span>
                    <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $record->kategori)) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">This Month Total:</span>
                    <span class="font-medium">Rp {{ number_format($analysis['category']['total_this_month']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">This Month Count:</span>
                    <span class="font-medium">{{ number_format($analysis['category']['count_this_month']) }} transactions</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Category Average:</span>
                    <span class="font-medium">Rp {{ number_format($analysis['category']['average_amount']) }}</span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t">
                    <span class="text-gray-600">Variance from Average:</span>
                    <span class="font-medium {{ $analysis['category']['vs_this_transaction'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        {{ $analysis['category']['vs_this_transaction'] >= 0 ? '+' : '' }}Rp {{ number_format($analysis['category']['vs_this_transaction']) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- User Frequency Analysis --}}
        <div class="bg-white rounded-lg border p-6">
            <h4 class="font-semibold text-gray-900 mb-4">👤 User Frequency Analysis</h4>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Input By:</span>
                    <span class="font-medium">{{ $record->inputBy->name ?? 'Unknown' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">User's Transactions This Month:</span>
                    <span class="font-medium">{{ number_format($analysis['frequency']['by_user_this_month']) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">User Activity Level:</span>
                    <span class="font-medium {{ $analysis['frequency']['user_is_frequent'] ? 'text-blue-600' : 'text-gray-600' }}">
                        {{ $analysis['frequency']['user_is_frequent'] ? 'Frequent User' : 'Regular User' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Transaction Date:</span>
                    <span class="font-medium">{{ $record->tanggal->format('l, d/m/Y') }}</span>
                </div>
                @if($record->tanggal->isWeekend())
                <div class="bg-orange-50 border border-orange-200 rounded p-2">
                    <p class="text-sm text-orange-700">⚠️ Weekend transaction - verify urgency</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Risk Factors --}}
    <div class="bg-white rounded-lg border p-6">
        <h4 class="font-semibold text-gray-900 mb-4">🔍 Risk Assessment Details</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Amount Risk --}}
            <div class="text-center p-4 rounded-lg {{ $record->nominal > 5000000 ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200' }} border">
                <div class="text-2xl mb-2">
                    {{ $record->nominal > 10000000 ? '🚨' : ($record->nominal > 5000000 ? '⚠️' : '✅') }}
                </div>
                <p class="text-sm font-medium">Amount Risk</p>
                <p class="text-xs text-gray-600">
                    {{ $record->nominal > 10000000 ? 'Ultra High' : ($record->nominal > 5000000 ? 'High' : 'Standard') }}
                </p>
            </div>

            {{-- Category Risk --}}
            <div class="text-center p-4 rounded-lg {{ in_array($record->kategori, ['lainnya', 'infrastruktur']) ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200' }} border">
                <div class="text-2xl mb-2">
                    {{ in_array($record->kategori, ['lainnya', 'infrastruktur']) ? '⚠️' : '✅' }}
                </div>
                <p class="text-sm font-medium">Category Risk</p>
                <p class="text-xs text-gray-600">
                    {{ in_array($record->kategori, ['lainnya', 'infrastruktur']) ? 'Review Required' : 'Standard' }}
                </p>
            </div>

            {{-- Frequency Risk --}}
            <div class="text-center p-4 rounded-lg {{ $analysis['frequency']['user_is_frequent'] ? 'bg-blue-50 border-blue-200' : 'bg-green-50 border-green-200' }} border">
                <div class="text-2xl mb-2">
                    {{ $analysis['frequency']['user_is_frequent'] ? '📊' : '✅' }}
                </div>
                <p class="text-sm font-medium">Frequency Risk</p>
                <p class="text-xs text-gray-600">
                    {{ $analysis['frequency']['user_is_frequent'] ? 'High Activity' : 'Normal' }}
                </p>
            </div>

            {{-- Timing Risk --}}
            <div class="text-center p-4 rounded-lg {{ $record->tanggal->isWeekend() ? 'bg-orange-50 border-orange-200' : 'bg-green-50 border-green-200' }} border">
                <div class="text-2xl mb-2">
                    {{ $record->tanggal->isWeekend() ? '⏰' : '✅' }}
                </div>
                <p class="text-sm font-medium">Timing Risk</p>
                <p class="text-xs text-gray-600">
                    {{ $record->tanggal->isWeekend() ? 'Weekend Entry' : 'Business Hours' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Recommendations --}}
    <div class="bg-white rounded-lg border p-6">
        <h4 class="font-semibold text-gray-900 mb-4">💡 Validation Recommendations</h4>
        <div class="space-y-3">
            @if($analysis['validation']['requires_attention'])
                <div class="flex items-start space-x-3">
                    <span class="text-orange-500 text-lg">⚠️</span>
                    <div>
                        <p class="font-medium text-orange-800">High Priority Review Required</p>
                        <p class="text-sm text-orange-700">This transaction requires special attention due to amount, category, or timing factors.</p>
                    </div>
                </div>
            @endif

            @if($analysis['amount']['is_outlier'])
                <div class="flex items-start space-x-3">
                    <span class="text-purple-500 text-lg">📊</span>
                    <div>
                        <p class="font-medium text-purple-800">Statistical Outlier</p>
                        <p class="text-sm text-purple-700">This amount is statistically unusual. Verify documentation and justification.</p>
                    </div>
                </div>
            @endif

            @if($record->nominal > 5000000)
                <div class="flex items-start space-x-3">
                    <span class="text-red-500 text-lg">💰</span>
                    <div>
                        <p class="font-medium text-red-800">High Value Transaction</p>
                        <p class="text-sm text-red-700">Consider requiring additional approval levels or documentation for amounts over 5M.</p>
                    </div>
                </div>
            @endif

            @if(in_array($record->kategori, ['lainnya', 'infrastruktur']))
                <div class="flex items-start space-x-3">
                    <span class="text-blue-500 text-lg">🏷️</span>
                    <div>
                        <p class="font-medium text-blue-800">Category Review</p>
                        <p class="text-sm text-blue-700">{{ ucfirst($record->kategori) }} category transactions may require additional scrutiny or approval.</p>
                    </div>
                </div>
            @endif

            @if($analysis['frequency']['user_is_frequent'])
                <div class="flex items-start space-x-3">
                    <span class="text-green-500 text-lg">👤</span>
                    <div>
                        <p class="font-medium text-green-800">Frequent User</p>
                        <p class="text-sm text-green-700">User has high transaction volume this month. Consider expedited processing if patterns are consistent.</p>
                    </div>
                </div>
            @endif

            @if($record->tanggal->isWeekend())
                <div class="flex items-start space-x-3">
                    <span class="text-yellow-500 text-lg">📅</span>
                    <div>
                        <p class="font-medium text-yellow-800">Weekend Entry</p>
                        <p class="text-sm text-yellow-700">Verify the urgency and legitimacy of transactions recorded on weekends.</p>
                    </div>
                </div>
            @endif

            @if($analysis['validation']['risk_level'] === 'Low Risk')
                <div class="flex items-start space-x-3">
                    <span class="text-green-500 text-lg">✅</span>
                    <div>
                        <p class="font-medium text-green-800">Low Risk Transaction</p>
                        <p class="text-sm text-green-700">This transaction appears routine and can likely be processed with standard validation procedures.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="font-semibold text-gray-900 mb-3">⚡ Suggested Actions</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            @if($analysis['validation']['risk_level'] === 'Low Risk')
                <div class="bg-green-100 border border-green-300 rounded p-3 text-center">
                    <p class="text-green-800 font-medium">✅ Quick Approve</p>
                    <p class="text-xs text-green-700">Low risk - suitable for fast-track approval</p>
                </div>
            @endif

            @if($analysis['validation']['requires_attention'])
                <div class="bg-orange-100 border border-orange-300 rounded p-3 text-center">
                    <p class="text-orange-800 font-medium">👁️ Manual Review</p>
                    <p class="text-xs text-orange-700">Requires careful validation</p>
                </div>
            @endif

            @if($record->nominal > 10000000)
                <div class="bg-red-100 border border-red-300 rounded p-3 text-center">
                    <p class="text-red-800 font-medium">🔍 Supervisor Approval</p>
                    <p class="text-xs text-red-700">Ultra high value - escalate to supervisor</p>
                </div>
            @endif
        </div>
    </div>
</div>