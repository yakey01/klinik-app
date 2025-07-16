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
            <!-- Dashboard Enhanced Link -->
            <a href="{{ route('petugas.enhanced.dashboard') }}" 
               target="_blank"
               class="block bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-lg p-4 border border-emerald-300 dark:border-emerald-600 hover:shadow-lg transition-all hover:from-emerald-600 hover:to-emerald-700 transform hover:scale-105">
                <div class="flex items-center space-x-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <div>
                        <h3 class="text-sm font-medium text-white">
                            ✨ Dashboard Enhanced
                        </h3>
                        <p class="text-xs text-emerald-100">Modern UI dengan Charts</p>
                    </div>
                </div>
            </a>
            
            @forelse ($viewData['actions'] as $action)
                <a href="{{ $action['url'] }}" 
                   class="block bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow hover:border-{{ $action['color'] }}-300 dark:hover:border-{{ $action['color'] }}-600">
                    <div class="flex items-center space-x-3">
                        @if($action['icon'])
                            <x-dynamic-component 
                                :component="$action['icon']" 
                                class="w-6 h-6 text-{{ $action['color'] }}-600 dark:text-{{ $action['color'] }}-400" 
                            />
                        @endif
                        <div>
                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $action['label'] }}
                            </h3>
                        </div>
                    </div>
                </a>
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