@extends('layouts.app')

@section('title', 'Dashboard Jaspel - WORLD CLASS')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Jaspel</h1>
                    <p class="text-gray-600">Sistem pendapatan layanan medis terdepan</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-lg px-4 py-2 shadow-sm">
                        <div class="text-sm text-gray-500">Sekarang</div>
                        <div class="font-semibold text-gray-900" id="current-time"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard -->
        <div class="max-w-4xl mx-auto">
            <div id="jaspel-dashboard-root"></div>
        </div>

        <!-- Navigation -->
        <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 shadow-lg">
            <div class="max-w-md mx-auto">
                <div class="flex justify-around py-3">
                    <a href="/paramedis" class="flex flex-col items-center space-y-1 px-4 py-2 text-blue-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
                        </svg>
                        <span class="text-xs font-medium">Beranda</span>
                    </a>
                    <a href="/paramedis/resources/jaspels" class="flex flex-col items-center space-y-1 px-4 py-2 text-purple-600 bg-purple-50 rounded-lg">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"/>
                        </svg>
                        <span class="text-xs font-medium">Jaspel</span>
                    </a>
                    <a href="/paramedis/pages/presensi-mobile" class="flex flex-col items-center space-y-1 px-4 py-2 text-gray-500">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xs font-medium">Presensi</span>
                    </a>
                    <a href="/paramedis/profile" class="flex flex-col items-center space-y-1 px-4 py-2 text-gray-500">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-xs font-medium">Profile</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Update current time
    function updateTime() {
        const now = new Date();
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
    }
    
    setInterval(updateTime, 1000);
    updateTime();
</script>
@endsection

@push('scripts')
@vite(['resources/js/components/paramedis/dashboard-new.tsx'])
@endpush