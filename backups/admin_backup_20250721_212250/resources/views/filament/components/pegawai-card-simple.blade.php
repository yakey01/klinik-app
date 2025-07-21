@php
    $record = $getRecord();
@endphp

<div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow h-72 flex flex-col">
    <!-- Header -->
    <div class="flex items-start space-x-3 mb-4">
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
            <p class="text-sm font-medium text-gray-900 dark:text-white break-words">
                {{ ucwords(strtolower($record->nama_lengkap)) }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 break-words">
                NIK: {{ $record->nik }}
            </p>
        </div>
        <div class="flex-shrink-0 pt-0.5">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                {{ $record->aktif ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-200' }}">
                {{ $record->aktif ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="space-y-2 flex-1">
        <div class="flex justify-between items-start">
            <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Jabatan</span>
            <span class="text-sm text-gray-900 dark:text-white text-right flex-1 ml-2 break-words">
                {{ $record->jabatan }}
            </span>
        </div>
        
        <div class="flex justify-between items-center">
            <span class="text-xs text-gray-500 dark:text-gray-400">Jenis</span>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                {{ $record->jenis_pegawai === 'Paramedis' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                {{ $record->jenis_pegawai }}
            </span>
        </div>

        <div class="flex justify-between items-start">
            <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Email</span>
            <span class="text-sm text-gray-900 dark:text-white text-right flex-1 ml-2 break-words">
                {{ $record->email ?: '-' }}
            </span>
        </div>

        <div class="flex justify-between items-start">
            <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0">Akun</span>
            <div class="text-right flex-1 ml-2">
                @php
                    $userAccounts = $record->users()->with('role')->get();
                @endphp
                @if($userAccounts->count() > 0)
                    <div class="space-y-1">
                        @foreach($userAccounts as $userAccount)
                            <div class="flex items-center justify-end space-x-1">
                                <span class="text-xs text-gray-900 dark:text-white break-words">
                                    {{ $userAccount->username }}
                                </span>
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                    @if($userAccount->role->name === 'paramedis') bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200
                                    @elseif($userAccount->role->name === 'petugas') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-200
                                    @elseif($userAccount->role->name === 'bendahara') bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-200
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @endif">
                                    {{ $userAccount->role->display_name }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @elseif($record->user_id)
                    <span class="text-sm text-gray-900 dark:text-white break-words">
                        {{ $record->user?->username ?? 'User' }}
                    </span>
                @elseif($record->username)
                    <span class="text-sm text-gray-900 dark:text-white break-words">
                        {{ $record->username }}
                    </span>
                @else
                    <span class="text-sm text-gray-900 dark:text-white">-</span>
                @endif
            </div>
        </div>

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
    <div class="mt-auto pt-3 border-t border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-3">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                ID: {{ $record->id }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400 truncate">
                {{ $record->created_at?->diffForHumans() }}
            </span>
        </div>
        
        <!-- Management Button Inside Card -->
        <div class="flex justify-center">
            <div class="management-button-fi-container">
                <!-- Button will be positioned here via CSS -->
            </div>
        </div>
    </div>
</div>