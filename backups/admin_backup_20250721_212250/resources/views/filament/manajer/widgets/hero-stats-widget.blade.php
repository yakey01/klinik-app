<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Widget Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Key Performance Indicators
                </h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Current month overview with gfi-grid fi-grid-cols-autoth trends
                </p>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-gray-500 dark:text-gray-400">Live</span>
            </div>
        </div>

        {{-- Main Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Revenue Card --}}
            <div class="stat-fi-card revenue-fi-card">
                <div class="stat-header">
                    <div class="stat-icon revenue-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <div class="stat-title">Revenue</div>
                </div>
                <div class="stat-value">{{ $stats['revenue']['formatted'] }}</div>
                <div class="stat-gfi-grid fi-grid-cols-autoth {{ $stats['revenue']['color'] }}">
                    @if($stats['revenue']['gfi-grid fi-grid-cols-autoth'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7.8 16.2M7 7v10h10"/>
                        </svg>
                    @endif
                    <span>{{ abs($stats['revenue']['gfi-grid fi-grid-cols-autoth']) }}%</span>
                </div>
                <div class="stat-detail">
                    Today: Rp {{ number_format($stats['revenue']['today'], 0, ',', '.') }}
                </div>
            </div>

            {{-- Patients Card --}}
            <div class="stat-fi-card patients-fi-card">
                <div class="stat-header">
                    <div class="stat-icon patients-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Patients</div>
                </div>
                <div class="stat-value">{{ $stats['patients']['formatted'] }}</div>
                <div class="stat-gfi-grid fi-grid-cols-autoth {{ $stats['patients']['color'] }}">
                    @if($stats['patients']['gfi-grid fi-grid-cols-autoth'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7.8 16.2M7 7v10h10"/>
                        </svg>
                    @endif
                    <span>{{ abs($stats['patients']['gfi-grid fi-grid-cols-autoth']) }}%</span>
                </div>
                <div class="stat-detail">
                    Today: {{ number_format($stats['patients']['today']) }}
                </div>
            </div>

            {{-- Procedures Card --}}
            <div class="stat-fi-card procedures-fi-card">
                <div class="stat-header">
                    <div class="stat-icon procedures-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="stat-title">Procedures</div>
                </div>
                <div class="stat-value">{{ $stats['procedures']['formatted'] }}</div>
                <div class="stat-gfi-grid fi-grid-cols-autoth {{ $stats['procedures']['color'] }}">
                    @if($stats['procedures']['gfi-grid fi-grid-cols-autoth'] >= 0)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7H7"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 7L7.8 16.2M7 7v10h10"/>
                        </svg>
                    @endif
                    <span>{{ abs($stats['procedures']['gfi-grid fi-grid-cols-autoth']) }}%</span>
                </div>
                <div class="stat-detail">
                    Today: {{ number_format($stats['procedures']['today']) }}
                </div>
            </div>

            {{-- Staff Card --}}
            <div class="stat-fi-card staff-fi-card">
                <div class="stat-header">
                    <div class="stat-icon staff-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="stat-title">Staff</div>
                </div>
                <div class="stat-value">{{ $stats['staff']['formatted'] }}</div>
                <div class="stat-gfi-grid fi-grid-cols-autoth {{ $stats['staff']['color'] }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span>{{ $stats['staff']['efficiency'] }}%</span>
                </div>
                <div class="stat-detail">
                    Efficiency Rate
                </div>
            </div>
        </div>

        {{-- Quick Actions & Alerts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Pending Approvals --}}
            <div class="quick-action-fi-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Pending Approvals</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Items requiring your attention</p>
                    </div>
                    <div class="bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 px-3 py-1 rounded-full text-sm font-medium">
                        {{ $quick_actions['pending_approvals'] }}
                    </div>
                </div>
            </div>

            {{-- Critical Alerts --}}
            <div class="quick-action-fi-card">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white">Critical Alerts</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400">System notifications</p>
                    </div>
                    <div class="bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 px-3 py-1 rounded-full text-sm font-medium">
                        {{ count($quick_actions['critical_alerts']) }}
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Custom Styles --}}
    <style>
        .stat-fi-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .stat-fi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border-color: rgba(99, 102, 241, 0.3);
        }

        .dark .stat-fi-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }

        .stat-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .revenue-icon { background: linear-gradient(135deg, #10B981, #059669); }
        .patients-icon { background: linear-gradient(135deg, #6366F1, #4F46E5); }
        .procedures-icon { background: linear-gradient(135deg, #F59E0B, #D97706); }
        .staff-icon { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }

        .stat-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6B7280;
        }

        .dark .stat-title {
            color: #9CA3AF;
        }

        .stat-value {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .dark .stat-value {
            color: #F9FAFB;
        }

        .stat-gfi-grid fi-grid-cols-autoth {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .stat-gfi-grid fi-grid-cols-autoth.success { color: #10B981; }
        .stat-gfi-grid fi-grid-cols-autoth.danger { color: #EF4444; }
        .stat-gfi-grid fi-grid-cols-autoth.warning { color: #F59E0B; }

        .stat-detail {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .dark .stat-detail {
            color: #9CA3AF;
        }

        .quick-action-fi-card {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(229, 231, 235, 0.5);
            border-radius: 12px;
            padding: 1.25rem;
            backdrop-filter: blur(10px);
        }

        .dark .quick-action-fi-card {
            background: rgba(55, 65, 81, 0.7);
            border-color: rgba(75, 85, 99, 0.5);
        }
    </style>
</x-filament-widgets::widget>