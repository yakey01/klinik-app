<div class="space-y-6">
    <!-- Security Score Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white">Security Score</h2>
            <div class="flex items-center space-x-2">
                <div class="w-16 h-16 relative">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="100, 100"
                              class="text-gray-300 dark:text-gray-600" />
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="currentColor" stroke-width="2" 
                              stroke-dasharray="{{ $this->getSecurityScore() }}, 100"
                              class="text-{{ $this->getSecurityScoreColor() }}-500" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xl font-bold text-{{ $this->getSecurityScoreColor() }}-600">{{ $this->getSecurityScore() }}</span>
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Security Score</p>
                    <p class="text-lg font-semibold text-{{ $this->getSecurityScoreColor() }}-600">
                        @if($this->getSecurityScore() >= 80)
                            Excellent
                        @elseif($this->getSecurityScore() >= 60)
                            Good
                        @else
                            Needs Improvement
                        @endif
                    </p>
                </div>
            </div>
        </div>
        
        @if(!empty($this->getSecurityRecommendations()))
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Recommendations</h3>
                <div class="space-y-2">
                    @foreach($this->getSecurityRecommendations() as $recommendation)
                        <div class="flex items-start space-x-3 p-3 rounded-lg bg-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-50 dark:bg-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-900/20">
                            <div class="flex-shrink-0">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-400" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-medium text-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-800 dark:text-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-200">
                                    {{ $recommendation['title'] }}
                                </p>
                                <p class="text-sm text-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-600 dark:text-{{ $recommendation['priority'] === 'high' ? 'red' : 'yellow' }}-300">
                                    {{ $recommendation['description'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Security Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Successful Logins</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $securityStats['successful_logins_24h'] ?? 0 }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Last 24 hours</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
                    <x-heroicon-o-x-circle class="h-6 w-6 text-red-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed Logins</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $securityStats['failed_logins_24h'] ?? 0 }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Last 24 hours</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-computer-desktop class="h-6 w-6 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Sessions</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $securityStats['active_sessions'] ?? 0 }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">System-wide</p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-o-shield-check class="h-6 w-6 text-purple-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">2FA Enabled</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $twoFactorStatus['users_with_2fa'] ?? 0 }}
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $twoFactorStatus['coverage_percentage'] ?? 0 }}% coverage
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Two-Factor Authentication</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Add an extra layer of security to your account
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    @if(app(\App\Services\TwoFactorAuthService::class)->isEnabled(Auth::user()))
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Enabled
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Disabled
                        </span>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">System Overview</h4>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Total Users</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $twoFactorStatus['total_users'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Users with 2FA</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $twoFactorStatus['users_with_2fa'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Coverage</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $twoFactorStatus['coverage_percentage'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Admin Compliance</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $twoFactorStatus['compliance_percentage'] ?? 0 }}%
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Quick Actions</h4>
                    <div class="space-y-2">
                        @if(!app(\App\Services\TwoFactorAuthService::class)->isEnabled(Auth::user()))
                            <button type="button" 
                                    class="w-full inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <x-heroicon-o-device-phone-mobile class="w-4 h-4 mr-2" />
                                Enable 2FA
                            </button>
                        @else
                            <button type="button" 
                                    class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <x-heroicon-o-cog-6-tooth class="w-4 h-4 mr-2" />
                                Manage 2FA
                            </button>
                        @endif
                        
                        <button type="button" 
                                wire:click="changePassword"
                                class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <x-heroicon-o-key class="w-4 h-4 mr-2" />
                            Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Sessions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Sessions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Manage your active login sessions
                    </p>
                </div>
                <button type="button" 
                        wire:click="terminateAllSessions"
                        class="inline-flex items-center px-3 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <x-heroicon-o-x-circle class="w-4 h-4 mr-2" />
                    Terminate All
                </button>
            </div>
        </div>
        
        <div class="p-6">
            @if($activeSessions->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-computer-desktop class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No active sessions</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        You don't have any active sessions.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($activeSessions as $session)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-heroicon-o-computer-desktop class="w-6 h-6 text-gray-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $session->user_agent ?? 'Unknown Bfi-grid fi-grid-cols-autoser' }}
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $session->ip_address }} • {{ $session->formatted_location }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Last active: {{ $session->last_activity_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($session->is_current)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Current
                                    </span>
                                @else
                                    <button type="button" 
                                            wire:click="terminateSession('{{ $session->session_id }}')"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200">
                                        Terminate
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>