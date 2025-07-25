{{-- DEBUG: Work Location Form Structure Validation --}}
<div class="work-location-debug-wrapper">
    {{-- This single root element ensures Livewire compatibility --}}
    <div class="hidden debug-info text-xs text-gray-500 p-2 border border-gray-200 rounded">
        <strong>✅ Single Root Element Structure:</strong><br>
        - No multiple root elements<br>
        - No inline JavaScript in HtmlString<br> 
        - All components use proper Blade views<br>
        - Livewire-safe DOM structure<br>
        <em>Generated: {{ now()->format('Y-m-d H:i:s') }}</em>
    </div>
</div>