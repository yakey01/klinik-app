<div class="fi-wi-widget">
    <div class="fi-wi-widget-content">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Welcome Section --}}
            <div class="bg-gradient-to-r from-emerald-500 to-teal-500 rounded-lg p-6 text-white">
                <h2 class="text-xl font-bold mb-2">
                    🩺 Selamat datang, {{ $user_name }}!
                </h2>
                <p class="text-emerald-100 mb-4">
                    Kelola presensi dan lihat laporan kehadiran Anda dengan mudah
                </p>
                <div class="flex items-center text-sm">
                    <span class="bg-white/20 px-2 py-1 rounded">
                        📊 {{ $attendance_count }} total presensi
                    </span>
                    <span class="ml-2 bg-white/20 px-2 py-1 rounded">
                        📅 {{ $this_month_count }} bulan ini
                    </span>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                    🚀 Akses Cepat
                </h3>
                <div class="space-y-3">
                    <a href="{{ url('/paramedis/attendance-histories') }}" 
                       class="flex items-center p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-emerald-500 rounded-lg flex items-center justify-center text-white mr-3">
                            📊
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white group-hover:text-emerald-600">
                                Laporan Presensi Saya
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Lihat riwayat kehadiran lengkap
                            </div>
                        </div>
                        <div class="ml-auto text-emerald-500">
                            →
                        </div>
                    </a>

                    <a href="{{ url('/paramedis/attendances') }}" 
                       class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center text-white mr-3">
                            ✏️
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600">
                                Input Presensi
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Tambah atau edit presensi
                            </div>
                        </div>
                        <div class="ml-auto text-blue-500">
                            →
                        </div>
                    </a>

                    <a href="{{ url('/paramedis/mobile-app') }}" 
                       class="flex items-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors group">
                        <div class="flex-shrink-0 w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white mr-3">
                            📱
                        </div>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white group-hover:text-purple-600">
                                Mobile App
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                Akses aplikasi mobile
                            </div>
                        </div>
                        <div class="ml-auto text-purple-500">
                            →
                        </div>
                    </a>
                </div>
            </div>
        </div>

        {{-- Help Section --}}
        <div class="mt-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-amber-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                        💡
                    </div>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-amber-800 dark:text-amber-200">
                        Tips: Cara Melihat Laporan Presensi
                    </h4>
                    <div class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                        <p>Klik tombol <strong>"📊 Laporan Presensi Saya"</strong> di atas atau gunakan menu sidebar <strong>"📅 PRESENSI & LAPORAN"</strong> → <strong>"📊 Laporan Presensi Saya"</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>