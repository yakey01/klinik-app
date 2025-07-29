@extends('layouts.app')

@section('title', 'Laporan Presensi - Paramedis')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50 flex flex-col max-w-md mx-auto relative overflow-hidden">
    <!-- Include CSRF token for API calls -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Include user authentication data for React component -->
    <meta name="user-data" content="{{ json_encode([
        'id' => auth()->user()->id,
        'name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'role' => auth()->user()->role?->name ?? 'paramedis',
        'authenticated' => true
    ]) }}">
    
    <!-- Include API token if available -->
    @php
        $apiToken = auth()->user()->tokens()->where('name', 'like', '%mobile-app%')->first();
        if (!$apiToken) {
            // Create a new token for this session if none exists
            $apiToken = auth()->user()->createToken('mobile-app-presensi-' . now()->timestamp);
        }
    @endphp
    <meta name="api-token" content="{{ $apiToken->plainTextToken ?? '' }}">
    
    <!-- Include Laravel session data for authentication -->
    <meta name="laravel-session" content="{{ session()->getId() }}">
    <meta name="laravel-token" content="{{ csrf_token() }}">
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 via-transparent to-blue-600/5 pointer-events-none"></div>
    
    <!-- Header -->
    <div class="relative z-10 bg-white/80 backdrop-blur-sm border-b border-gray-200 shadow-sm">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="text-center flex-1">
                    <h1 class="text-lg font-bold text-gray-900">Laporan Presensi</h1>
                </div>
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-700 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                    ←
                </a>
            </div>
        </div>
    </div>

    <!-- React Component Container - Match MainApp structure -->
    <div class="flex-1 p-4 pb-28 relative z-10">
        <div id="laporan-presensi-root"></div>
    </div>
</div>

<script>
    // Update time display
    function updateCurrentTime() {
        const now = new Date();
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
    
    // Update time every second
    setInterval(updateCurrentTime, 1000);
    updateCurrentTime();
</script>
@endsection

@push('scripts')
    @vite(['resources/js/app.js'])
@endpush