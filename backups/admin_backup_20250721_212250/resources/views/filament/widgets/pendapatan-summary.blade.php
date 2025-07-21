<div class="p-6 space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Total Pendapatan Hari Ini -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-xl p-4 border border-green-200 dark:border-green-700">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-green-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-green-600 dark:text-green-400">Hari Ini</p>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300">
                        Rp {{ number_format(\App\Models\PendapatanHarian::where('user_id', auth()->id())->whereDate('tanggal_input', today())->sum('nominal'), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Minggu Ini -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-700">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Minggu Ini</p>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                        Rp {{ number_format(\App\Models\PendapatanHarian::where('user_id', auth()->id())->whereBetween('tanggal_input', [now()->startOfWeek(), now()->endOfWeek()])->sum('nominal'), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Total Bulan Ini -->
        <div class="bg-gradient-to-br from-purple-50 to-violet-50 dark:from-purple-900/20 dark:to-violet-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-700">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-purple-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-purple-600 dark:text-purple-400">Bulan Ini</p>
                    <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">
                        Rp {{ number_format(\App\Models\PendapatanHarian::where('user_id', auth()->id())->whereBetween('tanggal_input', [now()->startOfMonth(), now()->endOfMonth()])->sum('nominal'), 0, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistik Per Shift -->
    <div class="bg-gradient-to-br from-gray-50 to-slate-50 dark:from-gray-800/50 dark:to-slate-800/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
            </svg>
            Pendapatan Per Shift (Bulan Ini)
        </h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @php
                $shiftPagi = \App\Models\PendapatanHarian::where('user_id', auth()->id())
                    ->where('shift', 'Pagi')
                    ->whereBetween('tanggal_input', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('nominal');
                    
                $shiftSore = \App\Models\PendapatanHarian::where('user_id', auth()->id())
                    ->where('shift', 'Sore')
                    ->whereBetween('tanggal_input', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('nominal');
            @endphp
            
            <div class="flex items-center justify-between p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg border border-orange-200 dark:border-orange-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-orange-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-orange-700 dark:text-orange-300">🌅 Shift Pagi</span>
                </div>
                <span class="text-lg font-bold text-orange-600 dark:text-orange-400">
                    Rp {{ number_format($shiftPagi, 0, ',', '.') }}
                </span>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-amber-50 dark:bg-amber-900/20 rounded-lg border border-amber-200 dark:border-amber-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-amber-500 rounded-lg">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                    </div>
                    <span class="font-medium text-amber-700 dark:text-amber-300">🌆 Shift Sore</span>
                </div>
                <span class="text-lg font-bold text-amber-600 dark:text-amber-400">
                    Rp {{ number_format($shiftSore, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>

    <!-- Data Terbaru -->
    <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-xl p-6 border border-teal-200 dark:border-teal-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Pendapatan Terbaru
        </h3>
        
        @php
            $recentData = \App\Models\PendapatanHarian::with('pendapatan')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        @endphp
        
        <div class="space-y-3">
            @forelse($recentData as $item)
                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-teal-500 rounded-lg">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $item->pendapatan?->nama_pendapatan ?? 'N/A' }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->tanggal_input->format('d/m/Y') }} • 
                                @if($item->shift == 'Pagi')
                                    🌅 Pagi
                                @else
                                    🌆 Sore
                                @endif
                            </p>
                        </div>
                    </div>
                    <span class="font-bold text-teal-600 dark:text-teal-400">
                        Rp {{ number_format($item->nominal, 0, ',', '.') }}
                    </span>
                </div>
            @empty
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p>Belum ada data pendapatan</p>
                </div>
            @endforelse
        </div>
    </div>
</div>