@php
    $record = $getRecord();
@endphp

<div class="group relative overflow-hidden rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg hover:shadow-xl transition-all duration-300 hover:border-blue-300 dark:hover:border-blue-600">
    <!-- Card Header with Gradient -->
    <div class="relative bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 dark:from-gray-800 dark:via-gray-700 dark:to-gray-600 px-6 py-4">
        <div class="flex items-center space-x-4">
            <!-- Avatar Section -->
            <div class="relative">
                @if($record->foto)
                    <img 
                        src="{{ asset('storage/' . $record->foto) }}" 
                        alt="{{ $record->nama_lengkap }}"
                        class="w-16 h-16 rounded-full object-cover ring-4 ring-white dark:ring-gray-700 shadow-md group-hover:ring-blue-200 dark:group-hover:ring-blue-600 transition-all duration-300"
                    />
                @else
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center ring-4 ring-white dark:ring-gray-700 shadow-md group-hover:ring-blue-200 dark:group-hover:ring-blue-600 transition-all duration-300">
                        <span class="text-white text-lg font-bold">
                            {{ strtoupper(substr($record->nama_lengkap, 0, 2)) }}
                        </span>
                    </div>
                @endif
                
                <!-- Status Indicator -->
                <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-white dark:border-gray-700 {{ $record->aktif ? 'bg-green-500' : 'bg-red-500' }}"></div>
            </div>
            
            <!-- Name and NIK -->
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white leading-tight mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200">
                    {{ ucwords(strtolower($record->nama_lengkap)) }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-md inline-block">
                    NIK: {{ $record->nik }}
                </p>
            </div>
        </div>
    </div>
    
    <!-- Card Body -->
    <div class="px-6 py-4 space-y-4">
        <!-- Job Information -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
                @php
                    $icon = match($record->jenis_pegawai) {
                        'Paramedis' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
                        'Non-Paramedis' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                        default => 'M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
                    };
                @endphp
                
                <svg class="w-5 h-5 text-{{ $record->jenis_pegawai === 'Paramedis' ? 'red' : 'blue' }}-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="{{ $icon }}"/>
                </svg>
                
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $record->jabatan }}
                </span>
            </div>
            
            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $record->jenis_pegawai === 'Paramedis' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                {{ $record->jenis_pegawai }}
            </span>
        </div>
        
        <!-- Status Grid -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Employee Status -->
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full {{ $record->aktif ? 'bg-green-500' : 'bg-red-500' }}"></div>
                <span class="text-sm {{ $record->aktif ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                    {{ $record->aktif ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            
            <!-- Card Status -->
            <div class="flex items-center space-x-2">
                @php
                    $hasCard = \App\Models\EmployeeCard::where('pegawai_id', $record->id)->exists();
                @endphp
                <div class="w-2 h-2 rounded-full {{ $hasCard ? 'bg-blue-500' : 'bg-gray-400' }}"></div>
                <span class="text-sm {{ $hasCard ? 'text-blue-700 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400' }}">
                    {{ $hasCard ? 'Kartu Ada' : 'Belum Kartu' }}
                </span>
            </div>
        </div>
        
        <!-- Account Status -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
            @if($record->user_id)
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <span class="text-sm font-medium text-green-700 dark:text-green-400">
                        User: {{ $record->user?->username ?? 'No username' }}
                    </span>
                </div>
            @elseif($record->has_login_account)
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                    </svg>
                    <span class="text-sm font-medium text-blue-700 dark:text-blue-400">
                        Login: {{ $record->username ?? 'No username' }}
                    </span>
                </div>
            @else
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
                    </svg>
                    <span class="text-sm font-medium text-yellow-700 dark:text-yellow-400">
                        Belum ada akun
                    </span>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Card Footer -->
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
        <div class="flex justify-between items-center">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                ID: {{ $record->id }}
            </div>
            
            <!-- Action Button akan dihandle oleh Filament Actions -->
            <div class="text-xs text-gray-500 dark:text-gray-400">
                {{ $record->created_at?->diffForHumans() }}
            </div>
        </div>
    </div>
    
    <!-- Hover Overlay -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-purple-600/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
</div>