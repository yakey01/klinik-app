@php
    $sizes = $getSizeClasses();
    $isDisabled = $disabled || $loading;
    $attributes = $attributes->merge([
        'class' => $getBaseClasses() . ' ' . $sizes['padding'] . ' ' . $sizes['text'] . ' ' . $getStateClasses(),
    ]);
    
    if ($href) {
        $attributes = $attributes->merge(['href' => $href]);
        if ($target) {
            $attributes = $attributes->merge(['target' => $target]);
        }
    } else {
        $attributes = $attributes->merge([
            'type' => 'button',
            'disabled' => $isDisabled,
        ]);
    }
@endphp

<{{ $tag }} {{ $attributes }}>
    @if($loading)
        <svg class="animate-spin -ml-1 mr-2 {{ $sizes['icon'] }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif($icon && $iconPosition === 'left')
        <x-dynamic-component :component="$icon" class="{{ $sizes['icon'] }} {{ $slot->isEmpty() ? '' : 'mr-2' }}" />
    @endif
    
    <span class="truncate">{{ $slot }}</span>
    
    @if($icon && $iconPosition === 'right' && !$loading)
        <x-dynamic-component :component="$icon" class="{{ $sizes['icon'] }} {{ $slot->isEmpty() ? '' : 'ml-2' }}" />
    @endif
</{{ $tag }}>