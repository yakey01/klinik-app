<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Premium Jaspel - {{ auth()->user()->name }}</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#007AFF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="light-content">
    <meta name="apple-mobile-web-app-title" content="Premium Jaspel">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" as="style">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* Premium Jaspel Dashboard - Pure CSS Implementation */
        :root {
            --primary-blue: #007AFF;
            --primary-blue-light: #3D9DFF;
            --primary-blue-dark: #0056CC;
            --accent-purple: #7B68EE;
            --accent-pink: #FF6B9D;
            --success-green: #30D158;
            --warning-orange: #FF9F0A;
            --error-red: #FF453A;
            --gold: #FFD700;
            --emerald: #50C878;
            --rose: #FF69B4;
            
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
            --gradient-gold: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
            --gradient-emerald: linear-gradient(135deg, #50C878 0%, #32CD32 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            touch-action: manipulation;
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

        .premium-jaspel-dashboard {
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

        /* Header Section */
        .dashboard-header {
            padding: 60px 24px 32px;
            background: linear-gradient(135deg, 
                rgba(0, 122, 255, 0.05) 0%, 
                rgba(123, 104, 238, 0.03) 50%, 
                rgba(255, 107, 157, 0.02) 100%
            );
            position: relative;
            overflow: hidden;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .back-button {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--gray-700);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-md);
        }

        .search-button {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--gray-700);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .search-button:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-md);
        }

        .page-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            font-size: 16px;
            color: var(--gray-500);
            font-weight: 500;
        }

        /* Main Content */
        .main-content {
            padding: 0 24px 120px;
        }

        /* Donut Chart Section */
        .chart-section {
            margin-bottom: 32px;
        }

        .chart-container {
            background: white;
            border-radius: 24px;
            padding: 32px 24px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .donut-chart {
            position: relative;
            width: 240px;
            height: 240px;
            margin: 0 auto 24px;
        }

        .donut-svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .donut-segment {
            fill: none;
            stroke-width: 20;
            transition: all 0.3s ease;
        }

        .donut-segment.primary {
            stroke: #007AFF;
            animation: drawPrimary 2s ease-in-out;
        }

        .donut-segment.success {
            stroke: #30D158;
            animation: drawSuccess 2s ease-in-out 0.5s both;
        }

        .donut-segment.warning {
            stroke: #FF9F0A;
            animation: drawWarning 2s ease-in-out 1s both;
        }

        @keyframes drawPrimary {
            0% { stroke-dasharray: 0 283; }
            100% { stroke-dasharray: 85 283; }
        }

        @keyframes drawSuccess {
            0% { stroke-dasharray: 0 283; }
            100% { stroke-dasharray: 90 283; }
        }

        @keyframes drawWarning {
            0% { stroke-dasharray: 0 283; }
            100% { stroke-dasharray: 108 283; }
        }

        .chart-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }

        .balance-label {
            font-size: 14px;
            color: var(--gray-500);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .balance-amount {
            font-size: 24px;
            font-weight: 800;
            color: var(--gray-900);
            margin-bottom: 4px;
            line-height: 1.1;
        }

        .balance-subtitle {
            font-size: 12px;
            color: var(--gray-400);
            font-weight: 500;
        }

        /* Legend */
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-color.primary {
            background: #007AFF;
        }

        .legend-color.success {
            background: #30D158;
        }

        .legend-color.warning {
            background: #FF9F0A;
        }

        .legend-text {
            font-size: 12px;
            color: var(--gray-600);
            font-weight: 500;
        }

        .legend-value {
            font-size: 12px;
            color: var(--gray-900);
            font-weight: 700;
        }

        /* Metric Cards */
        .metric-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 32px;
        }

        .metric-card {
            background: white;
            border-radius: 20px;
            padding: 20px 16px;
            box-shadow: var(--shadow-md);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
        }

        .metric-card.earning::before {
            background: var(--gradient-success);
        }

        .metric-card.spend::before {
            background: var(--gradient-warning);
        }

        .metric-card.available::before {
            background: var(--gradient-primary);
        }

        .metric-label {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .metric-value {
            font-size: 16px;
            font-weight: 800;
            color: var(--gray-900);
            line-height: 1.1;
        }

        .metric-trend {
            font-size: 10px;
            font-weight: 600;
            margin-top: 4px;
            padding: 2px 6px;
            border-radius: 6px;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .metric-trend.positive {
            background: rgba(48, 209, 88, 0.1);
            color: var(--success-green);
        }

        .metric-trend.negative {
            background: rgba(255, 69, 58, 0.1);
            color: var(--error-red);
        }

        /* Bill Tracking Section */
        .bill-section {
            margin-bottom: 32px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--gray-900);
        }

        .tab-controls {
            display: flex;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 4px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }

        .tab-button {
            flex: 1;
            padding: 8px 12px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            color: var(--gray-500);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .tab-button.active {
            background: white;
            color: var(--primary-blue);
            box-shadow: var(--shadow-sm);
        }

        /* Bill Items */
        .bill-items {
            background: white;
            border-radius: 20px;
            padding: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--gray-100);
        }

        .bill-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .bill-item:last-child {
            border-bottom: none;
        }

        .bill-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .bill-icon.jaspel {
            background: var(--gradient-success);
        }

        .bill-icon.tindakan {
            background: var(--gradient-primary);
        }

        .bill-icon.bonus {
            background: var(--gradient-gold);
        }

        .bill-content {
            flex: 1;
        }

        .bill-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
        }

        .bill-subtitle {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
        }

        .bill-amount {
            font-size: 14px;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 8px;
        }

        .pay-button {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            background: var(--gradient-primary);
            color: white;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pay-button:hover {
            transform: scale(1.05);
            box-shadow: var(--shadow-md);
        }

        .pay-button.paid {
            background: var(--gradient-success);
            pointer-events: none;
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

        /* Responsive Design */
        @media (max-width: 430px) {
            .dashboard-container {
                border-radius: 0;
                margin: 0;
                min-height: 100vh;
            }
            
            .metric-cards {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .metric-card {
                padding: 16px;
            }
        }

        /* Premium SVG Icons */
        .nav-item svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.5;
            stroke-linecap: round;
            stroke-linejoin: round;
            transition: all 0.3s ease;
        }

        .nav-item:hover svg {
            stroke-width: 2;
            transform: scale(1.1);
        }

        .nav-item.active svg {
            stroke-width: 2.5;
        }

        /* Other Icons using Unicode */
        .icon-arrow-left::before { content: '←'; }
        .icon-search::before { content: '🔍'; }
        .icon-dollar::before { content: '💰'; }
        .icon-activity::before { content: '📊'; }
        .icon-star::before { content: '⭐'; }
        .icon-trend-up::before { content: '📈'; }
        .icon-trend-down::before { content: '📉'; }
        .icon-check::before { content: '✅'; }
    </style>
</head>
<body>
    <div class="premium-jaspel-dashboard">
        <!-- Animated Background -->
        <div class="animated-background">
            <div class="floating-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>

        <div class="dashboard-container">
            <!-- Header Section -->
            <div class="dashboard-header">
                <div class="header-top">
                    <button class="back-button" onclick="goBack()">
                        <span class="icon-arrow-left"></span>
                    </button>
                    <button class="search-button">
                        <span class="icon-search"></span>
                    </button>
                </div>
                
                <div class="page-info">
                    <h1 class="page-title">Jaspel Dashboard</h1>
                    <p class="page-subtitle">Service Fee Overview</p>
                </div>
            </div>

            <!-- Main Content -->
            <div class="main-content">
                <!-- Donut Chart Section -->
                <div class="chart-section">
                    <div class="chart-container">
                        <div class="donut-chart">
                            <svg class="donut-svg" viewBox="0 0 100 100">
                                <circle class="donut-segment primary" cx="50" cy="50" r="45" 
                                        stroke-dasharray="85 283" stroke-dashoffset="0"></circle>
                                <circle class="donut-segment success" cx="50" cy="50" r="45" 
                                        stroke-dasharray="90 283" stroke-dashoffset="-85"></circle>
                                <circle class="donut-segment warning" cx="50" cy="50" r="45" 
                                        stroke-dasharray="108 283" stroke-dashoffset="-175"></circle>
                            </svg>
                            
                            <div class="chart-center">
                                <div class="balance-label">Total Jaspel</div>
                                <div class="balance-amount">Rp {{ number_format(\App\Models\Jaspel::where('user_id', auth()->id())->approved()->sum('nominal') / 1000000, 1) }}M</div>
                                <div class="balance-subtitle">Total Received</div>
                            </div>
                        </div>
                        
                        <div class="chart-legend">
                            <div class="legend-item">
                                <div class="legend-color primary"></div>
                                <span class="legend-text">Jaspel:</span>
                                <span class="legend-value">30%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color success"></div>
                                <span class="legend-text">Tindakan:</span>
                                <span class="legend-value">32%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color warning"></div>
                                <span class="legend-text">Bonus:</span>
                                <span class="legend-value">38%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Metric Cards -->
                <div class="metric-cards">
                    @php
                        $userId = auth()->id();
                        $userName = auth()->user()->name;
                        $currentMonth = now()->format('Y-m');
                        $lastMonth = now()->subMonth()->format('Y-m');
                        
                        // Debug logging
                        \Log::info('Jaspel Dashboard Debug', [
                            'user_id' => $userId,
                            'user_name' => $userName,
                            'current_month' => $currentMonth,
                            'last_month' => $lastMonth
                        ]);
                        
                        // This month jaspel
                        $thisMonthJaspel = \App\Models\Jaspel::where('user_id', $userId)
                            ->approved()
                            ->whereYear('tanggal', now()->year)
                            ->whereMonth('tanggal', now()->month)
                            ->sum('nominal');
                            
                        // Last month jaspel for comparison
                        $lastMonthJaspel = \App\Models\Jaspel::where('user_id', $userId)
                            ->approved()
                            ->whereYear('tanggal', now()->subMonth()->year)
                            ->whereMonth('tanggal', now()->subMonth()->month)
                            ->sum('nominal');
                            
                        // Total all time
                        $totalJaspel = \App\Models\Jaspel::where('user_id', $userId)
                            ->approved()
                            ->sum('nominal');
                            
                        // Pending jaspel
                        $pendingJaspel = \App\Models\Jaspel::where('user_id', $userId)
                            ->pending()
                            ->sum('nominal');
                            
                        // Calculate percentage change
                        $monthlyChange = $lastMonthJaspel > 0 
                            ? round((($thisMonthJaspel - $lastMonthJaspel) / $lastMonthJaspel) * 100, 1)
                            : 0;
                            
                        // Debug logging for values
                        \Log::info('Jaspel Dashboard Values', [
                            'this_month_jaspel' => $thisMonthJaspel,
                            'last_month_jaspel' => $lastMonthJaspel,
                            'total_jaspel' => $totalJaspel,
                            'pending_jaspel' => $pendingJaspel,
                            'monthly_change' => $monthlyChange
                        ]);
                    @endphp
                    
                    <div class="metric-card earning">
                        <div class="metric-label">This Month</div>
                        <div class="metric-value">{{ number_format($thisMonthJaspel / 1000000, 1) }}M</div>
                        <div class="metric-trend {{ $monthlyChange >= 0 ? 'positive' : 'negative' }}">
                            {{ $monthlyChange >= 0 ? '+' : '' }}{{ $monthlyChange }}%
                        </div>
                    </div>
                    
                    <div class="metric-card spend">
                        <div class="metric-label">Pending</div>
                        <div class="metric-value">{{ number_format($pendingJaspel / 1000000, 1) }}M</div>
                        <div class="metric-trend {{ $pendingJaspel > 0 ? 'positive' : 'negative' }}">
                            {{ \App\Models\Jaspel::where('user_id', $userId)->pending()->count() }} items
                        </div>
                    </div>
                    
                    <div class="metric-card available">
                        <div class="metric-label">Total All</div>
                        <div class="metric-value">{{ number_format($totalJaspel / 1000000, 1) }}M</div>
                        <div class="metric-trend positive">
                            {{ \App\Models\Jaspel::where('user_id', $userId)->approved()->count() }} paid
                        </div>
                    </div>
                </div>

                <!-- Bill Tracking Section -->
                <div class="bill-section">
                    <div class="section-header">
                        <h3 class="section-title">Payment History</h3>
                    </div>
                    
                    <div class="tab-controls">
                        <button class="tab-button active" onclick="switchTab('all')">All</button>
                        <button class="tab-button" onclick="switchTab('upcoming')">Upcoming</button>
                        <button class="tab-button" onclick="switchTab('previous')">Previous</button>
                    </div>
                    
                    <div class="bill-items">
                        @php
                            $recentJaspel = \App\Models\Jaspel::where('user_id', auth()->id())
                                ->with(['tindakan'])
                                ->orderBy('tanggal', 'desc')
                                ->take(5)
                                ->get();
                        @endphp
                        
                        @forelse($recentJaspel as $jaspel)
                            <div class="bill-item">
                                <div class="bill-icon {{ $jaspel->status_validasi === 'disetujui' ? 'jaspel' : ($jaspel->status_validasi === 'pending' ? 'tindakan' : 'bonus') }}">
                                    @if($jaspel->status_validasi === 'disetujui')
                                        <span class="icon-check"></span>
                                    @elseif($jaspel->status_validasi === 'pending')
                                        <span class="icon-clock"></span>
                                    @else
                                        <span class="icon-star"></span>
                                    @endif
                                </div>
                                <div class="bill-content">
                                    <div class="bill-title">{{ $jaspel->jenis_jaspel ?? 'Jaspel Medis' }}</div>
                                    <div class="bill-subtitle">{{ $jaspel->tanggal ? $jaspel->tanggal->format('d M Y') : 'Unknown date' }}</div>
                                </div>
                                <div>
                                    <div class="bill-amount">Rp {{ number_format($jaspel->nominal / 1000000, 1) }}M</div>
                                    @if($jaspel->status_validasi === 'disetujui')
                                        <button class="pay-button paid">
                                            <span class="icon-check"></span> Paid
                                        </button>
                                    @elseif($jaspel->status_validasi === 'pending')
                                        <button class="pay-button">Pending</button>
                                    @else
                                        <button class="pay-button">Rejected</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="bill-item">
                                <div class="bill-icon tindakan">
                                    <span class="icon-activity"></span>
                                </div>
                                <div class="bill-content">
                                    <div class="bill-title">No Jaspel Records</div>
                                    <div class="bill-subtitle">No data available</div>
                                </div>
                                <div>
                                    <div class="bill-amount">Rp 0</div>
                                    <button class="pay-button">-</button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Bottom Navigation -->
            <div class="bottom-navigation">
                <div class="nav-container">
                    <a href="{{ url('/paramedis') }}" class="nav-item">
                        <svg viewBox="0 0 24 24">
                            <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9,22 9,12 15,12 15,22"/>
                        </svg>
                        <span>Home</span>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.resources.attendances.index') }}" class="nav-item">
                        <svg viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12,6 12,12 16,14"/>
                        </svg>
                        <span>Presensi</span>
                    </a>
                    
                    <a href="{{ route('jaspel.dashboard') }}" class="nav-item active">
                        <svg viewBox="0 0 24 24">
                            <line x1="12" y1="1" x2="12" y2="23"/>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                        <span>Jaspel</span>
                        <div class="nav-indicator"></div>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.pages.presensi-mobile-page') }}" class="nav-item">
                        <svg viewBox="0 0 24 24">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                        <span>GPS</span>
                    </a>
                    
                    <a href="{{ route('filament.paramedis.resources.tindakan-paramedis.index') }}" class="nav-item">
                        <svg viewBox="0 0 24 24">
                            <polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/>
                        </svg>
                        <span>Tindakan</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function goBack() {
            window.location.href = '{{ url("/paramedis") }}';
        }
        
        function switchTab(tabName) {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-button').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Add active class to clicked tab
            event.target.classList.add('active');
            
            // Here you would filter the bill items based on the tab
            console.log('Switched to tab:', tabName);
        }
        
        // Touch interactions
        document.addEventListener('DOMContentLoaded', function() {
            const touchElements = document.querySelectorAll('.metric-card, .bill-item, .nav-item, .back-button, .search-button, .pay-button');
            
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
            const interactiveElements = document.querySelectorAll('.metric-card, .pay-button, .nav-item, .tab-button');
            interactiveElements.forEach(element => {
                element.addEventListener('click', function() {
                    if (navigator.vibrate) {
                        navigator.vibrate(10);
                    }
                });
            });

            // Handle pay button clicks
            document.querySelectorAll('.pay-button:not(.paid)').forEach(button => {
                button.addEventListener('click', function() {
                    this.innerHTML = '<span class="icon-check"></span> Paid';
                    this.classList.add('paid');
                });
            });
        });
    </script>
</body>
</html>