<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Aktivitas Terbaru
        </x-slot>

        <div class="space-y-4">
            @forelse($this->getViewData()['activities'] as $activity)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 rounded-lg 
                            @if($activity['type'] === 'pendapatan') 
                                bg-green-100 dark:bg-green-900/30 
                            @elseif($activity['type'] === 'pengeluaran') 
                                bg-red-100 dark:bg-red-900/30 
                            @else 
                                bg-blue-100 dark:bg-blue-900/30 
                            @endif">
                            @if($activity['type'] === 'pendapatan')
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            @elseif($activity['type'] === 'pengeluaran')
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ Str::limit($activity['description'], 40) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $activity['validated_by'] }} • {{ $activity['updated_at']->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                            Rp {{ number_format($activity['amount']) }}
                        </p>
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                            @if($activity['status'] === 'disetujui' || $activity['status'] === 'approved') 
                                bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 
                            @elseif($activity['status'] === 'ditolak') 
                                bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 
                            @else 
                                bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 
                            @endif">
                            {{ ucfirst($activity['status']) }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-8">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-2 text-sm">Belum ada aktivitas terbaru</p>
                    </div>
                </div>
            @endforelse
        </div>
        
        @if(count($this->getViewData()['activities']) > 0)
            <div class="mt-4 text-center">
                <a href="{{ route('filament.bendahara.resources.unified-financial-validation.index') }}" 
                   class="text-sm text-primary-600 hover:text-primary-500 dark:text-primary-400">
                    Lihat Semua Aktivitas →
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>