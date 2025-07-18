<div class="space-y-6">
    {{-- Transaction Summary --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600">{{ $type === 'income' ? 'Income' : 'Expense' }} Transaction</p>
                <p class="text-lg font-semibold text-gray-900">
                    {{ $type === 'income' ? $record->nama_pendapatan : $record->nama_pengeluaran }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Amount</p>
                <p class="text-lg font-semibold {{ $type === 'income' ? 'text-green-700' : 'text-red-700' }}">
                    {{ $type === 'income' ? '+' : '-' }}Rp {{ number_format($record->nominal) }}
                </p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Category</p>
                <p class="text-lg font-semibold text-gray-900">{{ ucfirst(str_replace('_', ' ', $record->kategori)) }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Date</p>
                <p class="text-lg font-semibold text-gray-900">{{ $record->tanggal->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            @foreach($timeline as $index => $event)
            <li>
                <div class="relative pb-8">
                    @if(!$loop->last)
                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                    @endif
                    
                    <div class="relative flex space-x-3">
                        <div>
                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white 
                                {{ match($event['color']) {
                                    'success' => 'bg-green-500',
                                    'danger' => 'bg-red-500', 
                                    'warning' => 'bg-yellow-500',
                                    'info' => 'bg-blue-500',
                                    default => 'bg-gray-500'
                                } }}">
                                @if($event['icon'] === 'heroicon-o-plus-circle')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                @elseif($event['icon'] === 'heroicon-o-check-circle')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @elseif($event['icon'] === 'heroicon-o-x-circle')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @elseif($event['icon'] === 'heroicon-o-pencil-square')
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </span>
                        </div>
                        
                        <div class="min-w-0 flex-1 pt-1.5">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $event['event'] }}</p>
                                    <p class="text-sm text-gray-500">by {{ $event['user'] }}</p>
                                </div>
                                <time class="text-sm text-gray-500" datetime="{{ $event['timestamp']->toISOString() }}">
                                    {{ $event['timestamp']->format('d/m/Y H:i') }}
                                </time>
                            </div>
                            
                            @if($event['description'])
                            <div class="mt-2">
                                <p class="text-sm text-gray-700 bg-gray-50 rounded p-2 whitespace-pre-line">
                                    {{ $event['description'] }}
                                </p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Current Status Summary --}}
    <div class="bg-white border rounded-lg p-4">
        <h4 class="font-medium text-gray-900 mb-3">📋 Current Status</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-600">Validation Status:</p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    {{ match($record->status_validasi) {
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'disetujui' => 'bg-green-100 text-green-800',
                        'ditolak' => 'bg-red-100 text-red-800',
                        'need_revision' => 'bg-blue-100 text-blue-800',
                        default => 'bg-gray-100 text-gray-800'
                    } }}">
                    {{ match($record->status_validasi) {
                        'pending' => '🕐 Pending Validation',
                        'disetujui' => '✅ Approved',
                        'ditolak' => '❌ Rejected',
                        'need_revision' => '📝 Needs Revision',
                        default => ucfirst($record->status_validasi)
                    } }}
                </span>
            </div>
            
            @if($record->validasi_at)
            <div>
                <p class="text-sm text-gray-600">Processing Time:</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ $record->created_at->diffForHumans($record->validasi_at, true) }}
                </p>
            </div>
            @endif
        </div>
        
        @if($record->catatan_validasi)
        <div class="mt-4">
            <p class="text-sm text-gray-600">💬 Validation Comments:</p>
            <div class="mt-1 text-sm text-gray-900 bg-gray-50 rounded p-3 whitespace-pre-line">{{ $record->catatan_validasi }}</div>
        </div>
        @endif
    </div>

    {{-- Financial Impact Analysis --}}
    <div class="bg-white border rounded-lg p-4">
        <h4 class="font-medium text-gray-900 mb-3">💹 Financial Impact</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-sm text-gray-600">Transaction Type</p>
                <p class="text-lg font-semibold {{ $type === 'income' ? 'text-green-700' : 'text-red-700' }}">
                    {{ $type === 'income' ? '💰 Income' : '💸 Expense' }}
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Amount Category</p>
                <p class="text-lg font-semibold text-gray-900">
                    @if($record->nominal > 10000000)
                        💎 Ultra High
                    @elseif($record->nominal > 5000000)
                        🔶 High Value
                    @elseif($record->nominal > 1000000)
                        🔸 Medium Value
                    @else
                        🔹 Standard
                    @endif
                </p>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">Risk Level</p>
                <p class="text-lg font-semibold 
                    @if($record->nominal > 10000000) text-red-700
                    @elseif($record->nominal > 5000000) text-orange-700
                    @elseif($record->nominal > 1000000) text-yellow-700
                    @else text-green-700
                    @endif">
                    @if($record->nominal > 10000000)
                        🚨 High Risk
                    @elseif($record->nominal > 5000000)
                        ⚠️ Medium Risk
                    @else
                        ✅ Low Risk
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Related Information --}}
    @if($type === 'income' && $record->tindakan_id)
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">🔗 Related Medical Procedure</h3>
                <div class="mt-1 text-sm text-blue-700">
                    <p>This income is linked to a medical procedure (Tindakan ID: {{ $record->tindakan_id }}). 
                       Validation should consider the corresponding medical service provided.</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Special Notes --}}
    @if($record->tanggal->isWeekend())
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-orange-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-orange-800">⚠️ Weekend Transaction</h3>
                <div class="mt-1 text-sm text-orange-700">
                    <p>This transaction was recorded on a weekend ({{ $record->tanggal->format('l, d/m/Y') }}). 
                       Please verify the legitimacy and urgency of this {{ $type }} transaction.</p>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>