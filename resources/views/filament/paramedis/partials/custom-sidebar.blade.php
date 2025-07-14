<!-- Custom Paramedis Sidebar -->
<div class="paramedis-sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-icon">
                <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.5 7.5l-1.5-1.5L12 12l-6-6L4.5 7.5 12 15l7.5-7.5z"/>
                </svg>
            </div>
            <span class="brand-text">Paramedis</span>
        </div>
        <div class="user-info">
            <div class="user-avatar">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="user-details">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Paramedis</div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <!-- Dashboard -->
            <li class="nav-item">
                <a href="/paramedis" 
                   class="nav-link {{ request()->is('paramedis') ? 'active' : '' }}">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5v4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v4"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 5v4"/>
                        </svg>
                    </div>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- Presensi Section -->
            <li class="nav-section">
                <div class="section-header">
                    <span class="section-title">Presensi</span>
                </div>
            </li>

            <!-- Attendance -->
            <li class="nav-item">
                <a href="#" onclick="showComingSoon('Presensi')" 
                   class="nav-link">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="nav-text">Presensi Saya</span>
                    @php
                        $todayAttendance = \App\Models\Attendance::where('user_id', auth()->id())
                            ->where('date', now()->toDateString())
                            ->first();
                    @endphp
                    @if($todayAttendance && $todayAttendance->time_in && !$todayAttendance->time_out)
                        <span class="nav-badge working">Sedang Kerja</span>
                    @elseif($todayAttendance && $todayAttendance->time_out)
                        <span class="nav-badge completed">Selesai</span>
                    @else
                        <span class="nav-badge pending">Belum Absen</span>
                    @endif
                </a>
            </li>

            <!-- GPS Presensi -->
            <li class="nav-item">
                <a href="/paramedis/presensi-mobile-page" 
                   class="nav-link {{ request()->is('paramedis/presensi-mobile-page') ? 'active' : '' }}">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="nav-text">GPS Presensi</span>
                </a>
            </li>

            <!-- Jaspel Section -->
            <li class="nav-section">
                <div class="section-header">
                    <span class="section-title">Jaspel</span>
                </div>
            </li>

            <!-- Jaspel -->
            <li class="nav-item">
                <a href="/paramedis/jaspel-premium-page" 
                   class="nav-link {{ request()->is('paramedis/jaspel-premium-page*') ? 'active' : '' }}">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <span class="nav-text">Jaspel Saya</span>
                    @php
                        $monthlyJaspel = \App\Models\Jaspel::where('user_id', auth()->id())
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->sum('jumlah') ?? 0;
                    @endphp
                    <span class="nav-amount">Rp {{ number_format($monthlyJaspel, 0, ',', '.') }}</span>
                </a>
            </li>

            <!-- Tindakan -->
            <li class="nav-item">
                <a href="#" onclick="showComingSoon('Tindakan')" 
                   class="nav-link">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <span class="nav-text">Tindakan Saya</span>
                </a>
            </li>

            <!-- Schedule Section -->
            <li class="nav-section">
                <div class="section-header">
                    <span class="section-title">Jadwal</span>
                </div>
            </li>

            <!-- Calendar -->
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="nav-text">Jadwal Kerja</span>
                </a>
            </li>

            <!-- Performance -->
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <div class="nav-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <span class="nav-text">Performa</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="footer-actions">
            <a href="#" class="footer-link">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Pengaturan</span>
            </a>
            <form method="POST" action="/paramedis/logout" class="logout-form">
                @csrf
                <button type="submit" class="footer-link logout-btn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
        <div class="sidebar-version">
            <span>Dokterku v1.0</span>
        </div>
    </div>
</div>

<script>
    function showComingSoon(feature) {
        // Modern toast notification for coming soon
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            font-weight: 500;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transform: translateX(400px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <div style="font-weight: 600; margin-bottom: 2px;">${feature} - Coming Soon!</div>
                    <div style="font-size: 12px; opacity: 0.9;">Will be available in React Native version</div>
                </div>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Slide in animation
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            toast.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
        
        // Haptic feedback if supported
        if (navigator.vibrate) {
            navigator.vibrate(50);
        }
    }
</script>

<style>
    /* Custom Sidebar Styles */
    .paramedis-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 280px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border-right: 1px solid #e2e8f0;
        display: flex;
        flex-direction: column;
        z-index: 50;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Sidebar Header */
    .sidebar-header {
        padding: 24px 20px;
        border-bottom: 1px solid #e2e8f0;
    }

    .sidebar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .brand-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }

    .brand-text {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
        letter-spacing: -0.5px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #f1f5f9;
        border-radius: 12px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 16px;
    }

    .user-details {
        flex: 1;
    }

    .user-name {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.2;
    }

    .user-role {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }

    /* Navigation */
    .sidebar-nav {
        flex: 1;
        padding: 16px 0;
        overflow-y: auto;
    }

    .nav-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .nav-section {
        margin: 20px 0 8px;
        padding: 0 20px;
    }

    .section-header {
        position: relative;
    }

    .section-title {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .nav-item {
        margin: 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 20px;
        text-decoration: none;
        color: #64748b;
        font-size: 14px;
        font-weight: 500;
        border-radius: 0;
        transition: all 150ms ease;
        position: relative;
    }

    .nav-link:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    .nav-link.active {
        background: linear-gradient(90deg, #10b981 0%, #059669 100%);
        color: white;
        font-weight: 600;
    }

    .nav-link.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #065f46;
    }

    .nav-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .nav-text {
        flex: 1;
    }

    .nav-badge {
        font-size: 10px;
        font-weight: 600;
        padding: 4px 8px;
        border-radius: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .nav-badge.working {
        background: #dcfce7;
        color: #166534;
    }

    .nav-badge.completed {
        background: #dbeafe;
        color: #1e40af;
    }

    .nav-badge.pending {
        background: #fed7d7;
        color: #991b1b;
    }

    .nav-amount {
        font-size: 11px;
        font-weight: 600;
        color: inherit;
        opacity: 0.8;
    }

    /* Sidebar Footer */
    .sidebar-footer {
        padding: 16px 20px 24px;
        border-top: 1px solid #e2e8f0;
    }

    .footer-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 16px;
    }

    .footer-link, .logout-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        font-size: 12px;
        color: #64748b;
        text-decoration: none;
        border-radius: 8px;
        transition: all 150ms ease;
        background: none;
        border: none;
        width: 100%;
        cursor: pointer;
    }

    .footer-link:hover, .logout-btn:hover {
        background: #f1f5f9;
        color: #1e293b;
    }

    .logout-form {
        margin: 0;
    }

    .sidebar-version {
        text-align: center;
        font-size: 10px;
        color: #94a3b8;
        font-weight: 500;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .paramedis-sidebar {
            transform: translateX(-100%);
            transition: transform 300ms ease;
        }

        .paramedis-sidebar.mobile-open {
            transform: translateX(0);
        }
        
        .sidebar-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            opacity: 0;
            pointer-events: none;
            transition: opacity 300ms ease;
        }
        
        .sidebar-backdrop.active {
            opacity: 1;
            pointer-events: auto;
        }
    }

    /* Content offset when sidebar is visible */
    @media (min-width: 769px) {
        .main-content-with-sidebar {
            margin-left: 280px;
        }
    }
</style>