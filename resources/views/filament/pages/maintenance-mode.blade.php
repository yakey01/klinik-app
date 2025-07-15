<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Current Status Card -->
        <div class="filament-card bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Current Status</h3>
                    <div class="flex items-center space-x-2">
                        @if(app()->isDownForMaintenance())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Laravel Down
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Laravel Up
                            </span>
                        @endif
                        
                        @if(\App\Models\SystemSetting::get('maintenance_mode', false))
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Custom Maintenance
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                <svg class="w-2 h-2 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3" />
                                </svg>
                                Normal Operation
                            </span>
                        @endif
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">System Information</h4>
                        <dl class="text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex justify-between">
                                <dt>Environment:</dt>
                                <dd class="font-mono">{{ config('app.env') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Debug Mode:</dt>
                                <dd class="font-mono">{{ config('app.debug') ? 'On' : 'Off' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Current Time:</dt>
                                <dd class="font-mono">{{ now()->format('Y-m-d H:i:s') }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-2">Maintenance Settings</h4>
                        <dl class="text-sm text-gray-600 dark:text-gray-400">
                            <div class="flex justify-between">
                                <dt>Custom Mode:</dt>
                                <dd class="font-mono">{{ \App\Models\SystemSetting::get('maintenance_mode', false) ? 'Enabled' : 'Disabled' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Allowed IPs:</dt>
                                <dd class="font-mono">{{ \App\Models\SystemSetting::get('maintenance_allowed_ips', '127.0.0.1') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt>Message:</dt>
                                <dd class="font-mono truncate max-w-xs">{{ \App\Models\SystemSetting::get('maintenance_message', 'Default message') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Banner -->
        @if(app()->isDownForMaintenance())
            <div class="filament-card bg-red-50 border border-red-200 rounded-lg p-4 dark:bg-red-900 dark:border-red-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-red-800 dark:text-red-200 font-medium">
                        Application is currently in Laravel maintenance mode. Regular users cannot access the system.
                    </span>
                </div>
            </div>
        @elseif(\App\Models\SystemSetting::get('maintenance_mode', false))
            <div class="filament-card bg-yellow-50 border border-yellow-200 rounded-lg p-4 dark:bg-yellow-900 dark:border-yellow-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="text-yellow-800 dark:text-yellow-200 font-medium">
                        Custom maintenance mode is enabled. Check your middleware configuration.
                    </span>
                </div>
            </div>
        @endif

        <!-- Configuration Form -->
        <div class="filament-card bg-white rounded-lg shadow-sm border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Maintenance Configuration</h3>
                {{ $this->form }}
            </div>
        </div>

        <!-- Help Section -->
        <div class="filament-card bg-blue-50 border border-blue-200 rounded-lg p-4 dark:bg-blue-900 dark:border-blue-800">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-blue-800 dark:text-blue-200">
                    <h4 class="font-medium mb-2">Maintenance Mode Options</h4>
                    <ul class="text-sm space-y-1">
                        <li><strong>Laravel Down:</strong> Uses Laravel's built-in maintenance mode (php artisan down)</li>
                        <li><strong>Custom Maintenance:</strong> Uses custom middleware for more flexible control</li>
                        <li><strong>Scheduled Maintenance:</strong> Automatically enable/disable maintenance at specific times</li>
                        <li><strong>IP Whitelisting:</strong> Allow specific IP addresses to bypass maintenance mode</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>