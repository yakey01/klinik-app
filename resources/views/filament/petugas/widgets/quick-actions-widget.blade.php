<x-filament-widgets::widget>
    <x-filament::section>
        @php
            $viewData = $this->getViewData();
        @endphp
        
        <x-slot name="heading">
            {{ $viewData['greeting'] }}
        </x-slot>

        <x-slot name="description">
            Akses cepat ke fungsi-fungsi utama sistem
        </x-slot>

        @if(isset($viewData['error']))
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <div class="flex items-center">
                    <span class="text-red-500 mr-2">⚠️</span>
                    <span class="text-sm text-red-700 dark:text-red-300">{{ $viewData['error'] }}</span>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @forelse ($viewData['actions'] as $action)
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                    {{ $action }}
                </div>
            @empty
                <div class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                    <div class="text-4xl mb-2">🔒</div>
                    <p class="text-sm">Tidak ada aksi yang tersedia</p>
                </div>
            @endforelse
        </div>

        @if(!empty($viewData['tips']))
            <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">
                    💡 Tips Workflow Harian
                </h3>
                <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                    @foreach ($viewData['tips'] as $tip)
                        <li class="flex items-start">
                            <span class="text-blue-500 mr-2">•</span>
                            {{ $tip }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-4 flex justify-between items-center text-xs text-gray-500">
            <span>Terakhir diperbarui: {{ $viewData['last_updated'] }}</span>
            <span>User: {{ $viewData['user']->name ?? 'Unknown' }}</span>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>