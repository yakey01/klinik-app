@php
    $colors = $getColorClasses();
@endphp

<div class="relative overflow-hidden rounded-lg {{ $colors['bg'] }} {{ $colors['border'] }} border p-6 transition-all duration-200 hover:shadow-md">
    @if($loading)
        <div class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-600 dark:text-gray-400 truncate">
                {{ $title }}
            </p>
            @if($subtitle)
                <p class="text-xs text-gray-500 dark:text-gray-500 truncate">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
        
        @if($icon)
            <div class="flex-shrink-0">
                <div class="p-2 rounded-full {{ $colors['icon'] }}">
                    <x-dynamic-component :component="$icon" class="h-6 w-6" />
                </div>
            </div>
        @endif
    </div>
    
    <div class="mt-4">
        <div class="flex items-baseline">
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $value }}
            </p>
            
            @if($change)
                <div class="ml-2 flex items-center">
                    <x-dynamic-component :component="$getTrendIcon()" class="h-4 w-4 {{ $getChangeColorClasses() }}" />
                    <span class="ml-1 text-sm font-medium {{ $getChangeColorClasses() }}">
                        {{ $change }}
                    </span>
                </div>
            @endif
        </div>
        
        @if($period)
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                {{ $period }}
            </p>
        @endif
    </div>
    
    @if($trend)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {!! $trend !!}
            </div>
        </div>
    @endif
</div>