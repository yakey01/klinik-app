@php
    $colors = $getColorClasses();
    $percentage = $getPercentage();
@endphp

<div class="w-full">
    @if($showLabel && $labelPosition === 'top')
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $slot->isEmpty() ? 'Progress' : $slot }}
            </span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $getFormattedLabel() }}
            </span>
        </div>
    @endif
    
    <div class="flex items-center {{ $labelPosition === 'right' ? 'space-x-3' : '' }}">
        <div class="flex-1 {{ $colors['track'] }} rounded-full {{ $getSizeClasses() }} overflow-hidden">
            <div class="h-full {{ $colors['bg'] }} rounded-full transition-all duration-300 ease-out {{ $getStripedClasses() }}" 
                 style="width: {{ $percentage }}%"
                 @if($animated) 
                     class="animate-pulse" 
                 @endif>
            </div>
        </div>
        
        @if($showLabel && $labelPosition === 'right')
            <div class="flex-shrink-0">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $getFormattedLabel() }}
                </span>
            </div>
        @endif
    </div>
    
    @if($showLabel && $labelPosition === 'bottom')
        <div class="flex justify-between items-center mt-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $slot->isEmpty() ? 'Progress' : $slot }}
            </span>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $getFormattedLabel() }}
            </span>
        </div>
    @endif
</div>