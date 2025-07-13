@props(['record'])

<div class="space-y-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Total Permohonan</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->permohonanCutis()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Disetujui</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->permohonanCutis()->where('status', 'Disetujui')->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Menunggu</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $record->permohonanCutis()->where('status', 'Menunggu')->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Allocation Info -->
    @if($record->alokasi_hari)
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Informasi Alokasi</h4>
        
        @php
            $totalUsed = $record->permohonanCutis()
                ->where('status', 'Disetujui')
                ->whereYear('tanggal_mulai', date('Y'))
                ->sum('durasicuti');
            $totalEmployees = \App\Models\User::count();
            $maxPossible = $totalEmployees * $record->alokasi_hari;
            $usagePercentage = $maxPossible > 0 ? ($totalUsed / $maxPossible) * 100 : 0;
        @endphp
        
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">Alokasi per Pegawai:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $record->alokasi_hari }} hari/tahun</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">Total Terpakai ({{ date('Y') }}):</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $totalUsed }} hari</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-600 dark:text-gray-400">Maksimal Tersedia:</span>
                <span class="font-medium text-gray-900 dark:text-white">{{ $maxPossible }} hari</span>
            </div>
            
            <!-- Usage Bar -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="h-3 rounded-full {{ $usagePercentage > 80 ? 'bg-red-500' : ($usagePercentage > 60 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                     style="width: {{ min($usagePercentage, 100) }}%"></div>
            </div>
            <div class="text-xs text-center text-gray-500 dark:text-gray-400">
                {{ round($usagePercentage, 1) }}% dari total alokasi terpakai
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Usage -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Penggunaan Terbaru</h4>
        
        @php
            $recentUsage = $record->permohonanCutis()
                ->with('pegawai')
                ->latest('tanggal_pengajuan')
                ->limit(5)
                ->get();
        @endphp
        
        @if($recentUsage->count() > 0)
        <div class="space-y-3">
            @foreach($recentUsage as $usage)
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center w-8 h-8 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full text-sm font-medium">
                            {{ substr($usage->pegawai->name, 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $usage->pegawai->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $usage->tanggal_mulai->format('d/m/Y') }} - {{ $usage->tanggal_selesai->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        @if($usage->status === 'Disetujui') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($usage->status === 'Menunggu') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @endif">
                        {{ $usage->status }}
                    </span>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $usage->durasicuti }} hari</p>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum Ada Penggunaan</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jenis cuti ini belum pernah digunakan</p>
        </div>
        @endif
    </div>
</div>