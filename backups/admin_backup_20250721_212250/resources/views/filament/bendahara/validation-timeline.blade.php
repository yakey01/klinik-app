<div class="space-y-6">
    {{-- Record Summary --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm font-medium text-gray-600">Tindakan</p>
                <p class="text-lg font-semibold text-gray-900">{{ $record->jenisTindakan->nama }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Pasien</p>
                <p class="text-lg font-semibold text-gray-900">{{ $record->pasien->nama }}</p>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-600">Tarif</p>
                <p class="text-lg font-semibold text-gray-900">Rp {{ number_format($record->tarif) }}</p>
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
                                <p class="text-sm text-gray-700 bg-gray-50 rounded p-2">
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
                <p class="text-sm text-gray-600">Status Validasi:</p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                    {{ match($record->status_validasi) {
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100 text-gray-800'
                    } }}">
                    {{ match($record->status_validasi) {
                        'pending' => '🕐 Menunggu Validasi',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        default => ucfirst($record->status_validasi)
                    } }}
                </span>
            </div>
            
            @if($record->validated_at)
            <div>
                <p class="text-sm text-gray-600">Waktu Pemrosesan:</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ $record->created_at->diffForHumans($record->validated_at, true) }}
                </p>
            </div>
            @endif
        </div>
        
        @if($record->komentar_validasi)
        <div class="mt-4">
            <p class="text-sm text-gray-600">💬 Komentar Validasi:</p>
            <div class="mt-1 text-sm text-gray-900 bg-gray-50 rounded p-3 whitespace-pre-line">{{ $record->komentar_validasi }}</div>
        </div>
        @endif
    </div>
</div>