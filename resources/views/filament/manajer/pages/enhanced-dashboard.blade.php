<x-filament-panels::page>
    {{-- Pinterest-Inspired Executive Dashboard --}}
    <div class="executive-dashboard-container">
        {{-- Dashboard Header --}}
        <div class="dashboard-header mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Executive Dashboard
                    </h1>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        Real-time insights and strategic metrics for executive decision making
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Last updated: {{ now()->format('M d, Y H:i') }}
                    </div>
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                </div>
            </div>
        </div>

        {{-- Pinterest-Style Widget Grid --}}
        <div class="executive-widgets-grid">
            {{-- Row 1: Hero Stats, Financial Insights, Team Performance --}}
            <div class="widget-row">
                <div class="widget-card hero-stats">
                    <livewire:app.filament.manajer.widgets.manajer-hero-stats-widget />
                </div>
                <div class="widget-card financial-insights">
                    <livewire:app.filament.manajer.widgets.manajer-financial-insights-widget />
                </div>
                <div class="widget-card team-performance">
                    <livewire:app.filament.manajer.widgets.manajer-team-performance-widget />
                </div>
            </div>

            {{-- Row 2: Operational Dashboard, Strategic Metrics, Approval Workflow --}}
            <div class="widget-row">
                <div class="widget-card operational-dashboard">
                    <livewire:app.filament.manajer.widgets.manajer-operational-dashboard-widget />
                </div>
                <div class="widget-card strategic-metrics">
                    <livewire:app.filament.manajer.widgets.manajer-strategic-metrics-widget />
                </div>
                <div class="widget-card approval-workflow">
                    <livewire:app.filament.manajer.widgets.manajer-approval-workflow-widget />
                </div>
            </div>
        </div>
    </div>

    {{-- Custom Styles for Pinterest-Inspired Layout --}}
    <style>
        .executive-dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }

        .executive-widgets-grid {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .widget-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 24px;
        }

        .widget-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(229, 231, 235, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 320px;
            position: relative;
            overflow: hidden;
        }

        .widget-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .widget-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366F1, #8B5CF6);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .widget-card:hover::before {
            opacity: 1;
        }

        /* Dark mode adjustments */
        .dark .widget-card {
            background: rgba(31, 41, 55, 0.95);
            border-color: rgba(75, 85, 99, 0.3);
        }

        .dark .widget-card:hover {
            background: rgba(31, 41, 55, 0.98);
            border-color: rgba(99, 102, 241, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .widget-row {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
                gap: 20px;
            }
        }

        @media (max-width: 768px) {
            .executive-dashboard-container {
                padding: 0 16px;
            }
            
            .widget-row {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .widget-card {
                padding: 20px;
                min-height: 280px;
            }
            
            .executive-widgets-grid {
                gap: 24px;
            }
        }

        /* Enhanced animations */
        .widget-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .widget-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .widget-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Loading states */
        .widget-card.loading {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }
    </style>

    {{-- Auto-refresh functionality --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const settings = @json($settings);
            
            if (settings.auto_refresh) {
                setInterval(function() {
                    Livewire.dispatch('refresh-widgets');
                }, settings.refresh_interval * 1000);
            }
        });
    </script>
</x-filament-panels::page>