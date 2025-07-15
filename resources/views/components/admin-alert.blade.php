@php
    $classes = $getTypeClasses();
    $iconComponent = $icon ?? $getDefaultIcon();
@endphp

<div class="rounded-lg {{ $classes['bg'] }} {{ $classes['border'] }} border {{ $getSizeClasses() }}" 
     @if($dismissible) x-data="{ show: true }" x-show="show" x-transition @endif>
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <x-dynamic-component :component="$iconComponent" class="{{ $getIconSizeClasses() }} {{ $classes['icon'] }}" />
        </div>
        
        <div class="ml-3 flex-1">
            @if($title)
                <h3 class="text-sm font-medium {{ $classes['text'] }} mb-1">
                    {{ $title }}
                </h3>
            @endif
            
            <div class="text-sm {{ $classes['text'] }} {{ $title ? '' : 'font-medium' }}">
                {{ $slot }}
            </div>
        </div>
        
        <div class="ml-4 flex-shrink-0 flex space-x-2">
            @if($action && $actionLabel)
                <button type="button" 
                        onclick="{{ $action }}"
                        class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded {{ $classes['button'] }} bg-transparent hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-150">
                    {{ $actionLabel }}
                </button>
            @endif
            
            @if($dismissible)
                <button type="button" 
                        @click="show = false"
                        class="inline-flex {{ $classes['button'] }} focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-150">
                    <span class="sr-only">Dismiss</span>
                    <x-heroicon-o-x-mark class="{{ $getIconSizeClasses() }}" />
                </button>
            @endif
        </div>
    </div>
</div>