<x-nonparamedis.layout title="Presensi Non-Paramedis">
    <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-500 to-accent-400 animate-background-shift flex items-center justify-center p-5">
        <div class="w-full max-w-md bg-white rounded-[40px] p-8 shadow-2xl">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-extrabold text-gray-800 mb-2">Presensi</h1>
                <p class="text-gray-600 mb-4">Sistem absensi non-paramedis</p>
            </div>
            
            {{-- Livewire Attendance Manager Component --}}
            <livewire:non-paramedis.attendance-manager />
            
            <div class="text-center mt-8">
                <a href="{{ route('nonparamedis.dashboard') }}" class="inline-block text-primary-500 font-semibold hover:underline transition-colors">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-nonparamedis.layout>