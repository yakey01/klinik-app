<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Notifikasi
                    @if($unreadCount > 0)
                        <span class="ml-2 inline-flex items-center justify-center w-6 h-6 text-xs font-bold text-white bg-red-500 rounded-full">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </h3>
                @if($unreadCount > 0)
                    <button 
                        wire:click="markAllAsRead"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                    >
                        Tandai Semua Dibaca
                    </button>
                @endif
            </div>
            
            @if($notifications->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-bell-slash class="w-12 h-12 mx-auto text-gray-400 mb-2" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada notifikasi</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($notifications as $notification)
                        <div 
                            wire:click="markAsRead('{{ $notification['id'] }}')"
                            class="
                                p-3 rounded-lg cursor-pointer transition-colors
                                {{ !$notification['read'] ? 'bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30' : 'bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700' }}
                            "
                        >
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    <div class="
                                        w-8 h-8 rounded-full flex items-center justify-center
                                        {{ match($notification['color']) {
                                            'success' => 'bg-green-100 dark:bg-green-900/30',
                                            'warning' => 'bg-amber-100 dark:bg-amber-900/30',
                                            'danger' => 'bg-red-100 dark:bg-red-900/30',
                                            default => 'bg-blue-100 dark:bg-blue-900/30'
                                        } }}
                                    ">
                                        <x-dynamic-component 
                                            :component="$notification['icon']" 
                                            class="
                                                w-4 h-4
                                                {{ match($notification['color']) {
                                                    'success' => 'text-green-600 dark:text-green-400',
                                                    'warning' => 'text-amber-600 dark:text-amber-400',
                                                    'danger' => 'text-red-600 dark:text-red-400',
                                                    default => 'text-blue-600 dark:text-blue-400'
                                                } }}
                                            " 
                                        />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $notification['title'] }}
                                        </p>
                                        @if(!$notification['read'])
                                            <div class="w-2 h-2 bg-blue-600 rounded-full ml-2"></div>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        {{ $notification['message'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                        {{ $notification['time'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                    <a href="/paramedis/notifications" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 flex items-center justify-center">
                        Lihat Semua Notifikasi
                        <x-heroicon-o-arrow-right class="w-4 h-4 ml-1" />
                    </a>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>