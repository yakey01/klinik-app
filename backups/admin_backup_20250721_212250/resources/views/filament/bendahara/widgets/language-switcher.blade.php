<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                    {{ __('Language') }}
                </span>
            </div>
            
            <div class="flex items-center gap-2">
                @foreach($supported_locales as $locale => $config)
                    <button
                        wire:click="changeLanguage('{{ $locale }}')"
                        class="flex items-center gap-2 px-2 py-1 text-xs rounded-md transition-colors
                               {{ $current_locale === $locale 
                                  ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' 
                                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}"
                        title="{{ $config['name'] }}"
                    >
                        <span class="text-sm">{{ $config['flag'] }}</span>
                        <span class="hidden sm:inline">{{ $locale }}</span>
                    </button>
                @endforeach
            </div>
        </div>
        
        @if($is_rtl)
            <div class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                RTL layout active
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>