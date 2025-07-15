<x-nonparamedis.layout title="Jadwal Non-Paramedis">
    <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-500 to-accent-400 animate-background-shift flex items-center justify-center p-5">
        <div class="w-full max-w-md bg-white rounded-[40px] p-8 shadow-2xl">
            <div class="text-center">
                <h1 class="text-3xl font-extrabold text-gray-800 mb-2">Jadwal</h1>
                <p class="text-gray-600 mb-8">Jadwal kerja non-paramedis</p>
                
                <div class="space-y-4">
                    <div class="bg-gradient-to-r from-primary-50 to-accent-50 p-4 rounded-2xl border border-primary-200">
                        <h3 class="font-bold text-primary-800">Hari Ini</h3>
                        <p class="text-sm text-primary-600">08:00 - 16:00</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-2xl">
                        <h3 class="font-bold text-gray-800">Besok</h3>
                        <p class="text-sm text-gray-600">08:00 - 16:00</p>
                    </div>
                    
                    <div class="bg-gray-50 p-4 rounded-2xl">
                        <h3 class="font-bold text-gray-800">Lusa</h3>
                        <p class="text-sm text-gray-600">08:00 - 16:00</p>
                    </div>
                </div>
                
                <a href="{{ route('nonparamedis.dashboard') }}" class="inline-block mt-6 text-primary-500 font-semibold hover:underline">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</x-nonparamedis.layout>