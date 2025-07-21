<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Quick Links Card -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Menu Pengaturan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- User Management -->
                    <a href="/settings/users" 
                       class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-blue-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                            </svg>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Manajemen User</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Kelola user, role, dan akses</p>
                            </div>
                        </div>
                    </a>

                    <!-- System Configuration -->
                    <a href="/settings/config" 
                       class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-green-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Konfigurasi Sistem</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Atur branding, jadwal & keamanan</p>
                            </div>
                        </div>
                    </a>

                    <!-- Backup & Export -->
                    <a href="/settings/backup" 
                       class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-purple-500 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                            </svg>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Backup & Export</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Export dan import data master</p>
                            </div>
                        </div>
                    </a>

                    <!-- Telegram Notifications -->
                    <a href="/settings/telegram" 
                       class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                        <div class="flex items-center">
                            <svg class="w-8 h-8 text-blue-500 mr-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/>
                            </svg>
                            <div>
                                <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Notifikasi Telegram</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Setup bot dan notifikasi role-based</p>
                            </div>
                        </div>
                    </a>

                </div>
            </div>
        </div>

        <!-- System Info Card -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Informasi Sistem</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                            {{ \App\Models\User::count() }}
                        </div>
                        <div class="text-sm text-blue-800 dark:text-blue-300">Total Users</div>
                    </div>
                    
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                            {{ \App\Models\User::where('is_active', true)->count() }}
                        </div>
                        <div class="text-sm text-green-800 dark:text-green-300">Active Users</div>
                    </div>
                    
                    <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                            {{ \App\Models\Role::count() }}
                        </div>
                        <div class="text-sm text-purple-800 dark:text-purple-300">Total Roles</div>
                    </div>
                    
                    <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                            {{ \App\Models\SystemConfig::count() }}
                        </div>
                        <div class="text-sm text-orange-800 dark:text-orange-300">System Configs</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
