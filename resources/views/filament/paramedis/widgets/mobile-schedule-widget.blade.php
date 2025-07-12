<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            @if($todaySchedule)
                <div class="bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
                    <h4 class="font-semibold text-primary-900 dark:text-primary-100 mb-2 flex items-center">
                        <x-heroicon-o-calendar class="w-5 h-5 mr-2" />
                        Jadwal Hari Ini
                    </h4>
                    @if($todaySchedule->is_day_off)
                        <p class="text-lg font-medium text-gray-600 dark:text-gray-400">Hari Libur</p>
                    @else
                        <div class="space-y-1">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Shift: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $todaySchedule->shift->name }}</span>
                            </p>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Jam: <span class="font-medium text-gray-900 dark:text-gray-100">{{ $todaySchedule->shift->start_time }} - {{ $todaySchedule->shift->end_time }}</span>
                            </p>
                        </div>
                    @endif
                </div>
            @endif
            
            <div>
                <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">Jadwal Minggu Ini</h4>
                <div class="grid grid-cols-7 gap-1">
                    @php
                        $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
                        $startOfWeek = now()->startOfWeek();
                    @endphp
                    
                    @foreach($days as $index => $day)
                        @php
                            $date = $startOfWeek->copy()->addDays($index);
                            $schedule = $schedules->firstWhere('date', $date->toDateString());
                            $isToday = $date->isToday();
                        @endphp
                        
                        <div class="text-center">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $day }}</p>
                            <div class="relative">
                                <div class="
                                    w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                                    {{ $isToday ? 'bg-primary-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300' }}
                                    {{ $schedule && !$schedule->is_day_off ? 'ring-2 ring-green-500' : '' }}
                                    {{ $schedule && $schedule->is_day_off ? 'opacity-50' : '' }}
                                ">
                                    {{ $date->day }}
                                </div>
                                @if($schedule && !$schedule->is_day_off)
                                    <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2">
                                        <div class="w-1 h-1 bg-green-500 rounded-full"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-3 flex items-center justify-center space-x-4 text-xs">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-1"></div>
                        <span class="text-gray-600 dark:text-gray-400">Jadwal Kerja</span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full mr-1 opacity-50"></div>
                        <span class="text-gray-600 dark:text-gray-400">Libur</span>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>