<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 dark:from-blue-700 dark:to-indigo-800 rounded-lg p-6 text-white">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <svg class="h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">Aplikasi Absensi Berbasis GPS</h2>
                    <p class="text-blue-100 mt-1">Sistem kehadiran modern dengan teknologi GPS dan geofencing</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <span class="text-green-600 dark:text-green-400 text-sm font-semibold">15</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Total Fitur</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Komprehensif</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 dark:text-blue-400 text-sm font-semibold">9</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Prioritas Tinggi</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Fitur Utama</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                            <span class="text-yellow-600 dark:text-yellow-400 text-sm font-semibold">📍</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">GPS Tracking</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Real-time</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                            <span class="text-purple-600 dark:text-purple-400 text-sm font-semibold">🔐</span>
                        </div>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Anti-Fraud</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Keamanan</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Table -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    🚀 Fitur-Fitur Unggulan
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Daftar lengkap fitur aplikasi absensi berbasis GPS yang modern dan profesional
                </p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-center">No</th>
                            <th scope="col" class="px-6 py-3">Fitur</th>
                            <th scope="col" class="px-6 py-3">Penjelasan</th>
                            <th scope="col" class="px-6 py-3 text-center">Kategori</th>
                            <th scope="col" class="px-6 py-3 text-center">Prioritas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->getViewData()['features'] as $feature)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 text-center">
                                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                                    {{ $feature['id'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $feature['fitur'] }}
                            </td>
                            <td class="px-6 py-4">
                                {{ $feature['penjelasan'] }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $badgeColor = match($feature['kategori']) {
                                        'Keamanan' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        'Lokasi' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'Validasi' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'Automasi' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'Verifikasi' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                        'Laporan' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                        'Monitoring' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'Manajemen' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                                        'Komunikasi' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                                        'Export' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'Analitik' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                                        'Skalabilitas' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'Platform' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'
                                    };
                                @endphp
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $badgeColor }}">
                                    {{ $feature['kategori'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $priorityColor = match($feature['prioritas']) {
                                        'Tinggi' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                        'Sedang' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                        'Rendah' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300',
                                        default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300'
                                    };
                                @endphp
                                <span class="text-xs font-medium px-2.5 py-0.5 rounded-full {{ $priorityColor }}">
                                    {{ $feature['prioritas'] }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Technology Stack Info -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                💻 Technology Stack
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl mb-2">📱</div>
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Mobile App</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">React Native / Flutter</p>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl mb-2">⚡</div>
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Backend</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Laravel + FilamentPHP</p>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="text-2xl mb-2">🗺️</div>
                    <h4 class="font-medium text-gray-900 dark:text-gray-100">Maps API</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Google Maps / OpenStreetMap</p>
                </div>
            </div>
        </div>

        <!-- Recommended Packages -->
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                📦 Recommended Laravel Packages
            </h3>
            
            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">IlhamGhaza/laravel-attendance-lab</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Laravel 11.x + Filament 3 + Face Recognition + Geolocation</p>
                    </div>
                    <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 text-xs rounded-full">⭐ Recommended</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">cheesegrits/filament-google-maps</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Google Maps integration untuk FilamentPHP</p>
                    </div>
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 text-xs rounded-full">🗺️ Maps</span>
                </div>
                
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-gray-100">ojkalam/gps_attendance</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">GPS Attendance Management dengan Laravel</p>
                    </div>
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 text-xs rounded-full">📍 GPS</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>