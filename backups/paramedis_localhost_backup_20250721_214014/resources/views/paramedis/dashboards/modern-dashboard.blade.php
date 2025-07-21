<!-- Single root element for Livewire compatibility -->
<div class="modern-dashboard-container" data-user="{{ json_encode($user) }}" data-stats="{{ json_encode($dashboardStats) }}" data-schedule="{{ json_encode($scheduleData) }}" data-jaspel="{{ json_encode($jaspenData) }}" data-chart-data="{{ json_encode($chartData) }}" data-quick-actions="{{ json_encode($quickActions) }}">
    
    <!-- React Dashboard Component will be mounted here -->
    <div id="modern-dashboard-root" class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-blue-50">
        <!-- Loading State -->
        <div class="flex items-center justify-center min-h-64 modern-dashboard-loading">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
                <div class="text-lg font-semibold text-blue-600 mb-2">🚀 Modern Dashboard v2.0</div>
                <span class="text-gray-600">Memuat dashboard modern...</span>
            </div>
        </div>
    </div>
    
    <!-- Fallback Content if React fails to load -->
    <noscript>
        <div class="p-6 bg-yellow-50 border-l-4 border-yellow-400">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Dashboard modern memerlukan JavaScript untuk berfungsi dengan optimal. 
                        Silakan aktifkan JavaScript di browser Anda.
                    </p>
                    <div class="mt-4">
                        <button onclick="window.location.reload()" 
                           class="text-sm text-yellow-700 underline hover:text-yellow-800">
                            Refresh Halaman
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </noscript>
    
    <!-- React Component Initialization -->
    @vite(['resources/js/paramedis/modern-dashboard.tsx'])
    
    <!-- Inline scripts for Livewire compatibility -->
    <script>
        // Initialize dashboard data for React components
        window.DashboardData = {
            user: @json($user),
            stats: @json($dashboardStats),
            schedule: @json($scheduleData),
            jaspel: @json($jaspenData),
            chartData: @json($chartData),
            quickActions: @json($quickActions),
            csrfToken: '{{ csrf_token() }}',
            apiBaseUrl: '{{ url('/api/v2') }}',
            panelUrl: '/paramedis',
            routes: {
                attendance: '/paramedis/presensi',
                schedule: '/paramedis/jadwal-jaga',
                tindakan: '/paramedis/modern',
                jaspel: '/paramedis/jaspel',
                profile: '/paramedis/modern'
            }
        };

        // Handle navigation between components
        window.navigateToComponent = function(component) {
            const event = new CustomEvent('navigate-component', {
                detail: { component: component }
            });
            window.dispatchEvent(event);
        };

        // Handle logout
        window.handleLogout = function() {
            if (confirm('Anda yakin ingin keluar?')) {
                window.location.href = '{{ route('logout') }}';
            }
        };

        // Real-time updates
        window.refreshDashboardData = function() {
            fetch('/api/v2/dashboards/paramedis/', {
                headers: {
                    'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || '{{ csrf_token() }}'),
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                const event = new CustomEvent('dashboard-data-updated', {
                    detail: data
                });
                window.dispatchEvent(event);
            })
            .catch(error => console.warn('Failed to refresh dashboard data:', error));
        };

        // Auto-refresh every 5 minutes
        setInterval(window.refreshDashboardData, 5 * 60 * 1000);

        // Mobile optimization
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => console.log('SW registered'))
                .catch(error => console.log('SW registration failed'));
        }

        // Add mobile-specific styles
        document.documentElement.classList.add('mobile-optimized');
        
        // Handle back button for mobile navigation
        window.addEventListener('popstate', function(event) {
            if (event.state && event.state.component) {
                window.navigateToComponent(event.state.component);
            }
        });
    </script>

    <!-- Inline styles for Livewire compatibility -->
    <style>
        /* Modern Dashboard Specific Styles */
        .modern-dashboard-container {
            --dashboard-primary: #3b82f6;
            --dashboard-secondary: #1e40af;
            --dashboard-success: #10b981;
            --dashboard-warning: #f59e0b;
            --dashboard-error: #ef4444;
            --dashboard-surface: #ffffff;
            --dashboard-background: #f8fafc;
        }

        .modern-dashboard-loading {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .modern-dashboard-container {
                padding: 0;
                margin: 0;
            }
            
            #modern-dashboard-root {
                min-height: 100vh;
                min-height: 100dvh; /* Dynamic viewport height for mobile */
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .modern-dashboard-container {
                --dashboard-surface: #1f2937;
                --dashboard-background: #111827;
            }
        }

        /* Print styles */
        @media print {
            .modern-dashboard-container {
                background: white !important;
            }
        }

        /* High contrast mode */
        @media (prefers-contrast: high) {
            .modern-dashboard-container {
                --dashboard-primary: #000000;
                --dashboard-secondary: #333333;
            }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            .modern-dashboard-loading {
                animation: none;
            }
        }
    </style>
</div>