<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">
                        🩺 Dashboard Paramedis
                    </h1>
                    <p class="text-emerald-100 mt-1">
                        Selamat datang, {{ auth()->user()->name }}! Kelola presensi dan data Anda dengan mudah.
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-emerald-100 text-sm">{{ now()->format('d F Y') }}</div>
                    <div class="text-white text-lg font-semibold">{{ now()->format('H:i') }}</div>
                </div>
            </div>
        </div>

        {{-- Quick Navigation --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="{{ url('/paramedis/attendance-histories') }}" 
               class="group bg-white dark:bg-gray-800 rounded-lg p-6 border-2 border-emerald-200 hover:border-emerald-400 transition-colors">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-emerald-500 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                        📊
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-emerald-600">
                            Laporan Presensi
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Lihat riwayat kehadiran
                        </p>
                    </div>
                </div>
            </a>

            <a href="{{ url('/paramedis/attendances') }}" 
               class="group bg-white dark:bg-gray-800 rounded-lg p-6 border-2 border-blue-200 hover:border-blue-400 transition-colors">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                        ✏️
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-blue-600">
                            Input Presensi
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Tambah/edit presensi
                        </p>
                    </div>
                </div>
            </a>

            <a href="{{ url('/paramedis/mobile-app') }}" 
               class="group bg-white dark:bg-gray-800 rounded-lg p-6 border-2 border-purple-200 hover:border-purple-400 transition-colors">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xl mr-4">
                        📱
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white group-hover:text-purple-600">
                            Mobile App
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Akses aplikasi mobile
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Widgets --}}
        <div class="space-y-6">
            @foreach ($this->getWidgets() as $widget)
                @livewire(\Filament\Facades\Filament::getWidget($widget))
            @endforeach
        </div>
    </div>
</x-filament-panels::page>