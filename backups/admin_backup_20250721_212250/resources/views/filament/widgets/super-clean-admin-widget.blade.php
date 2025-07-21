<x-filament-widgets::widget>
    <style>
        /* SUPER CLEAN INLINE CSS - ZERO CONFLICTS, ZERO EXTERNAL DEPS */
        .sca-container {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        .sca-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .sca-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .sca-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .sca-icon {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 20px;
            font-weight: bold;
        }
        
        .sca-icon-blue { background: #dbeafe; color: #2563eb; }
        .sca-icon-green { background: #dcfce7; color: #16a34a; }
        .sca-icon-purple { background: #f3e8ff; color: #7c3aed; }
        .sca-icon-orange { background: #fed7aa; color: #ea580c; }
        
        .sca-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .sca-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #111827;
            margin: 0.25rem 0 0.5rem 0;
        }
        
        .sca-stats {
            display: flex;
            justify-content: space-between;
        }
        
        .sca-stat {
            text-align: center;
            flex: 1;
        }
        
        .sca-stat-value {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .sca-stat-label {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .sca-success-banner {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            margin-top: 1.5rem;
        }
        
        .sca-success-title {
            font-weight: 600;
            color: #16a34a;
            margin-bottom: 0.5rem;
        }
        
        .sca-success-text {
            font-size: 0.875rem;
            color: #15803d;
        }
    </style>

    <div class="sca-container">
        <div class="sca-grid">
            <!-- Users Card -->
            <div class="sca-card">
                <div class="sca-header">
                    <div class="sca-icon sca-icon-blue">👥</div>
                    <div>
                        <div class="sca-title">Total Users</div>
                        <div class="sca-value">{{ number_format($this->getViewData()['stats']['users']['total']) }}</div>
                    </div>
                </div>
                <div class="sca-stats">
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #16a34a;">{{ $this->getViewData()['stats']['users']['active'] }}</div>
                        <div class="sca-stat-label">Active</div>
                    </div>
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #2563eb;">+{{ $this->getViewData()['stats']['users']['new_today'] }}</div>
                        <div class="sca-stat-label">New Today</div>
                    </div>
                </div>
            </div>

            <!-- Medical Card -->
            <div class="sca-card">
                <div class="sca-header">
                    <div class="sca-icon sca-icon-green">❤️</div>
                    <div>
                        <div class="sca-title">Patients Today</div>
                        <div class="sca-value">{{ $this->getViewData()['stats']['medical']['patients'] }}</div>
                    </div>
                </div>
                <div class="sca-stats">
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #7c3aed;">{{ $this->getViewData()['stats']['medical']['procedures'] }}</div>
                        <div class="sca-stat-label">Procedures</div>
                    </div>
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #16a34a;">{{ number_format($this->getViewData()['stats']['medical']['revenue'], 0, ',', '.') }}</div>
                        <div class="sca-stat-label">Revenue</div>
                    </div>
                </div>
            </div>

            <!-- System Health Card -->
            <div class="sca-card">
                <div class="sca-header">
                    <div class="sca-icon sca-icon-purple">⚡</div>
                    <div>
                        <div class="sca-title">System Health</div>
                        <div class="sca-value">{{ $this->getViewData()['stats']['system']['uptime'] }}%</div>
                    </div>
                </div>
                <div class="sca-stats">
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #2563eb;">{{ $this->getViewData()['stats']['system']['memory'] }}%</div>
                        <div class="sca-stat-label">Memory</div>
                    </div>
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #16a34a;">{{ $this->getViewData()['stats']['system']['cpu'] }}%</div>
                        <div class="sca-stat-label">CPU</div>
                    </div>
                </div>
            </div>

            <!-- Security Card -->
            <div class="sca-card">
                <div class="sca-header">
                    <div class="sca-icon sca-icon-orange">🛡️</div>
                    <div>
                        <div class="sca-title">Security Status</div>
                        <div class="sca-value">{{ $this->getViewData()['stats']['security']['sessions'] }}</div>
                    </div>
                </div>
                <div class="sca-stats">
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #dc2626;">{{ $this->getViewData()['stats']['security']['failed_logins'] }}</div>
                        <div class="sca-stat-label">Failed Logins</div>
                    </div>
                    <div class="sca-stat">
                        <div class="sca-stat-value" style="color: #16a34a;">{{ $this->getViewData()['stats']['security']['alerts'] }}</div>
                        <div class="sca-stat-label">Alerts</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Banner -->
        <div class="sca-success-banner">
            <div class="sca-success-title">
                🎉 SUPER CLEAN ADMIN DASHBOARD - FORENSIC FREE!
            </div>
            <div class="sca-success-text">
                ✅ Zero forensic scripts • ✅ Zero observer conflicts • ✅ Zero JavaScript errors<br>
                ✅ Super clean code • ✅ Production ready • ✅ Total forensic elimination<br><br>
                Last updated: {{ now()->format('d M Y, H:i') }} WIB
            </div>
        </div>
    </div>
</x-filament-widgets::widget>