@php
    $record = $getRecord();
@endphp

<div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
    <!-- Header -->
    <div class="flex items-center space-x-3 mb-4">
        <div class="flex-shrink-0">
            @if($record->foto)
                <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $record->foto) }}" alt="{{ $record->nama_lengkap }}">
            @else
                <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ strtoupper(substr($record->nama_lengkap, 0, 2)) }}
                    </span>
                </div>
            @endif
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                {{ ucwords(strtolower($record->nama_lengkap)) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                NIK: {{ $record->nik }}
            </p>
        </div>
        <div class="flex-shrink-0">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                {{ $record->aktif ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' }}">
                {{ $record->aktif ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Jabatan</span>
            <span class="text-sm text-gray-900 dark:text-white">{{ $record->jabatan }}</span>
        </div>
        
        <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Jenis</span>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                {{ $record->jenis_pegawai === 'Paramedis' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                {{ $record->jenis_pegawai }}
            </span>
        </div>

        @if($record->user_id || $record->has_login_account)
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-500 dark:text-gray-400">Akun</span>
                <span class="text-sm text-gray-900 dark:text-white">
                    @if($record->user_id)
                        {{ $record->user?->username ?? 'User' }}
                    @else
                        {{ $record->username ?? 'Login' }}
                    @endif
                </span>
            </div>
        @endif

        @php
            $hasCard = \App\Models\EmployeeCard::where('pegawai_id', $record->id)->exists();
        @endphp
        <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Kartu ID</span>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                {{ $hasCard ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                {{ $hasCard ? 'Ada' : 'Belum' }}
            </span>
        </div>
    </div>

    <!-- Footer -->
    <div class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                ID: {{ $record->id }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ $record->created_at?->diffForHumans() }}
            </span>
        </div>
    </div>
</div>