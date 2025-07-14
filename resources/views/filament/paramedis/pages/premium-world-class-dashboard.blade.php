<x-filament-panels::page>
    @push('styles')
    <link href="https://fonts.googleapis.com/css2?family=SF+Pro+Display:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* World-Class Premium Mobile Dashboard - React Native Inspired */
        :root {
            /* Premium Color Palette */
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            overflow-x: hidden;
        }
        
        /* Mobile Container */
        .premium-mobile-container {
            width: 100%;
            max-width: 430px;
            margin: 0 auto;
            min-height: 100vh;
            background: var(--bg-secondary);
            position: relative;
            overflow: hidden;
        }
        
        /* Override Filament Sidebar on Mobile */
        @media (max-width: 768px) {
            /* Hide sidebar */
            .fi-sidebar {
                display: none !important;
            }
            
            /* Full width content */
            .fi-main {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            /* Hide topbar */
            .fi-topbar {
                display: none !important;
            }
            
            /* Remove padding from page wrapper */
            .fi-main .fi-page {
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Remove any overlay/backdrop */
            .fi-sidebar-backdrop,
            .fi-sidebar-overlay {
                display: none !important;
            }
        }
        
        /* Status Bar */
        .status-bar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
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
            align-items: center;
            margin-bottom: 24px;
        }
        
        .greeting {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 4px;
        }
        
        .user-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        
        .profile-avatar {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            cursor: pointer;
            transition: transform var(--transition-base);
        }
        
        .profile-avatar:active {
            transform: scale(0.95);
        }
        
        /* Earnings Card - Premium Design */
        .earnings-card {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            border-radius: 24px;
            padding: 28px;
            margin: 0 20px 32px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 32px rgba(91, 141, 238, 0.3);
            transform: translateY(0);
            transition: all var(--transition-base);
        }
        
        .earnings-card:active {
            transform: translateY(2px);
            box-shadow: 0 8px 24px rgba(91, 141, 238, 0.25);
        }
        
        .earnings-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .earnings-content {
            position: relative;
            z-index: 2;
        }
        
        .earnings-label {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 12px;
        }
        
        .earnings-icon {
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .earnings-amount {
            font-size: 42px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
            letter-spacing: -1.5px;
            line-height: 1;
        }
        
        .earnings-progress {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 100px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.8) 0%, rgba(255, 255, 255, 1) 100%);
            border-radius: 100px;
            width: var(--progress, 83%);
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.4) 50%, transparent 100%);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Quick Actions Grid */
        .quick-actions {
            padding: 0 20px 32px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .see-all {
            color: var(--primary-gradient-end);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        
        .action-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all var(--transition-base);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }
        
        .action-card:active {
            transform: scale(0.97);
            box-shadow: var(--shadow-sm);
        }
        
        .action-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
            transition: all var(--transition-base);
        }
        
        .action-icon.primary {
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
        }
        
        .action-icon.success {
            background: linear-gradient(135deg, var(--success-gradient-start) 0%, var(--success-gradient-end) 100%);
        }
        
        .action-icon.warning {
            background: linear-gradient(135deg, var(--warning-gradient-start) 0%, var(--warning-gradient-end) 100%);
        }
        
        .action-icon.danger {
            background: linear-gradient(135deg, var(--secondary-gradient-start) 0%, var(--secondary-gradient-end) 100%);
        }
        
        .action-icon svg {
            width: 28px;
            height: 28px;
            color: white;
        }
        
        .action-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .action-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 2px;
        }
        
        /* Recent Activities */
        .recent-activities {
            padding: 0 20px 32px;
        }
        
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .activity-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-light);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all var(--transition-base);
        }
        
        .activity-item:active {
            background: var(--bg-primary);
            transform: scale(0.98);
        }
        
        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--text-light);
        }
        
        .activity-amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Floating Action Button */
        .fab-container {
            position: fixed;
            bottom: 100px;
            right: 20px;
            z-index: 50;
        }
        
        .fab {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary-gradient-start) 0%, var(--primary-gradient-end) 100%);
            border: none;
            box-shadow: 0 8px 24px rgba(91, 141, 238, 0.4);
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--transition-base);
            position: relative;
            overflow: hidden;
        }
        
        .fab::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: all 0.6s;
        }
        
        .fab:active::before {
            width: 120px;
            height: 120px;
        }
        
        .fab:active {
            transform: scale(0.9);
            box-shadow: 0 4px 16px rgba(91, 141, 238, 0.3);
        }
        
        .fab svg {
            width: 28px;
            height: 28px;
            position: relative;
            z-index: 1;
        }
        
        .fab-label {
            position: absolute;
            bottom: -36px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            transition: opacity var(--transition-base);
        }
        
        .fab:hover .fab-label {
            opacity: 1;
        }
        
        /* Bottom Navigation - iOS Style */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid var(--border-light);
            padding: 12px 0 28px;
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
        
        /* Responsive Design */
        @media (max-width: 430px) {
            .premium-mobile-container {
                border-radius: 0;
            }
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
        }
        
        /* Touch Feedback */
        .touchable {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
        
        /* Loading State */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
    </style>
    @endpush
    
    <div class="premium-mobile-container">
        <!-- Status Bar -->
        <div class="status-bar">
            <div class="time">{{ now()->format('H:i') }}</div>
            <div class="icons">
                <svg width="16" height="12" fill="white" viewBox="0 0 16 12">
                    <rect x="0" y="8" width="3" height="4" rx="0.5"/>
                    <rect x="4" y="6" width="3" height="6" rx="0.5"/>
                    <rect x="8" y="4" width="3" height="8" rx="0.5"/>
                    <rect x="12" y="2" width="3" height="10" rx="0.5"/>
                </svg>
                <svg width="16" height="12" fill="white" viewBox="0 0 16 12">
                    <path d="M8.5 2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    <path d="M13.5 2.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                    <path d="M5 8.5a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
                <svg width="24" height="12" fill="white" viewBox="0 0 24 12">
                    <rect x="0" y="2" width="20" height="8" rx="2" stroke="white" fill="none"/>
                    <rect x="20" y="5" width="2" height="3" rx="0.5"/>
                    <rect x="2" y="4" width="14" height="4" rx="1"/>
                </svg>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header Section -->
            <div class="header-section">
                <div class="header-top">
                    <div>
                        <div class="greeting">Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 18 ? 'Siang' : 'Malam') }} 👋</div>
                        <div class="user-name">{{ $user->name }}</div>
                    </div>
                    <div class="profile-avatar touchable">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </div>
            
            <!-- Earnings Card -->
            <div class="earnings-card touchable">
                <div class="earnings-content">
                    <div class="earnings-label">
                        <div class="earnings-icon">
                            <svg width="12" height="12" fill="white" viewBox="0 0 12 12">
                                <path d="M6 0C2.69 0 0 2.69 0 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 1.5c.83 0 1.5.67 1.5 1.5S6.83 4.5 6 4.5 4.5 3.83 4.5 3 5.17 1.5 6 1.5zm0 9c-1.65 0-3.09-.89-3.87-2.21.03-.71 2.58-1.1 3.87-1.1s3.84.39 3.87 1.1C9.09 9.61 7.65 10.5 6 10.5z"/>
                            </svg>
                        </div>
                        <span>Jaspel Earnings</span>
                    </div>
                    <div class="earnings-amount">
                        Rp {{ number_format($monthlyJaspel, 0, ',', '.') }}
                    </div>
                    <div class="earnings-progress">
                        <div class="progress-info">
                            <span>Target: Rp {{ number_format(10464000, 0, ',', '.') }}</span>
                            <span>{{ $progressPercentage = min(round(($monthlyJaspel / 10464000) * 100), 100) }}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="--progress: {{ $progressPercentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <div class="section-title">
                    <span>Aksi Cepat</span>
                    <a href="#" class="see-all">Lihat Semua</a>
                </div>
                <div class="actions-grid">
                    <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="action-card touchable">
                        <div class="action-icon primary">
                            <svg fill="white" viewBox="0 0 24 24">
                                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                                <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                        </div>
                        <div class="action-title">Presensi</div>
                        <div class="action-subtitle">Check In/Out</div>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.resources.jaspels.index') }}" class="action-card touchable">
                        <div class="action-icon success">
                            <svg fill="white" viewBox="0 0 24 24">
                                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                            </svg>
                        </div>
                        <div class="action-title">Jaspel</div>
                        <div class="action-subtitle">View earnings</div>
                    </a>
                    
                    <a href="#" class="action-card touchable">
                        <div class="action-icon warning">
                            <svg fill="white" viewBox="0 0 24 24">
                                <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                            </svg>
                        </div>
                        <div class="action-title">Tindakan</div>
                        <div class="action-subtitle">Medical actions</div>
                    </a>
                    
                    <a href="#" class="action-card touchable">
                        <div class="action-icon danger">
                            <svg fill="white" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <div class="action-title">Presensi Mobile</div>
                        <div class="action-subtitle">GPS Check-in</div>
                    </a>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="recent-activities">
                <div class="section-title">
                    <span>Aktivitas Terkini</span>
                </div>
                <div class="activity-list">
                    @foreach($recentActivities as $activity)
                    <div class="activity-item touchable">
                        <div class="activity-icon" style="background: linear-gradient(135deg, {{ $activity['color'] }}55 0%, {{ $activity['color'] }}88 100%);">
                            <svg width="20" height="20" fill="{{ $activity['color'] }}" viewBox="0 0 20 20">
                                {!! $activity['icon'] !!}
                            </svg>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">{{ $activity['title'] }}</div>
                            <div class="activity-time">{{ $activity['time'] }}</div>
                        </div>
                        @if(isset($activity['amount']))
                        <div class="activity-amount">+Rp {{ number_format($activity['amount'], 0, ',', '.') }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        
        <!-- Floating Action Button -->
        <div class="fab-container">
            <button class="fab touchable" onclick="quickCheckIn()">
                <svg fill="white" viewBox="0 0 24 24">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                <span class="fab-label">Quick Check-in</span>
            </button>
        </div>
        
        <!-- Bottom Navigation -->
        <nav class="bottom-nav">
            <div class="nav-container">
                <a href="#" class="nav-item active touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                
                <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="nav-item touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"/>
                            <path d="M12.5 7H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Presensi</span>
                </a>
                
                <a href="{{ route('filament.paramedis.resources.jaspels.index') }}" class="nav-item touchable">
                    <div class="nav-icon">
                        <svg fill="currentColor" viewBox="0 0 24 24">
                            <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                        </svg>
                    </div>
                    <span class="nav-label">Jaspel</span>
                </a>
                
                <a href="#" class="nav-item touchable">
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
        // Initialize progress bar animation
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const progressFill = document.querySelector('.progress-fill');
                if (progressFill) {
                    progressFill.style.width = progressFill.style.getPropertyValue('--progress');
                }
            }, 500);
        });
        
        // Quick Check-in Function
        function quickCheckIn() {
            // Haptic feedback (if supported)
            if (navigator.vibrate) {
                navigator.vibrate(50);
            }
            
            // Get current location
            if (navigator.geolocation) {
                const button = document.querySelector('.fab');
                button.innerHTML = '<div class="spinner"></div>';
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        // Success
                        button.innerHTML = '<svg fill="white" viewBox="0 0 24 24"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>';
                        
                        // Navigate to attendance with location data
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        window.location.href = `{{ route('filament.paramedis.resources.attendances.create') }}?lat=${lat}&lng=${lng}`;
                    },
                    function(error) {
                        // Error
                        button.innerHTML = '<svg fill="white" viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>';
                        alert('Gagal mendapatkan lokasi. Aktifkan GPS dan coba lagi.');
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                alert('Browser tidak mendukung geolocation.');
            }
        }
        
        // Touch feedback for all touchable elements
        document.querySelectorAll('.touchable').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            
            element.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
            
            element.addEventListener('touchcancel', function() {
                this.style.opacity = '1';
            });
        });
        
        // Pull to refresh (simplified)
        let touchStartY = 0;
        let touchEndY = 0;
        
        document.addEventListener('touchstart', function(e) {
            touchStartY = e.changedTouches[0].screenY;
        });
        
        document.addEventListener('touchend', function(e) {
            touchEndY = e.changedTouches[0].screenY;
            if (touchEndY > touchStartY + 100 && window.scrollY === 0) {
                location.reload();
            }
        });
    </script>
    
    <style>
        /* Spinner for loading state */
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    @endpush
</x-filament-panels::page>