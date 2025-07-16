<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <x-filament::icon
                    icon="heroicon-o-language"
                    class="w-5 h-5 text-gray-500"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Language
                </span>
            </div>
            
            <div class="flex items-center space-x-2">
                @foreach($this->getAvailableLocales() as $locale)
                    <x-filament::button
                        wire:click="switchLanguage('{{ $locale['code'] }}')"
                        :color="$this->getCurrentLocale() === $locale['code'] ? 'primary' : 'gray'"
                        size="sm"
                        :outlined="$this->getCurrentLocale() !== $locale['code']"
                    >
                        <span class="flex items-center space-x-1">
                            <span>{{ $locale['flag'] }}</span>
                            <span class="hidden sm:inline">{{ $locale['name'] }}</span>
                        </span>
                    </x-filament::button>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>