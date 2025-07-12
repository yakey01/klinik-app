<div class="space-y-6">
    <!-- System Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-700">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-server class="w-5 h-5 text-blue-600 dark:text-blue-400"/>
                <h3 class="font-semibold text-blue-900 dark:text-blue-100">Laravel Version</h3>
            </div>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300">{{ app()->version() }}</p>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-700">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-users class="w-5 h-5 text-green-600 dark:text-green-400"/>
                <h3 class="font-semibold text-green-900 dark:text-green-100">Total Users</h3>
            </div>
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ \App\Models\User::count() }}</p>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-700">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-purple-600 dark:text-purple-400"/>
                <h3 class="font-semibold text-purple-900 dark:text-purple-100">Active Sessions</h3>
            </div>
            <p class="text-2xl font-bold text-purple-700 dark:text-purple-300">{{ \App\Models\User::where('is_active', true)->count() }}</p>
        </div>
    </div>

    <!-- Environment Information -->
    <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Environment Information</h3>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP Version</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ PHP_VERSION }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Environment</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ app()->environment() }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Debug Mode</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Database</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ config('database.default') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Cache Driver</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ config('cache.default') }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Queue Driver</dt>
                <dd class="text-sm text-gray-900 dark:text-gray-100">{{ config('queue.default') }}</dd>
            </div>
        </dl>
    </div>

    <!-- Installed Packages -->
    <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Key Filament Packages</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Filament Shield</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">✓ Installed</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Spatie Permission</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">✓ Installed</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">FullCalendar</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">✓ Installed</span>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Google Maps</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">✓ Installed</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">ApexCharts</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">✓ Installed</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Map Picker</span>
                    <span class="text-sm font-medium text-green-600 dark:text-green-400">✓ Installed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Database Statistics -->
    <div class="bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Database Statistics</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ \App\Models\User::count() }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Users</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ \App\Models\Pasien::count() }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Patients</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ \App\Models\Tindakan::count() }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Procedures</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ \App\Models\JadwalJaga::count() }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">Schedules</p>
            </div>
        </div>
    </div>
</div>