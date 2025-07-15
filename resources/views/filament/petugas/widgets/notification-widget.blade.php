@php
    $viewData = $this->getViewData();
    $total = $viewData['total'];
    $unread = $viewData['unread'];
    $notifications = $viewData['notifications'];
    $error = $viewData['error'] ?? null;
@endphp

<div class="fi-wi-stats-overview">
    <div class="fi-wi-stats-overview-cards-grid grid gap-6 lg:grid-cols-4">
        <div class="fi-wi-stats-overview-card relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-wi-stats-overview-card-icon-wrapper flex items-center justify-center rounded-xl bg-primary-50 p-3 dark:bg-primary-500/10">
                <svg class="fi-wi-stats-overview-card-icon h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM10 7L7 4v3H2v10h5V7z"/>
                </svg>
            </div>
            
            <div class="fi-wi-stats-overview-card-content">
                <h3 class="fi-wi-stats-overview-card-label text-sm font-medium text-gray-500 dark:text-gray-400">
                    🔔 Notifications
                </h3>
                <p class="fi-wi-stats-overview-card-value text-3xl font-semibold text-gray-900 dark:text-white">
                    {{ $total }}
                </p>
                @if($error)
                    <p class="fi-wi-stats-overview-card-description text-sm text-danger-600 dark:text-danger-400">
                        ⚠️ {{ $error }}
                    </p>
                @elseif($unread > 0)
                    <p class="fi-wi-stats-overview-card-description text-sm text-danger-600 dark:text-danger-400">
                        {{ $unread }} belum dibaca
                    </p>
                @else
                    <p class="fi-wi-stats-overview-card-description text-sm text-success-600 dark:text-success-400">
                        Semua sudah dibaca
                    </p>
                @endif
            </div>
        </div>
        
        <div class="lg:col-span-3">
            <div class="fi-wi-stats-overview-card relative overflow-hidden rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        📬 Notifikasi Terbaru
                    </h3>
                    @if($total > 0)
                        <button 
                            type="button" 
                            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                            wire:click="clearAll"
                        >
                            Bersihkan Semua
                        </button>
                    @endif
                </div>
                
                <div class="space-y-4 max-h-80 overflow-y-auto">
                    @if($error)
                        <div class="text-center py-8 text-red-500 dark:text-red-400">
                            <div class="text-4xl mb-2">⚠️</div>
                            <p class="text-sm">{{ $error }}</p>
                            <button 
                                type="button" 
                                class="mt-2 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300"
                                wire:click="$refresh"
                            >
                                Coba Lagi
                            </button>
                        </div>
                    @else
                        @forelse($notifications as $notification)
                            <div 
                                class="notification-item flex items-start space-x-3 p-3 rounded-lg transition-colors
                                    {{ $notification['read_at'] ? 'bg-gray-50 dark:bg-gray-800' : 'bg-primary-50 dark:bg-primary-900/20' }}
                                    hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer"
                                wire:click="markAsRead('{{ $notification['id'] }}')"
                            >
                                <div class="flex-shrink-0">
                                    @php
                                        $priorityIcon = match($notification['priority'] ?? 'medium') {
                                            'low' => 'ℹ️',
                                            'medium' => '📢',
                                            'high' => '⚠️',
                                            'urgent' => '🚨',
                                            'critical' => '🔥',
                                            default => '📢',
                                        };
                                    @endphp
                                    <span class="text-lg">{{ $priorityIcon }}</span>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $notification['title'] ?? 'Notifikasi' }}
                                        </h4>
                                        <time class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ isset($notification['created_at']) ? \Carbon\Carbon::parse($notification['created_at'])->diffForHumans() : 'Tidak diketahui' }}
                                        </time>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                        {{ $notification['message'] ?? 'Tidak ada pesan' }}
                                    </p>
                                    
                                    @if(($notification['type'] ?? '') === 'validation_pending')
                                        <div class="mt-2 flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                                                Menunggu Validasi
                                            </span>
                                            @if(isset($notification['data']['priority']))
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                    {{ $notification['data']['priority'] === 'urgent' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' }}">
                                                    {{ $notification['data']['priority'] === 'urgent' ? 'Urgent' : 'Normal' }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                @if(!($notification['read_at'] ?? false))
                                    <div class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-primary-600 rounded-full"></div>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <div class="text-4xl mb-2">📭</div>
                                <p class="text-sm">Tidak ada notifikasi</p>
                            </div>
                        @endforelse
                    @endif
                </div>
                
                <div class="mt-4 flex justify-between items-center text-xs text-gray-500">
                    <span>Terakhir diperbarui: {{ $viewData['last_updated'] }}</span>
                    <span>User ID: {{ $viewData['user_id'] ?? 'Unknown' }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.notification-item {
    transition: all 0.2s ease-in-out;
}

.notification-item:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>