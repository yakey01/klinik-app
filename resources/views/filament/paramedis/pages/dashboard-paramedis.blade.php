<x-filament-panels::page>
    @push('styles')
    <link rel="stylesheet" href="{{ asset('build/assets/css/paramedis-mobile-ClsAggz_.css') }}">
    @endpush
    
    <div class="paramedis-mobile-dashboard">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div class="profile-info">
                <h2>{{ $user->name }}</h2>
                <p>Paramedis - {{ $user->role->name ?? 'Paramedis' }}</p>
            </div>
        </div>

        <!-- Action Tiles Grid -->
        <div class="action-tiles">
            <!-- Attendance Tile -->
            <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="action-tile attendance">
                @if($attendanceCount > 0)
                    <div class="tile-badge">{{ $attendanceCount }}</div>
                @endif
                <div class="tile-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="tile-title">Presensi</div>
                <div class="tile-subtitle">Check In/Out</div>
            </a>

            <!-- Schedule Tile -->
            <a href="{{ route('filament.paramedis.resources.attendances.create') }}" class="action-tile schedule">
                @if($upcomingSchedules > 0)
                    <div class="tile-badge">{{ $upcomingSchedules }}</div>
                @endif
                <div class="tile-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="tile-title">Quick Check-In</div>
                <div class="tile-subtitle">Fast Entry</div>
            </a>

            <!-- Jadwal Piket Tile -->
            <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="action-tile piket">
                @if($pendingTasks > 0)
                    <div class="tile-badge">{{ $pendingTasks }}</div>
                @endif
                <div class="tile-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="tile-title">History</div>
                <div class="tile-subtitle">View Records</div>
            </a>

            <!-- Jaspel Tile -->
            <a href="{{ route('jaspel.dashboard') }}" class="action-tile jaspel">
                <div class="tile-icon">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"></path>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="tile-title">Jaspel</div>
                <div class="tile-subtitle">Rp {{ number_format($monthlyJaspel, 0, ',', '.') }}</div>
            </a>
        </div>

        <!-- Auto Redirect to Premium Dashboard -->
        <div class="mt-4 text-center">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-blue-800 text-sm font-medium mb-2">
                    🚀 Redirecting to Premium Dashboard...
                </p>
                <p class="text-blue-600 text-xs">
                    You will be automatically redirected to the new premium interface.
                </p>
            </div>
        </div>
        
        <script>
            // Auto redirect to premium dashboard
            setTimeout(() => {
                window.location.href = '{{ route("premium.dashboard") }}';
            }, 2000);
        </script>

        <!-- Bottom Navigation (Mobile Only) -->
        <div class="mobile-bottom-nav">
            <a href="{{ route('filament.paramedis.pages.dashboard-paramedis') }}" class="nav-item active">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span>Beranda</span>
            </a>
            
            <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="nav-item">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span>Riwayat</span>
            </a>
            
            <a href="{{ route('filament.paramedis.resources.jaspels.index') }}" class="nav-item">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span>Menu</span>
            </a>
            
            <a href="#" class="nav-item">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
                <span>Akun</span>
            </a>
        </div>
        
        <!-- Touch interaction script -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tiles = document.querySelectorAll('.action-tile');
                
                tiles.forEach(tile => {
                    tile.addEventListener('touchstart', function() {
                        this.style.transform = 'translateY(-2px) scale(0.98)';
                    });
                    
                    tile.addEventListener('touchend', function() {
                        this.style.transform = '';
                    });
                    
                    tile.addEventListener('touchcancel', function() {
                        this.style.transform = '';
                    });
                });
            });
        </script>
    </div>
</x-filament-panels::page>