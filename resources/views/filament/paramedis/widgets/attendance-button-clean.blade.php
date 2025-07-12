<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-clock class="h-6 w-6 text-green-500" />
                <span class="text-lg font-semibold">Absensi Cepat - Real Time</span>
            </div>
        </x-slot>

        <div class="space-y-6">
            {{-- Real Time Clock --}}
            <div class="text-center p-6 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="text-5xl font-bold text-blue-600 dark:text-blue-400" id="realtime-clock">
                    {{ $currentTime->format('H:i:s') }}
                </div>
                <div class="text-lg text-gray-600 dark:text-gray-400 mt-2" id="realtime-date">
                    {{ $currentTime->format('l, d F Y') }}
                </div>
                <div class="text-sm text-blue-500 dark:text-blue-400 mt-1">
                    🕐 Waktu Real-Time WIB
                </div>
                <div class="text-xs text-green-500 mt-1" id="live-indicator">
                    ● Live
                </div>
            </div>

            {{-- Current Status --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Check-in Status --}}
                <div class="p-4 rounded-lg border {{ $todayAttendance ? 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800' }}">
                    <div class="flex items-center gap-x-3">
                        @if($todayAttendance)
                            <x-heroicon-o-check-circle class="h-6 w-6 text-green-500" />
                            <div>
                                <div class="font-medium text-green-700 dark:text-green-400">✅ Sudah Absen Masuk</div>
                                <div class="text-sm text-green-600 dark:text-green-500">
                                    Waktu: {{ \Carbon\Carbon::parse($todayAttendance->time_in)->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-green-500">
                                    Status: {{ $todayAttendance->status === 'late' ? '⚠️ Terlambat' : '✅ Tepat Waktu' }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">⏰ Belum Absen Masuk</div>
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
                                <div class="font-medium text-blue-700 dark:text-blue-400">✅ Sudah Absen Pulang</div>
                                <div class="text-sm text-blue-600 dark:text-blue-500">
                                    Waktu: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->format('H:i:s') }}
                                </div>
                                <div class="text-xs text-blue-500">
                                    Durasi: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(\Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                                </div>
                            </div>
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-gray-400" />
                            <div>
                                <div class="font-medium text-gray-700 dark:text-gray-300">🏠 Belum Absen Pulang</div>
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
                @if($canCheckin)
                    <button 
                        wire:click="checkin"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg shadow-lg"
                    >
                        <x-heroicon-o-play-circle class="h-6 w-6" />
                        <span wire:loading.remove wire:target="checkin">✅ Check In Masuk</span>
                        <span wire:loading wire:target="checkin">⏳ Sedang Absen...</span>
                    </button>
                @endif
                
                @if($canCheckout)
                    <button 
                        wire:click="checkout"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50"
                        class="flex-1 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white font-bold py-4 px-6 rounded-lg transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-3 text-lg shadow-lg"
                    >
                        <x-heroicon-o-stop-circle class="h-6 w-6" />
                        <span wire:loading.remove wire:target="checkout">🏠 Check Out Pulang</span>
                        <span wire:loading wire:target="checkout">⏳ Sedang Absen...</span>
                    </button>
                @endif
                
                @if($todayAttendance && $todayAttendance->time_out)
                    <div class="flex-1 text-center p-4 bg-green-100 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="text-green-800 dark:text-green-300 font-bold text-lg">✅ Absensi Selesai</div>
                        <div class="text-sm text-green-600 dark:text-green-400 mt-1">
                            Total Durasi Kerja: {{ \Carbon\Carbon::parse($todayAttendance->time_out)->diffForHumans(\Carbon\Carbon::parse($todayAttendance->time_in), true) }}
                        </div>
                        <div class="text-xs text-green-500 mt-2">
                            Terima kasih atas dedikasi Anda hari ini! 👏
                        </div>
                    </div>
                @endif
            </div>
            
            {{-- Quick Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                <div class="text-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="font-medium text-blue-800 dark:text-blue-300">⏰ Jam Kerja</div>
                    <div class="text-blue-600 dark:text-blue-400">08:00 - 17:00</div>
                </div>
                <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="font-medium text-green-800 dark:text-green-300">📍 Lokasi</div>
                    <div class="text-green-600 dark:text-green-400">Klinik Dokterku</div>
                </div>
                <div class="text-center p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="font-medium text-purple-800 dark:text-purple-300">👤 Petugas</div>
                    <div class="text-purple-600 dark:text-purple-400">{{ $user->name }}</div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Enhanced Real-time Clock Script with Debug --}}
    <script>
        console.log('📝 Attendance widget script loading...');
        
        // Global clock function to prevent conflicts
        window.ParamedisClockStarted = false;
        window.AccurateTimeOffset = 0; // Offset to correct system time
        
        // Get accurate time from internet
        async function getAccurateTime() {
            try {
                // Use WorldTimeAPI for accurate Indonesia time
                const response = await fetch('https://worldtimeapi.org/api/timezone/Asia/Jakarta');
                const data = await response.json();
                const accurateTime = new Date(data.datetime);
                const systemTime = new Date();
                
                // Calculate offset between accurate time and system time
                window.AccurateTimeOffset = accurateTime.getTime() - systemTime.getTime();
                
                console.log('✅ Got accurate time from API');
                console.log('🔍 System time:', systemTime.toString());
                console.log('🔍 Accurate time:', accurateTime.toString());
                console.log('🔍 Offset:', window.AccurateTimeOffset / 1000, 'seconds');
                
                return accurateTime;
            } catch (error) {
                console.warn('⚠️ Could not get accurate time from API, using manual correction');
                console.error(error);
                
                // Fallback: manual correction for Saturday July 13, 2024
                const manualDate = new Date(2024, 6, 13); // July 13, 2024 (Saturday)
                const currentTime = new Date();
                manualDate.setHours(currentTime.getHours());
                manualDate.setMinutes(currentTime.getMinutes());
                manualDate.setSeconds(currentTime.getSeconds());
                
                window.AccurateTimeOffset = manualDate.getTime() - currentTime.getTime();
                return manualDate;
            }
        }
        
        function getCurrentAccurateTime() {
            const systemTime = new Date();
            return new Date(systemTime.getTime() + window.AccurateTimeOffset);
        }
        
        function startParamedisClock() {
            if (window.ParamedisClockStarted) {
                console.log('⚠️ Clock already started, skipping...');
                return;
            }
            
            console.log('🚀 Starting Paramedis Clock...');
            
            const clockElement = document.getElementById('realtime-clock');
            const dateElement = document.getElementById('realtime-date');
            const indicator = document.getElementById('live-indicator');
            
            console.log('🔍 Elements found:', {
                clock: !!clockElement,
                date: !!dateElement, 
                indicator: !!indicator
            });
            
            if (!clockElement) {
                console.error('❌ Clock element not found!');
                return;
            }
            
            function updateClock() {
                try {
                    // Get accurate current time
                    const accurateNow = getCurrentAccurateTime();
                    
                    console.log('🔍 System time:', new Date().toString());
                    console.log('🔍 Accurate time:', accurateNow.toString());
                    
                    // Format time and date using accurate time
                    const timeOptions = {
                        timeZone: 'Asia/Jakarta',
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit', 
                        second: '2-digit'
                    };
                    
                    const dateOptions = {
                        timeZone: 'Asia/Jakarta',
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    
                    const timeString = accurateNow.toLocaleTimeString('id-ID', timeOptions);
                    const dateString = accurateNow.toLocaleDateString('id-ID', dateOptions);
                    
                    if (clockElement) {
                        clockElement.textContent = timeString;
                    }
                    
                    if (dateElement) {
                        dateElement.textContent = dateString;
                    }
                    
                    // Update indicator with pulse effect
                    if (indicator) {
                        indicator.style.color = '#22c55e';
                        indicator.textContent = '● Live ' + accurateNow.getSeconds();
                        setTimeout(() => {
                            if (indicator) {
                                indicator.style.color = '#10b981';
                                indicator.textContent = '● Live';
                            }
                        }, 100);
                    }
                    
                    console.log('🕐 Clock updated:', timeString, '|', dateString);
                } catch (error) {
                    console.error('❌ Clock update error:', error);
                }
            }
            
            // Get accurate time first, then start clock
            getAccurateTime().then(() => {
                // Update immediately and then every second
                updateClock();
                const interval = setInterval(updateClock, 1000);
                window.ParamedisClockStarted = true;
                
                console.log('✅ Paramedis real-time clock started successfully with accurate time');
                
                // Store interval for cleanup
                window.ParamedisClockInterval = interval;
            }).catch(error => {
                console.error('❌ Failed to get accurate time, starting with system time');
                
                // Fallback to system time if API fails
                updateClock();
                const interval = setInterval(updateClock, 1000);
                window.ParamedisClockStarted = true;
                window.ParamedisClockInterval = interval;
            });
        }
        
        // Multiple initialization methods to ensure it runs
        console.log('📋 Document readyState:', document.readyState);
        
        // Method 1: DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                console.log('🎯 DOM loaded, starting clock...');
                setTimeout(startParamedisClock, 100);
            });
        } else {
            console.log('🎯 DOM already ready, starting clock immediately...');
            setTimeout(startParamedisClock, 100);
        }
        
        // Method 2: Livewire events
        document.addEventListener('livewire:navigated', () => {
            console.log('🔄 Livewire navigated, restarting clock...');
            window.ParamedisClockStarted = false;
            if (window.ParamedisClockInterval) {
                clearInterval(window.ParamedisClockInterval);
            }
            setTimeout(startParamedisClock, 200);
        });
        
        // Method 3: Window load as fallback
        window.addEventListener('load', () => {
            console.log('🌐 Window loaded, ensuring clock is running...');
            if (!window.ParamedisClockStarted) {
                setTimeout(startParamedisClock, 100);
            }
        });
        
        // Method 4: Force start after delay
        setTimeout(() => {
            console.log('⏰ Force checking clock after 2 seconds...');
            if (!window.ParamedisClockStarted) {
                startParamedisClock();
            }
        }, 2000);
    </script>
</x-filament-widgets::widget>