<x-filament-panels::page>
    <div class="mobile-dashboard-container">
        {{-- Mobile Header --}}
        <div class="mobile-header bg-primary-600 text-white p-4 -mt-6 -mx-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold">Hi, {{ $user->name }}!</h2>
                    <p class="text-sm opacity-90">{{ now()->format('l, d F Y') }}</p>
                </div>
                <div class="relative">
                    <button class="p-2 rounded-full hover:bg-white/20 transition-colors">
                        <x-heroicon-o-bell class="w-6 h-6" />
                        @if($notifications->where('read_at', null)->count() > 0)
                            <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                        @endif
                    </button>
                </div>
            </div>
        </div>

        {{-- Attendance Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Presensi Hari Ini</h3>
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-400" />
            </div>
            
            @if($attendance)
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Check In:</span>
                        <span class="text-sm font-medium">{{ $attendance->time_in }}</span>
                    </div>
                    @if($attendance->time_out)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Check Out:</span>
                            <span class="text-sm font-medium">{{ $attendance->time_out }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-500">Status:</span>
                        <span class="text-sm font-medium capitalize">{{ $attendance->status }}</span>
                    </div>
                </div>
                
                @if($canCheckOut)
                    <button 
                        type="button"
                        onclick="handleCheckOut()"
                        class="w-full mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center justify-center space-x-2"
                    >
                        <x-heroicon-o-arrow-right-on-rectangle class="w-5 h-5" />
                        <span>Check Out</span>
                    </button>
                @endif
            @else
                <p class="text-sm text-gray-500 mb-4">Anda belum melakukan presensi hari ini</p>
                <button 
                    type="button"
                    onclick="handleCheckIn()"
                    class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center space-x-2"
                >
                    <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                    <span>Check In</span>
                </button>
            @endif
        </div>

        {{-- Schedule Card --}}
        @if($schedule)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Jadwal Hari Ini</h3>
                    <x-heroicon-o-calendar class="w-5 h-5 text-gray-400" />
                </div>
                
                @if($schedule->is_day_off)
                    <p class="text-sm text-gray-500">Hari Libur</p>
                @else
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Shift:</span>
                            <span class="text-sm font-medium capitalize">{{ $schedule->shift->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500">Jam Kerja:</span>
                            <span class="text-sm font-medium">{{ $schedule->shift->start_time }} - {{ $schedule->shift->end_time }}</span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- Performance Summary --}}
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-clipboard-document-list class="w-8 h-8 text-blue-500" />
                    <span class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $procedureCount }}</span>
                </div>
                <p class="text-xs text-gray-500">Tindakan Bulan Ini</p>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                <div class="flex items-center justify-between mb-2">
                    <x-heroicon-o-banknotes class="w-8 h-8 text-green-500" />
                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ number_format($totalJaspel, 0, ',', '.') }}</span>
                </div>
                <p class="text-xs text-gray-500">Jaspel Disetujui</p>
            </div>
        </div>

        {{-- Notifications Feed --}}
        @if($notifications->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 mb-20">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400">Notifikasi Terbaru</h3>
                    <a href="#" class="text-xs text-primary-600 hover:text-primary-700">Lihat Semua</a>
                </div>
                
                <div class="space-y-3">
                    @foreach($notifications->take(3) as $notification)
                        <div class="flex items-start space-x-3 {{ !$notification->read_at ? 'bg-blue-50 dark:bg-blue-900/20 -mx-2 px-2 py-1 rounded' : '' }}">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-2 h-2 {{ !$notification->read_at ? 'bg-blue-600' : 'bg-gray-300' }} rounded-full"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </p>
                                <p class="text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bottom Navigation --}}
        <div class="fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-4 h-16">
                <a href="/paramedis" class="flex flex-col items-center justify-center text-primary-600">
                    <x-heroicon-o-home class="w-5 h-5 mb-1" />
                    <span class="text-xs">Home</span>
                </a>
                <a href="/paramedis/schedules" class="flex flex-col items-center justify-center text-gray-500 hover:text-gray-700">
                    <x-heroicon-o-calendar-days class="w-5 h-5 mb-1" />
                    <span class="text-xs">Jadwal</span>
                </a>
                <a href="/paramedis/jaspels" class="flex flex-col items-center justify-center text-gray-500 hover:text-gray-700">
                    <x-heroicon-o-currency-dollar class="w-5 h-5 mb-1" />
                    <span class="text-xs">Jaspel</span>
                </a>
                <a href="/paramedis/profile" class="flex flex-col items-center justify-center text-gray-500 hover:text-gray-700">
                    <x-heroicon-o-user class="w-5 h-5 mb-1" />
                    <span class="text-xs">Profil</span>
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Geolocation configuration
        const CLINIC_LOCATION = {
            lat: -6.2088, // Example: Jakarta coordinates
            lng: 106.8456,
            radius: 100 // meters
        };

        // Calculate distance between two coordinates
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // Earth's radius in meters
            const φ1 = lat1 * Math.PI/180;
            const φ2 = lat2 * Math.PI/180;
            const Δφ = (lat2-lat1) * Math.PI/180;
            const Δλ = (lon2-lon1) * Math.PI/180;

            const a = Math.sin(Δφ/2) * Math.sin(Δφ/2) +
                    Math.cos(φ1) * Math.cos(φ2) *
                    Math.sin(Δλ/2) * Math.sin(Δλ/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

            return R * c; // Distance in meters
        }

        // Handle check in with geolocation
        function handleCheckIn() {
            if (!navigator.geolocation) {
                alert('Geolocation tidak didukung di browser Anda');
                return;
            }

            // Show loading state
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<x-heroicon-o-arrow-path class="w-5 h-5 animate-spin" /> Mendapatkan lokasi...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const distance = calculateDistance(
                        position.coords.latitude,
                        position.coords.longitude,
                        CLINIC_LOCATION.lat,
                        CLINIC_LOCATION.lng
                    );

                    if (distance > CLINIC_LOCATION.radius) {
                        button.disabled = false;
                        button.innerHTML = '<x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" /> Check In';
                        alert(`Anda berada ${Math.round(distance)} meter dari klinik. Maksimal jarak check-in adalah ${CLINIC_LOCATION.radius} meter.`);
                        return;
                    }

                    // Proceed with check-in
                    fetch('/api/paramedis/attendance/checkin', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            accuracy: position.coords.accuracy,
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Gagal melakukan check-in');
                            button.disabled = false;
                            button.innerHTML = '<x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" /> Check In';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Terjadi kesalahan saat check-in');
                        button.disabled = false;
                        button.innerHTML = '<x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" /> Check In';
                    });
                },
                (error) => {
                    button.disabled = false;
                    button.innerHTML = '<x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" /> Check In';
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            alert('Akses lokasi ditolak. Silakan aktifkan lokasi untuk melakukan check-in.');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            alert('Informasi lokasi tidak tersedia.');
                            break;
                        case error.TIMEOUT:
                            alert('Waktu permintaan lokasi habis.');
                            break;
                        default:
                            alert('Terjadi kesalahan saat mendapatkan lokasi.');
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        // Handle check out
        function handleCheckOut() {
            if (confirm('Apakah Anda yakin ingin check out?')) {
                fetch('/api/paramedis/attendance/checkout', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Gagal melakukan check-out');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan saat check-out');
                });
            }
        }
    </script>
    @endpush

    @push('styles')
    <style>
        /* Mobile-optimized styles */
        .mobile-dashboard-container {
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Ensure proper spacing for fixed bottom nav */
        .fi-page-content {
            padding-bottom: 5rem !important;
        }
        
        /* Mobile-first responsive design */
        @media (max-width: 640px) {
            .mobile-header {
                margin-left: -1rem;
                margin-right: -1rem;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .mobile-header {
                background-color: rgb(59 130 246);
            }
        }
    </style>
    @endpush
</x-filament-panels::page>