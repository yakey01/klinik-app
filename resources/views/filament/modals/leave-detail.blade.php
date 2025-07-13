@props(['record', 'leaveHistory', 'totalCutiTerpakai', 'sisaCuti'])

<div class="space-y-6">
    <!-- Header Card -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
        <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
                <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $record->pegawai->name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $record->pegawai->email }}</p>
                <div class="mt-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($record->status === 'Menunggu') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @elseif($record->status === 'Disetujui') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @endif">
                        @if($record->status === 'Menunggu') 🕐
                        @elseif($record->status === 'Disetujui') ✅
                        @else ❌
                        @endif
                        {{ $record->status }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Detail Cuti -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Detail Permohonan
            </h4>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Tanggal Mulai:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $record->tanggal_mulai->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Tanggal Selesai:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $record->tanggal_selesai->format('d/m/Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Durasi:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $record->durasicuti }} hari</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Jenis Cuti:</span>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                        @if($record->jenis_cuti === 'Cuti Tahunan') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($record->jenis_cuti === 'Sakit') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                        @elseif($record->jenis_cuti === 'Izin') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                        @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                        @endif">
                        {{ $record->jenis_cuti }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Tanggal Pengajuan:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $record->tanggal_pengajuan->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- Sisa Cuti -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4"></path>
                </svg>
                Informasi Cuti {{ date('Y') }}
            </h4>
            
            <div class="space-y-4">
                <div class="text-center">
                    <div class="text-3xl font-bold {{ $sisaCuti > 5 ? 'text-green-600' : ($sisaCuti > 2 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $sisaCuti }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Sisa Cuti</div>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Total Jatah:</span>
                    <span class="font-medium text-gray-900 dark:text-white">12 hari</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">Sudah Terpakai:</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $totalCutiTerpakai }} hari</span>
                </div>
                
                <!-- Progress Bar -->
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                    <div class="h-3 rounded-full {{ $totalCutiTerpakai > 10 ? 'bg-red-500' : ($totalCutiTerpakai > 7 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                         style="width: {{ min(($totalCutiTerpakai / 12) * 100, 100) }}%"></div>
                </div>
                <div class="text-xs text-center text-gray-500 dark:text-gray-400">
                    {{ round(($totalCutiTerpakai / 12) * 100) }}% dari jatah tahunan
                </div>
            </div>
        </div>
    </div>

    <!-- Alasan -->
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.942 8.942 0 01-4.477-1.204l-3.198.968.968-3.198A8.942 8.942 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z"></path>
            </svg>
            Alasan/Keterangan
        </h4>
        <p class="text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg italic">
            "{{ $record->keterangan }}"
        </p>
    </div>

    <!-- Catatan Approval -->
    @if($record->catatan_approval)
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Catatan Approval
        </h4>
        <div class="text-gray-700 dark:text-gray-300 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
            <pre class="whitespace-pre-wrap font-sans">{{ $record->catatan_approval }}</pre>
        </div>
    </div>
    @endif

    <!-- Riwayat Cuti Tahun Ini -->
    @if($leaveHistory->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Riwayat Cuti {{ date('Y') }}
        </h4>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Durasi</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jenis</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($leaveHistory as $history)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                            {{ $history->tanggal_mulai->format('d/m') }} - {{ $history->tanggal_selesai->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ $history->durasicuti }} hari
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                @if($history->jenis_cuti === 'Cuti Tahunan') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($history->jenis_cuti === 'Sakit') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($history->jenis_cuti === 'Izin') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @endif">
                                {{ $history->jenis_cuti }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                            {{ $history->keterangan }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>