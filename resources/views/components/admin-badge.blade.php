@php
    $variants = $getVariantClasses();
    $sizes = $getSizeClasses();
@endphp

<span class="inline-flex items-center {{ $sizes['padding'] }} {{ $sizes['text'] }} font-medium {{ $variants['bg'] }} {{ $variants['text'] }} border {{ $variants['border'] }} {{ $getShapeClasses() }}" 
      @if($removable) x-data="{ show: true }" x-show="show" x-transition @endif>
    
    @if($icon)
        <x-dynamic-component :component="$icon" class="{{ $sizes['icon'] }} mr-1" />
    @endif
    
    <span class="truncate">{{ $slot }}</span>
    
    @if($removable)
        <button type="button" 
                @if($removeAction) 
                    onclick="{{ $removeAction }}"
                @else
                    @click="show = false"
                @endif
                class="ml-1 inline-flex items-center justify-center {{ $variants['text'] }} hover:bg-black/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 rounded-full transition-colors duration-150">
            <span class="sr-only">Remove</span>
            <x-heroicon-o-x-mark class="{{ $sizes['icon'] }}" />
        </button>
    @endif
</span>