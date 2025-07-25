<div class="shift-preview-wrapper">
    @php
        $colorClasses = [
            'morning' => ['border' => 'border-blue-400', 'bg' => 'from-blue-50 to-blue-100', 'title' => 'text-blue-800', 'time' => 'text-blue-700', 'detail' => 'text-blue-600'],
            'afternoon' => ['border' => 'border-yellow-400', 'bg' => 'from-yellow-50 to-yellow-100', 'title' => 'text-yellow-800', 'time' => 'text-yellow-700', 'detail' => 'text-yellow-600'],
            'night' => ['border' => 'border-indigo-400', 'bg' => 'from-indigo-50 to-indigo-100', 'title' => 'text-indigo-800', 'time' => 'text-indigo-700', 'detail' => 'text-indigo-600'],
        ];
        
        $colors = $colorClasses[$type] ?? $colorClasses['morning'];
    @endphp
    
    <div class="shift-preview-card {{ $type }}-shift p-4 rounded-lg border-l-4 {{ $colors['border'] }} bg-gradient-to-r {{ $colors['bg'] }}">
        <div class="flex items-center gap-2 mb-2">
            <span class="text-lg">{{ $icon }}</span>
            <span class="font-bold {{ $colors['title'] }}">{{ $title }}</span>
        </div>
        <div class="text-sm space-y-1">
            <div class="font-semibold {{ $colors['time'] }}">{{ $schedule }}</div>
            <div class="text-xs {{ $colors['detail'] }}">
                {!! $details !!}
            </div>
        </div>
    </div>
</div>