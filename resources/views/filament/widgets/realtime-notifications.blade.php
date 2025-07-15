<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Real-time Notifications
        </x-slot>
        
        <x-slot name="headerEnd">
            <x-filament::badge color="success">
                Live
            </x-filament::badge>
        </x-slot>

        <div class="space-y-3">
            @forelse($notifications as $notification)
                <div class="flex items-start space-x-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                    <div class="flex-shrink-0">
                        <x-filament::icon 
                            :icon="$notification['icon']"
                            class="w-5 h-5 text-{{ $notification['type'] === 'success' ? 'green' : ($notification['type'] === 'warning' ? 'yellow' : 'blue') }}-500"
                        />
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $notification['title'] }}
                            </p>
                            
                            <x-filament::badge 
                                :color="$notification['type'] === 'success' ? 'success' : ($notification['type'] === 'warning' ? 'warning' : 'info')"
                                size="sm"
                            >
                                {{ $notification['type'] }}
                            </x-filament::badge>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $notification['message'] }}
                        </p>
                        
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ $notification['timestamp'] }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="flex items-center justify-center p-6 text-gray-500 dark:text-gray-400">
                    <div class="text-center">
                        <x-filament::icon 
                            icon="heroicon-o-bell-slash"
                            class="w-8 h-8 mx-auto mb-2"
                        />
                        <p class="text-sm">No new notifications</p>
                    </div>
                </div>
            @endforelse
        </div>
        
        <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Auto-refresh every 5 seconds</span>
                <span>Last updated: {{ $lastUpdate }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>