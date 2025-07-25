@php
    use Carbon\Carbon;
    
    $timeIn = $record->time_in ? Carbon::parse($record->time_in) : null;
    $timeOut = $record->time_out ? Carbon::parse($record->time_out) : null;
    $duration = ($timeIn && $timeOut) ? $timeOut->diffInMinutes($timeIn) : null;
    
    $hours = $duration ? intval($duration / 60) : 0;
    $minutes = $duration ? $duration % 60 : 0;
@endphp

<div class="space-y-6">
    {{-- Header Info --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $record->date->format('d/m/Y') }}
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $record->date->format('l') }}
                </p>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                    @switch($record->status)
                        @case('present') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 @break
                        @case('late') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 @break
                        @case('absent') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 @break
                        @case('sick') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 @break
                        @case('permission') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 @break
                        @default bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300
                    @endswitch
                ">
                    @switch($record->status)
                        @case('present') Hadir @break
                        @case('late') Terlambat @break
                        @case('absent') Tidak Hadir @break
                        @case('sick') Sakit @break
                        @case('permission') Izin @break
                        @default {{ $record->status }}
                    @endswitch
                </span>
            </div>
        </div>
    </div>

    {{-- Time Information --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Check In</p>
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                        {{ $timeIn ? $timeIn->format('H:i') : '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Check Out</p>
                    <p class="text-lg font-semibold text-red-600 dark:text-red-400">
                        {{ $timeOut ? $timeOut->format('H:i') : '-' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Total Jam Kerja</p>
                    <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                        @if($duration)
                            {{ $hours }}j {{ $minutes }}m
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Location Information --}}
    @if($record->location_name_in || $record->latitude)
        <div class="space-y-4">
            <h4 class="text-md font-semibold text-gray-900 dark:text-white">Informasi Lokasi</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Check In</h5>
                    @if($record->location_name_in)
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->location_name_in }}</p>
                    @endif
                    @if($record->latitude && $record->longitude)
                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                            {{ number_format($record->latitude, 6) }}, {{ number_format($record->longitude, 6) }}
                        </p>
                    @endif
                </div>

                @if($record->location_name_out || $record->checkout_latitude)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Check Out</h5>
                        @if($record->location_name_out)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->location_name_out }}</p>
                        @endif
                        @if($record->checkout_latitude && $record->checkout_longitude)
                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                {{ number_format($record->checkout_latitude, 6) }}, {{ number_format($record->checkout_longitude, 6) }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Notes --}}
    @if($record->notes)
        <div>
            <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-2">Catatan</h4>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->notes }}</p>
            </div>
        </div>
    @endif

    {{-- Additional Info --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="grid grid-cols-2 gap-4 text-xs text-gray-500 dark:text-gray-400">
            <div>
                <span class="font-medium">Dibuat:</span> {{ $record->created_at->format('d/m/Y H:i') }}
            </div>
            @if($record->updated_at && $record->updated_at != $record->created_at)
                <div>
                    <span class="font-medium">Diubah:</span> {{ $record->updated_at->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>
    </div>
</div>