<div class="tolerance-header mb-4">
    @php
        $colorClasses = [
            'blue' => 'text-blue-800',
            'green' => 'text-green-800', 
            'orange' => 'text-orange-800',
            'purple' => 'text-purple-800',
        ];
        
        $subtitleColorClasses = [
            'blue' => 'text-blue-600',
            'green' => 'text-green-600',
            'orange' => 'text-orange-600', 
            'purple' => 'text-purple-600',
        ];
        
        $dividerColorClasses = [
            'blue' => 'bg-blue-200',
            'green' => 'bg-green-200',
            'orange' => 'bg-orange-200',
            'purple' => 'bg-purple-200',
        ];
    @endphp
    
    <div class="flex items-center gap-3 mb-2">
        <div class="tolerance-icon text-2xl">{{ $icon }}</div>
        <div class="tolerance-title-group">
            <h3 class="text-lg font-bold {{ $colorClasses[$color] ?? 'text-slate-800' }} leading-tight">{{ $title }}</h3>
            <p class="text-sm {{ $subtitleColorClasses[$color] ?? 'text-slate-600' }} leading-tight">{{ $subtitle }}</p>
        </div>
    </div>
    <div class="tolerance-divider h-px {{ $dividerColorClasses[$color] ?? 'bg-slate-200' }} mb-3"></div>
</div>