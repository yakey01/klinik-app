<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Klinik Dokterku - {{ auth()->user()->name ?? 'Dokter' }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        :root {
            --primary-blue: #1e40af;
            --secondary-blue: #3b82f6;
            --accent-green: #10b981;
            --warning-orange: #f59e0b;
            --success-green: #10b981;
            --error-red: #ef4444;
            --neutral-50: #f8fafc;
            --neutral-100: #f1f5f9;
            --neutral-200: #e2e8f0;
            --neutral-300: #cbd5e1;
            --neutral-600: #475569;
            --neutral-900: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 35%, var(--accent-green) 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
            animation: backgroundFlow 20s ease-in-out infinite;
            font-feature-settings: 'kern' 1, 'liga' 1;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        @keyframes backgroundFlow {
            0%, 100% { background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 35%, var(--accent-green) 100%); }
            25% { background: linear-gradient(135deg, var(--secondary-blue) 0%, var(--primary-blue) 35%, var(--success-green) 100%); }
            50% { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 35%, var(--accent-green) 100%); }
            75% { background: linear-gradient(135deg, var(--primary-blue) 0%, #2563eb 35%, #059669 100%); }
        }
        
        .device-container {
            width: 375px;
            height: 812px;
            background: linear-gradient(145deg, #0f172a, #1e293b, #334155);
            border-radius: 40px;
            padding: 4px;
            box-shadow: 
                0 50px 150px rgba(0,0,0,0.4),
                0 0 0 1px rgba(16, 185, 129, 0.3),
                inset 0 1px 0 rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }
        
        .device-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: conic-gradient(from 0deg, var(--secondary-blue), var(--accent-green), var(--secondary-blue));
            border-radius: 42px;
            z-index: -1;
            animation: borderRotate 8s linear infinite;
            opacity: 0.6;
        }
        
        @keyframes borderRotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .screen {
            width: 100%;
            height: 100%;
            background: #ffffff;
            border-radius: 36px;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        
        .status-bar {
            height: 44px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            position: relative;
        }
        
        .app-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--secondary-blue) 70%, var(--accent-green) 100%);
            padding: 20px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .app-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.2) 0%, transparent 70%);
            animation: headerGlow 15s ease-in-out infinite;
        }
        
        @keyframes headerGlow {
            0%, 100% { transform: translate(-25%, -25%) scale(1); opacity: 0.3; }
            50% { transform: translate(-75%, -75%) scale(1.2); opacity: 0.1; }
        }
        
        .header-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .back-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .back-btn:hover {
            background: rgba(255,255,255,0.35);
            transform: scale(1.05);
        }
        
        .header-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .main-content {
            flex: 1;
            background: linear-gradient(to bottom, var(--neutral-50), var(--neutral-100));
            overflow-y: auto;
            position: relative;
        }
        
        .page {
            display: none;
            padding: 24px 20px;
            min-height: 100%;
        }
        
        .page.active {
            display: block;
        }
        
        .welcome-card {
            background: white;
            border-radius: 24px;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            border: 1px solid var(--neutral-200);
            text-align: center;
        }
        
        .welcome-avatar {
            width: 80px;
            height: 80px;
            border-radius: 40px;
            background: linear-gradient(135deg, var(--accent-green), var(--success-green));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 32px;
            margin: 0 auto 20px;
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.4);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.06);
            border: 1px solid var(--neutral-200);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.12);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--neutral-900);
            margin-bottom: 16px;
        }
        
        .bottom-nav {
            background: linear-gradient(135deg, #ffffff 0%, var(--neutral-50) 100%);
            border-top: 1px solid var(--neutral-200);
            padding: 16px 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 -8px 32px rgba(0,0,0,0.06);
        }
        
        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            min-width: 44px;
            min-height: 44px;
            position: relative;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, var(--secondary-blue), var(--accent-green));
            color: white;
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(16, 185, 129, 0.3);
        }
        
        .nav-item:not(.active) {
            color: #64748b;
        }
        
        .nav-item:not(.active):hover {
            background: rgba(16, 185, 129, 0.08);
            color: var(--accent-green);
            transform: translateY(-2px);
        }
        
        .nav-icon {
            font-size: 22px;
            transition: all 0.3s ease;
        }
        
        .nav-item.active .nav-icon {
            transform: scale(1.1);
        }
        
        .nav-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        
        .toast {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, var(--success-green), #059669);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 8px 30px rgba(16, 185, 129, 0.3);
            animation: toastSlide 0.3s ease;
            opacity: 0;
            pointer-events: none;
        }
        
        .toast.show {
            opacity: 1;
            pointer-events: auto;
        }
        
        .toast.error {
            background: linear-gradient(135deg, var(--error-red), #dc2626);
            box-shadow: 0 8px 30px rgba(239, 68, 68, 0.3);
        }
        
        .toast.warning {
            background: linear-gradient(135deg, var(--warning-orange), #f59e0b);
            box-shadow: 0 8px 30px rgba(245, 158, 11, 0.3);
        }
        
        .toast.info {
            background: linear-gradient(135deg, var(--secondary-blue), #2563eb);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.3);
        }
        
        @keyframes toastSlide {
            from { transform: translateX(-50%) translateY(-20px); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        
        .floating-action {
            position: absolute;
            bottom: 100px;
            right: 24px;
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--accent-green), var(--success-green));
            border-radius: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 700;
            box-shadow: 0 16px 60px rgba(16, 185, 129, 0.4);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
            border: 3px solid white;
        }
        
        .floating-action:hover {
            transform: scale(1.15) rotate(90deg);
            box-shadow: 0 24px 80px rgba(16, 185, 129, 0.6);
        }
    </style>
</head>
<body>
    <div class="device-container">
        <div class="screen">
            <div class="status-bar">
                <div id="currentTimeStatus">9:41</div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <div id="connectionStatus" style="display: flex; align-items: center; gap: 4px; font-size: 12px; cursor: pointer;" onclick="retryConnection()">
                        <div id="connectionDot" style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></div>
                        <span id="connectionText">LIVE</span>
                    </div>
                    <span>●●●●○</span>
                    <span>100%</span>
                </div>
            </div>
            
            <div class="app-header">
                <div class="header-content">
                    <button class="back-btn" onclick="showPage('home')" style="opacity: 0; pointer-events: none;">←</button>
                    <h1 class="header-title" id="headerTitle">DOKTER MOBILE</h1>
                    <div class="header-actions">
                        <button class="back-btn">🔔</button>
                        <button class="back-btn">⚙️</button>
                    </div>
                </div>
            </div>
            
            <div class="main-content">
                <!-- HOME PAGE -->
                <div id="homePage" class="page active">
                    <div class="welcome-card">
                        <div class="welcome-avatar" id="userAvatar">DR</div>
                        <h2 style="font-size: 24px; font-weight: 800; color: var(--neutral-900); margin-bottom: 8px;">Selamat Datang, Dokter!</h2>
                        <p style="color: var(--neutral-600); font-size: 16px;" id="userInfo">{{ auth()->user()->name ?? 'Dr. Dokter' }} - Dokter Umum</p>
                        <div style="margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 8px;" id="shiftStatus">
                            <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--success-green);"></div>
                            <span style="font-size: 14px; color: var(--success-green); font-weight: 600;">Shift Aktif</span>
                        </div>
                    </div>
                    
                    <!-- Real-time Attendance Status -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--neutral-900); margin-bottom: 12px;">Status Kehadiran Hari Ini</h3>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; border-radius: 12px; background: linear-gradient(135deg, var(--success-green), #059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 18px;" id="attendanceStatusIcon">✓</div>
                            <div style="flex: 1;">
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);" id="attendanceStatusText">Sudah Check-in</div>
                                <div style="font-size: 12px; color: var(--neutral-600);" id="attendanceStatusTime">08:00 - Tepat waktu</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 12px; color: var(--neutral-600);">Jam Kerja</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);" id="workingHours">7.5 jam</div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Schedule Overview -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--neutral-900); margin-bottom: 12px;">Jadwal Hari Ini</h3>
                        <div id="todaySchedule">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--neutral-200);">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--secondary-blue); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">08</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Shift Pagi</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">08:00 - 16:00 | Poliklinik Umum</div>
                                </div>
                                <div style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Aktif</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats" id="statsContainer">
                        <div class="stat-card">
                            <div class="stat-number" id="attendanceRate">95%</div>
                            <div class="stat-label">Tingkat Kehadiran</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="schedulesWeek">5</div>
                            <div class="stat-label">Jadwal Minggu Ini</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="jaspelMonth">25M</div>
                            <div class="stat-label">Jaspel Bulan Ini</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="lateArrivals">2</div>
                            <div class="stat-label">Keterlambatan Bulan Ini</div>
                        </div>
                    </div>

                    <!-- Schedule Notifications -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 style="font-size: 16px; font-weight: 700; color: var(--neutral-900); margin-bottom: 12px;">Notifikasi Jadwal</h3>
                        <div id="scheduleNotifications">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 0;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--warning-orange); display: flex; align-items: center; justify-content: center; color: white; font-size: 14px;">!</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Reminder: Shift Besok</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Shift Sore - 14:00-22:00 | Poliklinik Umum</div>
                                </div>
                                <div style="font-size: 11px; color: var(--neutral-500);">1 jam lagi</div>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: white; border-radius: 20px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 style="font-size: 18px; font-weight: 700; color: var(--neutral-900); margin-bottom: 16px;">Aksi Cepat</h3>
                        <div style="display: grid; gap: 12px;" id="quickActionsContainer">
                            <div class="quick-action-item" onclick="showPage('presensi')" style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--neutral-50); border-radius: 16px; cursor: pointer; transition: all 0.3s ease;">
                                <div style="width: 48px; height: 48px; border-radius: 16px; background: linear-gradient(135deg, var(--secondary-blue), var(--accent-green)); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">📋</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900); margin-bottom: 4px;">Presensi</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Kelola kehadiran dan absensi</div>
                                </div>
                                <div style="color: var(--neutral-300); font-size: 18px;">→</div>
                            </div>
                            <div class="quick-action-item" onclick="showPage('jadwal')" style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--neutral-50); border-radius: 16px; cursor: pointer; transition: all 0.3s ease;">
                                <div style="width: 48px; height: 48px; border-radius: 16px; background: linear-gradient(135deg, var(--secondary-blue), var(--accent-green)); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">📅</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900); margin-bottom: 4px;">Jadwal Jaga</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Kelola jadwal dan shift kerja</div>
                                </div>
                                <div style="color: var(--neutral-300); font-size: 18px;">→</div>
                            </div>
                            <div class="quick-action-item" onclick="showPage('laporan')" style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--neutral-50); border-radius: 16px; cursor: pointer; transition: all 0.3s ease;">
                                <div style="width: 48px; height: 48px; border-radius: 16px; background: linear-gradient(135deg, var(--secondary-blue), var(--accent-green)); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">📊</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900); margin-bottom: 4px;">Laporan Kehadiran</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Lihat laporan dan statistik kehadiran</div>
                                </div>
                                <div style="color: var(--neutral-300); font-size: 18px;">→</div>
                            </div>
                            <div class="quick-action-item" onclick="showPage('jaspel')" style="display: flex; align-items: center; gap: 16px; padding: 16px; background: var(--neutral-50); border-radius: 16px; cursor: pointer; transition: all 0.3s ease;">
                                <div style="width: 48px; height: 48px; border-radius: 16px; background: linear-gradient(135deg, var(--secondary-blue), var(--accent-green)); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">💰</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900); margin-bottom: 4px;">Jaspel</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Lihat jasa pelayanan dan penghasilan</div>
                                </div>
                                <div style="color: var(--neutral-300); font-size: 18px;">→</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- PRESENSI PAGE -->
                <div id="presensiPage" class="page">
                    <!-- Current Status -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Status Presensi Hari Ini</h3>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 80px; height: 80px; margin: 0 auto 16px; border-radius: 50%; background: linear-gradient(135deg, var(--success-green), var(--accent-green)); display: flex; align-items: center; justify-content: center;">
                                <div style="color: white; font-size: 24px;">✓</div>
                            </div>
                            <div style="font-size: 18px; font-weight: 600; color: var(--success-green); margin-bottom: 8px;">Sudah Check-in</div>
                            <div style="font-size: 14px; color: var(--neutral-600);">Masuk: 08:00 WIB</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div style="text-align: center; padding: 16px; background: var(--neutral-50); border-radius: 12px;">
                                <div style="font-size: 20px; font-weight: 700; color: var(--secondary-blue);">8h 15m</div>
                                <div style="font-size: 12px; color: var(--neutral-600); font-weight: 500;">Jam Kerja Hari Ini</div>
                            </div>
                            <div style="text-align: center; padding: 16px; background: var(--neutral-50); border-radius: 12px;">
                                <div style="font-size: 20px; font-weight: 700; color: var(--success-green);">Normal</div>
                                <div style="font-size: 12px; color: var(--neutral-600); font-weight: 500;">Status Kehadiran</div>
                            </div>
                        </div>
                    </div>

                    <!-- GPS Location Status -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Status Lokasi GPS</h3>
                        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--success-green); display: flex; align-items: center; justify-content: center;">
                                <div style="color: white; font-size: 20px;">📍</div>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900);">Lokasi Terdeteksi</div>
                                <div style="font-size: 14px; color: var(--neutral-600);">Klinik Dokterku - Area Valid</div>
                            </div>
                            <div style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">VALID</div>
                        </div>
                        
                        <div style="display: grid; gap: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Akurasi GPS</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--success-green);">±5 meter</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Jarak dari Klinik</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--success-green);">12 meter</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Zona Presensi</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--success-green);">Dalam Area</div>
                            </div>
                        </div>
                    </div>

                    <!-- Check-in/Check-out Buttons -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Presensi</h3>
                        <div style="display: grid; gap: 12px;">
                            <button onclick="performCheckIn()" style="background: linear-gradient(135deg, var(--success-green), var(--accent-green)); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; opacity: 0.5;" disabled>
                                ✓ Check-in Selesai (08:00 WIB)
                            </button>
                            <button onclick="performCheckOut()" style="background: linear-gradient(135deg, var(--error-red), #dc2626); color: white; border: none; padding: 16px; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer;">
                                Check-out Sekarang
                            </button>
                        </div>
                    </div>

                    <!-- Today's Schedule -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Jadwal Hari Ini</h3>
                        <div style="display: grid; gap: 12px;">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--secondary-blue); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">08</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Shift Pagi</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">08:00 - 16:00 | Poliklinik Umum</div>
                                </div>
                                <div style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">AKTIF</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--neutral-300); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">20</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Shift Malam</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">20:00 - 08:00 | Gawat Darurat</div>
                                </div>
                                <div style="background: #f3f4f6; color: #374151; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">NANTI</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Attendance History -->
                    <div style="background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Riwayat Presensi Terkini</h3>
                        <div style="display: grid; gap: 8px;">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--success-green); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">15</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Hari Ini</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">08:00 - ... | Sedang Berlangsung</div>
                                </div>
                                <div style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">AKTIF</div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--success-green); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">14</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Kemarin</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">08:00 - 16:00 | Tepat waktu</div>
                                </div>
                                <div style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">SELESAI</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- JADWAL JAGA PAGE -->
                <div id="jadwalPage" class="page">
                    <!-- Monthly Calendar View -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Jadwal Jaga Bulan Ini</h3>
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 16px;">
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">MIN</div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">SEN</div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">SEL</div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">RAB</div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">KAM</div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">JUM</div>
                            <div style="text-align: center; font-size: 12px; font-weight: 600; color: var(--neutral-600); padding: 8px;">SAB</div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px;" id="calendarDays">
                            <!-- Calendar days will be populated by JavaScript -->
                        </div>
                    </div>

                    <!-- Today's Schedule Details -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Jadwal Hari Ini</h3>
                        <div id="todayScheduleDetails">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--neutral-50); border-radius: 12px; margin-bottom: 12px;">
                                <div style="width: 48px; height: 48px; border-radius: 12px; background: linear-gradient(135deg, var(--secondary-blue), var(--accent-green)); display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; font-weight: 600;">P</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 16px; font-weight: 600; color: var(--neutral-900);">Shift Pagi</div>
                                    <div style="font-size: 14px; color: var(--neutral-600);">08:00 - 16:00</div>
                                    <div style="font-size: 12px; color: var(--neutral-500);">Poliklinik Umum</div>
                                </div>
                                <div style="background: #dcfce7; color: #166534; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">Aktif</div>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule Actions -->
                    <div style="background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Aksi Jadwal</h3>
                        <div style="display: grid; gap: 12px;">
                            <button onclick="requestScheduleChange()" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--neutral-50); border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--warning-orange); display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">🔄</div>
                                <div style="flex: 1; text-align: left;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Permintaan Ganti Jadwal</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Ajukan perubahan jadwal shift</div>
                                </div>
                            </button>
                            <button onclick="requestShiftSwap()" style="display: flex; align-items: center; gap: 12px; padding: 16px; background: var(--neutral-50); border: none; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: var(--secondary-blue); display: flex; align-items: center; justify-content: center; color: white; font-size: 16px;">↔️</div>
                                <div style="flex: 1; text-align: left;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Tukar Shift</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Tukar jadwal dengan dokter lain</div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- LAPORAN KEHADIRAN PAGE -->
                <div id="laporanPage" class="page">
                    <!-- Monthly Attendance Summary -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Ringkasan Kehadiran Bulan Ini</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                            <div style="text-align: center; padding: 16px; background: var(--neutral-50); border-radius: 12px;">
                                <div style="font-size: 28px; font-weight: 900; color: var(--success-green);">22</div>
                                <div style="font-size: 12px; color: var(--neutral-600); font-weight: 600;">Hari Hadir</div>
                            </div>
                            <div style="text-align: center; padding: 16px; background: var(--neutral-50); border-radius: 12px;">
                                <div style="font-size: 28px; font-weight: 900; color: var(--error-red);">2</div>
                                <div style="font-size: 12px; color: var(--neutral-600); font-weight: 600;">Keterlambatan</div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <div style="font-size: 14px; color: var(--neutral-600);">Tingkat Kehadiran</div>
                            <div style="font-size: 16px; font-weight: 700; color: var(--success-green);">95%</div>
                        </div>
                        <div style="width: 100%; height: 8px; background: var(--neutral-200); border-radius: 4px;">
                            <div style="width: 95%; height: 100%; background: linear-gradient(90deg, var(--success-green), var(--accent-green)); border-radius: 4px;"></div>
                        </div>
                    </div>

                    <!-- Attendance Statistics -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Statistik Kehadiran</h3>
                        <div style="display: grid; gap: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Total Jam Kerja</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">176 jam</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Rata-rata Jam per Hari</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">8.0 jam</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Jam Lembur</div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--warning-orange);">12 jam</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Attendance -->
                    <div style="background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Kehadiran Terakhir</h3>
                        <div style="display: grid; gap: 8px;" id="recentAttendance">
                            <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--success-green); display: flex; align-items: center; justify-content: center; color: white; font-size: 12px; font-weight: 600;">15</div>
                                <div style="flex: 1;">
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Hari Ini</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">08:00 - 16:00 | Tepat waktu</div>
                                </div>
                                <div style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Hadir</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- JASPEL PAGE -->
                <div id="jaspelPage" class="page">
                    <!-- Monthly Jaspel Summary -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Jaspel Bulan Ini</h3>
                        <div style="text-align: center; margin-bottom: 20px;">
                            <div style="font-size: 32px; font-weight: 900; color: var(--accent-green); margin-bottom: 8px;">Rp 25.800.000</div>
                            <div style="font-size: 14px; color: var(--neutral-600);">Total Jaspel Januari 2025</div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div style="text-align: center; padding: 16px; background: var(--neutral-50); border-radius: 12px;">
                                <div style="font-size: 20px; font-weight: 700; color: var(--secondary-blue);">Rp 22.5M</div>
                                <div style="font-size: 12px; color: var(--neutral-600); font-weight: 500;">Jaspel Pokok</div>
                            </div>
                            <div style="text-align: center; padding: 16px; background: var(--neutral-50); border-radius: 12px;">
                                <div style="font-size: 20px; font-weight: 700; color: var(--warning-orange);">Rp 3.3M</div>
                                <div style="font-size: 12px; color: var(--neutral-600); font-weight: 500;">Jaspel Tambahan</div>
                            </div>
                        </div>
                    </div>

                    <!-- Jaspel Breakdown -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Rincian Jaspel</h3>
                        <div style="display: grid; gap: 12px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Jaspel Poliklinik</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">22 hari kerja</div>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Rp 18.5M</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Jaspel Rawat Inap</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">15 hari jaga</div>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Rp 4.0M</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Jaspel Gawat Darurat</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">8 shift malam</div>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Rp 2.4M</div>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Insentif Kinerja</div>
                                    <div style="font-size: 12px; color: var(--neutral-600);">Target tercapai 105%</div>
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: var(--warning-orange);">Rp 900K</div>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Metrics -->
                    <div style="background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Metrik Kinerja</h3>
                        <div style="display: grid; gap: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Tingkat Kehadiran</div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--success-green);">95%</div>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--neutral-200); border-radius: 4px; margin-bottom: 8px;">
                                <div style="width: 95%; height: 100%; background: linear-gradient(90deg, var(--success-green), var(--accent-green)); border-radius: 4px;"></div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Pasien Dilayani</div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--secondary-blue);">324</div>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--neutral-200); border-radius: 4px; margin-bottom: 8px;">
                                <div style="width: 88%; height: 100%; background: linear-gradient(90deg, var(--secondary-blue), var(--primary-blue)); border-radius: 4px;"></div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-size: 14px; color: var(--neutral-600);">Kepuasan Pasien</div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--accent-green);">4.8/5</div>
                            </div>
                            <div style="width: 100%; height: 8px; background: var(--neutral-200); border-radius: 4px;">
                                <div style="width: 96%; height: 100%; background: linear-gradient(90deg, var(--accent-green), var(--success-green)); border-radius: 4px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Comparison -->
                    <div style="background: white; border-radius: 20px; padding: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.06); border: 1px solid var(--neutral-200);">
                        <h3 class="section-title">Perbandingan Bulanan</h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                            <div style="text-align: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Nov 2024</div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--neutral-600);">Rp 23.2M</div>
                            </div>
                            <div style="text-align: center; padding: 12px; background: var(--neutral-50); border-radius: 8px;">
                                <div style="font-size: 14px; font-weight: 600; color: var(--neutral-900);">Des 2024</div>
                                <div style="font-size: 16px; font-weight: 700; color: var(--neutral-600);">Rp 24.6M</div>
                            </div>
                            <div style="text-align: center; padding: 12px; background: var(--accent-green); border-radius: 8px;">
                                <div style="font-size: 14px; font-weight: 600; color: white;">Jan 2025</div>
                                <div style="font-size: 16px; font-weight: 700; color: white;">Rp 25.8M</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; justify-content: center;">
                            <div style="font-size: 12px; color: var(--neutral-600);">Peningkatan dari bulan lalu:</div>
                            <div style="font-size: 14px; font-weight: 600; color: var(--success-green);">+4.9%</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bottom-nav">
                <div class="nav-item active" onclick="showPage('home')">
                    <div class="nav-icon">🏠</div>
                    <div class="nav-label">Beranda</div>
                </div>
                <div class="nav-item" onclick="showPage('presensi')">
                    <div class="nav-icon">📋</div>
                    <div class="nav-label">Presensi</div>
                </div>
                <div class="nav-item" onclick="showPage('jadwal')">
                    <div class="nav-icon">📅</div>
                    <div class="nav-label">Jadwal</div>
                </div>
                <div class="nav-item" onclick="showPage('laporan')">
                    <div class="nav-icon">📊</div>
                    <div class="nav-label">Laporan</div>
                </div>
                <div class="nav-item" onclick="showPage('jaspel')">
                    <div class="nav-icon">💰</div>
                    <div class="nav-label">Jaspel</div>
                </div>
            </div>
            
            <div class="floating-action" onclick="showPage('laporan')">+</div>
            
            <div id="toast" class="toast"></div>
        </div>
    </div>

    <script>
        // Global state for doctor app
        let appState = {
            userInfo: {
                name: '{{ auth()->user()->name ?? "Dr. Dokter" }}',
                role: 'Dokter Umum',
                initials: '{{ substr(auth()->user()->name ?? "Dr", 0, 2) }}'
            },
            isOnline: true
        };
        
        // API Configuration
        const APP_URL = "{{ config('app.url') ?? 'http://localhost:8000' }}";
        const API_BASE = `${APP_URL}/api/v2`;
        const API_TOKEN = "{{ $token ?? '' }}";
        
        // API helper function
        async function apiRequest(endpoint, options = {}) {
            try {
                // For demo purposes, return mock data instead of making actual API calls
                return getMockData(endpoint);
                
            } catch (error) {
                console.error('API request failed:', error);
                updateConnectionStatus('offline');
                // Return mock data as fallback
                return getMockData(endpoint);
            }
        }
        
        // Mock data function for demo
        function getMockData(endpoint) {
            const mockData = {
                '/dashboards/dokter/': {
                    success: true,
                    data: {
                        user: {
                            name: '{{ auth()->user()->name ?? "Dr. Dokter" }}',
                            role: 'Dokter Umum',
                            initials: '{{ substr(auth()->user()->name ?? "Dr", 0, 2) }}'
                        },
                        stats: {
                            patients_today: 24,
                            tindakan_today: 18,
                            jaspel_month: 25800000,
                            shifts_week: 5,
                            attendance_rate: 95,
                            late_arrivals: 2
                        },
                        schedules: [
                            {
                                id: 1,
                                time: '08:00-16:00',
                                type: 'Shift Pagi',
                                location: 'Poliklinik Umum',
                                status: 'active'
                            },
                            {
                                id: 2,
                                time: '20:00-08:00',
                                type: 'Shift Malam',
                                location: 'Gawat Darurat',
                                status: 'upcoming'
                            }
                        ]
                    }
                }
            };
            
            return mockData[endpoint] || { success: false, message: 'Endpoint not found' };
        }
        
        // Load dashboard data
        async function loadDashboardData() {
            try {
                updateConnectionStatus('connecting');
                const data = await apiRequest('/dashboards/dokter/');
                
                if (data.success) {
                    updateDashboardUI(data.data);
                    updateConnectionStatus('live');
                    console.log('Dashboard data loaded successfully');
                } else {
                    updateConnectionStatus('offline');
                    showToast('Gagal memuat data dashboard', 'warning');
                }
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
                updateConnectionStatus('offline');
                showToast('Mode demo aktif - Data simulasi ditampilkan', 'info');
            }
        }
        
        function updateDashboardUI(data) {
            try {
                // Update user info
                const userAvatar = document.getElementById('userAvatar');
                const userInfo = document.getElementById('userInfo');
                
                if (userAvatar && data.user) {
                    userAvatar.textContent = data.user.initials;
                }
                
                if (userInfo && data.user) {
                    userInfo.textContent = `${data.user.name} - ${data.user.role}`;
                }
                
                // Update stats
                if (data.stats) {
                    const attendanceRate = document.getElementById('attendanceRate');
                    const schedulesWeek = document.getElementById('schedulesWeek');
                    const jaspelMonth = document.getElementById('jaspelMonth');
                    const lateArrivals = document.getElementById('lateArrivals');
                    
                    if (attendanceRate) attendanceRate.textContent = data.stats.attendance_rate + '%';
                    if (schedulesWeek) schedulesWeek.textContent = data.stats.shifts_week;
                    if (jaspelMonth) jaspelMonth.textContent = (data.stats.jaspel_month / 1000000).toFixed(0) + 'M';
                    if (lateArrivals) lateArrivals.textContent = data.stats.late_arrivals;
                }
                
                console.log('Dashboard UI updated successfully');
                
            } catch (error) {
                console.error('Error updating dashboard UI:', error);
            }
        }
        
        // Navigation functions
        function showPage(pageId) {
            try {
                // Hide all pages
                const pages = document.querySelectorAll('.page');
                pages.forEach(page => page.classList.remove('active'));
                
                // Show selected page
                const targetPage = document.getElementById(pageId + 'Page');
                if (targetPage) {
                    targetPage.classList.add('active');
                }
                
                // Update navigation
                const navItems = document.querySelectorAll('.nav-item');
                navItems.forEach(item => item.classList.remove('active'));
                
                // Find and activate the corresponding nav item
                const pageOrder = ['home', 'presensi', 'jadwal', 'laporan', 'jaspel'];
                const pageIndex = pageOrder.indexOf(pageId);
                if (pageIndex !== -1 && navItems[pageIndex]) {
                    navItems[pageIndex].classList.add('active');
                }
                
                // Update header title
                const titles = {
                    'home': 'DOKTER MOBILE',
                    'presensi': 'PRESENSI',
                    'jadwal': 'JADWAL JAGA',
                    'laporan': 'LAPORAN KEHADIRAN',
                    'jaspel': 'JASPEL'
                };
                
                const headerTitle = document.getElementById('headerTitle');
                if (headerTitle) {
                    headerTitle.textContent = titles[pageId] || 'DOKTER MOBILE';
                }
                
            } catch (e) {
                console.warn('Error in showPage:', e);
            }
        }
        
        // GPS and Attendance Functions
        function performCheckIn() {
            try {
                showToast('Memproses check-in...', 'info');
                
                // Check GPS availability
                if (!navigator.geolocation) {
                    showToast('GPS tidak didukung oleh browser ini.', 'error');
                    return;
                }
                
                // Get current location with enhanced error handling
                getCurrentLocationWithTimeout()
                    .then(position => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        // Validate GPS accuracy
                        if (accuracy > 100) {
                            showToast(`GPS tidak cukup akurat (${Math.round(accuracy)}m). Silakan coba lagi di area terbuka.`, 'warning');
                            return;
                        }
                        
                        // Validate geofence
                        const validationResult = validateGeofence(latitude, longitude);
                        
                        if (validationResult.isValid) {
                            showToast('Check-in berhasil! Lokasi tervalidasi.', 'success');
                            updateAttendanceStatus('checked_in', {
                                latitude,
                                longitude,
                                accuracy,
                                timestamp: new Date().toISOString()
                            });
                        } else {
                            showToast(`Check-in gagal: ${validationResult.message}`, 'error');
                        }
                    })
                    .catch(error => {
                        handleGpsError(error);
                    });
                    
            } catch (error) {
                console.error('Check-in error:', error);
                showToast('Terjadi kesalahan saat check-in.', 'error');
            }
        }
        
        function performCheckOut() {
            try {
                showToast('Memproses check-out...', 'info');
                
                // Check GPS availability
                if (!navigator.geolocation) {
                    showToast('GPS tidak didukung oleh browser ini.', 'error');
                    return;
                }
                
                // Get current location with enhanced error handling
                getCurrentLocationWithTimeout()
                    .then(position => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        const accuracy = position.coords.accuracy;
                        
                        // Validate GPS accuracy
                        if (accuracy > 100) {
                            showToast(`GPS tidak cukup akurat (${Math.round(accuracy)}m). Silakan coba lagi di area terbuka.`, 'warning');
                            return;
                        }
                        
                        // Validate geofence
                        const validationResult = validateGeofence(latitude, longitude);
                        
                        if (validationResult.isValid) {
                            showToast('Check-out berhasil! Terima kasih atas kerja keras Anda.', 'success');
                            updateAttendanceStatus('checked_out', {
                                latitude,
                                longitude,
                                accuracy,
                                timestamp: new Date().toISOString()
                            });
                        } else {
                            showToast(`Check-out gagal: ${validationResult.message}`, 'error');
                        }
                    })
                    .catch(error => {
                        handleGpsError(error);
                    });
                    
            } catch (error) {
                console.error('Check-out error:', error);
                showToast('Terjadi kesalahan saat check-out.', 'error');
            }
        }
        
        function validateGeofence(latitude, longitude) {
            // Get clinic coordinates from environment or use default (Malang - main office)
            const clinicLat = {{ config('app.clinic_latitude', -7.9666) }};
            const clinicLng = {{ config('app.clinic_longitude', 112.6326) }};
            const validRadius = {{ config('app.clinic_radius', 100) }}; // meters
            
            // Debug logging
            console.log('Geofence Validation:', {
                userLat: latitude,
                userLng: longitude,
                clinicLat: clinicLat,
                clinicLng: clinicLng,
                validRadius: validRadius
            });
            
            // Calculate distance using Haversine formula
            const distance = calculateDistance(latitude, longitude, clinicLat, clinicLng);
            
            console.log('Distance calculated:', distance, 'meters');
            
            return {
                isValid: distance <= validRadius,
                distance: Math.round(distance),
                message: distance <= validRadius 
                    ? `Lokasi valid (${Math.round(distance)}m dari klinik)`
                    : `Anda berada ${Math.round(distance)}m dari klinik (maksimal ${validRadius}m)`
            };
        }
        
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Earth's radius in meters
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        // Enhanced GPS Location with timeout and error handling
        function getCurrentLocationWithTimeout(timeout = 15000) {
            return new Promise((resolve, reject) => {
                // First try to get cached location
                const cachedLocation = getCachedLocation();
                if (cachedLocation) {
                    console.log('Using cached location:', cachedLocation);
                    resolve(cachedLocation);
                    return;
                }
                
                const timeoutId = setTimeout(() => {
                    reject(new Error('GPS_TIMEOUT'));
                }, timeout);
                
                // Try with high accuracy first
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        clearTimeout(timeoutId);
                        cacheLocation(position);
                        resolve(position);
                    },
                    (error) => {
                        console.log('High accuracy failed, trying lower accuracy...');
                        
                        // Fallback to lower accuracy
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                clearTimeout(timeoutId);
                                cacheLocation(position);
                                resolve(position);
                            },
                            (fallbackError) => {
                                clearTimeout(timeoutId);
                                reject(fallbackError);
                            },
                            {
                                enableHighAccuracy: false,
                                timeout: timeout / 2,
                                maximumAge: 300000 // 5 minutes cache for fallback
                            }
                        );
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: timeout / 2,
                        maximumAge: 60000 // 1 minute cache
                    }
                );
            });
        }
        
        // Cache location functions
        function cacheLocation(position) {
            try {
                const locationData = {
                    coords: {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        altitude: position.coords.altitude,
                        altitudeAccuracy: position.coords.altitudeAccuracy,
                        heading: position.coords.heading,
                        speed: position.coords.speed
                    },
                    timestamp: position.timestamp || Date.now()
                };
                
                localStorage.setItem('cached_gps_location', JSON.stringify(locationData));
                console.log('Location cached successfully');
            } catch (error) {
                console.error('Error caching location:', error);
            }
        }
        
        function getCachedLocation() {
            try {
                const cached = localStorage.getItem('cached_gps_location');
                if (!cached) return null;
                
                const locationData = JSON.parse(cached);
                const now = Date.now();
                const cacheAge = now - locationData.timestamp;
                
                // Use cached location if less than 2 minutes old
                if (cacheAge < 120000) {
                    return locationData;
                }
                
                // Remove expired cache
                localStorage.removeItem('cached_gps_location');
                return null;
            } catch (error) {
                console.error('Error getting cached location:', error);
                return null;
            }
        }
        
        // Enhanced GPS Error Handler
        function handleGpsError(error) {
            let message;
            let action = null;
            
            switch (error.code || error.message) {
                case 1:
                case 'PERMISSION_DENIED':
                    message = 'Izin GPS ditolak. Silakan aktifkan GPS dan berikan izin lokasi.';
                    action = 'Buka Pengaturan';
                    break;
                case 2:
                case 'POSITION_UNAVAILABLE':
                    message = 'Lokasi tidak tersedia. Pastikan GPS aktif dan Anda berada di area terbuka.';
                    action = 'Coba Lagi';
                    break;
                case 3:
                case 'TIMEOUT':
                case 'GPS_TIMEOUT':
                    message = 'GPS timeout. Mencoba menggunakan mode offline...';
                    action = 'Coba Lagi';
                    // Try offline mode
                    tryOfflineMode();
                    return;
                default:
                    message = 'Gagal mendapatkan lokasi GPS. Silakan coba lagi.';
                    action = 'Coba Lagi';
                    break;
            }
            
            showToast(message, 'error');
            
            // Store error in cache for analysis
            storeGpsError(error);
            
            console.error('GPS Error:', error);
        }
        
        // Try offline mode when GPS fails
        function tryOfflineMode() {
            try {
                const lastKnownLocation = localStorage.getItem('last_known_location');
                if (lastKnownLocation) {
                    const locationData = JSON.parse(lastKnownLocation);
                    const now = Date.now();
                    const locationAge = now - new Date(locationData.timestamp).getTime();
                    
                    // Use last known location if less than 10 minutes old
                    if (locationAge < 600000) {
                        showToast('Menggunakan lokasi terakhir yang diketahui...', 'info');
                        
                        // Use the last known location
                        const validationResult = validateGeofence(locationData.latitude, locationData.longitude);
                        
                        if (validationResult.isValid) {
                            showToast('Presensi berhasil menggunakan lokasi offline!', 'success');
                            updateAttendanceStatus('offline_attendance', {
                                latitude: locationData.latitude,
                                longitude: locationData.longitude,
                                accuracy: locationData.accuracy,
                                timestamp: new Date().toISOString(),
                                offline: true
                            });
                        } else {
                            showToast(`Lokasi offline tidak valid: ${validationResult.message}`, 'error');
                        }
                        return;
                    }
                }
                
                // No valid offline location available
                showToast('Tidak ada lokasi offline yang valid. Silakan coba lagi dengan GPS aktif.', 'warning');
                
                // Show retry option
                showRetryDialog();
                
            } catch (error) {
                console.error('Error in offline mode:', error);
                showToast('Mode offline gagal. Silakan coba lagi.', 'error');
            }
        }
        
        // Show retry dialog
        function showRetryDialog() {
            const retryHtml = `
                <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;" id="retryDialog">
                    <div style="background: white; padding: 30px; border-radius: 20px; max-width: 350px; margin: 20px; text-align: center;">
                        <h3 style="margin: 0 0 20px 0; font-size: 18px; color: var(--neutral-900);">GPS Timeout</h3>
                        <p style="margin: 0 0 25px 0; font-size: 14px; color: var(--neutral-600); line-height: 1.5;">
                            Tidak dapat mendapatkan lokasi GPS. Silakan pastikan:
                        </p>
                        <ul style="text-align: left; margin: 0 0 25px 0; font-size: 14px; color: var(--neutral-600);">
                            <li>GPS aktif di perangkat</li>
                            <li>Izin lokasi telah diberikan</li>
                            <li>Berada di area terbuka</li>
                            <li>Dalam radius 100m dari klinik</li>
                        </ul>
                        <div style="display: flex; gap: 12px;">
                            <button onclick="closeRetryDialog()" style="flex: 1; padding: 12px; background: var(--neutral-200); color: var(--neutral-900); border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                                Tutup
                            </button>
                            <button onclick="retryGpsLocation()" style="flex: 1; padding: 12px; background: var(--secondary-blue); color: white; border: none; border-radius: 8px; font-size: 14px; cursor: pointer;">
                                Coba Lagi
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', retryHtml);
        }
        
        // Close retry dialog
        function closeRetryDialog() {
            const dialog = document.getElementById('retryDialog');
            if (dialog) {
                dialog.remove();
            }
        }
        
        // Retry GPS location
        function retryGpsLocation() {
            closeRetryDialog();
            
            // Clear GPS cache to force fresh request
            localStorage.removeItem('cached_gps_location');
            
            // Show loading
            showToast('Mencoba mendapatkan lokasi GPS...', 'info');
            
            // Retry with extended timeout
            getCurrentLocationWithTimeout(20000)
                .then(position => {
                    showToast('Lokasi GPS berhasil didapatkan!', 'success');
                    
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    const accuracy = position.coords.accuracy;
                    
                    // Validate geofence
                    const validationResult = validateGeofence(latitude, longitude);
                    
                    if (validationResult.isValid) {
                        showToast('Presensi berhasil!', 'success');
                        updateAttendanceStatus('retry_success', {
                            latitude,
                            longitude,
                            accuracy,
                            timestamp: new Date().toISOString()
                        });
                    } else {
                        showToast(`Lokasi tidak valid: ${validationResult.message}`, 'error');
                    }
                })
                .catch(error => {
                    console.error('Retry GPS failed:', error);
                    showToast('GPS masih timeout. Coba lagi nanti atau hubungi admin.', 'error');
                });
        }
        
        function updateAttendanceStatus(status, locationData = null) {
            try {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                // Update UI based on status
                if (status === 'checked_in') {
                    console.log('Attendance updated: checked in at', timeString);
                    // Store check-in data for offline sync
                    storeOfflineAttendance('check_in', locationData);
                } else if (status === 'checked_out') {
                    console.log('Attendance updated: checked out at', timeString);
                    // Store check-out data for offline sync
                    storeOfflineAttendance('check_out', locationData);
                }
                
                // This would normally make an API call to update attendance
                // apiRequest('/attendance/update', { method: 'POST', body: JSON.stringify({ status, timestamp: now, location: locationData }) });
                
            } catch (error) {
                console.error('Error updating attendance status:', error);
            }
        }
        
        // Offline GPS Coordinate Caching
        function storeOfflineAttendance(type, locationData) {
            try {
                const attendanceData = {
                    type: type,
                    timestamp: new Date().toISOString(),
                    location: locationData,
                    userId: '{{ auth()->user()->id ?? 0 }}',
                    synced: false
                };
                
                // Store in localStorage for offline support
                const offlineData = getOfflineAttendanceData();
                offlineData.push(attendanceData);
                localStorage.setItem('offline_attendance', JSON.stringify(offlineData));
                
                console.log('Attendance data stored offline:', attendanceData);
                
                // Try to sync if online
                if (navigator.onLine) {
                    syncOfflineData();
                }
                
            } catch (error) {
                console.error('Error storing offline attendance:', error);
            }
        }
        
        function getOfflineAttendanceData() {
            try {
                const data = localStorage.getItem('offline_attendance');
                return data ? JSON.parse(data) : [];
            } catch (error) {
                console.error('Error getting offline attendance data:', error);
                return [];
            }
        }
        
        function syncOfflineData() {
            try {
                const offlineData = getOfflineAttendanceData();
                const unsyncedData = offlineData.filter(item => !item.synced);
                
                if (unsyncedData.length === 0) {
                    return;
                }
                
                console.log(`Syncing ${unsyncedData.length} offline attendance records...`);
                
                // Simulate API sync (replace with actual API call)
                unsyncedData.forEach(item => {
                    // Mark as synced
                    item.synced = true;
                    console.log('Synced attendance:', item);
                });
                
                // Update localStorage
                localStorage.setItem('offline_attendance', JSON.stringify(offlineData));
                
                showToast(`${unsyncedData.length} data presensi berhasil disinkronkan`, 'success');
                
            } catch (error) {
                console.error('Error syncing offline data:', error);
                showToast('Gagal sinkronisasi data offline', 'error');
            }
        }
        
        function storeGpsError(error) {
            try {
                const errorData = {
                    timestamp: new Date().toISOString(),
                    code: error.code || error.message,
                    message: error.message,
                    userId: '{{ auth()->user()->id ?? 0 }}'
                };
                
                // Store GPS errors for analysis
                const gpsErrors = getGpsErrors();
                gpsErrors.push(errorData);
                
                // Keep only last 50 errors
                if (gpsErrors.length > 50) {
                    gpsErrors.splice(0, gpsErrors.length - 50);
                }
                
                localStorage.setItem('gps_errors', JSON.stringify(gpsErrors));
                
            } catch (error) {
                console.error('Error storing GPS error:', error);
            }
        }
        
        function getGpsErrors() {
            try {
                const data = localStorage.getItem('gps_errors');
                return data ? JSON.parse(data) : [];
            } catch (error) {
                console.error('Error getting GPS errors:', error);
                return [];
            }
        }
        
        // Real-time Location Monitoring
        function startLocationMonitoring() {
            if (!navigator.geolocation) {
                console.warn('Geolocation not supported');
                return;
            }
            
            // Start with relaxed settings for continuous monitoring
            const options = {
                enableHighAccuracy: false, // Start with low accuracy for better battery life
                timeout: 15000,
                maximumAge: 180000 // 3 minutes cache for monitoring
            };
            
            const watchId = navigator.geolocation.watchPosition(
                (position) => {
                    const locationData = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        timestamp: new Date().toISOString()
                    };
                    
                    // Update real-time location status
                    updateLocationStatus(locationData);
                    
                    // Cache the location for offline use
                    localStorage.setItem('last_known_location', JSON.stringify(locationData));
                },
                (error) => {
                    console.error('Location monitoring error:', error);
                    
                    // Don't immediately set to offline for monitoring errors
                    // Only set offline if it's a permission error
                    if (error.code === 1) {
                        updateConnectionStatus('offline');
                    }
                },
                options
            );
            
            // Store watch ID for cleanup
            window.locationWatchId = watchId;
            
            console.log('Location monitoring started with relaxed settings');
        }
        
        function stopLocationMonitoring() {
            if (window.locationWatchId) {
                navigator.geolocation.clearWatch(window.locationWatchId);
                window.locationWatchId = null;
                console.log('Location monitoring stopped');
            }
        }
        
        function updateLocationStatus(locationData) {
            try {
                // Update GPS status in UI
                const gpsStatusElement = document.querySelector('.gps-status');
                if (gpsStatusElement) {
                    const accuracy = Math.round(locationData.accuracy);
                    gpsStatusElement.textContent = `GPS: ±${accuracy}m`;
                    
                    // Update accuracy indicator color
                    if (accuracy <= 10) {
                        gpsStatusElement.style.color = 'var(--success-green)';
                    } else if (accuracy <= 50) {
                        gpsStatusElement.style.color = 'var(--warning-orange)';
                    } else {
                        gpsStatusElement.style.color = 'var(--error-red)';
                    }
                }
                
                // Validate current location
                const validation = validateGeofence(locationData.latitude, locationData.longitude);
                
                // Update geofence status
                const geofenceStatusElement = document.querySelector('.geofence-status');
                if (geofenceStatusElement) {
                    geofenceStatusElement.textContent = validation.message;
                    geofenceStatusElement.style.color = validation.isValid ? 'var(--success-green)' : 'var(--error-red)';
                }
                
                // Store location for offline use
                localStorage.setItem('last_known_location', JSON.stringify(locationData));
                
            } catch (error) {
                console.error('Error updating location status:', error);
            }
        }
        
        // Auto-sync when online
        window.addEventListener('online', () => {
            console.log('Connection restored, syncing offline data...');
            syncOfflineData();
        });
        
        window.addEventListener('offline', () => {
            console.log('Connection lost, switching to offline mode...');
            updateConnectionStatus('offline');
        });
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            stopLocationMonitoring();
        });
        
        // Show clinic location info for debugging
        function showClinicLocationInfo() {
            const clinicLat = {{ config('app.clinic_latitude', -7.89946200) }};
            const clinicLng = {{ config('app.clinic_longitude', 111.96239900) }};
            const validRadius = {{ config('app.clinic_radius', 100) }};
            
            console.log('=== CLINIC LOCATION INFO ===');
            console.log('Clinic Name: Klinik Dokterku');
            console.log('Location: Mojo, Malang, Jawa Timur');
            console.log('Latitude:', clinicLat);
            console.log('Longitude:', clinicLng);
            console.log('Valid Radius:', validRadius, 'meters');
            console.log('Google Maps:', `https://maps.google.com/?q=${clinicLat},${clinicLng}`);
            console.log('================================');
            
            // Show user-friendly location info
            if (typeof showToast === 'function') {
                showToast(`Lokasi klinik: Mojo, Malang (Radius: ${validRadius}m)`, 'info');
            }
        }
        
        // Utility functions
        function showToast(message, type = 'success') {
            try {
                const toast = document.getElementById('toast');
                if (!toast) return;
                
                const emojis = {
                    'success': '✅',
                    'error': '❌',
                    'warning': '⚠️',
                    'info': 'ℹ️'
                };
                
                const emoji = emojis[type] || emojis['info'];
                toast.textContent = `${emoji} ${message}`;
                toast.className = `toast ${type}`;
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
                
            } catch (e) {
                console.warn('Error showing toast:', e);
            }
        }
        
        function updateTime() {
            try {
                const now = new Date();
                const timeString = now.toLocaleTimeString('id-ID', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                const timeStatus = document.getElementById('currentTimeStatus');
                if (timeStatus) timeStatus.textContent = timeString;
                
            } catch (e) {
                console.warn('Error updating time:', e);
            }
        }
        
        function updateConnectionStatus(status) {
            try {
                const dot = document.getElementById('connectionDot');
                const text = document.getElementById('connectionText');
                
                if (!dot || !text) return;
                
                switch (status) {
                    case 'live':
                        dot.style.background = '#10b981';
                        text.textContent = 'LIVE';
                        break;
                    case 'offline':
                        dot.style.background = '#ef4444';
                        text.textContent = 'OFFLINE';
                        break;
                    case 'connecting':
                        dot.style.background = '#6b7280';
                        text.textContent = 'LOADING';
                        break;
                }
            } catch (e) {
                console.warn('Error updating connection status:', e);
            }
        }
        
        function retryConnection() {
            showToast('Sedang dalam mode demo. Fitur lengkap akan segera tersedia.', 'info');
        }
        
        // Initialize app
        function initializeApp() {
            try {
                console.log('Initializing Dokter Mobile App...');
                
                // Update time
                updateTime();
                setInterval(updateTime, 60000);
                
                // Set connection status
                updateConnectionStatus('connecting');
                
                // Initialize with home page
                showPage('home');
                
                // Load dashboard data
                loadDashboardData();
                
                // Start location monitoring for real-time GPS tracking
                startLocationMonitoring();
                
                // Sync any offline data
                if (navigator.onLine) {
                    syncOfflineData();
                }
                
                // Show clinic location info
                showClinicLocationInfo();
                
                // Show welcome message
                showToast('Selamat datang di Dokter Mobile App!', 'success');
                
                console.log('Dokter Mobile App initialized successfully!');
                
            } catch (e) {
                console.error('Error initializing app:', e);
                showToast('Gagal memuat aplikasi', 'error');
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', initializeApp);
        
        // Add hover effects to quick actions
        document.addEventListener('DOMContentLoaded', function() {
            const quickActions = document.querySelectorAll('.quick-action-item');
            quickActions.forEach(action => {
                action.addEventListener('mouseenter', function() {
                    this.style.background = 'white';
                    this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.06)';
                });
                
                action.addEventListener('mouseleave', function() {
                    this.style.background = 'var(--neutral-50)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>