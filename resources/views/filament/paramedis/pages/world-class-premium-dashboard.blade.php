<x-filament-panels::page>
    @push('styles')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        /* World-Class Premium Dashboard - Global Standards */
        :root {
            /* Primary Brand Colors */
            --primary-royal: #4F46E5;
            --primary-light: #6366F1;
            --primary-dark: #3730A3;
            
            /* Functional Colors */
            --attendance-blue: #0EA5E9;
            --attendance-blue-light: #38BDF8;
            --jaspel-green: #10B981;
            --jaspel-green-light: #34D399;
            --schedule-purple: #8B5CF6;
            --schedule-purple-light: #A78BFA;
            --info-gray: #6B7280;
            --info-gray-light: #9CA3AF;
            
            /* Background & Surface */
            --bg-primary: #FAFBFC;
            --bg-secondary: #FFFFFF;
            --bg-glass: rgba(255, 255, 255, 0.85);
            --bg-sidebar: rgba(255, 255, 255, 0.95);
            
            /* Text Colors */
            --text-primary: #111827;
            --text-secondary: #6B7280;
            --text-tertiary: #9CA3AF;
            --text-inverse: #FFFFFF;
            
            /* Border & Divider */
            --border-primary: #E5E7EB;
            --border-secondary: #F3F4F6;
            --border-glass: rgba(255, 255, 255, 0.2);
            
            /* Shadows */
            --shadow-xs: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --shadow-glass: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            
            /* Glassmorphism */
            --glass-backdrop: blur(40px);
            --glass-border: 1px solid rgba(255, 255, 255, 0.18);
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --radius-2xl: 24px;
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 250ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 350ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Layout Container */
        .dashboard-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        /* Sidebar - Desktop/Tablet */
        .sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            backdrop-filter: var(--glass-backdrop);
            -webkit-backdrop-filter: var(--glass-backdrop);
            border-right: var(--glass-border);
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 50;
            transform: translateX(-100%);
            transition: transform var(--transition-base);
            box-shadow: var(--shadow-glass);
        }
        
        .sidebar.open {
            transform: translateX(0);
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-secondary);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .sidebar-brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary-royal) 0%, var(--primary-light) 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-inverse);
        }
        
        .sidebar-nav {
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .sidebar-nav-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 16px;
            border-radius: var(--radius-lg);
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-fast);
            position: relative;
        }
        
        .sidebar-nav-item:hover {
            background: var(--bg-secondary);
            color: var(--text-primary);
            box-shadow: var(--shadow-sm);
        }
        
        .sidebar-nav-item.active {
            background: linear-gradient(135deg, var(--primary-royal) 0%, var(--primary-light) 100%);
            color: var(--text-inverse);
            box-shadow: var(--shadow-md);
        }
        
        .sidebar-nav-icon {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }
        
        /* Main Content Area */
        .main-content {
            flex: 1;
            margin-left: 0;
            transition: margin-left var(--transition-base);
        }
        
        .main-content.with-sidebar {
            margin-left: 280px;
        }
        
        /* Mobile Header */
        .mobile-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-primary);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 40;
            backdrop-filter: var(--glass-backdrop);
            -webkit-backdrop-filter: var(--glass-backdrop);
        }
        
        .mobile-header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .menu-toggle {
            background: none;
            border: none;
            padding: 8px;
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .menu-toggle:hover {
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .mobile-header-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .mobile-header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-avatar {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--primary-royal) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-inverse);
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: transform var(--transition-fast);
        }
        
        .header-avatar:active {
            transform: scale(0.95);
        }
        
        /* Content Container */
        .content-container {
            padding: 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Greeting Section */
        .greeting-section {
            margin-bottom: 32px;
        }
        
        .greeting-text {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }
        
        .user-name {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        
        /* Dashboard Cards */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        
        /* Primary Earnings Card */
        .earnings-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, var(--primary-royal) 0%, var(--primary-light) 100%);
            border-radius: var(--radius-2xl);
            padding: 32px;
            color: var(--text-inverse);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            cursor: pointer;
            transition: all var(--transition-base);
        }
        
        .earnings-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.5);
        }
        
        .earnings-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        
        .earnings-content {
            position: relative;
            z-index: 2;
        }
        
        .earnings-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .earnings-icon {
            width: 48px;
            height: 48px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
        }
        
        .earnings-label {
            font-size: 16px;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .earnings-amount {
            font-size: 48px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 24px;
            letter-spacing: -1px;
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
            font-size: 14px;
            opacity: 0.9;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 100px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 100px;
            width: var(--progress, 0%);
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
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
            margin-bottom: 32px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .section-action {
            color: var(--primary-royal);
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .section-action:hover {
            color: var(--primary-dark);
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            padding: 24px;
            text-decoration: none;
            transition: all var(--transition-base);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255, 255, 255, 0.1) 100%);
            opacity: 0;
            transition: opacity var(--transition-base);
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--border-secondary);
        }
        
        .action-card:hover::before {
            opacity: 1;
        }
        
        .action-card.attendance {
            border-left: 4px solid var(--attendance-blue);
        }
        
        .action-card.jaspel {
            border-left: 4px solid var(--jaspel-green);
        }
        
        .action-card.schedule {
            border-left: 4px solid var(--schedule-purple);
        }
        
        .action-card.profile {
            border-left: 4px solid var(--info-gray);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            position: relative;
            z-index: 2;
        }
        
        .action-icon.attendance {
            background: linear-gradient(135deg, var(--attendance-blue) 0%, var(--attendance-blue-light) 100%);
            color: var(--text-inverse);
        }
        
        .action-icon.jaspel {
            background: linear-gradient(135deg, var(--jaspel-green) 0%, var(--jaspel-green-light) 100%);
            color: var(--text-inverse);
        }
        
        .action-icon.schedule {
            background: linear-gradient(135deg, var(--schedule-purple) 0%, var(--schedule-purple-light) 100%);
            color: var(--text-inverse);
        }
        
        .action-icon.profile {
            background: linear-gradient(135deg, var(--info-gray) 0%, var(--info-gray-light) 100%);
            color: var(--text-inverse);
        }
        
        .action-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            position: relative;
            z-index: 2;
        }
        
        .action-subtitle {
            font-size: 14px;
            color: var(--text-secondary);
            position: relative;
            z-index: 2;
        }
        
        /* Recent Activities */
        .recent-activities {
            margin-bottom: 32px;
        }
        
        .activity-list {
            background: var(--bg-secondary);
            border: 1px solid var(--border-primary);
            border-radius: var(--radius-xl);
            overflow: hidden;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-secondary);
            transition: background var(--transition-fast);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item:hover {
            background: var(--bg-primary);
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-md);
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
            color: var(--text-tertiary);
        }
        
        .activity-amount {
            font-size: 16px;
            font-weight: 700;
            color: var(--jaspel-green);
        }
        
        /* Bottom Navigation - Mobile */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--bg-glass);
            backdrop-filter: var(--glass-backdrop);
            -webkit-backdrop-filter: var(--glass-backdrop);
            border-top: var(--glass-border);
            padding: 12px 0 calc(12px + env(safe-area-inset-bottom));
            z-index: 100;
            box-shadow: var(--shadow-xl);
        }
        
        .nav-container {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            max-width: 430px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 8px 0;
            text-decoration: none;
            color: var(--text-tertiary);
            transition: all var(--transition-fast);
            position: relative;
        }
        
        .nav-item.active {
            color: var(--primary-royal);
        }
        
        .nav-item.active::before {
            content: '';
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 24px;
            height: 3px;
            background: var(--primary-royal);
            border-radius: 100px;
        }
        
        .nav-item:active {
            transform: scale(0.95);
        }
        
        .nav-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .nav-label {
            font-size: 11px;
            font-weight: 500;
        }
        
        /* Responsive Design */
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 280px;
            }
            
            .mobile-header {
                display: none;
            }
            
            .bottom-nav {
                display: none;
            }
            
            .content-container {
                padding: 32px;
            }
        }
        
        @media (max-width: 767px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .actions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            
            .content-container {
                padding: 20px;
                padding-bottom: 100px;
            }
            
            .earnings-amount {
                font-size: 36px;
            }
        }
        
        /* Dark Mode Support */
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-primary: #0F172A;
                --bg-secondary: #1E293B;
                --bg-glass: rgba(30, 41, 59, 0.85);
                --bg-sidebar: rgba(30, 41, 59, 0.95);
                --text-primary: #F8FAFC;
                --text-secondary: #CBD5E1;
                --text-tertiary: #64748B;
                --border-primary: #334155;
                --border-secondary: #475569;
                --border-glass: rgba(148, 163, 184, 0.1);
            }
        }
        
        /* Touch Feedback */
        .touchable {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
        
        /* Loading States */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            visibility: hidden;
            transition: all var(--transition-base);
        }
        
        .sidebar-overlay.show {
            opacity: 1;
            visibility: visible;
        }
    </style>
    @endpush
    
    <div class="dashboard-layout">
        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <div class="sidebar-brand-icon">
                        <i data-lucide="heart-pulse"></i>
                    </div>
                    <span>Dokterku</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="{{ route('filament.paramedis.pages.premium-dashboard') }}" class="sidebar-nav-item active">
                    <i data-lucide="layout-dashboard" class="sidebar-nav-icon"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="sidebar-nav-item">
                    <i data-lucide="clock" class="sidebar-nav-icon"></i>
                    <span>Presensi</span>
                </a>
                
                <a href="{{ route('filament.paramedis.resources.jaspels.index') }}" class="sidebar-nav-item">
                    <i data-lucide="banknote" class="sidebar-nav-icon"></i>
                    <span>Jaspel</span>
                </a>
                
                <a href="#" class="sidebar-nav-item">
                    <i data-lucide="calendar" class="sidebar-nav-icon"></i>
                    <span>Jadwal Jaga</span>
                </a>
                
                <a href="#" class="sidebar-nav-item">
                    <i data-lucide="clipboard-list" class="sidebar-nav-icon"></i>
                    <span>Tindakan</span>
                </a>
                
                <a href="#" class="sidebar-nav-item">
                    <i data-lucide="settings" class="sidebar-nav-icon"></i>
                    <span>Pengaturan</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Mobile Header -->
            <header class="mobile-header">
                <div class="mobile-header-left">
                    <button class="menu-toggle touchable" id="menuToggle">
                        <i data-lucide="menu" width="20" height="20"></i>
                    </button>
                    <h1 class="mobile-header-title">Dashboard</h1>
                </div>
                <div class="mobile-header-right">
                    <div class="header-avatar touchable">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                </div>
            </header>
            
            <!-- Content Container -->
            <div class="content-container">
                <!-- Greeting Section -->
                <section class="greeting-section">
                    <div class="greeting-text">
                        Selamat {{ now()->format('H') < 12 ? 'Pagi' : (now()->format('H') < 18 ? 'Siang' : 'Malam') }} 👋
                    </div>
                    <h1 class="user-name">{{ $user->name }}</h1>
                </section>
                
                <!-- Dashboard Grid -->
                <div class="dashboard-grid">
                    <!-- Earnings Card -->
                    <div class="earnings-card touchable">
                        <div class="earnings-content">
                            <div class="earnings-header">
                                <div class="earnings-icon">
                                    <i data-lucide="trending-up" width="24" height="24"></i>
                                </div>
                                <div class="earnings-label">Jaspel Earnings</div>
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
                </div>
                
                <!-- Quick Actions -->
                <section class="quick-actions">
                    <div class="section-header">
                        <h2 class="section-title">Aksi Cepat</h2>
                        <a href="#" class="section-action">Lihat Semua</a>
                    </div>
                    
                    <div class="actions-grid">
                        <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="action-card attendance touchable">
                            <div class="action-icon attendance">
                                <i data-lucide="clock" width="24" height="24"></i>
                            </div>
                            <div class="action-title">Presensi</div>
                            <div class="action-subtitle">Check In/Out</div>
                        </a>
                        
                        <a href="{{ route('filament.paramedis.resources.jaspels.index') }}" class="action-card jaspel touchable">
                            <div class="action-icon jaspel">
                                <i data-lucide="banknote" width="24" height="24"></i>
                            </div>
                            <div class="action-title">Jaspel</div>
                            <div class="action-subtitle">View earnings</div>
                        </a>
                        
                        <a href="#" class="action-card schedule touchable">
                            <div class="action-icon schedule">
                                <i data-lucide="calendar" width="24" height="24"></i>
                            </div>
                            <div class="action-title">Jadwal</div>
                            <div class="action-subtitle">Schedule shifts</div>
                        </a>
                        
                        <a href="#" class="action-card profile touchable">
                            <div class="action-icon profile">
                                <i data-lucide="user" width="24" height="24"></i>
                            </div>
                            <div class="action-title">Profil</div>
                            <div class="action-subtitle">Account settings</div>
                        </a>
                    </div>
                </section>
                
                <!-- Recent Activities -->
                <section class="recent-activities">
                    <div class="section-header">
                        <h2 class="section-title">Aktivitas Terkini</h2>
                    </div>
                    
                    <div class="activity-list">
                        @foreach($recentActivities as $activity)
                        <div class="activity-item">
                            <div class="activity-icon" style="background: linear-gradient(135deg, {{ $activity['color'] }}20 0%, {{ $activity['color'] }}40 100%);">
                                <i data-lucide="{{ $activity['lucide_icon'] }}" width="20" height="20" style="color: {{ $activity['color'] }};"></i>
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
                </section>
            </div>
        </main>
        
        <!-- Bottom Navigation - Mobile -->
        <nav class="bottom-nav">
            <div class="nav-container">
                <a href="{{ route('filament.paramedis.pages.premium-dashboard') }}" class="nav-item active touchable">
                    <div class="nav-icon">
                        <i data-lucide="home" width="20" height="20"></i>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                
                <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="nav-item touchable">
                    <div class="nav-icon">
                        <i data-lucide="clock" width="20" height="20"></i>
                    </div>
                    <span class="nav-label">Presensi</span>
                </a>
                
                <a href="{{ route('filament.paramedis.resources.jaspels.index') }}" class="nav-item touchable">
                    <div class="nav-icon">
                        <i data-lucide="banknote" width="20" height="20"></i>
                    </div>
                    <span class="nav-label">Jaspel</span>
                </a>
                
                <a href="#" class="nav-item touchable">
                    <div class="nav-icon">
                        <i data-lucide="calendar" width="20" height="20"></i>
                    </div>
                    <span class="nav-label">Jadwal</span>
                </a>
                
                <a href="#" class="nav-item touchable">
                    <div class="nav-icon">
                        <i data-lucide="user" width="20" height="20"></i>
                    </div>
                    <span class="nav-label">Profil</span>
                </a>
            </div>
        </nav>
    </div>
    
    @push('scripts')
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Mobile sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('open');
            sidebarOverlay.classList.toggle('show');
        }
        
        menuToggle?.addEventListener('click', toggleSidebar);
        sidebarOverlay?.addEventListener('click', toggleSidebar);
        
        // Initialize progress bar animation
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                const progressFill = document.querySelector('.progress-fill');
                if (progressFill) {
                    const progress = progressFill.style.getPropertyValue('--progress');
                    progressFill.style.width = progress;
                }
            }, 500);
            
            // Reinitialize Lucide icons after DOM manipulation
            setTimeout(() => {
                lucide.createIcons();
            }, 100);
        });
        
        // Touch feedback for touchable elements
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
        
        // Active navigation state management
        function setActiveNav() {
            const currentPath = window.location.pathname;
            const navItems = document.querySelectorAll('.nav-item, .sidebar-nav-item');
            
            navItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && currentPath.includes(href.split('/').pop())) {
                    item.classList.add('active');
                } else if (!href || href === '#') {
                    item.classList.remove('active');
                }
            });
        }
        
        // Update navigation states on page load
        document.addEventListener('DOMContentLoaded', setActiveNav);
        
        // Responsive behavior
        function handleResize() {
            if (window.innerWidth >= 768) {
                sidebar.classList.remove('open');
                sidebarOverlay.classList.remove('show');
            }
        }
        
        window.addEventListener('resize', handleResize);
        
        // Pull to refresh (mobile)
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
    @endpush
</x-filament-panels::page>