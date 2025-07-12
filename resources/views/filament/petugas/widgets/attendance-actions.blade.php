<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-clock class="h-6 w-6 text-blue-500" />
                <span class="text-lg font-semibold">Presensi Cepat - {{ now()->format('d M Y') }}</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Current Time Display --}}
            <div class="text-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" id="current-time">
                    {{ now()->format('H:i:s') }}
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ now()->format('l, d F Y') }}
                </div>
            </div>

            {{-- Quick Status Check --}}
            @php
                $today = \Carbon\Carbon::today();
                $todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())
                    ->where('date', $today)
                    ->first();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Check-in Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-500" />
                            <div>
                                <div class="font-medium text-green-700 dark:text-green-400">Sudah Absen Masuk</div>
                                <div class="text-sm text-green-600 dark:text-green-500">
                                    {{ \Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i') }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">Belum Absen Masuk</div>
                                <div class="text-sm text-gray-500">Silakan lakukan presensi masuk</div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Check-out Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance && $todayAttendance->time_out ? 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance && $todayAttendance->time_out)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-blue-500" />
                            <div>
                                <div class="font-medium text-blue-700 dark:text-blue-400">Sudah Absen Pulang</div>
                                <div class="text-sm text-blue-600 dark:text-blue-500">
                                    {{ \Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i') }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">Belum Absen Pulang</div>
                                <div class="text-sm text-gray-500">
                                    {{ $todayAttendance ? 'Silakan lakukan presensi pulang' : 'Absen masuk dulu' }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                {{ ($this->checkinAction)(['size' => 'xl', 'extraAttributes' => ['class' => 'w-full sm:w-auto']]) }}
                {{ ($this->checkoutAction)(['size' => 'xl', 'extraAttributes' => ['class' => 'w-full sm:w-auto']]) }}
            </div>

            {{-- Work Duration Today (if applicable) --}}
            @if($todayAttendance && $todayAttendance->time_in)
                <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <div class="font-medium text-yellow-800 dark:text-yellow-400">
                        @if($todayAttendance->time_out)
                            Total Durasi Kerja: {{ $todayAttendance->formatted_work_duration }}
                        @else
                            @php
                                $timeIn = \Carbon\Carbon::parse($todayAttendance->time_in);
                                $currentDuration = $timeIn->diffForHumans(null, true);
                            @endphp
                            Sedang Bekerja: {{ $currentDuration }}
                        @endif
                    </div>
                    <div class="text-sm text-yellow-600 dark:text-yellow-500 mt-1">
                        Status: {{ $todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu' }}
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>

    {{-- Auto-refresh clock --}}
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }

        // Update clock every second
        setInterval(updateClock, 1000);
        
        // Initial update
        updateClock();
    </script>
</x-filament-widgets::widget>