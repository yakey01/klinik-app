<x-filament-panels::page>
    @push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Modern Mobile Dashboard - Inspired by Beautiful Design */
        :root {
            --primary-blue: #4A90E2;
            --primary-blue-dark: #357ABD;
            --bg-light: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-primary: #1A1A1A;
            --text-secondary: #8A8A8A;
            --text-light: #B0B0B0;
            --shadow-card: 0 8px 24px rgba(0, 0, 0, 0.08);
            --shadow-soft: 0 2px 8px rgba(0, 0, 0, 0.04);
            --border-radius: 20px;
            --border-radius-large: 24px;
            --red-color: #FF5A5A;
            --orange-color: #FF9F43;
            --green-color: #2ED8B6;
            --blue-color: #4A90E2;
            --purple-color: #A55EEA;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        .mobile-dashboard {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #E8F2FF 0%, #F0F8FF 100%);
            min-height: 100vh;
            padding: 0;
            overflow-x: hidden;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding-top: 40px;
        }
        
        /* Phone Container */
        .phone-container {
            width: 375px;
            max-width: 90vw;
            background: var(--bg-card);
            border-radius: 32px;
            padding: 32px 24px 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            position: relative;
            min-height: 700px;
        }
        
        /* Header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        
        .dashboard-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        
        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: #E0E0E0;
            background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDgiIGhlaWdodD0iNDgiIHZpZXdCb3g9IjAgMCA0OCA0OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiByeD0iMTYiIGZpbGw9IiNFMEUwRTAiLz4KPHN2ZyB4PSIxMiIgeT0iMTIiIHdpZHRoPSIyNCIgaGVpZ2h0PSIyNCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIj4KPHBhdGggZD0iTTEyIDEyQzE0LjIwOTEgMTIgMTYgMTAuMjA5MSAxNiA4QzE2IDUuNzkwODYgMTQuMjA5MSA0IDEyIDRDOS43OTA4NiA0IDggNS43OTA4NiA4IDhDOCAxMC4yMDkxIDkuNzkwODYgMTIgMTIgMTJaIiBmaWxsPSIjOUU5RTlFIi8+CjxwYXRoIGQ9Ik0xMiAxNEM5LjMzIDMgNyAxNS4zNCA3IDE4LjJWMjBIMTdWMTguMkMxNyAxNS4zNCAxNC42NyAxNCAxMiAxNFoiIGZpbGw9IiM5RTlFOUUiLz4KPC9zdmc+Cjwvc3ZnPgo=');
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9E9E9E;
            font-weight: 600;
            font-size: 16px;
        }
        
        /* Main Jaspel Card */
        .main-jaspel-card {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-blue-dark) 100%);
            border-radius: var(--border-radius-large);
            padding: 24px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }
        
        .main-jaspel-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            background: url("data:image/svg+xml,%3Csvg width='120' height='120' viewBox='0 0 120 120' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='60' cy='60' r='60'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") no-repeat;
            opacity: 0.3;
        }
        
        .jaspel-month {
            color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            position: relative;
            z-index: 2;
        }
        
        .jaspel-amount {
            color: white;
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            letter-spacing: -1px;
            position: relative;
            z-index: 2;
        }
        
        .jaspel-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .jaspel-target {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
        }
        
        .jaspel-chart {
            display: flex;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 600;
        }
        
        .chart-icon {
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
        
        /* Daily Activities Section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .see-all {
            color: var(--primary-blue);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        
        .activities-list {
            margin-bottom: 32px;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0;
            border-bottom: 1px solid #F5F5F5;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-left {
            display: flex;
            align-items: center;
        }
        
        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
        }
        
        .activity-icon.red {
            background: var(--red-color);
        }
        
        .activity-icon.orange {
            background: var(--orange-color);
        }
        
        .activity-icon.green {
            background: var(--green-color);
        }
        
        .activity-icon svg {
            width: 24px;
            height: 24px;
            color: white;
        }
        
        .activity-info h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .activity-info p {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .activity-right {
            text-align: right;
        }
        
        .activity-amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .activity-date {
            font-size: 12px;
            color: var(--text-light);
            font-weight: 500;
        }
        
        /* Quick Actions Grid */
        .quick-actions {
            margin-bottom: 32px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }
        
        .action-item {
            aspect-ratio: 1;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-soft);
        }
        
        .action-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-card);
        }
        
        .action-item.blue {
            background: var(--blue-color);
        }
        
        .action-item.green {
            background: var(--green-color);
        }
        
        .action-item.orange {
            background: var(--orange-color);
        }
        
        .action-item.red {
            background: var(--red-color);
        }
        
        .action-item.purple {
            background: var(--purple-color);
        }
        
        .action-item svg {
            width: 24px;
            height: 24px;
            color: white;
        }
        
        /* Floating Action Button */
        .fab {
            position: absolute;
            bottom: 80px;
            left: 50%;
            transform: translateX(-50%);
            width: 56px;
            height: 56px;
            background: var(--primary-blue);
            border: none;
            border-radius: 50%;
            box-shadow: 0 8px 20px rgba(74, 144, 226, 0.4);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .fab:hover {
            transform: translateX(-50%) translateY(-2px);
            box-shadow: 0 12px 24px rgba(74, 144, 226, 0.5);
        }
        
        .fab svg {
            width: 24px;
            height: 24px;
        }
        
        /* Bottom Navigation */
        .bottom-nav {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-card);
            padding: 16px 0;
            border-top: 1px solid #F0F0F0;
            border-radius: 0 0 32px 32px;
        }
        
        .nav-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 32px;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: var(--text-light);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .nav-item.active {
            color: var(--primary-blue);
        }
        
        .nav-item svg {
            width: 24px;
            height: 24px;
            margin-bottom: 4px;
        }
        
        .nav-label {
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Responsive */
        @media (max-width: 425px) {
            .phone-container {
                width: 100vw;
                border-radius: 0;
                min-height: 100vh;
                padding: 40px 20px 20px;
            }
            
            .mobile-dashboard {
                padding-top: 0;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-light: #1A1A1A;
                --bg-card: #2A2A2A;
                --text-primary: #FFFFFF;
                --text-secondary: #B0B0B0;
                --text-light: #8A8A8A;
            }
        }
    </style>
    @endpush

    <div class="mobile-dashboard">
        <div class="phone-container">
            <!-- Header -->
            <div class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
                <div class="user-avatar">
                    {{ strtoupper(substr($user->name, 0, 2)) }}
                </div>
            </div>

            <!-- Main Jaspel Card -->
            <div class="main-jaspel-card">
                <div class="jaspel-month">{{ now()->format('F') }}</div>
                <div class="jaspel-amount">Rp {{ number_format($monthlyJaspel/1000, 0) }}K</div>
                <div class="jaspel-info">
                    <div class="jaspel-target">Target harian: Rp {{ number_format(($monthlyJaspel/30)/1000, 0) }}K</div>
                    <div class="jaspel-chart">
                        <svg class="chart-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6h-6z"/>
                        </svg>
                        {{ rand(65, 85) }}%
                    </div>
                </div>
            </div>

            <!-- Daily Activities -->
            <div class="activities-section">
                <div class="section-header">
                    <div class="section-title">Aktivitas Harian</div>
                    <a href="#" class="see-all">Lihat Semua</a>
                </div>
                
                <div class="activities-list">
                    <div class="activity-item">
                        <div class="activity-left">
                            <div class="activity-icon red">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                </svg>
                            </div>
                            <div class="activity-info">
                                <h4>Tindakan Medis</h4>
                                <p>Rp {{ number_format(rand(300000, 500000), 0, '.', '.') }}</p>
                            </div>
                        </div>
                        <div class="activity-right">
                            <div class="activity-date">Hari ini</div>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-left">
                            <div class="activity-icon orange">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                                    <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                </svg>
                            </div>
                            <div class="activity-info">
                                <h4>Jam Kerja</h4>
                                <p>{{ $monthlyHours }} jam</p>
                            </div>
                        </div>
                        <div class="activity-right">
                            <div class="activity-date">{{ now()->subDays(1)->format('d M, Y') }}</div>
                        </div>
                    </div>

                    <div class="activity-item">
                        <div class="activity-left">
                            <div class="activity-icon green">
                                <svg fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                            </div>
                            <div class="activity-info">
                                <h4>Presensi</h4>
                                <p>Check-in berhasil</p>
                            </div>
                        </div>
                        <div class="activity-right">
                            <div class="activity-date">{{ now()->subDays(2)->format('d M, Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="section-header">
                    <div class="section-title">Aksi Cepat</div>
                    <a href="#" class="see-all">Lihat Semua</a>
                </div>
                
                <div class="actions-grid">
                    <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="action-item blue">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7h-3V2h-2v2H8V2H6v2H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H3V9h14v11z"/>
                        </svg>
                    </a>
                    
                    <a href="#" class="action-item green">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </a>
                    
                    <a href="{{ route('jaspel.dashboard') }}" class="action-item orange">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                    </a>
                    
                    <a href="#" class="action-item red">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 11H7v6h2v-6zm4 0h-2v6h2v-6zm4 0h-2v6h2v-6zm2-7h-3V2h-2v2H8V2H6v2H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H3V9h14v11z"/>
                        </svg>
                    </a>
                    
                    <a href="#" class="action-item purple">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Floating Action Button -->
            <button class="fab" onclick="quickAction()">
                <svg fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
            </button>

            <!-- Bottom Navigation -->
            <div class="bottom-nav">
                <div class="nav-container">
                    <a href="#" class="nav-item active">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                        <span class="nav-label">Beranda</span>
                    </a>
                    
                    <a href="#" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5c0-1.38 1.12-2.5 2.5-2.5s2.5 1.12 2.5 2.5v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/>
                        </svg>
                        <span class="nav-label">Favorit</span>
                    </a>
                    
                    <a href="#" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <span class="nav-label">Lokasi</span>
                    </a>
                    
                    <a href="#" class="nav-item">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                        </svg>
                        <span class="nav-label">Profil</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function quickAction() {
            // Quick action functionality
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        alert('Aksi cepat: Lokasi terdeteksi! (Demo)');
                    },
                    function(error) {
                        alert('Gagal mendapatkan lokasi. Aktifkan GPS dan coba lagi.');
                    }
                );
            } else {
                alert('Browser tidak mendukung geolocation.');
            }
        }
        
        // Touch interactions
        document.addEventListener('DOMContentLoaded', function() {
            const touchElements = document.querySelectorAll('.action-item, .fab, .nav-item');
            
            touchElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                });
                
                element.addEventListener('touchcancel', function() {
                    this.style.opacity = '1';
                });
            });
        });
    </script>
    @endpush
</x-filament-panels::page>