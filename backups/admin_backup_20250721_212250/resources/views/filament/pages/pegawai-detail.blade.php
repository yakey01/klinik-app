<div class="space-y-6">
    {{-- Header Card --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border dark:border-gray-700 p-6">
        <div class="flex items-center space-x-4">
            <div class="flex-shrink-0">
                <img class="h-20 w-20 rounded-full object-cover" 
                     src="{{ $record->foto ? Storage::url($record->foto) : $record->default_avatar }}" 
                     alt="{{ $record->nama_lengkap }}">
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $record->nama_lengkap }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $record->jabatan }}
                </p>
                <div class="flex items-center space-x-2 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->jenis_pegawai === 'Paramedis' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                        {{ $record->jenis_pegawai }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $record->aktif ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' }}">
                        {{ $record->aktif ? 'Aktif' : 'Tidak Aktif' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Details Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Personal Information --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border dark:border-gray-700 p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Informasi Personal
            </h4>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">NIK Pegawai</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->nik ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Kelamin</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->jenis_kelamin ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Lahir</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        {{ $record->tanggal_lahir ? $record->tanggal_lahir->format('d F Y') : '-' }}
                        @if($record->tanggal_lahir)
                            <span class="text-gray-500 dark:text-gray-400">
                                ({{ $record->tanggal_lahir->age }} tahun)
                            </span>
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Employment Information --}}
        <div class="bg-white dark:bg-gray-900 rounded-lg border dark:border-gray-700 p-6">
            <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                Informasi Pekerjaan
            </h4>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jabatan</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->jabatan }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Jenis Pegawai</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $record->jenis_pegawai }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        {{ $record->aktif ? 'Aktif' : 'Tidak Aktif' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Bergabung</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        {{ $record->created_at->format('d F Y') }}
                        <span class="text-gray-500 dark:text-gray-400">
                            ({{ $record->created_at->diffForHumans() }})
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- System Information --}}
    <div class="bg-white dark:bg-gray-900 rounded-lg border dark:border-gray-700 p-6">
        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Informasi Sistem
        </h4>
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dibuat oleh</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ $record->inputBy ? $record->inputBy->name : 'System' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Dibuat pada</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ $record->created_at->format('d/m/Y H:i') }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Terakhir diupdate</dt>
                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ $record->updated_at->format('d/m/Y H:i') }}
                </dd>
            </div>
        </dl>
    </div>
</div>