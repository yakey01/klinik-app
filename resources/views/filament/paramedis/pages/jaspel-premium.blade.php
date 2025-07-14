<x-filament-panels::page>
    @push('styles')
    @include('filament.paramedis.partials.mobile-meta')
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* World-Class React Native Inspired Jaspel Dashboard */
        :root {
            /* Premium Color Palette - Matching Dashboard */
            --primary-gradient-start: #5B8DEE;
            --primary-gradient-end: #3F67E9;
            --secondary-gradient-start: #FF6B9D;
            --secondary-gradient-end: #FF4757;
            --success-gradient-start: #2ED8B6;
            --success-gradient-end: #00D980;
            --warning-gradient-start: #FFB64D;
            --warning-gradient-end: #FF9F43;
            
            /* Base Colors */
            --bg-primary: #F7F9FC;
            --bg-secondary: #FFFFFF;
            --text-primary: #1E293B;
            --text-secondary: #64748B;
            --text-light: #94A3B8;
            --border-light: #E2E8F0;
            
            /* Shadows */
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 24px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15);
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 500ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Hide Filament Components */
        .fi-sidebar,
        .fi-sidebar-backdrop,
        .fi-sidebar-overlay,
        .fi-topbar,
        .fi-sidebar-nav,
        .fi-sidebar-group,
        .fi-sidebar-item,
        [data-sidebar],
        aside.fi-sidebar,
        nav.fi-sidebar-nav,
        [x-data*="sidebar"],
        [x-data*="navigation"],
        .filament-sidebar,
        .filament-navigation,
        .fi-navigation,
        .fi-nav,
        body > div > aside,
        body > div > div > aside {
            display: none !important;
            visibility: hidden !important;
        }

        /* Full width content */
        .fi-main {
            margin-left: 0 !important;
            width: 100% !important;
        }
        
        /* Remove padding from page wrapper */
        .fi-main .fi-page {
            padding: 0 !important;
            margin: 0 !important;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            overflow-x: hidden;
        }
        
        /* ===== ANIMATION KEYFRAMES ===== */
        @keyframes fade-in-up {
            0% {
                opacity: 0;
                transform: translateY(30px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scale-in {
            0% {
                opacity: 0;
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* ===== MAIN CONTAINER ===== */
        .premium-mobile-container {
            width: 100%;
            max-width: 430px;
            min-height: 100vh;
            background: var(--bg-primary);
            margin: 0 auto;
            position: relative;
            overflow: hidden;
        }
        
        /* Status Bar */
        .status-bar {
            position: fixed;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 430px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            color: white;
        }
        
        .status-bar .time {
            font-weight: 600;
            font-size: 15px;
        }
        
        .status-bar .icons {
            display: flex;
            gap: 6px;
            align-items: center;
        }
        
        /* Main Content */
        .main-content {
            padding-top: 44px;
            padding-bottom: 80px;
            min-height: 100vh;
        }
        
        /* Header Section */
        .header-section {
            padding: 24px 20px 32px;
            background: linear-gradient(180deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .greeting h1 {
            font-size: 28px;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        
        .greeting p {
            font-size: 16px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .profile-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            box-shadow: var(--shadow-md);
        }
        
        /* Info Cards Section */
        .info-cards-section {
            padding: 0 20px 32px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-cards {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
        }
        
        .info-card {
            border-radius: 24px;
            padding: 28px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            opacity: 0;
            transform: translateY(30px);
            animation: fade-in-up 0.8s ease-out forwards;
            cursor: pointer;
            transition: all var(--transition-base);
        }
        
        .info-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .info-card.monthly {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            animation-delay: 100ms;
        }
        
        .info-card.weekly {
            background: linear-gradient(135deg, var(--warning-gradient-start) 0%, var(--warning-gradient-end) 100%);
            animation-delay: 200ms;
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(40%, -40%);
        }
        
        .card-content {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            backdrop-filter: blur(10px);
        }
        
        .card-label {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
            opacity: 0.95;
        }
        
        .card-amount {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 12px;
            letter-spacing: -1px;
        }
        
        .card-subtitle {
            font-size: 14px;
            opacity: 0.8;
            font-weight: 500;
        }
        
        /* Daily Jaspel Table Section */
        .daily-section {
            padding: 0 20px 32px;
        }
        
        .daily-table {
            background: var(--bg-secondary);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            opacity: 0;
            animation: scale-in 0.6s ease-out 400ms forwards;
        }
        
        .table-header {
            background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
            padding: 20px;
            border-bottom: 1px solid var(--border-light);
        }
        
        .table-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 4px;
        }
        
        .table-header p {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .table-content {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .table-row {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all var(--transition-fast);
        }
        
        .table-row:hover {
            background: #F8FAFC;
        }
        
        .table-row:last-child {
            border-bottom: none;
        }
        
        .row-main {
            flex: 1;
        }
        
        .row-date {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        
        .row-action {
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .row-amount {
            text-align: right;
        }
        
        .amount-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--success-gradient-end);
        }
        
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        
        .empty-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .empty-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        /* Bottom Navigation - Preserve from Dashboard */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 430px;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 100;
        }
        
        .nav-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            align-items: center;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--text-light);
            text-decoration: none;
            cursor: pointer;
            transition: all var(--transition-fast);
            padding: 8px 0;
            position: relative;
        }
        
        .nav-item.active {
            color: var(--primary-gradient-end);
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 5px;
            height: 5px;
            background: var(--primary-gradient-end);
            border-radius: 50%;
        }
        
        .nav-item:active {
            transform: scale(0.9);
        }
        
        .nav-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-icon svg {
            width: 22px;
            height: 22px;
        }
        
        .nav-label {
            font-size: 11px;
            font-weight: 500;
        }
        
        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #0F172A;
                --bg-secondary: #1E293B;
                --text-primary: #F1F5F9;
                --text-secondary: #CBD5E1;
                --text-light: #64748B;
                --border-light: #334155;
            }
            
            .status-bar {
                background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);
            }
            
            .bottom-nav {
                background: rgba(30, 41, 59, 0.95);
            }
            
            .table-header {
                background: linear-gradient(135deg, #1E293B 0%, #334155 100%);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 430px) {
            .premium-mobile-container {
                border-radius: 0;
            }
        }
        
        /* Touch Feedback */
        .touchable {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
    </style>
    @endpush
    
    <div class="premium-mobile-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <div class="time" id="current-time">{{ now()->format('H:i') }}</div>
            <div class="icons">
                <div style="width: 18px; height: 10px; border: 1px solid white; border-radius: 2px; position: relative;">
                    <div style="width: 12px; height: 6px; background: white; border-radius: 1px; position: absolute; top: 1px; left: 1px;"></div>
                </div>
                <svg width="15" height="11" viewBox="0 0 15 11" fill="white">
                    <path d="M1 4l4 4 8-8" stroke="white" stroke-width="2"/>
                </svg>
            </div>
        </div>
        
        <div class="main-content">
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-top">
                    <div class="greeting">
                        <h1>💰 Jaspel</h1>
                        <p>Service Fee Dashboard</p>
                    </div>
                    <div class="profile-section">
                        <div class="avatar">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Info Cards Section -->
            <div class="info-cards-section">
                <div class="section-title">
                    <span>📊 Ringkasan Jaspel</span>
                </div>
                <div class="info-cards">
                    <!-- Monthly Jaspel Card -->
                    <div class="info-card monthly touchable">
                        <div class="card-content">
                            <div class="card-icon">
                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                </svg>
                            </div>
                            <div class="card-label">💳 Jaspel Bulan Ini</div>
                            <div class="card-amount">Rp {{ number_format($monthlyJaspel, 0, ',', '.') }}</div>
                            <div class="card-subtitle">{{ now()->format('F Y') }}</div>
                        </div>
                    </div>
                    
                    <!-- Weekly Jaspel Card -->
                    <div class="info-card weekly touchable">
                        <div class="card-content">
                            <div class="card-icon">
                                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                </svg>
                            </div>
                            <div class="card-label">📅 Jaspel Minggu Ini</div>
                            <div class="card-amount">Rp {{ number_format($weeklyJaspel, 0, ',', '.') }}</div>
                            <div class="card-subtitle">{{ now()->startOfWeek()->format('d') }} - {{ now()->endOfWeek()->format('d M') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daily Jaspel Table -->
            <div class="daily-section">
                <div class="section-title">
                    <span>📋 Jaspel Harian</span>
                </div>
                <div class="daily-table">
                    <div class="table-header">
                        <h3>Transaksi Terkini</h3>
                        <p>Daftar tindakan dan nominal jaspel</p>
                    </div>
                    <div class="table-content">
                        @forelse($dailyJaspel as $date => $dayTransactions)
                            @foreach($dayTransactions as $transaction)
                            <div class="table-row touchable">
                                <div class="row-main">
                                    <div class="row-date">{{ $transaction['tanggal'] }}</div>
                                    <div class="row-action">{{ $transaction['tindakan'] }}</div>
                                </div>
                                <div class="row-amount">
                                    <div class="amount-value">Rp {{ number_format($transaction['nominal'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                            @endforeach
                        @empty
                        <div class="empty-state">
                            <div class="empty-icon">
                                <svg width="32" height="32" fill="#9CA3AF" viewBox="0 0 24 24">
                                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div class="empty-title">Belum Ada Transaksi</div>
                            <div class="empty-subtitle">Data jaspel akan muncul setelah<br>tindakan divalidasi oleh bendahara</div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Bottom Navigation - Preserve exact structure from dashboard -->
        <nav class="bottom-nav">
            <div class="nav-container">
                <a href="{{ route('filament.paramedis.pages.uji-coba-dashboard') }}" class="nav-item touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                
                <a href="#" class="nav-item touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                            <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Presensi</span>
                </a>
                
                <a href="{{ route('filament.paramedis.pages.jaspel-premium-page') }}" class="nav-item active touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Jaspel</span>
                </a>
                
                <a href="{{ route('filament.paramedis.pages.presensi-mobile') }}" class="nav-item touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                    </div>
                    <span class="nav-label">GPS</span>
                </a>
                
                <a href="#" class="nav-item touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Tindakan</span>
                </a>
            </div>
        </nav>
    </div>
    
    @push('scripts')
    <script>
        // Force Remove Filament Sidebar
        document.addEventListener('DOMContentLoaded', function() {
            // Remove any Filament sidebar elements that might exist
            const filamentSidebars = document.querySelectorAll(
                '.fi-sidebar, .fi-sidebar-backdrop, .fi-sidebar-overlay, .fi-topbar, ' +
                '.fi-sidebar-nav, .fi-sidebar-group, .fi-sidebar-item, [data-sidebar], ' +
                'aside.fi-sidebar, nav.fi-sidebar-nav, [x-data*="sidebar"], ' +
                '[x-data*="navigation"], .filament-sidebar, .filament-navigation, ' +
                '.fi-navigation, .fi-nav, aside'
            );
            
            filamentSidebars.forEach(element => {
                if (element && !element.classList.contains('paramedis-sidebar')) {
                    element.remove();
                }
            });
            
            // Ensure main content is full width
            const fiMain = document.querySelector('.fi-main');
            if (fiMain) {
                fiMain.style.marginLeft = '0';
                fiMain.style.width = '100%';
            }
            
            // Update clock every minute
            function updateClock() {
                const now = new Date();
                const time = now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit', 
                    hour12: false 
                });
                const clockElement = document.getElementById('current-time');
                if (clockElement) {
                    clockElement.textContent = time;
                }
            }
            
            // Update clock immediately and then every minute
            updateClock();
            setInterval(updateClock, 60000);
            
            // Add ripple effect to touchable elements
            function createRippleEffect(event, element) {
                const rect = element.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = event.clientX - rect.left - size / 2;
                const y = event.clientY - rect.top - size / 2;
                
                const ripple = document.createElement('span');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(255, 255, 255, 0.3);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    left: ${x}px;
                    top: ${y}px;
                    width: ${size}px;
                    height: ${size}px;
                    pointer-events: none;
                `;
                
                element.style.position = 'relative';
                element.style.overflow = 'hidden';
                element.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            }
            
            // Add ripple to all touchable elements
            document.querySelectorAll('.touchable').forEach(element => {
                element.addEventListener('touchstart', function(e) {
                    createRippleEffect(e.touches[0], this);
                });
                
                element.addEventListener('mousedown', function(e) {
                    createRippleEffect(e, this);
                });
            });
            
            // Add CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
    @endpush
</x-filament-panels::page>