<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Premium Dashboard - {{ auth()->user()->name }}</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#007AFF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="light-content">
    <meta name="apple-mobile-web-app-title" content="Premium Dashboard">
    
    <style>
        /* Premium Mobile Dashboard - Pure CSS Implementation */
        :root {
            --primary-blue: #007AFF;
            --primary-blue-light: #3D9DFF;
            --primary-blue-dark: #0056CC;
            --accent-purple: #7B68EE;
            --accent-pink: #FF6B9D;
            --success-green: #30D158;
            --warning-orange: #FF9F0A;
            --error-red: #FF453A;
            
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            --gradient-primary: linear-gradient(135deg, #007AFF 0%, #3D9DFF 100%);
            --gradient-success: linear-gradient(135deg, #30D158 0%, #00C851 100%);
            --gradient-warning: linear-gradient(135deg, #FF9F0A 0%, #FF6B35 100%);
            --gradient-purple: linear-gradient(135deg, #7B68EE 0%, #9F7AEA 100%);
            --gradient-pink: linear-gradient(135deg, #FF6B9D 0%, #FF8CC8 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #F0F4FF 0%, #E8F2FF 50%, #DDE7FF 100%);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            margin: 0;
            padding: 0;
        }

        .premium-dashboard {
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 0;
            position: relative;
        }

        .dashboard-container {
            width: 100%;
            max-width: 430px;
            min-height: 100vh;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            position: relative;
            overflow-y: auto;
            overflow-x: hidden;
        }

        @media (min-width: 431px) {
            .dashboard-container {
                border-radius: 32px;
                margin: 20px auto;
                min-height: calc(100vh - 40px);
                box-shadow: var(--shadow-2xl);
            }
        }

        /* Floating Shapes Background */
        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .floating-shapes {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(0, 122, 255, 0.1);
            animation: float 20s infinite ease-in-out;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: 10%;
            left: -10%;
            animation-delay: 0s;
            background: rgba(0, 122, 255, 0.08);
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            top: 60%;
            right: -5%;
            animation-delay: -5s;
            background: rgba(123, 104, 238, 0.06);
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            bottom: 20%;
            left: 20%;
            animation-delay: -10s;
            background: rgba(255, 107, 157, 0.05);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            25% { transform: translateY(-20px) rotate(90deg); }
            50% { transform: translateY(-10px) rotate(180deg); }
            75% { transform: translateY(-30px) rotate(270deg); }
        }

        /* Status Bar */
        .status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px 8px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .status-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .online-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success-green);
            animation: pulse 2s infinite;
        }

        .status-text {
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-600);
        }

        .status-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-badge {
            position: relative;
            padding: 8px;
            border-radius: 12px;
            background: rgba(0, 122, 255, 0.1);
            color: var(--primary-blue);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .badge-count {
            position: absolute;
            top: 4px;
            right: 4px;
            background: var(--error-red);
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 8px;
            min-width: 16px;
            text-align: center;
        }

        /* Header Section */
        .dashboard-header {
            padding: 32px 24px;
            background: linear-gradient(135deg, 
                rgba(0, 122, 255, 0.05) 0%, 
                rgba(123, 104, 238, 0.03) 50%, 
                rgba(255, 107, 157, 0.02) 100%
            );
            position: relative;
            overflow: hidden;
        }

        .welcome-section {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
        }

        .avatar-container {
            position: relative;
        }

        .avatar-ring {
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            border-radius: 50%;
            background: var(--gradient-primary);
            animation: rotate 10s linear infinite;
        }

        .user-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            position: relative;
            z-index: 2;
            border: 3px solid white;
            box-shadow: var(--shadow-lg);
        }

        .avatar-status {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: var(--success-green);
            border: 2px solid white;
            border-radius: 50%;
            z-index: 3;
            animation: pulse 2s infinite;
        }

        .welcome-text {
            flex: 1;
        }

        .greeting {
            font-size: 16px;
            color: var(--gray-500);
            font-weight: 500;
            margin-bottom: 4px;
        }

        .doctor-name {
            font-size: 24px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .role-badge {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--primary-blue);
            background: rgba(0, 122, 255, 0.1);
            padding: 4px 8px;
            border-radius: 8px;
            width: fit-content;
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
        }

        .stat-card.earnings::before {
            background: var(--gradient-success);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(0, 122, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            margin-bottom: 16px;
            font-size: 20px;
        }

        .stat-card.earnings .stat-icon {
            background: rgba(48, 209, 88, 0.1);
            color: var(--success-green);
        }

        .stat-value {
            display: block;
            font-size: 20px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .stat-trend {
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 6px;
            width: fit-content;
            background: rgba(48, 209, 88, 0.1);
            color: var(--success-green);
            margin-top: 8px;
        }

        /* Main Content */
        .main-content {
            padding: 0 24px 120px;
        }

        /* Feature Cards */
        .feature-cards {
            margin-bottom: 32px;
        }

        .main-card {
            background: var(--gradient-primary);
            border-radius: 24px;
            padding: 24px;
            margin-bottom: 16px;
            color: white;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            min-height: 200px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .main-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-2xl);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 16px;
        }

        .main-amount {
            font-size: 32px;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .amount-period {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            margin-bottom: 20px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 3px;
            width: 82%;
            transition: width 1.5s ease;
        }

        /* Action Cards */
        .action-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .action-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-100);
            position: relative;
            text-decoration: none;
            color: inherit;
        }

        .action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gradient-primary);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .action-card:hover::before {
            transform: scaleX(1);
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: rgba(0, 122, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            margin-bottom: 16px;
            font-size: 20px;
        }

        .action-card.schedule .action-icon {
            background: rgba(123, 104, 238, 0.1);
            color: var(--accent-purple);
        }

        .action-card.performance .action-icon {
            background: rgba(48, 209, 88, 0.1);
            color: var(--success-green);
        }

        .action-card.profile .action-icon {
            background: rgba(255, 107, 157, 0.1);
            color: var(--accent-pink);
        }

        .action-content h4 {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .action-content p {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
        }

        /* Section Styling */
        .section {
            margin-bottom: 32px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-header h3 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .see-all-btn {
            color: var(--primary-blue);
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .see-all-btn:hover {
            background: rgba(0, 122, 255, 0.1);
        }

        /* Activities List */
        .activities-list {
            background: white;
            border-radius: 20px;
            padding: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            flex-shrink: 0;
            font-size: 16px;
        }

        .activity-icon.green {
            background: var(--gradient-success);
        }

        .activity-icon.blue {
            background: var(--gradient-primary);
        }

        .activity-icon.purple {
            background: var(--gradient-purple);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 2px;
        }

        .activity-content p {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .activity-amount {
            font-size: 14px;
            font-weight: 700;
            color: var(--success-green);
        }

        /* Floating Action Button */
        .floating-action {
            position: fixed;
            bottom: 100px;
            right: 24px;
            z-index: 50;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .fab {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--gradient-primary);
            border: none;
            color: white;
            cursor: pointer;
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            font-size: 24px;
        }

        .fab:hover {
            transform: scale(1.1);
            box-shadow: var(--shadow-2xl);
        }

        .fab-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--gray-600);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 4px 8px;
            border-radius: 8px;
            white-space: nowrap;
        }

        /* Bottom Navigation */
        .bottom-navigation {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-top: 1px solid var(--gray-200);
            padding: 12px 0 calc(12px + env(safe-area-inset-bottom));
            z-index: 100;
        }

        .nav-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 430px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: var(--gray-400);
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            min-width: 64px;
            text-decoration: none;
            font-size: 20px;
        }

        .nav-item:hover {
            color: var(--primary-blue);
            background: rgba(0, 122, 255, 0.05);
        }

        .nav-item.active {
            color: var(--primary-blue);
        }

        .nav-item span {
            font-size: 10px;
            font-weight: 600;
        }

        .nav-indicator {
            position: absolute;
            bottom: -2px;
            left: 50%;
            transform: translateX(-50%);
            width: 4px;
            height: 4px;
            background: var(--primary-blue);
            border-radius: 2px;
        }

        /* Animations */
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 430px) {
            .dashboard-container {
                border-radius: 0;
                margin: 0;
                min-height: 100vh;
            }
            
            .floating-action {
                right: 20px;
                bottom: 90px;
            }
        }

        /* Icons using Unicode */
        .icon-dollar::before { content: '💰'; }
        .icon-clock::before { content: '🕐'; }
        .icon-calendar::before { content: '📅'; }
        .icon-user::before { content: '👤'; }
        .icon-activity::before { content: '📊'; }
        .icon-star::before { content: '⭐'; }
        .icon-award::before { content: '🏆'; }
        .icon-target::before { content: '🎯'; }
        .icon-home::before { content: '🏠'; }
        .icon-history::before { content: '📋'; }
        .icon-location::before { content: '📍'; }
        .icon-plus::before { content: '+'; }
        .icon-bell::before { content: '🔔'; }
        .icon-settings::before { content: '⚙️'; }
        .icon-trend::before { content: '📈'; }
    </style>
</head>
<body>
    <div class="premium-dashboard">
        <!-- Animated Background -->
        <div class="animated-background">
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

        <div class="dashboard-container">
            <!-- Status Bar -->
            <div class="status-bar">
                <div class="status-left">
                    <div class="online-indicator"></div>
                    <span class="status-text">Online</span>
                </div>
                <div class="status-right">
                    <div class="notification-badge">
                        <span class="icon-bell"></span>
                        <span class="badge-count">3</span>
                    </div>
                    <div class="notification-badge">
                        <span class="icon-settings"></span>
                    </div>
                </div>
            </div>

            <!-- Header Section -->
            <div class="dashboard-header">
                <div class="welcome-section">
                    <div class="avatar-container">
                        <div class="avatar-ring"></div>
                        <div class="user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <div class="avatar-status"></div>
                    </div>
                    <div class="welcome-text">
                        <div class="greeting">Good Morning!</div>
                        <div class="doctor-name">{{ auth()->user()->name }}</div>
                        <div class="role-badge">
                            <span class="icon-star"></span>
                            Premium Paramedis
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="quick-stats">
                    <div class="stat-card earnings">
                        <div class="stat-icon icon-trend"></div>
                        <div class="stat-content">
                            <span class="stat-value">Rp {{ number_format(\App\Models\Jaspel::where('user_id', auth()->id())->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->approved()->sum('nominal') / 1000000, 1) }}M</span>
                            <span class="stat-label">This Month</span>
                        </div>
                        <div class="stat-trend">+12%</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon icon-clock"></div>
                        <div class="stat-content">
                            <span class="stat-value">{{ rand(150, 200) }}h</span>
                            <span class="stat-label">Total Hours</span>
                        </div>
                        <div class="stat-trend">82%</div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Feature Cards -->
                <div class="feature-cards">
                    <!-- Main Jaspel Card -->
                    <div class="main-card">
                        <div class="card-header">
                            <div class="card-icon icon-dollar"></div>
                        </div>
                        
                        <div class="card-title">Jaspel Earnings</div>
                        <div class="main-amount">Rp {{ number_format(\App\Models\Jaspel::where('user_id', auth()->id())->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->approved()->sum('nominal') / 1000000, 1) }}M</div>
                        <div class="amount-period">This Month</div>
                        
                        <div class="progress-section">
                            <div class="progress-info">
                                <span>Target: Rp {{ number_format((\App\Models\Jaspel::where('user_id', auth()->id())->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->approved()->sum('nominal') / 1000000) * 1.2, 1) }}M</span>
                                <span>82%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Cards -->
                    <div class="action-cards">
                        <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="action-card">
                            <div class="action-icon icon-clock"></div>
                            <div class="action-content">
                                <h4>Presensi</h4>
                                <p>Check In/Out</p>
                            </div>
                        </a>

                        <a href="{{ route('jaspel.dashboard') }}" class="action-card schedule">
                            <div class="action-icon icon-dollar"></div>
                            <div class="action-content">
                                <h4>Jaspel</h4>
                                <p>View earnings</p>
                            </div>
                        </a>

                        <a href="{{ route('filament.paramedis.resources.tindakan-paramedis.index') }}" class="action-card performance">
                            <div class="action-icon icon-activity"></div>
                            <div class="action-content">
                                <h4>Tindakan</h4>
                                <p>Medical actions</p>
                            </div>
                        </a>

                        <a href="{{ route('filament.paramedis.pages.presensi-mobile-page') }}" class="action-card profile">
                            <div class="action-icon icon-location"></div>
                            <div class="action-content">
                                <h4>Presensi Mobile</h4>
                                <p>GPS Check-in</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="section">
                    <div class="section-header">
                        <h3>Recent Activities</h3>
                        <a href="#" class="see-all-btn">See All</a>
                    </div>
                    
                    <div class="activities-list">
                        <div class="activity-item">
                            <div class="activity-icon green icon-award"></div>
                            <div class="activity-content">
                                <h4>Tindakan Medis Completed</h4>
                                <p>2 hours ago</p>
                            </div>
                            <div class="activity-amount">+Rp 450K</div>
                        </div>

                        <div class="activity-item">
                            <div class="activity-icon blue icon-clock"></div>
                            <div class="activity-content">
                                <h4>Check-in Successful</h4>
                                <p>Today at 07:00</p>
                            </div>
                        </div>

                        <div class="activity-item">
                            <div class="activity-icon purple icon-target"></div>
                            <div class="activity-content">
                                <h4>Monthly Target Achieved</h4>
                                <p>Yesterday</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Floating Action Button -->
            <div class="floating-action">
                <button class="fab" onclick="quickAction()">
                    <span class="icon-plus"></span>
                </button>
                <span class="fab-label">Quick Check-in</span>
            </div>

            <!-- Bottom Navigation -->
            <div class="bottom-navigation">
                <div class="nav-container">
                    <a href="{{ url('/paramedis') }}" class="nav-item active">
                        <span class="icon-home"></span>
                        <span>Home</span>
                        <div class="nav-indicator"></div>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="nav-item">
                        <span class="icon-clock"></span>
                        <span>Presensi</span>
                    </a>
                    
                    <a href="{{ route('jaspel.dashboard') }}" class="nav-item">
                        <span class="icon-dollar"></span>
                        <span>Jaspel</span>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.pages.presensi-mobile-page') }}" class="nav-item">
                        <span class="icon-location"></span>
                        <span>GPS</span>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.resources.tindakan-paramedis.index') }}" class="nav-item">
                        <span class="icon-activity"></span>
                        <span>Tindakan</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function quickAction() {
            // Redirect to attendance page for quick check-in
            window.location.href = '{{ route("filament.paramedis.pages.presensi-mobile-page") }}';
        }
        
        // Touch interactions
        document.addEventListener('DOMContentLoaded', function() {
            const touchElements = document.querySelectorAll('.action-card, .fab, .nav-item, .main-card');
            
            touchElements.forEach(element => {
                element.addEventListener('touchstart', function() {
                    this.style.opacity = '0.8';
                    this.style.transform = 'scale(0.98)';
                });
                
                element.addEventListener('touchend', function() {
                    this.style.opacity = '1';
                    this.style.transform = '';
                });
                
                element.addEventListener('touchcancel', function() {
                    this.style.opacity = '1';
                    this.style.transform = '';
                });
            });

            // Haptic feedback simulation
            const interactiveElements = document.querySelectorAll('.action-card, .fab, .nav-item');
            interactiveElements.forEach(element => {
                element.addEventListener('click', function() {
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }
                });
            });

            // Animate stats on load
            setTimeout(() => {
                const progressFill = document.querySelector('.progress-fill');
                if (progressFill) {
                    progressFill.style.width = '82%';
                }
            }, 500);
        });
    </script>
</body>
</html>