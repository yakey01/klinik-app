@php
    $record = $getRecord();
    $latitude = $record->latitude;
    $longitude = $record->longitude;
    $radius = $record->radius;
    $name = $record->name;
@endphp

<div class="flex items-center space-x-2">
    <div class="flex-shrink-0">
        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
            @if($latitude && $longitude)
                <iframe 
                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $longitude - 0.01 }},{{ $latitude - 0.01 }},{{ $longitude + 0.01 }},{{ $latitude + 0.01 }}&layer=mapnik&marker={{ $latitude }},{{ $longitude }}"
                    width="80" 
                    height="80" 
                    style="border: none;"
                    loading="lazy"
                    title="Map preview for {{ $name }}"
                ></iframe>
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-400 dark:text-gray-600">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            @endif
        </div>
    </div>
    
    <div class="flex-1 min-w-0">
        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
            📍 {{ $name }}
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
            @if($latitude && $longitude)
                <div>📐 {{ number_format($latitude, 6) }}, {{ number_format($longitude, 6) }}</div>
                <div>🎯 Radius {{ $radius ?? 100 }}m</div>
            @else
                <div class="text-orange-500">⚠️ Koordinat belum diset</div>
            @endif
        </div>
    </div>
    
    @if($latitude && $longitude)
        <div class="flex-shrink-0">
            <a href="https://www.google.com/maps?q={{ $latitude }},{{ $longitude }}" 
               target="_blank" 
               class="inline-flex items-center px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                🗺️ Maps
            </a>
        </div>
    @endif
</div>